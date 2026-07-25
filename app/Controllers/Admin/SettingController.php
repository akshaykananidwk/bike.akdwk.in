<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Settings;
use App\Core\Request;
use App\Core\Session;
use App\Core\Auth;
use App\Core\Uploader;
use App\Core\ActivityLog;

class SettingController extends Controller
{
    /** Plain (non-secret) settings editable on this page. */
    private array $keys = [
        'site_name', 'site_name_gu', 'site_url', 'currency_symbol', 'currency_code',
        'default_language', 'timezone', 'gst_percent', 'advance_percent', 'booking_fee',
        'auto_cancel_minutes', 'attribution_days', 'min_withdrawal', 'otp_enabled',
        'commission_type', 'commission_value', 'commission_on_base_only',
        'primary_color', 'secondary_color', 'contact_mobile', 'contact_whatsapp',
        'contact_email', 'map_link', 'maintenance_mode',
        'razorpay_enabled', 'upi_enabled', 'cash_enabled', 'phonepe_enabled', 'cashfree_enabled',
        'smtp_host', 'smtp_port', 'smtp_user', 'smtp_from',
    ];

    public function index(): string
    {
        if (!Auth::is('super_admin')) { http_response_code(403); return 'Forbidden'; }

        if (Request::isPost()) {
            $this->verifyCsrf();
            foreach ($this->keys as $k) {
                $v = Request::post($k);
                // Checkboxes: absent means 0
                if (in_array($k, ['commission_on_base_only','maintenance_mode','otp_enabled','razorpay_enabled','upi_enabled','cash_enabled','phonepe_enabled','cashfree_enabled'], true)) {
                    $v = Request::post($k) ? '1' : '0';
                }
                Settings::set($k, $v);
            }
            // Logo / favicon uploads
            foreach (['logo', 'favicon'] as $img) {
                $file = Request::file($img);
                if ($file && ($file['error'] ?? 1) === UPLOAD_ERR_OK) {
                    $r = Uploader::handle($file, 'misc', ['jpg','jpeg','png','webp']);
                    if ($r['ok']) { Settings::set($img, $r['path']); }
                }
            }
            // Bump asset version so cached CSS/JS refresh.
            Settings::set('asset_version', (string)time());
            ActivityLog::record('settings.update', 'settings');
            Session::flash('success', 'Settings saved.');
            return $this->redirect('/admin/settings');
        }

        return $this->view('admin/settings/index', [
            'title' => 'Settings', 'active' => 'settings', 's' => Settings::all(),
        ], 'admin');
    }
}
