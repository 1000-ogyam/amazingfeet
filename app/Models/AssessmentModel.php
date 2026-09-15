<?php
class AssessmentModel {
    private PDO $db;
    public const WEEK_TARGET = 10;
    public const MONTH_TARGET = 40;

    public function __construct() { $this->db = getDB(); }

    public static function commissionLevel(int $pairsMonth): string {
        if ($pairsMonth < 40) return 'No commission (below 40 pairs)';
        if ($pairsMonth <= 50) return '3% (40–50 pairs)';
        if ($pairsMonth <= 70) return '5% (51–70 pairs)';
        return '7–8% (70+ pairs)';
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name AS staff_name, u.role AS staff_role
            FROM staff_daily_assessments a
            JOIN users u ON u.id = a.staff_id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findForStaffDate(int $staffId, string $date): ?array {
        $stmt = $this->db->prepare("SELECT * FROM staff_daily_assessments WHERE staff_id=? AND report_date=?");
        $stmt->execute([$staffId, $date]);
        return $stmt->fetch() ?: null;
    }

    public function all(array $f = []): array {
        $w = ['1=1']; $p = [];
        if (!empty($f['staff_only'])) { $w[] = "u.role='staff'"; }
        if (!empty($f['date'])) { $w[] = 'a.report_date=?'; $p[] = $f['date']; }
        if (!empty($f['staff_id'])) { $w[] = 'a.staff_id=?'; $p[] = (int)$f['staff_id']; }
        if (!empty($f['from'])) { $w[] = 'a.report_date>=?'; $p[] = $f['from']; }
        if (!empty($f['to'])) { $w[] = 'a.report_date<=?'; $p[] = $f['to']; }
        $stmt = $this->db->prepare("
            SELECT a.*, u.name AS staff_name
            FROM staff_daily_assessments a
            JOIN users u ON u.id = a.staff_id
            WHERE ".implode(' AND ', $w)."
            ORDER BY a.report_date DESC, u.name ASC
            LIMIT 500
        ");
        $stmt->execute($p);
        return $stmt->fetchAll();
    }

    public function forStaff(int $staffId, int $limit = 60): array {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name AS staff_name
            FROM staff_daily_assessments a
            JOIN users u ON u.id = a.staff_id
            WHERE a.staff_id=?
            ORDER BY a.report_date DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $staffId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function upsert(int $staffId, string $date, array $d): int {
        $existing = $this->findForStaffDate($staffId, $date);
        $fields = [
            'pairs_day','pairs_week','pairs_month','week_target','month_target','commission_level',
            'customers_today','contacts_collected','asked_all_customers',
            'floor_cleaned','glass_cleaned','shelves_dusted','shoes_arranged','counter_cleaned',
            'toilet_cleaned','sink_cleaned','washroom_floor_ok','tissue_soap_ok',
            'neat_dressing','clean_footwear','hair_tidy','presentation_ok',
            'whatsapp_posted','whatsapp_content','has_complaints','complaints_detail','notes',
        ];
        $vals = [];
        foreach ($fields as $f) {
            $vals[$f] = $d[$f] ?? null;
        }

        if ($existing) {
            $sets = implode(', ', array_map(fn($f) => "$f=?", $fields));
            $stmt = $this->db->prepare("UPDATE staff_daily_assessments SET $sets WHERE id=?");
            $stmt->execute([...array_values($vals), (int)$existing['id']]);
            return (int)$existing['id'];
        }

        $cols = 'staff_id, report_date, '.implode(', ', $fields);
        $ph = implode(', ', array_fill(0, count($fields) + 2, '?'));
        $stmt = $this->db->prepare("INSERT INTO staff_daily_assessments ($cols) VALUES ($ph)");
        $stmt->execute([$staffId, $date, ...array_values($vals)]);
        return (int)$this->db->lastInsertId();
    }

    /** Checklist score for admin overview (0–100). */
    public function checklistScore(array $a): int {
        $checks = [
            'asked_all_customers','floor_cleaned','glass_cleaned','shelves_dusted','shoes_arranged',
            'counter_cleaned','toilet_cleaned','sink_cleaned','washroom_floor_ok','tissue_soap_ok',
            'neat_dressing','clean_footwear','hair_tidy','presentation_ok','whatsapp_posted',
        ];
        $yes = 0;
        foreach ($checks as $c) {
            if (($a[$c] ?? '') === 'yes') $yes++;
        }
        return (int) round($yes / count($checks) * 100);
    }
}
