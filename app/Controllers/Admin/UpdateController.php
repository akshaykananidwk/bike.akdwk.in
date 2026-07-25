<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Settings;
use App\Core\Request;
use App\Core\Session;
use App\Core\Crypto;
use App\Core\Auth;
use App\Core\ActivityLog;
use App\Services\GitHubClient;
use App\Services\Updater;

class UpdateController extends Controller
{
    public function index(): string
    {
        if (!Auth::is('super_admin')) { http_response_code(403); return 'Forbidden'; }

        if (Request::isPost()) {
            $this->verifyCsrf();
            Settings::set('github_repo', trim((string)Request::post('github_repo')));
            Settings::set('github_branch', trim((string)Request::post('github_branch')) ?: 'main');
            Settings::set('github_auto_check', Request::post('github_auto_check') ? '1' : '0');
            Settings::set('update_keep_backups', (string)(int)Request::post('update_keep_backups', 5));
            $token = (string)Request::post('github_token');
            if ($token !== '' && strpos($token, '•') === false) {
                Settings::set('github_token', $token, true);
            }
            ActivityLog::record('update.settings');
            Session::flash('success', 'Update settings saved.');
            return $this->redirect('/admin/updates');
        }

        $history = Database::fetchAll("SELECT * FROM {p}update_history ORDER BY id DESC LIMIT 20");
        return $this->view('admin/updates/index', [
            'title' => 'Update Manager', 'active' => 'updates',
            'repo' => Settings::get('github_repo', ''),
            'branch' => Settings::get('github_branch', 'main'),
            'tokenMask' => Crypto::mask((string)Settings::getSecret('github_token', '')),
            'autoCheck' => Settings::get('github_auto_check') === '1',
            'keepBackups' => Settings::get('update_keep_backups', 5),
            'version' => Updater::localVersion(),
            'history' => $history,
        ], 'admin');
    }

    /** Apply any pending DB migrations (useful after a manual file upload). */
    public function migrate(): string
    {
        if (!Auth::is('super_admin')) { http_response_code(403); return 'Forbidden'; }
        $this->verifyCsrf();
        try {
            $applied = \App\Services\MigrationRunner::runPending();
            Session::flash('success', $applied
                ? 'Applied ' . count($applied) . ' migration(s): ' . implode(', ', $applied)
                : 'Database is already up to date — no pending migrations.');
            ActivityLog::record('updates.migrate', 'database', null, [], ['applied' => $applied]);
        } catch (\Throwable $e) {
            Session::flash('error', 'Migration failed: ' . $e->getMessage());
        }
        return $this->redirect('/admin/updates');
    }

    public function check(): string
    {
        $this->verifyCsrf();
        try {
            return $this->json(['ok' => true, 'info' => Updater::checkForUpdate()]);
        } catch (\Throwable $e) {
            return $this->json(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /** Test GitHub connectivity. */
    public function testConnection(): string
    {
        $this->verifyCsrf();
        return $this->json((new GitHubClient())->testConnection());
    }

    /** Run the update, streaming each step to the browser. */
    public function run(): string
    {
        $this->verifyCsrf();
        // Stream plain text lines "status|step".
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Accel-Buffering: no');
        while (ob_get_level() > 0) { ob_end_flush(); }

        $emit = function (string $step, string $status) {
            echo $status . '|' . $step . "\n";
            @flush();
        };

        ActivityLog::record('update.run');
        $result = Updater::updateNow($emit);
        $emit(json_encode($result), $result['ok'] ? 'result_ok' : 'result_fail');
        return '';
    }
}
