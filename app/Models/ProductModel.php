<?php
class ProductModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function all(array $f = []): array {
        $w = ['p.is_active = 1']; $params = [];
        if (!empty($f['category_id'])) { $w[] = 'p.category_id = ?'; $params[] = $f['category_id']; }
        if (!empty($f['gender']))      { $w[] = 'p.gender = ?';      $params[] = $f['gender']; }
        if (!empty($f['search'])) {
            $w[] = '(p.name LIKE ? OR p.design LIKE ? OR p.barcode LIKE ? OR p.size LIKE ?)';
            $q = '%'.$f['search'].'%';
            array_push($params, $q, $q, $q, $q);
        }
        if (!empty($f['low_stock'])) { $w[] = 'p.quantity <= p.low_stock_threshold'; }
        $sql = "SELECT p.*, c.name AS category_name
                FROM products p JOIN categories c ON p.category_id = c.id
                WHERE ".implode(' AND ', $w)."
                ORDER BY c.sort_order, p.name, p.gender, p.design, p.size+0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id=c.id WHERE p.id=?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByBarcode(string $bc): ?array {
        $stmt = $this->db->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id=c.id WHERE p.barcode=? AND p.is_active=1");
        $stmt->execute([$bc]);
        return $stmt->fetch() ?: null;
    }

