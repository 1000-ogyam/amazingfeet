<?php
class ProductModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function all(array $f = []): array {
        $w = ['p.is_active = 1']; $params = [];
        if (!empty($f['category_id'])) { $w[] = 'p.category_id = ?'; $params[] = $f['category_id']; }
        if (!empty($f['gender']))      { $w[] = 'p.gender = ?';      $params[] = $f['gender']; }
        if (!empty($f['search'])) {
            if ($this->hasSkuColumn()) {
                $w[] = '(p.name LIKE ? OR p.design LIKE ? OR p.barcode LIKE ? OR p.sku LIKE ? OR p.size LIKE ?)';
                $q = '%'.$f['search'].'%';
                array_push($params, $q, $q, $q, $q, $q);
            } else {
                $w[] = '(p.name LIKE ? OR p.design LIKE ? OR p.barcode LIKE ? OR p.size LIKE ?)';
                $q = '%'.$f['search'].'%';
                array_push($params, $q, $q, $q, $q);
            }
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
        return $this->findByCode($bc);
    }

    /** Find active product by SKU or barcode. */
    public function findByCode(string $code): ?array {
        $code = trim($code);
        if ($code === '') return null;
        if ($this->hasSkuColumn()) {
            $stmt = $this->db->prepare("
                SELECT p.*, c.name AS category_name
                FROM products p JOIN categories c ON p.category_id=c.id
                WHERE p.is_active=1 AND (p.sku=? OR p.barcode=?)
                LIMIT 1
            ");
            $stmt->execute([$code, $code]);
        } else {
            $stmt = $this->db->prepare("
                SELECT p.*, c.name AS category_name
                FROM products p JOIN categories c ON p.category_id=c.id
                WHERE p.is_active=1 AND p.barcode=?
                LIMIT 1
            ");
            $stmt->execute([$code]);
        }
        return $stmt->fetch() ?: null;
    }

    /** Paginated POS browser grouped by style_key (fallback: name/gender/design/category). */
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
            if ($this->hasSkuColumn()) {
                $w[] = '(p.name LIKE ? OR p.design LIKE ? OR p.size LIKE ? OR p.barcode LIKE ? OR p.sku LIKE ? OR p.gender LIKE ?)';
                $like = '%' . trim($q) . '%';
                array_push($params, $like, $like, $like, $like, $like, $like);
            } else {
                $w[] = '(p.name LIKE ? OR p.design LIKE ? OR p.size LIKE ? OR p.barcode LIKE ? OR p.gender LIKE ?)';
                $like = '%' . trim($q) . '%';
                array_push($params, $like, $like, $like, $like, $like);
            }
        }
        $where = implode(' AND ', $w);
        $groupExpr = $this->hasStyleKeyColumn()
            ? "COALESCE(NULLIF(TRIM(p.style_key), ''), CONCAT(p.category_id,'|',p.name,'|',p.gender,'|',COALESCE(p.design,'')))"
            : 'p.category_id, p.name, p.gender, COALESCE(p.design, \'\')';

        $countSql = "SELECT COUNT(*) FROM (
            SELECT 1 FROM products p JOIN categories c ON p.category_id=c.id
            WHERE $where GROUP BY $groupExpr
        ) t";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $styleSelect = $this->hasStyleKeyColumn()
            ? "MAX(p.style_key) AS style_key,"
            : "NULL AS style_key,";

        $sql = "SELECT
                    MIN(p.id) AS id,
                    $styleSelect
                    p.category_id,
                    p.name,
                    p.gender,
                    COALESCE(p.design, '') AS design,
                    c.name AS category_name,
                    MAX(p.image) AS image,
                    MIN(p.selling_price) AS price_min,
                    MAX(p.selling_price) AS price_max,
                    SUM(p.quantity) AS quantity,
                    COUNT(*) AS size_count
                FROM products p
                JOIN categories c ON p.category_id=c.id
                WHERE $where
                GROUP BY $groupExpr, p.category_id, p.name, p.gender, COALESCE(p.design, ''), c.name, c.sort_order
                ORDER BY c.sort_order, p.name, p.gender, COALESCE(p.design, '')
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $families = $stmt->fetchAll();

