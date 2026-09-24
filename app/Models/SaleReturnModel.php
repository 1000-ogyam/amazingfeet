<?php
/**
 * Sale returns & exchanges — restock returned items, optional replacement sale.
 */
class SaleReturnModel {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function generateRef(): string {
        return 'RET-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -5));
    }

    public function all(array $f = []): array {
        $w = ['1=1'];
        $p = [];
        if (!empty($f['date'])) {
            $w[] = 'DATE(r.created_at)=?';
            $p[] = $f['date'];
        }
        if (!empty($f['search'])) {
            $w[] = '(r.return_ref LIKE ? OR s.sale_ref LIKE ? OR r.reason LIKE ?)';
            $q = '%'.$f['search'].'%';
            array_push($p, $q, $q, $q);
        }
        $stmt = $this->db->prepare("
            SELECT r.*, s.sale_ref, u.name AS processed_by_name
            FROM sale_returns r
            JOIN sales s ON r.sale_id = s.id
            JOIN users u ON r.processed_by = u.id
            WHERE ".implode(' AND ', $w)."
            ORDER BY r.created_at DESC
            LIMIT 200
        ");
        $stmt->execute($p);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT r.*, s.sale_ref, s.total AS sale_total, u.name AS processed_by_name,
                   c.name AS customer_name
            FROM sale_returns r
            JOIN sales s ON r.sale_id = s.id
            JOIN users u ON r.processed_by = u.id
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getItems(int $returnId): array {
        $stmt = $this->db->prepare("
            SELECT ri.*, p.name, p.size, p.gender, p.design
            FROM sale_return_items ri
            JOIN products p ON ri.product_id = p.id
            WHERE ri.return_id = ?
            ORDER BY ri.id
        ");
        $stmt->execute([$returnId]);
        return $stmt->fetchAll();
    }

    public function getExchanges(int $returnId): array {
        $stmt = $this->db->prepare("
            SELECT re.*, p.name, p.size, p.gender, p.design
            FROM sale_return_exchanges re
            JOIN products p ON re.product_id = p.id
            WHERE re.return_id = ?
            ORDER BY re.id
        ");
        $stmt->execute([$returnId]);
        return $stmt->fetchAll();
    }

    /** Qty already returned per sale_item_id. */
    public function returnedQtyBySaleItem(int $saleId): array {
        $stmt = $this->db->prepare("
            SELECT ri.sale_item_id, SUM(ri.quantity) AS qty
            FROM sale_return_items ri
            JOIN sale_returns r ON ri.return_id = r.id
            WHERE r.sale_id = ?
            GROUP BY ri.sale_item_id
        ");
        $stmt->execute([$saleId]);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int)$row['sale_item_id']] = (int)$row['qty'];
        }
        return $map;
    }

    public function forSale(int $saleId): array {
        $stmt = $this->db->prepare("
            SELECT r.*, u.name AS processed_by_name
            FROM sale_returns r
            JOIN users u ON r.processed_by = u.id
            WHERE r.sale_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$saleId]);
        return $stmt->fetchAll();
    }

    /**
     * @param array{
     *   sale_id:int,
     *   reason?:string,
     *   refund_method?:string,
     *   notes?:string,
     *   items: list<array{sale_item_id:int,quantity:int}>,
     *   exchanges?: list<array{product_id:int,quantity:int}>
     * } $data
     * @return array{id:int,return_ref:string,refund_amount:float,exchange_amount:float,exchange_sale_id:?int}
     */
    public function process(array $data, int $userId): array {
        $saleId = (int)($data['sale_id'] ?? 0);
        $sm = new SaleModel();
        $sale = $sm->findById($saleId);
        if (!$sale) {
            throw new InvalidArgumentException('Sale not found.');
        }

        $saleItems = $sm->getItems($saleId);
        $byId = [];
        foreach ($saleItems as $si) {
            $byId[(int)$si['id']] = $si;
        }
        $already = $this->returnedQtyBySaleItem($saleId);

        $returnLines = [];
        foreach ((array)($data['items'] ?? []) as $row) {
            $siId = (int)($row['sale_item_id'] ?? 0);
            $qty = (int)($row['quantity'] ?? 0);
            if ($siId < 1 || $qty < 1 || !isset($byId[$siId])) continue;
            $orig = (int)$byId[$siId]['quantity'];
            $left = $orig - ($already[$siId] ?? 0);
            if ($left < 1) continue;
            $qty = min($qty, $left);
            $unit = (float)$byId[$siId]['unit_price'];
            $returnLines[] = [
                'sale_item_id' => $siId,
                'product_id'   => (int)$byId[$siId]['product_id'],
                'quantity'     => $qty,
                'unit_price'   => $unit,
                'line_total'   => round($unit * $qty, 2),
            ];
        }
        if (!$returnLines) {
            throw new InvalidArgumentException('Select at least one item quantity to return.');
        }

        $pm = new ProductModel();
        $exchangeLines = [];
        foreach ((array)($data['exchanges'] ?? []) as $row) {
            $pid = (int)($row['product_id'] ?? 0);
            $qty = (int)($row['quantity'] ?? 0);
            if ($pid < 1 || $qty < 1) continue;
            $p = $pm->findById($pid);
            if (!$p || !(int)$p['is_active']) {
                throw new InvalidArgumentException('Exchange product not found or inactive.');
            }
            if ((int)$p['quantity'] < $qty) {
                throw new InvalidArgumentException(
                    'Not enough stock for exchange: '.$p['name'].' Sz '.$p['size']
                    .' (have '.(int)$p['quantity'].').'
                );
            }
            $unit = (float)$p['selling_price'];
            $cost = (float)$p['cost_price'];
            $exchangeLines[] = [
                'product_id' => $pid,
                'quantity'   => $qty,
                'unit_price' => $unit,
                'cost_price' => $cost,
                'line_total' => round($unit * $qty, 2),
            ];
        }

        $returnTotal = array_sum(array_column($returnLines, 'line_total'));
        $exchangeTotal = array_sum(array_column($exchangeLines, 'line_total'));
        // Net refund to customer (0 if exchange covers or exceeds return value)
        $refundAmount = max(0, round($returnTotal - $exchangeTotal, 2));

        $method = $data['refund_method'] ?? 'cash';
        $allowed = ['cash', 'momo', 'card', 'store_credit', 'none'];
        if (!in_array($method, $allowed, true)) $method = 'cash';
        if ($refundAmount <= 0 && empty($exchangeLines)) {
            // full even return with no money movement still ok
        }
        if ($refundAmount <= 0) {
            $method = $method === 'none' ? 'none' : ($exchangeTotal > 0 ? 'none' : $method);
            if ($exchangeTotal >= $returnTotal) $method = 'none';
        }

        $returnRef = $this->generateRef();
        $reason = trim((string)($data['reason'] ?? '')) ?: null;
        $notes = trim((string)($data['notes'] ?? '')) ?: null;

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO sale_returns
                    (return_ref, sale_id, processed_by, reason, refund_method,
                     refund_amount, exchange_amount, notes)
                VALUES (?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $returnRef, $saleId, $userId, $reason, $method,
                $refundAmount, $exchangeTotal, $notes,
            ]);
            $returnId = (int)$this->db->lastInsertId();

            $ins = $this->db->prepare("
                INSERT INTO sale_return_items
                    (return_id, sale_item_id, product_id, quantity, unit_price, line_total)
                VALUES (?,?,?,?,?,?)
            ");
            foreach ($returnLines as $line) {
                $ins->execute([
                    $returnId, $line['sale_item_id'], $line['product_id'],
                    $line['quantity'], $line['unit_price'], $line['line_total'],
                ]);
                $note = 'Return '.$returnRef.' from '.$sale['sale_ref'];
                $pm->adjustStock($line['product_id'], $line['quantity'], 'return', $note, $userId);
            }

            $exIns = $this->db->prepare("
                INSERT INTO sale_return_exchanges
                    (return_id, product_id, quantity, unit_price, cost_price, line_total)
                VALUES (?,?,?,?,?,?)
            ");
            foreach ($exchangeLines as $line) {
                $exIns->execute([
                    $returnId, $line['product_id'], $line['quantity'],
                    $line['unit_price'], $line['cost_price'], $line['line_total'],
                ]);
            }

            $exchangeSaleId = null;
            if ($exchangeLines) {
                $saleItemsPayload = [];
                $subtotal = 0.0;
                foreach ($exchangeLines as $line) {
                    $saleItemsPayload[] = [
                        'product_id' => $line['product_id'],
                        'quantity'   => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'cost_price' => $line['cost_price'],
                        'line_total' => $line['line_total'],
                    ];
                    $subtotal += $line['line_total'];
                }
                // Customer pays difference if exchange costs more
                $due = max(0, round($exchangeTotal - $returnTotal, 2));
                $payMethod = $due > 0
                    ? (($data['exchange_payment'] ?? 'cash') ?: 'cash')
                    : 'cash';
                if (!in_array($payMethod, ['cash', 'momo', 'card', 'split'], true)) {
                    $payMethod = 'cash';
                }

                $exchangeSaleId = $sm->create([
                    'sale_ref'        => $sm->generateRef(),
                    'staff_id'        => $userId,
                    'location_id'     => (int)$sale['location_id'],
                    'customer_id'     => $sale['customer_id'] ?? null,
                    'subtotal'        => $subtotal,
                    'discount'        => max(0, round($subtotal - $due, 2)), // credit from return
                    'total'           => $due,
                    'payment_method'  => $payMethod,
                    'amount_tendered' => $due > 0 ? $due : null,
                    'change_due'      => null,
                    'momo_ref'        => null,
                    'notes'           => 'Exchange for '.$returnRef.' (original '.$sale['sale_ref'].')',
                ], $saleItemsPayload, false);

                $this->db->prepare('UPDATE sale_returns SET exchange_sale_id=? WHERE id=?')
                    ->execute([$exchangeSaleId, $returnId]);
            }

            // Annotate original sale notes
            $tag = ' ['.$returnRef.']';
            $existingNotes = (string)($sale['notes'] ?? '');
            if (!str_contains($existingNotes, $returnRef)) {
                $this->db->prepare('UPDATE sales SET notes=? WHERE id=?')->execute([
                    trim($existingNotes.$tag) ?: $returnRef,
                    $saleId,
                ]);
            }

            $this->db->commit();
            return [
                'id'               => $returnId,
                'return_ref'       => $returnRef,
                'refund_amount'    => $refundAmount,
                'exchange_amount'  => $exchangeTotal,
                'exchange_sale_id' => $exchangeSaleId,
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
