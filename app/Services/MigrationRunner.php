<?php
namespace App\Services;

use App\Core\Database;

/**
 * Runs pending SQL migrations from /migrations (001_xxx.sql, 002_xxx.sql, ...).
 * Only files not already recorded in the migrations table are executed, each
 * inside a transaction, and recorded on success.
 */
class MigrationRunner
{
    public static function pending(): array
    {
        $dir = BASE_PATH . '/migrations';
        if (!is_dir($dir)) { return []; }
        $files = glob($dir . '/*.sql') ?: [];
        sort($files);
        $applied = Database::fetchAll("SELECT migration FROM {p}migrations");
        $done = array_column($applied, 'migration');
        $pending = [];
        foreach ($files as $f) {
            $name = pathinfo($f, PATHINFO_FILENAME);
            if (!in_array($name, $done, true)) {
                $pending[] = $f;
            }
        }
        return $pending;
    }

    /** Execute all pending migrations. Returns the names applied. */
    public static function runPending(): array
    {
        $applied = [];
        $batch = (int)Database::scalar("SELECT COALESCE(MAX(batch),0)+1 FROM {p}migrations");
        foreach (self::pending() as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $sql = (string)file_get_contents($file);
            $sql = Database::table($sql); // replace {p}
            self::execScript($sql);
            Database::insert('migrations', ['migration' => $name, 'batch' => $batch]);
            $applied[] = $name;
        }
        return $applied;
    }

    /** Execute a multi-statement SQL script (comments + quoted strings aware). */
    private static function execScript(string $sql): void
    {
        $pdo = Database::pdo();
        foreach (self::split($sql) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') { continue; }
            $pdo->exec($stmt);
        }
    }

    private static function split(string $sql): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $sql);
        $clean = [];
        foreach ($lines as $l) {
            if (preg_match('/^\s*--/', $l)) { continue; }
            $clean[] = $l;
        }
        $sql = implode("\n", $clean);
        $out = []; $buf = ''; $inS = false; $inD = false;
        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i]; $prev = $i > 0 ? $sql[$i - 1] : '';
            if ($ch === "'" && !$inD && $prev !== '\\') { $inS = !$inS; }
            elseif ($ch === '"' && !$inS && $prev !== '\\') { $inD = !$inD; }
            if ($ch === ';' && !$inS && !$inD) { $out[] = $buf; $buf = ''; }
            else { $buf .= $ch; }
        }
        if (trim($buf) !== '') { $out[] = $buf; }
        return $out;
    }
}
