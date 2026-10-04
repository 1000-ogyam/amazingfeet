<?php
class SaleModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function generateRef(?string $date = null): string {
        $ymd = $date ? str_replace('-', '', $date) : date('Ymd');
        return 'AF-'.$ymd.'-'.strtoupper(substr(uniqid(), -5));
    }

    /** Today's date according to MySQL (sales timestamps use the DB clock). */
    public function dbToday(): string {
        return (string)$this->db->query('SELECT CURDATE()')->fetchColumn();
    }

    public function dbNow(): string {
        return (string)$this->db->query('SELECT NOW()')->fetchColumn();
    }

    /**
     * @param array $sale optional 'sale_date' (Y-m-d) backdates the sale, keeping the current time of day
     * @param bool $useTransaction set false when already inside a parent transaction
     */
    public function create(array $sale, array $items, bool $useTransaction = true): int {
        if ($useTransaction) $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO sales (sale_ref,staff_id,location_id,customer_id,subtotal,discount,total,
                    payment_method,amount_tendered,change_due,momo_ref,notes,created_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?, COALESCE(CONCAT(?, ' ', CURTIME()), NOW()))
            ");
            $stmt->execute([
                $sale['sale_ref'], $sale['staff_id'], $sale['location_id'],
                $sale['customer_id']??null, $sale['subtotal'], $sale['discount']??0,
                $sale['total'], $sale['payment_method'],
                $sale['amount_tendered']??null, $sale['change_due']??null,
                $sale['momo_ref']??null, $sale['notes']??null,
                $sale['sale_date']??null,
            ]);
            $saleId = (int)$this->db->lastInsertId();

            $pm = new ProductModel();
            foreach ($items as $item) {
                $this->db->prepare("
                    INSERT INTO sale_items (sale_id,product_id,quantity,unit_price,cost_price,line_total)
                    VALUES (?,?,?,?,?,?)
                ")->execute([
                    $saleId, $item['product_id'], $item['quantity'],
                    $item['unit_price'], $item['cost_price'], $item['line_total'],
                ]);
                $pm->deductStock($item['product_id'], $item['quantity']);
            }
            if ($useTransaction) $this->db->commit();
            return $saleId;
        } catch (Exception $e) {
            if ($useTransaction) $this->db->rollBack();
            throw $e;
        }
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT s.*, u.name AS staff_name, l.name AS location_name,
                   c.name AS customer_name, c.phone AS customer_phone
            FROM sales s
            JOIN users u ON s.staff_id=u.id
            JOIN locations l ON s.location_id=l.id
            LEFT JOIN customers c ON s.customer_id=c.id
            WHERE s.id=?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getItems(int $saleId): array {
        $skuSelect = $this->productsHaveSku() ? 'p.sku' : 'NULL AS sku';
        $stmt = $this->db->prepare("
            SELECT si.*, p.name, p.gender, p.design, p.size, {$skuSelect}, p.barcode, c.name AS category_name
            FROM sale_items si
            JOIN products p ON si.product_id=p.id
            JOIN categories c ON p.category_id=c.id
            WHERE si.sale_id=?
        ");
        $stmt->execute([$saleId]);
        return $stmt->fetchAll();
    }

    private function productsHaveSku(): bool {
        static $has = null;
        if ($has !== null) return $has;
        try {
            $this->db->query('SELECT sku FROM products LIMIT 1');
            $has = true;
        } catch (Throwable $e) {
            $has = false;
        }
        return $has;
    }

    public function all(array $f = []): array {
        [$sql, $p] = $this->listQuery($f);
        $stmt = $this->db->prepare($sql . ' LIMIT 500');
        $stmt->execute($p);
        return $stmt->fetchAll();
    }

    /** Sales list SQL (no LIMIT) and its parameters, for paginateQuery(). */
    public function listQuery(array $f = []): array {
        $w = ['1=1']; $p = [];
        if (!empty($f['date']))        { $w[] = 'DATE(s.created_at)=?'; $p[] = $f['date']; }
        if (!empty($f['staff_id']))    { $w[] = 's.staff_id=?';         $p[] = $f['staff_id']; }
        if (!empty($f['location_id'])) { $w[] = 's.location_id=?';      $p[] = $f['location_id']; }
        if (!empty($f['payment_method'])) { $w[] = 's.payment_method=?'; $p[] = $f['payment_method']; }
        if (!empty($f['search'])) {
            $w[] = '(s.sale_ref LIKE ? OR u.name LIKE ? OR l.name LIKE ? OR s.notes LIKE ?)';
            $q = '%'.$f['search'].'%';
            array_push($p, $q, $q, $q, $q);
        }
        if (!empty($f['week'])) {
            $w[] = 'YEARWEEK(s.created_at,1)=YEARWEEK(CURDATE(),1)';
        }
        $sql = "
            SELECT s.*, u.name AS staff_name, l.name AS location_name
            FROM sales s JOIN users u ON s.staff_id=u.id JOIN locations l ON s.location_id=l.id
            WHERE ".implode(' AND ',$w)." ORDER BY s.created_at DESC, s.id DESC";
        return [$sql, $p];
    }

    /** Revenue across every sale matching the list filters (not just one page). */
    public function listTotal(array $f = []): float {
        [$sql, $p] = $this->listQuery($f);
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(t.total),0) FROM ({$sql}) t");
        $stmt->execute($p);
        return (float)$stmt->fetchColumn();
    }

    /**
     * Edit a sale's details and line items. Stock moves by the difference in quantities.
     * Lines that have been (partly) returned cannot be removed or go below the returned quantity.
     *
     * @param array $d location_id, staff_id, discount, payment_method, amount_tendered, momo_ref, notes,
     *                 created_at (Y-m-d H:i:s or null to keep), customer_id (int|null)
     * @param list<array{item_id?:int,product_id:int,quantity:int,unit_price:float}> $items
     */
    public function update(int $id, array $d, array $items): void {
        $sale = $this->findById($id);
        if (!$sale) throw new InvalidArgumentException('Sale not found.');

        $existing = [];
        foreach ($this->getItems($id) as $it) $existing[(int)$it['id']] = $it;
        $returned = (new SaleReturnModel())->returnedQtyBySaleItem($id);

        $keep = [];
        $new = [];
        foreach ($items as $row) {
            $pid = (int)($row['product_id'] ?? 0);
            $qty = (int)($row['quantity'] ?? 0);
            if ($pid < 1) continue;
            $line = ['product_id' => $pid, 'quantity' => $qty, 'unit_price' => max(0, round((float)($row['unit_price'] ?? 0), 2))];
            $itemId = (int)($row['item_id'] ?? 0);
            if ($itemId && isset($existing[$itemId])) $keep[$itemId] = $line;
            elseif ($qty >= 1) $new[] = $line;
        }
        if (!$keep && !$new) throw new InvalidArgumentException('A sale needs at least one item.');

        // Net stock change per product (positive = more units leave stock)
        $delta = [];
        foreach ($existing as $itemId => $it) {
            $label = $it['name'].' Sz '.$it['size'];
            $ret = $returned[$itemId] ?? 0;
            $newQty = isset($keep[$itemId]) ? $keep[$itemId]['quantity'] : 0;
            if (!isset($keep[$itemId]) && $ret > 0) {
                throw new InvalidArgumentException("{$label} has {$ret} returned and cannot be removed.");
            }
            if (isset($keep[$itemId]) && $newQty < max(1, $ret)) {
                throw new InvalidArgumentException($ret > 0
                    ? "{$label}: quantity cannot be less than the {$ret} already returned."
                    : "{$label}: quantity must be at least 1. Remove the item instead.");
            }
            $pid = (int)$it['product_id'];
            $delta[$pid] = ($delta[$pid] ?? 0) + $newQty - (int)$it['quantity'];
        }
        $pm = new ProductModel();
        $products = [];
        foreach ($new as $line) {
            $delta[$line['product_id']] = ($delta[$line['product_id']] ?? 0) + $line['quantity'];
        }
        foreach ($delta as $pid => $n) {
            $p = $pm->findById($pid);
            if (!$p) throw new InvalidArgumentException('A product on this sale no longer exists.');
            $products[$pid] = $p;
            if ($n > 0 && (int)$p['quantity'] < $n) {
                throw new InvalidArgumentException("Not enough stock for {$p['name']} Sz {$p['size']}: {$p['quantity']} available, {$n} more needed.");
            }
        }

        $subtotal = 0.0;
        foreach ($keep as $line) $subtotal += $line['quantity'] * $line['unit_price'];
        foreach ($new as $line) $subtotal += $line['quantity'] * $line['unit_price'];
        $discount = min(max(0, (float)($d['discount'] ?? 0)), $subtotal);
        $total = $subtotal - $discount;
        $payMethod = $d['payment_method'] ?? $sale['payment_method'];
        $tendered = isset($d['amount_tendered']) && $d['amount_tendered'] !== '' ? (float)$d['amount_tendered'] : null;
        if ($payMethod === 'cash' && $tendered !== null && $tendered + 0.005 < $total) {
            throw new RuntimeException('Amount tendered (' . money($tendered) . ') is less than the new total (' . money($total) . '). Update the amount tendered.');
        }
        $change = $payMethod === 'cash' && $tendered !== null ? max(0, $tendered - $total) : null;

        $this->db->beginTransaction();
        try {
            $upd = $this->db->prepare("UPDATE sale_items SET quantity=?, unit_price=?, line_total=? WHERE id=? AND sale_id=?");
            $del = $this->db->prepare("DELETE FROM sale_items WHERE id=? AND sale_id=?");
            foreach ($existing as $itemId => $it) {
                if (isset($keep[$itemId])) {
                    $l = $keep[$itemId];
                    $upd->execute([$l['quantity'], $l['unit_price'], $l['quantity'] * $l['unit_price'], $itemId, $id]);
                } else {
                    $del->execute([$itemId, $id]);
                }
            }
            $ins = $this->db->prepare("
                INSERT INTO sale_items (sale_id,product_id,quantity,unit_price,cost_price,line_total) VALUES (?,?,?,?,?,?)
            ");
            foreach ($new as $l) {
                $ins->execute([$id, $l['product_id'], $l['quantity'], $l['unit_price'],
                    (float)$products[$l['product_id']]['cost_price'], $l['quantity'] * $l['unit_price']]);
            }
            foreach ($delta as $pid => $n) {
                if ($n > 0) $pm->deductStock($pid, $n);
                elseif ($n < 0) $pm->addStock($pid, -$n);
            }

            $this->db->prepare("
                UPDATE sales SET
                  location_id=?, staff_id=?, customer_id=?, subtotal=?, discount=?, total=?,
                  payment_method=?, amount_tendered=?, change_due=?, momo_ref=?, notes=?,
                  created_at=COALESCE(?, created_at)
                WHERE id=?
            ")->execute([
                (int)$d['location_id'],
                (int)$d['staff_id'],
                $d['customer_id'] ?? null,
                $subtotal,
                $discount,
                $total,
                $payMethod,
                $tendered,
                $change,
                trim((string)($d['momo_ref'] ?? '')) ?: null,
                trim((string)($d['notes'] ?? '')) ?: null,
                $d['created_at'] ?? null,
                $id,
            ]);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Delete sale and restore stock for its line items. */
    public function delete(int $id): bool {
        $sale = $this->findById($id);
        if (!$sale) return false;
        $items = $this->getItems($id);
        $this->db->beginTransaction();
        try {
            $pm = new ProductModel();
            foreach ($items as $item) {
                $pm->addStock((int)$item['product_id'], (int)$item['quantity']);
            }
            $this->db->prepare('DELETE FROM sales WHERE id=?')->execute([$id]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteMany(array $ids): int {
        $count = 0;
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0 && $this->delete($id)) $count++;
        }
        return $count;
    }

    // ── Analytics ──────────────────────────────────────────────
    /** One row per sale so sale totals are not repeated once per item line. */
    private const ITEMS_PER_SALE = "(
        SELECT sale_id, SUM(quantity) AS units, SUM(quantity * cost_price) AS cost
        FROM sale_items GROUP BY sale_id
    )";

    public function summaryForPeriod(string $start, string $end): array {
        $stmt = $this->db->prepare("
            SELECT COUNT(s.id) AS num_sales,
                   SUM(s.total) AS revenue,
                   SUM(si.units) AS units_sold,
                   SUM(si.cost) AS total_cost,
                   SUM(s.total) - SUM(si.cost) AS gross_profit
            FROM sales s
            JOIN ".self::ITEMS_PER_SALE." si ON s.id=si.sale_id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$start,$end]);
        return $stmt->fetch();
    }

    public function byCategory(string $start, string $end): array {
        $stmt = $this->db->prepare("
            SELECT c.name AS category, SUM(si.quantity) AS units, SUM(si.line_total) AS revenue
            FROM sale_items si
            JOIN sales s ON si.sale_id=s.id
            JOIN products p ON si.product_id=p.id
            JOIN categories c ON p.category_id=c.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY c.id ORDER BY revenue DESC
        ");
        $stmt->execute([$start,$end]);
        return $stmt->fetchAll();
    }

    public function byLocation(string $start, string $end): array {
        $stmt = $this->db->prepare("
            SELECT l.name AS location, l.type, COUNT(s.id) AS num_sales,
                   SUM(s.total) AS revenue, SUM(si.units) AS units
            FROM sales s
            JOIN locations l ON s.location_id=l.id
            JOIN ".self::ITEMS_PER_SALE." si ON s.id=si.sale_id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY l.id ORDER BY revenue DESC
        ");
        $stmt->execute([$start,$end]);
        return $stmt->fetchAll();
    }

    public function byStaff(string $start, string $end): array {
        $stmt = $this->db->prepare("
            SELECT u.name AS staff_name, COUNT(s.id) AS num_sales,
                   SUM(s.total) AS revenue, SUM(si.units) AS units_sold
            FROM sales s
            JOIN users u ON s.staff_id=u.id
            JOIN ".self::ITEMS_PER_SALE." si ON s.id=si.sale_id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY u.id ORDER BY revenue DESC
        ");
        $stmt->execute([$start,$end]);
        return $stmt->fetchAll();
    }

    public function dailyRevenueLast30(): array {
        $stmt = $this->db->query("
            SELECT DATE(created_at) AS day, SUM(total) AS revenue, COUNT(id) AS sales
            FROM sales
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at) ORDER BY day ASC
        ");
        return $stmt->fetchAll();
    }

    public function todaySummary(): array {
        $stmt = $this->db->query("
            SELECT COUNT(s.id) AS num_sales, COALESCE(SUM(s.total),0) AS revenue,
                   COALESCE(SUM(si.units),0) AS units_sold
            FROM sales s LEFT JOIN ".self::ITEMS_PER_SALE." si ON s.id=si.sale_id
            WHERE DATE(s.created_at)=CURDATE()
        ");
        return $stmt->fetch();
    }

    public function weekSummary(): array {
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(s.total),0) AS revenue, COALESCE(SUM(si.units),0) AS units_sold
            FROM sales s LEFT JOIN ".self::ITEMS_PER_SALE." si ON s.id=si.sale_id
            WHERE YEARWEEK(s.created_at,1)=YEARWEEK(CURDATE(),1)
        ");
        return $stmt->fetch();
    }

    /** Pairs (units) sold by one staff member between two dates (inclusive). */
    public function staffUnits(int $staffId, string $start, string $end): int {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(si.quantity),0)
            FROM sales s
            JOIN sale_items si ON si.sale_id = s.id
            WHERE s.staff_id = ? AND DATE(s.created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$staffId, $start, $end]);
        return (int)$stmt->fetchColumn();
    }

    /** Customer / contact counts for a staff member on one day. */
    public function staffCustomerStats(int $staffId, string $date): array {
        $stmt = $this->db->prepare("
            SELECT
              COUNT(s.id) AS customers_today,
              SUM(CASE WHEN s.customer_id IS NOT NULL THEN 1 ELSE 0 END) AS contacts_collected
            FROM sales s
            WHERE s.staff_id = ? AND DATE(s.created_at) = ?
        ");
        $stmt->execute([$staffId, $date]);
        $row = $stmt->fetch() ?: [];
        return [
            'customers_today'    => (int)($row['customers_today'] ?? 0),
            'contacts_collected' => (int)($row['contacts_collected'] ?? 0),
        ];
    }
}
