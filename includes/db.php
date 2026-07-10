<?php
class DB {
    private static ?PDO $pdo = null;

    public static function pdo(): PDO {
        if (self::$pdo === null) {
            $cfg = require __DIR__ . '/config.php';
            $d = $cfg['db'];
            $dsn = "mysql:host={$d['host']};port={$d['port']};dbname={$d['name']};charset={$d['charset']}";
            self::$pdo = new PDO($dsn, $d['user'], $d['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$pdo;
    }

    public static function all(string $sql, array $params = []): array {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function run(string $sql, array $params = []): PDOStatement {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function insert(string $table, array $data): int {
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $cols);
        $sql = "INSERT INTO $table (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
        self::run($sql, $data);
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Ejecuta $fn dentro de una transacción. Si $fn lanza, se hace rollBack y se
     * re-lanza la excepción (el caller decide cómo presentar el error). Si termina
     * bien, se hace commit. Devuelve lo que devuelva $fn.
     *
     * Soporta anidamiento: sólo la transacción más externa hace el begin/commit real;
     * las internas comparten esa transacción (evita "There is already an active transaction").
     */
    public static function transaction(callable $fn) {
        $pdo = self::pdo();
        $outermost = !$pdo->inTransaction();
        if ($outermost) $pdo->beginTransaction();
        try {
            $result = $fn();
            if ($outermost) $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($outermost && $pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(string $table, array $data, array $where): int {
        $sets = array_map(fn($c) => "$c = :$c", array_keys($data));
        $whereParts = [];
        $params = $data;
        foreach ($where as $k => $v) {
            $whereParts[] = "$k = :w_$k";
            $params["w_$k"] = $v;
        }
        $sql = "UPDATE $table SET " . implode(',', $sets) . " WHERE " . implode(' AND ', $whereParts);
        return self::run($sql, $params)->rowCount();
    }

    public static function delete(string $table, array $where): int {
        $whereParts = [];
        $params = [];
        foreach ($where as $k => $v) {
            $whereParts[] = "$k = :$k";
            $params[$k] = $v;
        }
        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $whereParts);
        return self::run($sql, $params)->rowCount();
    }
}
