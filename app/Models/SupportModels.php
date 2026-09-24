<?php
class UserModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }
    public function findByEmail(string $e): ?array {
        $s = $this->db->prepare("SELECT * FROM users WHERE email=? AND is_active=1");
        $s->execute([$e]); return $s->fetch() ?: null;
    }
    public function findById(int $id): ?array {
        $s = $this->db->prepare("SELECT * FROM users WHERE id=?");
        $s->execute([$id]); return $s->fetch() ?: null;
    }
    public function allStaff(): array {
        return $this->db->query("SELECT * FROM users WHERE role='staff' ORDER BY name")->fetchAll();
    }
    public function all(): array {
        return $this->db->query("SELECT * FROM users ORDER BY role,name")->fetchAll();
    }
    public function create(array $d): int {
        $s = $this->db->prepare("INSERT INTO users (name,email,phone,password,role,pin) VALUES (?,?,?,?,?,?)");
        $s->execute([$d['name'],$d['email'],$d['phone']??null,
            password_hash($d['password'],PASSWORD_DEFAULT),$d['role']??'staff',$d['pin']??null]);
        return (int)$this->db->lastInsertId();
    }
    public function toggle(int $id): void {
        $this->db->prepare("UPDATE users SET is_active=NOT is_active WHERE id=?")->execute([$id]);
    }
    public function verify(string $pw, string $hash): bool { return password_verify($pw,$hash); }
}

class CategoryModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }
    public function all(): array { return $this->db->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll(); }
    public function findById(int $id): ?array {
        $s=$this->db->prepare("SELECT * FROM categories WHERE id=?"); $s->execute([$id]); return $s->fetch()?:null;
    }
    public function create(array $d): void {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i','-',$d['name']));
        $this->db->prepare("INSERT INTO categories (name,slug,sort_order) VALUES (?,?,?)")
            ->execute([$d['name'],$slug,$d['sort_order']??0]);
    }
    public function delete(int $id): void {
        $this->db->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
    }
}

class LocationModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }
    public function all(): array { return $this->db->query("SELECT * FROM locations WHERE is_active=1 ORDER BY name")->fetchAll(); }
    public function findById(int $id): ?array {
        $s=$this->db->prepare("SELECT * FROM locations WHERE id=?"); $s->execute([$id]); return $s->fetch()?:null;
    }
    public function create(array $d): void {
        $this->db->prepare("INSERT INTO locations (name,type) VALUES (?,?)")->execute([$d['name'],$d['type']??'shop']);
    }
    public function delete(int $id): void {
        $this->db->prepare("UPDATE locations SET is_active=0 WHERE id=?")->execute([$id]);
    }
}

class SupplierModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function all(string $search = '', bool $activeOnly = true): array {
        $w = [];
        $p = [];
        if ($activeOnly) $w[] = 'is_active=1';
        if ($search !== '') {
            $w[] = '(name LIKE ? OR phone LIKE ? OR email LIKE ?)';
            $q = '%'.$search.'%';
            array_push($p, $q, $q, $q);
        }
        $where = $w ? 'WHERE '.implode(' AND ', $w) : '';
        $stmt = $this->db->prepare("SELECT * FROM suppliers {$where} ORDER BY name");
        $stmt->execute($p);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $s = $this->db->prepare('SELECT * FROM suppliers WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function create(array $d): int {
        $name = trim((string)($d['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Supplier name is required.');
        }
        $this->db->prepare("
            INSERT INTO suppliers (name, phone, email, notes) VALUES (?,?,?,?)
        ")->execute([
            $name,
            trim((string)($d['phone'] ?? '')) ?: null,
            trim((string)($d['email'] ?? '')) ?: null,
            trim((string)($d['notes'] ?? '')) ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): bool {
        $name = trim((string)($d['name'] ?? ''));
        if ($name === '') return false;
        return $this->db->prepare("
            UPDATE suppliers SET name=?, phone=?, email=?, notes=? WHERE id=?
        ")->execute([
            $name,
            trim((string)($d['phone'] ?? '')) ?: null,
            trim((string)($d['email'] ?? '')) ?: null,
            trim((string)($d['notes'] ?? '')) ?: null,
            $id,
        ]);
    }

    public function deactivate(int $id): void {
        $this->db->prepare('UPDATE suppliers SET is_active=0 WHERE id=?')->execute([$id]);
    }

    public function count(): int {
        return (int)$this->db->query('SELECT COUNT(*) FROM suppliers WHERE is_active=1')->fetchColumn();
    }
}

class CustomerModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }
    public function all(string $search=''): array {
        if ($search) {
            $s=$this->db->prepare("SELECT * FROM customers WHERE name LIKE ? OR phone LIKE ? ORDER BY name");
            $q='%'.$search.'%'; $s->execute([$q,$q]); return $s->fetchAll();
        }
        return $this->db->query("SELECT * FROM customers ORDER BY name")->fetchAll();
    }
    public function findById(int $id): ?array {
        $s=$this->db->prepare("SELECT * FROM customers WHERE id=?"); $s->execute([$id]); return $s->fetch()?:null;
    }
    public function findByPhone(string $ph): ?array {
        $s=$this->db->prepare("SELECT * FROM customers WHERE phone=?"); $s->execute([$ph]); return $s->fetch()?:null;
    }
    public function create(array $d): int {
        $s=$this->db->prepare("INSERT INTO customers (name,phone,shoe_size,notes) VALUES (?,?,?,?)");
        $s->execute([$d['name'],$d['phone']??null,$d['shoe_size']??null,$d['notes']??null]);
        return (int)$this->db->lastInsertId();
    }
    public function count(): int { return (int)$this->db->query("SELECT COUNT(*) FROM customers")->fetchColumn(); }

    /** Customers that have a non-empty phone number (for SMS campaigns). */
    public function withPhones(): array {
        return $this->db->query("
            SELECT id, name, phone FROM customers
            WHERE phone IS NOT NULL AND TRIM(phone) <> ''
            ORDER BY name
        ")->fetchAll();
    }

    public function countWithPhones(): int {
        return (int)$this->db->query("
            SELECT COUNT(*) FROM customers
            WHERE phone IS NOT NULL AND TRIM(phone) <> ''
        ")->fetchColumn();
    }
}

class TargetModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function currentWeekTargets(): array {
        $stmt = $this->db->query("
            SELECT t.*, c.name AS category_name FROM weekly_targets t
            LEFT JOIN categories c ON t.category_id=c.id
            WHERE t.week_start <= CURDATE() AND t.week_end >= CURDATE()
            ORDER BY t.category_id IS NULL DESC, c.sort_order
        ");
        return $stmt->fetchAll();
    }

    public function all(): array {
        $stmt = $this->db->query("
            SELECT t.*, c.name AS category_name, u.name AS created_by_name FROM weekly_targets t
            LEFT JOIN categories c ON t.category_id=c.id
            JOIN users u ON t.created_by=u.id
            ORDER BY t.week_start DESC
        ");
        return $stmt->fetchAll();
    }

    public function create(array $d): void {
        $this->db->prepare("
            INSERT INTO weekly_targets (category_id,week_start,week_end,target_units,target_revenue,created_by)
            VALUES (?,?,?,?,?,?)
        ")->execute([
            $d['category_id']??null, $d['week_start'], $d['week_end'],
            $d['target_units']??0, $d['target_revenue']??0, $d['created_by'],
        ]);
    }

    public function delete(int $id): void {
        $this->db->prepare("DELETE FROM weekly_targets WHERE id=?")->execute([$id]);
    }

    public function getActualVsTarget(): array {
        $targets = $this->currentWeekTargets();
        $sm      = new SaleModel();
        $week    = $sm->weekSummary();
        $results = [];
        foreach ($targets as $t) {
            // Get actual for this category (or overall)
            if ($t['category_id']) {
                $stmt = $this->db->prepare("
                    SELECT COALESCE(SUM(si.quantity),0) AS units, COALESCE(SUM(si.line_total),0) AS revenue
                    FROM sale_items si
                    JOIN sales s ON si.sale_id=s.id
                    JOIN products p ON si.product_id=p.id
                    WHERE p.category_id=? AND YEARWEEK(s.created_at,1)=YEARWEEK(CURDATE(),1)
                ");
                $stmt->execute([$t['category_id']]);
                $actual = $stmt->fetch();
            } else {
                $actual = $week;
            }
            $pctUnits   = $t['target_units']   > 0 ? min(100, round($actual['units']   / $t['target_units']   * 100)) : 0;
            $pctRevenue = $t['target_revenue']  > 0 ? min(100, round($actual['revenue'] / $t['target_revenue'] * 100)) : 0;
            $results[] = array_merge($t, [
                'actual_units'   => $actual['units'],
                'actual_revenue' => $actual['revenue'],
                'pct_units'      => $pctUnits,
                'pct_revenue'    => $pctRevenue,
            ]);
        }
        return $results;
    }
}
