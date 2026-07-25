<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\Whatsapp;

/**
 * Mobile OTP for checkout. OTP is 6 digits, expires in 5 minutes, rate-limited,
 * and delivered via the WhatsApp queue.
 */
class OtpController extends Controller
{
    private const EXPIRY_MIN = 5;
    private const RESEND_SECONDS = 30;

    public function send(): string
    {
        $this->verifyCsrf();
        $mobile = preg_replace('/[^0-9]/', '', (string)Request::post('mobile'));
        if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            return $this->json(['ok' => false, 'error' => 'Enter a valid 10-digit mobile number.'], 422);
        }

        // Rate limit: one OTP per RESEND_SECONDS.
        $recent = Database::scalar(
            "SELECT created_at FROM {p}otp_verifications WHERE mobile=? ORDER BY id DESC LIMIT 1",
            [$mobile]
        );
        if ($recent && (time() - strtotime($recent)) < self::RESEND_SECONDS) {
            return $this->json(['ok' => false, 'error' => 'Please wait before requesting another OTP.'], 429);
        }

        $otp = (string)random_int(100000, 999999);
        Database::insert('otp_verifications', [
            'mobile'     => $mobile,
            'otp'        => $otp,
            'purpose'    => 'booking',
            'expires_at' => date('Y-m-d H:i:s', time() + self::EXPIRY_MIN * 60),
            'created_at' => now(),
        ]);

        // Deliver via WhatsApp (the {amount} placeholder carries the OTP in the template).
        Whatsapp::notify('otp_verification', $mobile, ['amount' => $otp]);

        $resp = ['ok' => true, 'message' => 'OTP sent to your WhatsApp.'];
        // In debug mode only, expose the OTP to ease local testing.
        if (!empty($GLOBALS['app_config']['app']['debug'])) {
            $resp['debug_otp'] = $otp;
        }
        return $this->json($resp);
    }

    public function verify(): string
    {
        $this->verifyCsrf();
        $mobile = preg_replace('/[^0-9]/', '', (string)Request::post('mobile'));
        $otp = preg_replace('/[^0-9]/', '', (string)Request::post('otp'));

        $row = Database::fetch(
            "SELECT * FROM {p}otp_verifications WHERE mobile=? AND purpose='booking' ORDER BY id DESC LIMIT 1",
            [$mobile]
        );
        if (!$row) {
            return $this->json(['ok' => false, 'error' => 'Please request an OTP first.'], 422);
        }
        if ((int)$row['attempts'] >= 5) {
            return $this->json(['ok' => false, 'error' => 'Too many attempts. Request a new OTP.'], 429);
        }
        if (strtotime($row['expires_at']) < time()) {
            return $this->json(['ok' => false, 'error' => 'OTP expired. Request a new one.'], 422);
        }
        Database::update('otp_verifications', ['attempts' => (int)$row['attempts'] + 1], ['id' => $row['id']]);

        if (!hash_equals($row['otp'], $otp)) {
            return $this->json(['ok' => false, 'error' => 'Incorrect OTP.'], 422);
        }

        Database::update('otp_verifications', ['verified' => 1], ['id' => $row['id']]);
        Session::set('otp_verified_mobile', $mobile);
        Session::set('otp_verified_at', time());
        return $this->json(['ok' => true, 'message' => 'Mobile verified.']);
    }
}