    /** Paginated POS product browser (search + category + gender). */
    public function forPosBrowse(?string $q, int $page, int $perPage, ?string $categoryId, ?string $gender): array {
        $page = max(1, $page);
        $perPage = min(50, max(1, $perPage));
        $offset = ($page - 1) * $perPage;

        $w = ['p.is_active = 1'];
        $params = [];
        if ($categoryId !== null && $categoryId !== '') {
            $w[] = 'p.category_id = ?';
            $params[] = (int) $categoryId;
        }
        if ($gender !== null && $gender !== '') {
            $w[] = 'p.gender = ?';
            $params[] = $gender;
        }
        if ($q !== null && trim($q) !== '') {
            $w[] = '(p.name LIKE ? OR p.design LIKE ? OR p.size LIKE ? OR p.barcode LIKE ? OR p.gender LIKE ?)';
            $like = '%' . trim($q) . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $where = implode(' AND ', $w);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id=c.id WHERE $where");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = "SELECT p.*, c.name AS category_name FROM products p
                JOIN categories c ON p.category_id=c.id
                WHERE $where
                ORDER BY c.sort_order, p.name, p.gender, p.design, p.size+0
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return [
            'items'      => $items,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $total > 0 ? (int) ceil($total / $perPage) : 1,
        ];
    }

    public function search(string $q, int $limit = 12): array {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name AS category_name FROM products p
            JOIN categories c ON p.category_id=c.id
            WHERE p.is_active=1 AND p.quantity > 0
              AND (p.name LIKE ? OR p.design LIKE ? OR p.size LIKE ? OR p.barcode LIKE ? OR p.gender LIKE ?)
            ORDER BY p.name, p.size+0 LIMIT ?
        ");
        $like = '%'.$q.'%';
        $stmt->bindValue(1, $like);
        $stmt->bindValue(2, $like);
        $stmt->bindValue(3, $like);
        $stmt->bindValue(4, $like);
        $stmt->bindValue(5, $like);
        $stmt->bindValue(6, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $d): int {
        $stmt = $this->db->prepare("
            INSERT INTO products (category_id,name,gender,design,size,barcode,cost_price,selling_price,quantity,low_stock_threshold,image)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([$d['category_id'],$d['name'],$d['gender'],$d['design'],$d['size'],
            $d['barcode']??null,$d['cost_price'],$d['selling_price'],$d['quantity'],
            $d['low_stock_threshold']??LOW_STOCK_THRESHOLD,$d['image']??null]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): bool {
        $stmt = $this->db->prepare("
            UPDATE products SET category_id=?,name=?,gender=?,design=?,size=?,barcode=?,
            cost_price=?,selling_price=?,low_stock_threshold=?,image=COALESCE(?,image) WHERE id=?
        ");
        return $stmt->execute([$d['category_id'],$d['name'],$d['gender'],$d['design'],$d['size'],
            $d['barcode']??null,$d['cost_price'],$d['selling_price'],
            $d['low_stock_threshold']??LOW_STOCK_THRESHOLD,$d['image']??null,$id]);
    }

    public function deductStock(int $id, int $qty): void {
        $this->db->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?")->execute([$qty,$id]);
        $this->checkLowStock($id);
    }

    public function addStock(int $id, int $qty): void {
        $this->db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")->execute([$qty,$id]);
    }

    public function checkLowStock(int $id): void {
        $p = $this->findById($id);
        if ($p && $p['quantity'] <= $p['low_stock_threshold']) {
            // Log alert (avoid duplicates within same day)
            $stmt = $this->db->prepare("
                SELECT id FROM stock_alerts WHERE product_id=? AND DATE(created_at)=CURDATE() AND is_read=0
            ");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $this->db->prepare("INSERT INTO stock_alerts (product_id,quantity) VALUES (?,?)")
                    ->execute([$id,$p['quantity']]);
            }
        }
    }

    public function adjustStock(int $productId, int $qty, string $type, ?string $note, int $userId): void {
        $this->db->prepare("INSERT INTO stock_adjustments (product_id,user_id,type,quantity,note) VALUES (?,?,?,?,?)")
            ->execute([$productId,$userId,$type,$qty,$note]);
        if ($qty > 0) $this->addStock($productId, abs($qty));
        else          $this->db->prepare("UPDATE products SET quantity = quantity + ? WHERE id=?")->execute([$qty,$productId]);
        $this->checkLowStock($productId);
    }

    public function softDelete(int $id): void {
        $this->db->prepare("UPDATE products SET is_active=0 WHERE id=?")->execute([$id]);
    }

    public function getLowStockAlerts(bool $unreadOnly = false): array {
        $w = $unreadOnly ? 'AND a.is_read=0' : '';
        $stmt = $this->db->query("
            SELECT a.*, p.name, p.gender, p.design, p.size, p.quantity, p.low_stock_threshold
            FROM stock_alerts a JOIN products p ON a.product_id=p.id
            WHERE 1=1 $w ORDER BY a.created_at DESC LIMIT 50
        ");
        return $stmt->fetchAll();
    }

    public function markAlertsRead(): void {
        $this->db->query("UPDATE stock_alerts SET is_read=1");
    }

    public function getStockValue(): array {
        $stmt = $this->db->query("
            SELECT SUM(quantity*cost_price) AS cost_value,
                   SUM(quantity*selling_price) AS retail_value,
                   SUM(quantity) AS total_units
            FROM products WHERE is_active=1
        ");
        return $stmt->fetch();
    }

    public function topSellers(string $period = 'week', int $limit = 10): array {
        $dateFilter = match($period) {
            'today' => 'DATE(s.created_at) = CURDATE()',
            'week'  => 'YEARWEEK(s.created_at,1) = YEARWEEK(CURDATE(),1)',
            'month' => 'YEAR(s.created_at)=YEAR(CURDATE()) AND MONTH(s.created_at)=MONTH(CURDATE())',
            default => '1=1',
        };
        $stmt = $this->db->query("
            SELECT p.name, p.gender, p.design, p.size, c.name AS category_name,
                   SUM(si.quantity) AS units_sold,
                   SUM(si.line_total) AS revenue
            FROM sale_items si
            JOIN sales s ON si.sale_id=s.id
            JOIN products p ON si.product_id=p.id
            JOIN categories c ON p.category_id=c.id
            WHERE $dateFilter
            GROUP BY si.product_id ORDER BY units_sold DESC LIMIT $limit
        ");
        return $stmt->fetchAll();
    }
}