        $items = [];
        foreach ($families as $f) {
            $variants = !empty($f['style_key'])
                ? $this->variantsByStyleKey((string)$f['style_key'])
                : $this->variantsOf((int)$f['category_id'], $f['name'], $f['gender'], $f['design'] ?? '');
            $items[] = [
                'id'            => (int)$f['id'],
                'style_key'     => $f['style_key'] ?? null,
                'category_id'   => (int)$f['category_id'],
                'name'          => $f['name'],
                'gender'        => $f['gender'],
                'design'        => $f['design'],
                'category_name' => $f['category_name'],
                'image'         => $f['image'],
                'price_min'     => (float)$f['price_min'],
                'price_max'     => (float)$f['price_max'],
                'quantity'      => (int)$f['quantity'],
                'size_count'    => (int)$f['size_count'],
                'variants'      => array_map(static function ($v) {
                    return [
                        'id'            => (int)$v['id'],
                        'size'          => $v['size'],
                        'sku'           => $v['sku'] ?? null,
                        'selling_price' => (float)$v['selling_price'],
                        'cost_price'    => (float)$v['cost_price'],
                        'quantity'      => (int)$v['quantity'],
                        'barcode'       => $v['barcode'],
                        'name'          => $v['name'],
                        'design'        => $v['design'],
                        'gender'        => $v['gender'],
                        'category_name' => $v['category_name'],
                    ];
                }, $variants),
            ];
        }

