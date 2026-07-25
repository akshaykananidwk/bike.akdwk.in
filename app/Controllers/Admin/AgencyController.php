<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Uploader;
use App\Core\ActivityLog;

class AgencyController extends Controller
{
    public function index(): string
    {
        $agencies = Database::fetchAll(
            "SELECT a.*, (SELECT COUNT(*) FROM {p}vehicles v WHERE v.agency_id=a.id) AS vehicles,
                    (SELECT balance FROM {p}wallets w WHERE w.owner_type='agency' AND w.owner_id=a.id) AS wallet
             FROM {p}agencies a ORDER BY a.id DESC"
        );
        return $this->view('admin/agencies/index', ['title' => 'Agencies', 'active' => 'agencies', 'agencies' => $agencies], 'admin');
    }

    public function create(): string
    {
        if (Request::isPost()) { $this->verifyCsrf(); return $this->save(null); }
        return $this->view('admin/agencies/form', ['title' => 'Add Agency', 'active' => 'agencies', 'agency' => null, 'code' => $this->nextCode()], 'admin');
    }

    public function edit(array $p): string
    {
        $agency = Database::fetch("SELECT * FROM {p}agencies WHERE id=?", [$p['id']]);
        if (!$agency) { Session::flash('error', 'Agency not found.'); return $this->redirect('/admin/agencies'); }
        if (Request::isPost()) { $this->verifyCsrf(); return $this->save($agency); }
        return $this->view('admin/agencies/form', ['title' => 'Edit Agency', 'active' => 'agencies', 'agency' => $agency, 'code' => $agency['code']], 'admin');
    }

    private function save(?array $agency): string
    {
        $data = $this->validate([
            'name' => 'required|max:190',
            'mobile' => 'nullable|mobile',
            'commission_type' => 'required|in:percent,fixed,inherit',
        ]);
        $fields = [
            'name'             => $data['name'],
            'name_gu'          => Request::post('name_gu') ?: null,
            'owner_name'       => Request::post('owner_name') ?: null,
            'mobile'           => Request::post('mobile') ?: null,
            'whatsapp'         => Request::post('whatsapp') ?: Request::post('mobile') ?: null,
            'email'            => Request::post('email') ?: null,
            'address'          => Request::post('address') ?: null,
            'upi_id'           => Request::post('upi_id') ?: null,
            'commission_type'  => $data['commission_type'],
            'commission_value' => (float)Request::post('commission_value', 0),
            'settlement_rate'  => (float)Request::post('settlement_rate', 0),
            'bank_holder'      => Request::post('bank_holder') ?: null,
            'bank_account'     => Request::post('bank_account') ?: null,
            'bank_ifsc'        => strtoupper((string)Request::post('bank_ifsc')) ?: null,
            'bank_name'        => Request::post('bank_name') ?: null,
            'status'           => Request::post('status', 'active'),
        ];
        $qr = Request::file('upi_qr_image');
        if ($qr && ($qr['error'] ?? 1) === UPLOAD_ERR_OK) {
            $r = Uploader::handle($qr, 'agencies', ['jpg','jpeg','png','webp']);
            if ($r['ok']) { $fields['upi_qr_image'] = $r['path']; }
            else { Session::flash('error', $r['error']); return $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/agencies'); }
        }

        if ($agency) {
            Database::update('agencies', $fields, ['id' => $agency['id']]);
            ActivityLog::record('agency.update', 'agency', $agency['id'], $agency, $fields);
            Session::flash('success', 'Agency updated.');
        } else {
            $fields['code'] = Request::post('code') ?: $this->nextCode();
            $id = Database::insert('agencies', $fields);
            Database::insert('wallets', ['owner_type' => 'agency', 'owner_id' => $id, 'balance' => 0]);
            ActivityLog::record('agency.create', 'agency', $id, [], $fields);
            Session::flash('success', 'Agency created with code ' . $fields['code'] . '.');
        }
        return $this->redirect('/admin/agencies');
    }

    public function delete(array $p): string
    {
        $this->verifyCsrf();
        $agency = Database::fetch("SELECT * FROM {p}agencies WHERE id=?", [$p['id']]);
        if ($agency) {
            Database::delete('agencies', ['id' => $agency['id']]);
            ActivityLog::record('agency.delete', 'agency', $agency['id'], $agency, []);
            Session::flash('success', 'Agency deleted.');
        }
        return $this->redirect('/admin/agencies');
    }

    private function nextCode(): string
    {
        $last = Database::scalar("SELECT code FROM {p}agencies WHERE code LIKE 'AGN-DWK-%' ORDER BY id DESC LIMIT 1");
        $n = 0;
        if ($last && preg_match('/(\d+)$/', $last, $m)) { $n = (int)$m[1]; }
        return sprintf('AGN-DWK-%03d', $n + 1);
    }
}
