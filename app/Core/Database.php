<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * Thin PDO wrapper. Prepared statements only — never concatenate SQL.
 */
class Database
{
    private static ?PDO $pdo = null;
    private static string $prefix = '';

    public static function init(array $cfg): void
    {
        if (self::$pdo instanceof PDO) {
            return;
        }
        self::$prefix = $cfg['prefix'] ?? '';
        $host    = $cfg['host'] ?? 'localhost';
        $port    = $cfg['port'] ?? 3306;
        $name    = $cfg['name'] ?? '';
        $charset = $cfg['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        try {
            self::$pdo = new PDO($dsn, $cfg['user'] ?? '', $cfg['pass'] ?? '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /** Raw PDO for advanced needs (transactions, lastInsertId). */
    public static function pdo(): PDO
    {
        if (!self::$pdo instanceof PDO) {
            throw new \RuntimeException('Database not initialised.');
        }
        return self::$pdo;
    }

    public static function prefix(): string
    {
        return self::$prefix;
    }

    /** Replace {p} placeholder with the configured table prefix. */
    public static function table(string $sql): string
    {
        return str_replace('{p}', self::$prefix, $sql);
    }

    /** Run a prepared statement and return the PDOStatement. */
    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare(self::table($sql));
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch a single row (or null). */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Fetch all rows. */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** Fetch a single scalar column. */
    public static function scalar(string $sql, array $params = [])
    {
        return self::run($sql, $params)->fetchColumn();
    }

    /** INSERT helper. Returns last insert id. */
    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $place = implode(',', array_map(fn($c) => ':' . $c, $cols));
        $colList = implode(',', array_map(fn($c) => "`$c`", $cols));
        $sql = "INSERT INTO {p}{$table} ({$colList}) VALUES ({$place})";
        self::run($sql, $data);
        return (int)self::pdo()->lastInsertId();
    }

    /** UPDATE helper with a simple WHERE map. Returns affected rows. */
    public static function update(string $table, array $data, array $where): int
    {
        $set = implode(',', array_map(fn($c) => "`$c` = :s_$c", array_keys($data)));
        $cond = implode(' AND ', array_map(fn($c) => "`$c` = :w_$c", array_keys($where)));
        $params = [];
        foreach ($data as $k => $v)  { $params["s_$k"] = $v; }
        foreach ($where as $k => $v) { $params["w_$k"] = $v; }
        $sql = "UPDATE {p}{$table} SET {$set} WHERE {$cond}";
        return self::run($sql, $params)->rowCount();
    }

    /** DELETE helper. Returns affected rows. */
    public static function delete(string $table, array $where): int
    {
        $cond = implode(' AND ', array_map(fn($c) => "`$c` = :$c", array_keys($where)));
        return self::run("DELETE FROM {p}{$table} WHERE {$cond}", $where)->rowCount();
    }

    public static function beginTransaction(): void { self::pdo()->beginTransaction(); }
    public static function commit(): void { self::pdo()->commit(); }
    public static function rollBack(): void { if (self::pdo()->inTransaction()) { self::pdo()->rollBack(); } }

    /**
     * Run a closure inside a transaction. Reentrant: if a transaction is
     * already active, the closure runs within it (no nested BEGIN, which MySQL
     * does not support) and the outer caller controls commit/rollback.
     */
    public static function transaction(callable $fn)
    {
        if (self::pdo()->inTransaction()) {
            return $fn();
        }
        self::beginTransaction();
        try {
            $result = $fn();
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollBack();
            throw $e;
        }
    }
}
