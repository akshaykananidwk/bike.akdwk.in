<?php
namespace App\Core;

/**
 * Very small active-record-ish base model. Concrete models set $table.
 */
abstract class Model
{
    protected static string $table = '';

    public static function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {p}" . static::$table . " WHERE id = ?", [$id]);
    }

    public static function findBy(string $column, $value): ?array
    {
        return Database::fetch(
            "SELECT * FROM {p}" . static::$table . " WHERE `{$column}` = ? LIMIT 1",
            [$value]
        );
    }

    public static function all(string $orderBy = 'id DESC'): array
    {
        return Database::fetchAll("SELECT * FROM {p}" . static::$table . " ORDER BY {$orderBy}");
    }

    public static function where(string $whereSql, array $params = [], string $orderBy = 'id DESC'): array
    {
        return Database::fetchAll(
            "SELECT * FROM {p}" . static::$table . " WHERE {$whereSql} ORDER BY {$orderBy}",
            $params
        );
    }

    public static function create(array $data): int
    {
        return Database::insert(static::$table, $data);
    }

    public static function updateById(int $id, array $data): int
    {
        return Database::update(static::$table, $data, ['id' => $id]);
    }

    public static function deleteById(int $id): int
    {
        return Database::delete(static::$table, ['id' => $id]);
    }

    public static function count(string $whereSql = '1', array $params = []): int
    {
        return (int)Database::scalar(
            "SELECT COUNT(*) FROM {p}" . static::$table . " WHERE {$whereSql}",
            $params
        );
    }
}
