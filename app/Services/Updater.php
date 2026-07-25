<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Settings;

/**
 * GitHub one-click updater with automatic backup + rollback.
 *
 * Protected paths are never overwritten by an update, so local config,
 * uploads, and backups survive.
 */
class Updater
{
    /** Paths (relative to project root) never touched by an update. */
    private const PROTECTED = [
        'config',
        'uploads',
        'backups',
        'logs',
        'cache',
        '.env',
        '.git',
        'install/database.sql', // keep local seed if customised
    ];

    private static function lockFile(): string { return BASE_PATH . '/cache/update.lock'; }

    // ---- Check ------------------------------------------------------------

    public static function checkForUpdate(): array
    {
        $client = new GitHubClient();
        if (!$client->isConfigured()) {
            throw new \RuntimeException('GitHub repository is not configured.');
        }
        $branch = (string)Settings::get('github_branch', 'main');
        $latest = $client->latestCommit($branch);
        $latestSha = $latest['sha'] ?? '';
        $local = self::localVersion();
        $localSha = $local['commit'] ?? '';

        $updateAvailable = $latestSha !== '' && $latestSha !== $localSha;

        $changedFiles = [];
        if ($updateAvailable && $localSha !== '') {
            try {
                $cmp = $client->compare($localSha, $latestSha);
                $changedFiles = array_map(fn($f) => $f['filename'], $cmp['files'] ?? []);
            } catch (\Throwable $e) { /* base commit may be unknown */ }
        }

        return [
            'update_available' => $updateAvailable,
            'local_commit'     => $localSha,
            'local_version'    => $local['version'] ?? '1.0.0',
            'latest_commit'    => $latestSha,
            'short_sha'        => substr($latestSha, 0, 7),
            'message'          => $latest['commit']['message'] ?? '',
            'author'           => $latest['commit']['author']['name'] ?? '',
            'date'             => $latest['commit']['author']['date'] ?? '',
            'changed_files'    => $changedFiles,
            'changed_count'    => count($changedFiles),
        ];
    }

    // ---- Update -----------------------------------------------------------

    /**
     * Run the full update. $log is a callback(string $step, string $status)
     * for live progress. Returns a summary array.
     */
    public static function updateNow(?callable $log = null): array
    {
        $log = $log ?? function () {};
        $historyId = 0;
        $backupZip = '';
        $dbDump = '';

        // 0) Single-run lock
        if (is_file(self::lockFile())) {
            throw new \RuntimeException('An update is already running.');
        }
        @file_put_contents(self::lockFile(), (string)time());

        $local = self::localVersion();
        $historyId = Database::insert('update_history', [
            'from_version' => $local['version'] ?? '1.0.0',
            'from_commit'  => $local['commit'] ?? '',
            'status'       => 'running',
        ]);

        try {
            // 1) Preflight
            $log('Pre-flight checks', 'running');
            self::preflight();
            Settings::set('maintenance_mode', '1');
            $log('Pre-flight checks', 'done');

            // 2) Backup
            $log('Creating backup', 'running');
            [$backupZip, $dbDump] = self::backup();
            Database::update('update_history', ['files_backup' => basename($backupZip), 'db_backup' => basename($dbDump)], ['id' => $historyId]);
            $log('Creating backup', 'done');

            // 3) Download
            $log('Downloading update', 'running');
            $branch = (string)Settings::get('github_branch', 'main');
            $zipPath = BASE_PATH . '/cache/update_' . date('YmdHis') . '.zip';
            $size = (new GitHubClient())->downloadZip($branch, $zipPath);
            if ($size < 1000) { throw new \RuntimeException('Downloaded file is too small — aborting.'); }
            $log('Downloading update', 'done');

            // 4) Extract to staging
            $log('Extracting files', 'running');
            $staging = BASE_PATH . '/cache/staging_' . date('YmdHis');
            $root = self::extract($zipPath, $staging);
            $log('Extracting files', 'done');

            // 5) Copy files (skip protected)
            $log('Applying files', 'running');
            $copied = self::copyTree($root, BASE_PATH);
            $log('Applying files', 'done (' . $copied . ' files)');

            // 6) Migrations
            $log('Running migrations', 'running');
            $migrated = MigrationRunner::runPending();
            $log('Running migrations', 'done (' . count($migrated) . ' applied)');

            // 7) Clear cache + bump version
            $log('Clearing cache', 'running');
            self::clearCache([$zipPath, $staging]);
            if (function_exists('opcache_reset')) { @opcache_reset(); }
            $latest = self::checkForUpdate();
            self::writeVersion($latest['latest_commit'], $local['version'] ?? '1.0.0');
            Settings::set('asset_version', (string)time());
            $log('Clearing cache', 'done');

            // 8) Finish
            Settings::set('maintenance_mode', '0');
            Database::update('update_history', [
                'status'        => 'success',
                'to_commit'     => $latest['latest_commit'] ?? '',
                'to_version'    => $local['version'] ?? '1.0.0',
                'changed_files' => json_encode($migrated),
                'finished_at'   => now(),
            ], ['id' => $historyId]);
            @unlink(self::lockFile());
            $log('Update complete', 'success');

            return ['ok' => true, 'migrated' => $migrated, 'commit' => $latest['latest_commit'] ?? ''];
        } catch (\Throwable $e) {
            // Rollback
            $log('ERROR: ' . $e->getMessage(), 'error');
            $log('Rolling back', 'running');
            try {
                if ($backupZip) { self::restoreFiles($backupZip); }
                if ($dbDump)    { self::restoreDb($dbDump); }
                $log('Rolling back', 'done');
            } catch (\Throwable $re) {
                $log('Rollback error: ' . $re->getMessage(), 'error');
            }
            Settings::set('maintenance_mode', '0');
            if ($historyId) {
                Database::update('update_history', ['status' => 'rolled_back', 'log' => $e->getMessage(), 'finished_at' => now()], ['id' => $historyId]);
            }
            @unlink(self::lockFile());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    // ---- Steps ------------------------------------------------------------

    private static function preflight(): void
    {
        foreach (['cache', 'backups', 'app'] as $d) {
            if (!is_writable(BASE_PATH . '/' . $d)) {
                throw new \RuntimeException("Directory /{$d} is not writable.");
            }
        }
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            throw new \RuntimeException('PHP 8.1+ required.');
        }
        $free = @disk_free_space(BASE_PATH);
        if ($free !== false && $free < 20 * 1024 * 1024) {
            throw new \RuntimeException('Not enough free disk space.');
        }
    }

    /** @return array{0:string,1:string} [filesZip, dbDump] */
    public static function backup(): array
    {
        $stamp = date('Ymd_His');
        $zipPath = BASE_PATH . "/backups/files_{$stamp}.zip";
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create backup archive.');
        }
        $skip = ['backups', 'cache', '.git', 'uploads', 'logs'];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(BASE_PATH, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $file) {
            $path = $file->getPathname();
            $rel = ltrim(str_replace(BASE_PATH, '', $path), '/\\');
            $top = explode('/', str_replace('\\', '/', $rel))[0];
            if (in_array($top, $skip, true)) { continue; }
            if ($file->isDir()) { $zip->addEmptyDir($rel); }
            else { $zip->addFile($path, $rel); }
        }
        $zip->close();

        $dbPath = BASE_PATH . "/backups/db_{$stamp}.sql";
        DbBackup::toFile($dbPath);

        self::pruneBackups();
        return [$zipPath, $dbPath];
    }

