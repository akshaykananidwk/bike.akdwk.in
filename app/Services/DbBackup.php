<?php
namespace App\Services;

use App\Core\Database;

/**
 * Pure-PHP database dump (no mysqldump dependency — works on shared hosting).
 * Produces a restorable SQL script of all prefixed tables.
 */
class DbBackup
{
    /** Return the full SQL dump as a string. */
    public static function dump(): string
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $out = "-- Dwarka Rental DB backup " . gmdate('Y-m-d H:i:s') . " UTC\n";
        $out .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            if ($prefix !== '' && strpos($table, $prefix) !== 0) {
                continue; // only back up our tables
            }
            // Structure
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $out .= ($create['Create Table'] ?? '') . ";\n\n";

            // Data
            $rows = $pdo->query("SELECT * FROM `{$table}`");
            $buffer = '';
            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $cols = '`' . implode('`,`', array_keys($row)) . '`';
                $vals = implode(',', array_map(function ($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote((string)$v);
                }, array_values($row)));
                $buffer .= "INSERT INTO `{$table}` ({$cols}) VALUES ({$vals});\n";
            }
            $out .= $buffer . "\n";
        }
        $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $out;
    }

    /** Write the dump to a file and return its path. */
    public static function toFile(string $path): string
    {
        file_put_contents($path, self::dump());
        return $path;
    }
}