        return [
            'items'      => $items,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $total > 0 ? (int) ceil($total / $perPage) : 1,
        ];
    }

    public function search(string $q, int $limit = 12): array {
        if ($this->hasSkuColumn()) {
            $stmt = $this->db->prepare("
                SELECT p.*, c.name AS category_name FROM products p
                JOIN categories c ON p.category_id=c.id
                WHERE p.is_active=1 AND p.quantity > 0
                  AND (p.name LIKE ? OR p.design LIKE ? OR p.size LIKE ? OR p.barcode LIKE ? OR p.sku LIKE ? OR p.gender LIKE ?)
                ORDER BY p.name, p.size+0 LIMIT ?
            ");
            $like = '%'.$q.'%';
            $stmt->bindValue(1, $like);
            $stmt->bindValue(2, $like);
            $stmt->bindValue(3, $like);
            $stmt->bindValue(4, $like);
            $stmt->bindValue(5, $like);
            $stmt->bindValue(6, $like);
            $stmt->bindValue(7, $limit, PDO::PARAM_INT);
        } else {
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
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function newStyleKey(): string {
        return bin2hex(random_bytes(16));
    }

    private function hasStyleKeyColumn(): bool {
        static $has = null;
        if ($has !== null) return $has;
        try {
            $this->db->query('SELECT style_key FROM products LIMIT 1');
            $has = true;
        } catch (Throwable $e) {
            $has = false;
        }
        return $has;
    }

    private function hasSkuColumn(): bool {
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

    public function create(array $d): int {
        $sku = trim((string)($d['sku'] ?? '')) ?: null;
        $barcode = trim((string)($d['barcode'] ?? '')) ?: null;
        $hasSku = $this->hasSkuColumn();
        if ($this->hasStyleKeyColumn() && $hasSku) {
            $stmt = $this->db->prepare("
                INSERT INTO products (style_key,category_id,name,gender,design,size,sku,barcode,cost_price,selling_price,quantity,low_stock_threshold,image)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $d['style_key'] ?? $this->newStyleKey(),
                $d['category_id'],$d['name'],$d['gender'],$d['design'],$d['size'],
                $sku, $barcode, $d['cost_price'],$d['selling_price'],$d['quantity'],
                $d['low_stock_threshold']??LOW_STOCK_THRESHOLD,$d['image']??null,
            ]);
        } elseif ($this->hasStyleKeyColumn()) {
            $stmt = $this->db->prepare("
                INSERT INTO products (style_key,category_id,name,gender,design,size,barcode,cost_price,selling_price,quantity,low_stock_threshold,image)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $d['style_key'] ?? $this->newStyleKey(),
                $d['category_id'],$d['name'],$d['gender'],$d['design'],$d['size'],
                $barcode, $d['cost_price'],$d['selling_price'],$d['quantity'],
                $d['low_stock_threshold']??LOW_STOCK_THRESHOLD,$d['image']??null,
            ]);
        } elseif ($hasSku) {
            $stmt = $this->db->prepare("
                INSERT INTO products (category_id,name,gender,design,size,sku,barcode,cost_price,selling_price,quantity,low_stock_threshold,image)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([$d['category_id'],$d['name'],$d['gender'],$d['design'],$d['size'],
                $sku, $barcode, $d['cost_price'],$d['selling_price'],$d['quantity'],
                $d['low_stock_threshold']??LOW_STOCK_THRESHOLD,$d['image']??null]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO products (category_id,name,gender,design,size,barcode,cost_price,selling_price,quantity,low_stock_threshold,image)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([$d['category_id'],$d['name'],$d['gender'],$d['design'],$d['size'],
                $barcode, $d['cost_price'],$d['selling_price'],$d['quantity'],
                $d['low_stock_threshold']??LOW_STOCK_THRESHOLD,$d['image']??null]);
        }
        return (int)$this->db->lastInsertId();
    }

    /** Create one shared product style with many size rows (each may have its own price/stock). */
    public function createWithSizes(array $shared, array $sizes): int {
        $created = 0;
        $firstId = 0;
        $styleKey = $this->newStyleKey();
        $this->db->beginTransaction();
        try {
            foreach ($sizes as $row) {
                $size = trim((string)($row['size'] ?? ''));
                if ($size === '') continue;
                $id = $this->create([
                    'style_key'           => $styleKey,
                    'category_id'         => $shared['category_id'],
                    'name'                => $shared['name'],
                    'gender'              => $shared['gender'],
                    'design'              => $shared['design'] ?? null,
                    'size'                => $size,
                    'sku'                 => ($row['sku'] ?? '') !== '' ? trim((string)$row['sku']) : null,
                    'barcode'             => ($row['barcode'] ?? '') !== '' ? trim((string)$row['barcode']) : null,
                    'cost_price'          => (float)($row['cost_price'] ?? $shared['cost_price'] ?? 0),
                    'selling_price'       => (float)($row['selling_price'] ?? 0),
                    'quantity'            => (int)($row['quantity'] ?? 0),
                    'low_stock_threshold' => $shared['low_stock_threshold'] ?? LOW_STOCK_THRESHOLD,
                    'image'               => $shared['image'] ?? null,
                ]);
                if (!$firstId) $firstId = $id;
                $created++;
            }
            if ($created < 1) {
                throw new InvalidArgumentException('Add at least one size with a selling price.');
            }
            $this->db->commit();
            return $firstId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Other size SKUs that belong to the same style. */
    public function siblings(int $id, bool $includeSelf = true): array {
        $p = $this->findById($id);
        if (!$p) return [];
        if (!empty($p['style_key'])) {
            $rows = $this->variantsByStyleKey((string)$p['style_key']);
            if ($includeSelf) return $rows;
            return array_values(array_filter($rows, fn($r) => (int)$r['id'] !== $id));
        }
        return $this->variantsOf(
            (int)$p['category_id'],
            $p['name'],
            $p['gender'],
            $p['design'] ?? '',
            $includeSelf ? null : $id
        );
    }

    public function variantsByStyleKey(string $styleKey, ?int $excludeId = null): array {
        $w = ['p.is_active=1', 'p.style_key=?'];
        $params = [$styleKey];
        if ($excludeId) {
            $w[] = 'p.id<>?';
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare("
            SELECT p.*, c.name AS category_name
            FROM products p JOIN categories c ON p.category_id=c.id
            WHERE ".implode(' AND ', $w)."
            ORDER BY p.size+0, p.size
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function variantsOf(int $categoryId, string $name, string $gender, string $design = '', ?int $excludeId = null): array {
        $w = ['p.is_active=1', 'p.category_id=?', 'p.name=?', 'p.gender=?', 'COALESCE(p.design,\'\')=?'];
        $params = [$categoryId, $name, $gender, (string)$design];
        if ($excludeId) {
            $w[] = 'p.id<>?';
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare("
            SELECT p.*, c.name AS category_name
            FROM products p JOIN categories c ON p.category_id=c.id
            WHERE ".implode(' AND ', $w)."
            ORDER BY p.size+0, p.size
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function addSizeToFamily(int $siblingId, array $row): int {
        $p = $this->findById($siblingId);
        if (!$p) throw new InvalidArgumentException('Product not found.');
        $styleKey = $p['style_key'] ?? null;
        if ($this->hasStyleKeyColumn() && (!$styleKey || $styleKey === '')) {
            $styleKey = $this->newStyleKey();
            $this->db->prepare('UPDATE products SET style_key=? WHERE id=?')->execute([$styleKey, $siblingId]);
            // Attach existing composite siblings to the same key
            foreach ($this->variantsOf((int)$p['category_id'], $p['name'], $p['gender'], $p['design'] ?? '') as $s) {
                $this->db->prepare('UPDATE products SET style_key=? WHERE id=?')->execute([$styleKey, (int)$s['id']]);
            }
        }
        return $this->create([
            'style_key'           => $styleKey,
            'category_id'         => (int)$p['category_id'],
            'name'                => $p['name'],
            'gender'              => $p['gender'],
            'design'              => $p['design'],
            'size'                => trim((string)$row['size']),
            'sku'                 => ($row['sku'] ?? '') !== '' ? trim((string)$row['sku']) : null,
            'barcode'             => ($row['barcode'] ?? '') !== '' ? trim((string)$row['barcode']) : null,
            'cost_price'          => (float)($row['cost_price'] ?? $p['cost_price']),
            'selling_price'       => (float)$row['selling_price'],
            'quantity'            => (int)($row['quantity'] ?? 0),
            'low_stock_threshold' => (int)$p['low_stock_threshold'],
            'image'               => $p['image'],
        ]);
    }

    public function update(int $id, array $d): bool {
        $current = $this->findById($id);
        if (!$current) return false;

        // Keep family identity in sync when shared fields change
        $siblings = $this->siblings($id, true);
        $sharedSql = $this->db->prepare("
            UPDATE products SET category_id=?, name=?, gender=?, design=?,
              low_stock_threshold=?, image=COALESCE(?, image)
            WHERE id=?
        ");
        foreach ($siblings as $s) {
            $sharedSql->execute([
                (int)$d['category_id'],
                $d['name'],
                $d['gender'],
                $d['design'] ?? null,
                $d['low_stock_threshold'] ?? LOW_STOCK_THRESHOLD,
                $d['image'] ?? null,
                (int)$s['id'],
            ]);
        }

        $stmt = $this->db->prepare("
            UPDATE products SET size=?, ".($this->hasSkuColumn() ? 'sku=?,' : '')." barcode=?, cost_price=?, selling_price=?
            WHERE id=?
        ");
        $params = [$d['size']];
        if ($this->hasSkuColumn()) $params[] = trim((string)($d['sku'] ?? '')) ?: null;
        array_push($params, $d['barcode'] ?? null, $d['cost_price'], $d['selling_price'], $id);
        return $stmt->execute($params);
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
