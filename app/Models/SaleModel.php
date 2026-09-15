<?php
class SaleModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function generateRef(): string {
        return 'AF-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -5));
    }

    public function create(array $sale, array $items): int {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO sales (sale_ref,staff_id,location_id,customer_id,subtotal,discount,total,
                    payment_method,amount_tendered,change_due,momo_ref,notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $sale['sale_ref'], $sale['staff_id'], $sale['location_id'],
                $sale['customer_id']??null, $sale['subtotal'], $sale['discount']??0,
                $sale['total'], $sale['payment_method'],
                $sale['amount_tendered']??null, $sale['change_due']??null,
                $sale['momo_ref']??null, $sale['notes']??null,
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
            $this->db->commit();
            return $saleId;
        } catch (Exception $e) {
            $this->db->rollBack();
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
        $stmt = $this->db->prepare("
            SELECT si.*, p.name, p.gender, p.design, p.size, p.sku, p.barcode, c.name AS category_name
            FROM sale_items si
            JOIN products p ON si.product_id=p.id
            JOIN categories c ON p.category_id=c.id
            WHERE si.sale_id=?
        ");
        $stmt->execute([$saleId]);
        return $stmt->fetchAll();
    }

    public function all(array $f = []): array {
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
        $stmt = $this->db->prepare("
            SELECT s.*, u.name AS staff_name, l.name AS location_name
            FROM sales s JOIN users u ON s.staff_id=u.id JOIN locations l ON s.location_id=l.id
            WHERE ".implode(' AND ',$w)." ORDER BY s.created_at DESC LIMIT 500
        ");
        $stmt->execute($p);
        return $stmt->fetchAll();
    }

    public function update(int $id, array $d): bool {
        $sale = $this->findById($id);
        if (!$sale) return false;

        $discount = max(0, (float)($d['discount'] ?? $sale['discount']));
        $subtotal = (float)$sale['subtotal'];
        $total    = max(0, $subtotal - $discount);
        $payMethod = $d['payment_method'] ?? $sale['payment_method'];
        $tendered  = isset($d['amount_tendered']) && $d['amount_tendered'] !== ''
            ? (float)$d['amount_tendered'] : $sale['amount_tendered'];
        $change = $payMethod === 'cash' && $tendered !== null
            ? max(0, (float)$tendered - $total) : null;

        $stmt = $this->db->prepare("
            UPDATE sales SET
              location_id=?, staff_id=?, discount=?, total=?,
              payment_method=?, amount_tendered=?, change_due=?,
              momo_ref=?, notes=?
            WHERE id=?
        ");
        return $stmt->execute([
            (int)$d['location_id'],
            (int)$d['staff_id'],
            $discount,
            $total,
            $payMethod,
            $tendered,
            $change,
            trim((string)($d['momo_ref'] ?? '')) ?: null,
            trim((string)($d['notes'] ?? '')) ?: null,
            $id,
        ]);
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
    public function summaryForPeriod(string $start, string $end): array {
        $stmt = $this->db->prepare("
            SELECT COUNT(s.id) AS num_sales,
                   SUM(s.total) AS revenue,
                   SUM(si.quantity) AS units_sold,
                   SUM(si.quantity * si.cost_price) AS total_cost,
                   SUM(s.total) - SUM(si.quantity * si.cost_price) AS gross_profit
            FROM sales s
            JOIN sale_items si ON s.id=si.sale_id
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
                   SUM(s.total) AS revenue, SUM(si.quantity) AS units
            FROM sales s
            JOIN locations l ON s.location_id=l.id
            JOIN sale_items si ON s.id=si.sale_id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY l.id ORDER BY revenue DESC
        ");
        $stmt->execute([$start,$end]);
        return $stmt->fetchAll();
    }

    public function byStaff(string $start, string $end): array {
        $stmt = $this->db->prepare("
            SELECT u.name AS staff_name, COUNT(s.id) AS num_sales,
                   SUM(s.total) AS revenue, SUM(si.quantity) AS units_sold
            FROM sales s
            JOIN users u ON s.staff_id=u.id
            JOIN sale_items si ON s.id=si.sale_id
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
                   COALESCE(SUM(si.quantity),0) AS units_sold
            FROM sales s LEFT JOIN sale_items si ON s.id=si.sale_id
            WHERE DATE(s.created_at)=CURDATE()
        ");
        return $stmt->fetch();
    }

    public function weekSummary(): array {
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(s.total),0) AS revenue, COALESCE(SUM(si.quantity),0) AS units_sold
            FROM sales s LEFT JOIN sale_items si ON s.id=si.sale_id
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
