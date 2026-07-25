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
use App\Services\Whatsapp;

class WhatsappController extends Controller
{
    private array $secretKeys = ['wa_api_url', 'wa_api_key', 'wa_session_id', 'wa_sender'];

    public function settings(): string
    {
        if (!Auth::is('super_admin')) { http_response_code(403); return 'Forbidden'; }

        if (Request::isPost()) {
            $this->verifyCsrf();
            foreach ($this->secretKeys as $k) {
                $v = Request::post($k);
                // Keep existing value if the masked field was left unchanged/blank.
                if ($v === '' || $v === null) { continue; }
                if (strpos($v, '•') !== false) { continue; } // masked, untouched
                Settings::set($k, $v, true); // encrypted
            }
            Settings::set('wa_enabled', Request::post('wa_enabled') ? '1' : '0');
            Settings::set('wa_daily_limit', (string)(int)Request::post('wa_daily_limit', 500));
            Settings::set('wa_min_delay', (string)(int)Request::post('wa_min_delay', 3));
            Settings::set('wa_max_delay', (string)(int)Request::post('wa_max_delay', 8));
            Settings::set('wa_inbound_secret', Request::post('wa_inbound_secret') ?: Settings::get('wa_inbound_secret', ''));
            ActivityLog::record('whatsapp.settings');
            Session::flash('success', 'WhatsApp settings saved.');
            return $this->redirect('/admin/whatsapp');
        }

        // Provide masked values for display.
        $masked = [];
        foreach ($this->secretKeys as $k) {
            $masked[$k] = Crypto::mask((string)Settings::getSecret($k, ''));
        }
        return $this->view('admin/whatsapp/settings', [
            'title' => 'WhatsApp Settings', 'active' => 'whatsapp', 'masked' => $masked,
            'enabled' => Settings::get('wa_enabled') === '1',
            's' => Settings::all(),
        ], 'admin');
    }

    public function templates(): string
    {
        if (Request::isPost()) {
            $this->verifyCsrf();
            $id = (int)Request::post('id');
            Database::update('whatsapp_templates', [
                'body' => (string)Request::post('body'),
                'is_active' => Request::post('is_active') ? 1 : 0,
            ], ['id' => $id]);
            ActivityLog::record('whatsapp.template', 'template', $id);
            Session::flash('success', 'Template updated.');
            return $this->redirect('/admin/whatsapp/templates');
        }
        $templates = Database::fetchAll("SELECT * FROM {p}whatsapp_templates ORDER BY `key`, locale");
        return $this->view('admin/whatsapp/templates', [
            'title' => 'WhatsApp Templates', 'active' => 'whatsapp', 'templates' => $templates,
        ], 'admin');
    }

    public function queue(): string
    {
        $queue = Database::fetchAll("SELECT * FROM {p}whatsapp_queue ORDER BY id DESC LIMIT 200");
        $stats = [
            'pending' => (int)Database::scalar("SELECT COUNT(*) FROM {p}whatsapp_queue WHERE status='pending'"),
            'sent'    => (int)Database::scalar("SELECT COUNT(*) FROM {p}whatsapp_queue WHERE status='sent'"),
            'failed'  => (int)Database::scalar("SELECT COUNT(*) FROM {p}whatsapp_queue WHERE status='failed'"),
        ];
        return $this->view('admin/whatsapp/queue', [
            'title' => 'WhatsApp Queue', 'active' => 'whatsapp', 'queue' => $queue, 'stats' => $stats,
        ], 'admin');
    }

    public function inbox(): string
    {
        $messages = Database::fetchAll("SELECT * FROM {p}whatsapp_logs WHERE direction='in' ORDER BY id DESC LIMIT 200");
        return $this->view('admin/whatsapp/inbox', [
            'title' => 'WhatsApp Inbox', 'active' => 'whatsapp', 'messages' => $messages,
        ], 'admin');
    }

    /** Send a test message immediately and show the raw response. */
    public function test(): string
    {
        $this->verifyCsrf();
        $to = preg_replace('/[^0-9]/', '', (string)Request::post('to'));
        $msg = (string)Request::post('message') ?: 'Test message from Dwarka Rental ✔';
        if ($to === '') {
            return $this->json(['ok' => false, 'error' => 'Enter a number.'], 422);
        }
        $result = Whatsapp::sendNow($to, $msg);
        Whatsapp::log('out', $to, $msg, $result['ok'] ? 'test_sent' : 'test_failed', $result['response']);
        return $this->json(['ok' => $result['ok'], 'http' => $result['http'], 'response' => substr((string)$result['response'], 0, 500)]);
    }
}