    private static function extract(string $zipPath, string $staging): string
    {
        @mkdir($staging, 0755, true);
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Cannot open downloaded archive.');
        }
        $zip->extractTo($staging);
        $zip->close();
        // GitHub zipball wraps everything in a single top folder.
        $entries = array_values(array_filter(scandir($staging), fn($e) => $e !== '.' && $e !== '..'));
        if (count($entries) === 1 && is_dir($staging . '/' . $entries[0])) {
            return $staging . '/' . $entries[0];
        }
        return $staging;
    }

    /** Copy $src tree into $dest, skipping protected paths. Returns file count. */
    public static function copyTree(string $src, string $dest, string $base = ''): int
    {
        $count = 0;
        foreach (scandir($src) as $entry) {
            if ($entry === '.' || $entry === '..') { continue; }
            $rel = ltrim($base . '/' . $entry, '/');
            if (self::isProtected($rel)) { continue; }
            $from = $src . '/' . $entry;
            $to = $dest . '/' . $entry;
            if (is_dir($from)) {
                if (!is_dir($to)) { @mkdir($to, 0755, true); }
                $count += self::copyTree($from, $to, $rel);
            } else {
                if (@copy($from, $to)) { $count++; }
            }
        }
        return $count;
    }

    private static function isProtected(string $rel): bool
    {
        $rel = str_replace('\\', '/', $rel);
        // Never overwrite generated/runtime files.
        if (in_array($rel, ['config/config.php', 'config/installed.lock', 'version.json'], true)) {
            return true;
        }
        foreach (self::PROTECTED as $p) {
            if ($rel === $p || strpos($rel, $p . '/') === 0) {
                return true;
            }
        }
        return false;
    }

    private static function clearCache(array $extra = []): void
    {
        $dir = BASE_PATH . '/cache';
        foreach (glob($dir . '/*') ?: [] as $f) {
            if (basename($f) === 'update.lock') { continue; }
            self::rrmdir($f);
        }
        foreach ($extra as $e) { self::rrmdir($e); }
    }

    public static function restoreFiles(string $zipPath): void
    {
        if (!is_file($zipPath)) { return; }
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) { return; }
        // Extract over the live tree (protected paths were not in the backup set).
        $zip->extractTo(BASE_PATH);
        $zip->close();
    }

    public static function restoreDb(string $sqlPath): void
    {
        if (!is_file($sqlPath)) { return; }
        MigrationRunner::class; // ensure autoload
        $sql = (string)file_get_contents($sqlPath);
        $pdo = Database::pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        // Reuse the same splitter as the installer via a lightweight inline parse.
        foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || str_starts_with($stmt, '--')) { continue; }
            try { $pdo->exec($stmt); } catch (\Throwable $e) { /* continue best-effort */ }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private static function pruneBackups(): void
    {
        $keep = max(1, (int)Settings::get('update_keep_backups', 5));
        foreach (['files_*.zip', 'db_*.sql'] as $pattern) {
            $files = glob(BASE_PATH . '/backups/' . $pattern) ?: [];
            usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
            foreach (array_slice($files, $keep) as $old) { @unlink($old); }
        }
    }

    private static function rrmdir(string $path): void
    {
        if (is_dir($path)) {
            foreach (scandir($path) as $e) {
                if ($e !== '.' && $e !== '..') { self::rrmdir($path . '/' . $e); }
            }
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }

    // ---- version.json -----------------------------------------------------

    public static function localVersion(): array
    {
        $f = BASE_PATH . '/version.json';
        if (is_file($f)) {
            $j = json_decode((string)file_get_contents($f), true);
            return is_array($j) ? $j : [];
        }
        return [];
    }

    private static function writeVersion(string $commit, string $version): void
    {
        @file_put_contents(BASE_PATH . '/version.json', json_encode([
            'version' => $version,
            'build'   => date('Ymd'),
            'commit'  => $commit,
        ], JSON_PRETTY_PRINT));
    }
}
