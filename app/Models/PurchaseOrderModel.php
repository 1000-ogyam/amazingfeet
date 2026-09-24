<?php
class PurchaseOrderModel {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function generateRef(): string {
        return 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }

    public function all(array $f = []): array {
        $w = ['1=1'];
        $p = [];
        if (!empty($f['status'])) {
            $w[] = 'po.status=?';
            $p[] = $f['status'];
        }
        if (!empty($f['search'])) {
            $w[] = '(po.po_ref LIKE ? OR po.supplier LIKE ? OR po.notes LIKE ?)';
            $q = '%' . $f['search'] . '%';
            array_push($p, $q, $q, $q);
        }
        $sql = "
            SELECT po.*,
                   u.name AS created_by_name,
                   r.name AS received_by_name,
                   (SELECT COUNT(*) FROM purchase_order_items i WHERE i.purchase_order_id=po.id) AS line_count,
                   (SELECT COALESCE(SUM(i.quantity_ordered),0) FROM purchase_order_items i WHERE i.purchase_order_id=po.id) AS units_ordered,
                   (SELECT COALESCE(SUM(i.quantity_received),0) FROM purchase_order_items i WHERE i.purchase_order_id=po.id) AS units_received,
                   (SELECT COALESCE(SUM(i.quantity_ordered * i.unit_cost),0) FROM purchase_order_items i WHERE i.purchase_order_id=po.id) AS total_cost
            FROM purchase_orders po
            JOIN users u ON po.created_by=u.id
            LEFT JOIN users r ON po.received_by=r.id
            WHERE " . implode(' AND ', $w) . "
            ORDER BY po.created_at DESC
            LIMIT 200
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($p);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT po.*,
                   u.name AS created_by_name,
                   r.name AS received_by_name
            FROM purchase_orders po
            JOIN users u ON po.created_by=u.id
            LEFT JOIN users r ON po.received_by=r.id
            WHERE po.id=?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getItems(int $poId): array {
        $stmt = $this->db->prepare("
            SELECT i.*,
                   p.name, p.size, p.sku, p.barcode, p.gender, p.design, p.quantity AS stock_qty,
                   p.cost_price AS product_cost, p.selling_price,
                   c.name AS category_name
            FROM purchase_order_items i
            JOIN products p ON i.product_id=p.id
            JOIN categories c ON p.category_id=c.id
            WHERE i.purchase_order_id=?
            ORDER BY p.name, p.size+0, p.size
        ");
        $stmt->execute([$poId]);
        return $stmt->fetchAll();
    }

    /**
     * @param list<array{product_id:int,quantity:int,unit_cost?:float}> $items
     */
    public function create(array $header, array $items, int $userId): int {
        $lines = [];
        foreach ($items as $row) {
            $pid = (int)($row['product_id'] ?? 0);
            $qty = (int)($row['quantity'] ?? 0);
            if ($pid < 1 || $qty < 1) continue;
            $lines[] = [
                'product_id' => $pid,
                'quantity'   => $qty,
                'unit_cost'  => (float)($row['unit_cost'] ?? 0),
            ];
        }
        if (!$lines) {
            throw new InvalidArgumentException('Add at least one product with quantity.');
        }

        $status = ($header['status'] ?? 'draft') === 'ordered' ? 'ordered' : 'draft';
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO purchase_orders (po_ref, supplier, supplier_id, status, notes, created_by, ordered_at)
                VALUES (?,?,?,?,?,?,?)
            ");
            $supplierId = !empty($header['supplier_id']) ? (int)$header['supplier_id'] : null;
            $supplierName = trim((string)($header['supplier'] ?? ''));
            if ($supplierId && $supplierName === '') {
                $sup = (new SupplierModel())->findById($supplierId);
                if ($sup) $supplierName = $sup['name'];
            }
            $stmt->execute([
                $header['po_ref'] ?? $this->generateRef(),
                $supplierName !== '' ? $supplierName : null,
                $supplierId,
                $status,
                trim((string)($header['notes'] ?? '')) ?: null,
                $userId,
                $status === 'ordered' ? date('Y-m-d H:i:s') : null,
            ]);
            $poId = (int)$this->db->lastInsertId();

            $ins = $this->db->prepare("
                INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity_ordered, quantity_received, unit_cost)
                VALUES (?,?,?,0,?)
            ");
            foreach ($lines as $line) {
                $ins->execute([$poId, $line['product_id'], $line['quantity'], $line['unit_cost']]);
            }

            $this->db->commit();
            return $poId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Receive stock for a PO.
     * @param array<int,int> $receiveMap item_id => qty to receive now
     */
    public function receive(int $poId, array $receiveMap, int $userId, bool $updateProductCost = false): array {
        $po = $this->findById($poId);
        if (!$po) throw new InvalidArgumentException('Purchase order not found.');
        if (in_array($po['status'], ['received', 'cancelled'], true)) {
            throw new InvalidArgumentException('This purchase order cannot be received.');
        }

        $items = $this->getItems($poId);
        $byId = [];
        foreach ($items as $it) {
            $byId[(int)$it['id']] = $it;
        }

        $pm = new ProductModel();
        $receivedNow = 0;
        $linesTouched = 0;

        $this->db->beginTransaction();
        try {
            foreach ($receiveMap as $itemId => $qty) {
                $itemId = (int)$itemId;
                $qty = (int)$qty;
                if ($qty < 1 || !isset($byId[$itemId])) continue;

                $it = $byId[$itemId];
                $remaining = max(0, (int)$it['quantity_ordered'] - (int)$it['quantity_received']);
                if ($remaining < 1) continue;
                $qty = min($qty, $remaining);

                $this->db->prepare("
                    UPDATE purchase_order_items
                    SET quantity_received = quantity_received + ?
                    WHERE id=?
                ")->execute([$qty, $itemId]);

                $note = 'PO ' . $po['po_ref'] . ' receive';
                $pm->adjustStock((int)$it['product_id'], $qty, 'addition', $note, $userId);

                if ($updateProductCost && (float)$it['unit_cost'] > 0) {
                    $this->db->prepare('UPDATE products SET cost_price=? WHERE id=?')
                        ->execute([(float)$it['unit_cost'], (int)$it['product_id']]);
                }

                $receivedNow += $qty;
                $linesTouched++;
            }

            if ($receivedNow < 1) {
                throw new InvalidArgumentException('Enter quantities to receive for at least one line.');
            }

            $fresh = $this->getItems($poId);
            $ordered = 0;
            $got = 0;
            foreach ($fresh as $it) {
                $ordered += (int)$it['quantity_ordered'];
                $got += (int)$it['quantity_received'];
            }
            $status = ($got >= $ordered && $ordered > 0) ? 'received' : 'partial';

            $this->db->prepare("
                UPDATE purchase_orders
                SET status=?, received_by=?, received_at=NOW(),
                    ordered_at=COALESCE(ordered_at, NOW())
                WHERE id=?
            ")->execute([$status, $userId, $poId]);

            $this->db->commit();
            return [
                'received_units' => $receivedNow,
                'lines' => $linesTouched,
                'status' => $status,
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Create PO and receive all lines in one step. */
    public function createAndReceive(array $header, array $items, int $userId, bool $updateProductCost = false): array {
        $poId = $this->create(array_merge($header, ['status' => 'ordered']), $items, $userId);
        $lines = $this->getItems($poId);
        $map = [];
        foreach ($lines as $it) {
            $map[(int)$it['id']] = (int)$it['quantity_ordered'];
        }
        $result = $this->receive($poId, $map, $userId, $updateProductCost);
        $result['po_id'] = $poId;
        return $result;
    }

    public function cancel(int $poId): bool {
        $po = $this->findById($poId);
        if (!$po) return false;
        if ($po['status'] === 'received') {
            throw new InvalidArgumentException('Received orders cannot be cancelled.');
        }
        $sum = $this->db->prepare('SELECT COALESCE(SUM(quantity_received),0) FROM purchase_order_items WHERE purchase_order_id=?');
        $sum->execute([$poId]);
        if ((int)$sum->fetchColumn() > 0) {
            throw new InvalidArgumentException('Partially received orders cannot be cancelled. Receive remaining or leave as partial.');
        }
        $stmt = $this->db->prepare("UPDATE purchase_orders SET status='cancelled' WHERE id=? AND status IN ('draft','ordered')");
        return $stmt->execute([$poId]);
    }

    public function markOrdered(int $poId): bool {
        $stmt = $this->db->prepare("
            UPDATE purchase_orders
            SET status='ordered', ordered_at=COALESCE(ordered_at, NOW())
            WHERE id=? AND status='draft'
        ");
        return $stmt->execute([$poId]);
    }
}
