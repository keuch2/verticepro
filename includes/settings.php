<?php
require_once __DIR__ . '/db.php';

/**
 * Settings — key/value store for site-wide configuration (SMTP, social, contact, SEO).
 * Cargado bajo demanda y cacheado en memoria por request.
 */
class Settings {
    private static ?array $cache = null;

    private static function load(): array {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (DB::all('SELECT `key`, `value` FROM settings') as $row) {
                    self::$cache[$row['key']] = $row['value'];
                }
            } catch (\Throwable $e) {
                self::$cache = [];
            }
        }
        return self::$cache;
    }

    public static function get(string $key, ?string $default = null): ?string {
        $all = self::load();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void {
        // upsert
        DB::run('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', [$key, $value]);
        if (self::$cache !== null) self::$cache[$key] = $value;
    }

    public static function setMany(array $pairs): void {
        foreach ($pairs as $k => $v) self::set($k, $v);
    }

    /** Devuelve solo claves de un prefijo dado, sin el prefijo en las keys. */
    public static function group(string $prefix): array {
        $all = self::load();
        $out = [];
        foreach ($all as $k => $v) {
            if (str_starts_with($k, $prefix)) $out[substr($k, strlen($prefix))] = $v;
        }
        return $out;
    }
}

/** Atajo para uso en templates. */
function setting(string $key, ?string $default = null): ?string {
    return Settings::get($key, $default);
}
