<?php
/**
 * System settings stored as key/value rows. The table is created on first use,
 * so no migration is needed on existing installs.
 */
class SettingsModel {
    private const DEFAULTS = [
        'sms_enabled'   => '1',
        'sms_receipts'  => '1',
        'sms_campaigns' => '1',
    ];
    private static ?array $cache = null;

    public static function get(string $key): string {
        $all = self::load();
        return $all[$key] ?? (self::DEFAULTS[$key] ?? '');
    }

    public static function bool(string $key): bool {
        return self::get($key) === '1';
    }

    /** SMS master switch plus the switch for one feature ('receipts' or 'campaigns'). */
    public static function smsAllowed(string $feature = ''): bool {
        if (!self::bool('sms_enabled')) return false;
        return $feature === '' || self::bool('sms_' . $feature);
    }

    public static function setMany(array $values): void {
        self::load();
        $stmt = getDB()->prepare("
            INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        foreach ($values as $k => $v) {
            $stmt->execute([$k, (string)$v]);
            self::$cache[$k] = (string)$v;
        }
    }

    private static function load(): array {
        if (self::$cache !== null) return self::$cache;
        $db = getDB();
        try {
            $rows = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            $db->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    setting_key   VARCHAR(64) NOT NULL PRIMARY KEY,
                    setting_value TEXT NULL,
                    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $rows = [];
        }
        return self::$cache = $rows;
    }
}
