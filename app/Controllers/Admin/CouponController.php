<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\ActivityLog;

class CouponController extends Controller
{
    public function index(): string
    {
        if (Request::isPost()) {
            $this->verifyCsrf();
            $action = Request::post('action');
            if ($action === 'delete') {
                Database::delete('coupons', ['id' => (int)Request::post('id')]);
                Session::flash('success', 'Coupon deleted.');
                return $this->redirect('/admin/coupons');
            }
            $fields = [
                'code'         => strtoupper(trim((string)Request::post('code'))),
                'type'         => Request::post('type') === 'fixed' ? 'fixed' : 'percent',
                'value'        => (float)Request::post('value', 0),
                'min_amount'   => (float)Request::post('min_amount', 0),
                'max_discount' => in_array(Request::post('max_discount'), ['', null], true) ? null : (float)Request::post('max_discount'),
                'usage_limit'  => in_array(Request::post('usage_limit'), ['', null], true) ? null : (int)Request::post('usage_limit'),
                'starts_at'    => Request::post('starts_at') ?: null,
                'ends_at'      => Request::post('ends_at') ?: null,
                'status'       => Request::post('status', 'active'),
            ];
            if ($fields['code'] === '') { Session::flash('error', 'Code required.'); return $this->redirect('/admin/coupons'); }
            $id = (int)Request::post('id');
            if ($id) {
                Database::update('coupons', $fields, ['id' => $id]);
                Session::flash('success', 'Coupon updated.');
            } else {
                if (Database::scalar("SELECT COUNT(*) FROM {p}coupons WHERE code=?", [$fields['code']])) {
                    Session::flash('error', 'Coupon code already exists.');
                    return $this->redirect('/admin/coupons');
                }
                $newId = Database::insert('coupons', $fields);
                ActivityLog::record('coupon.create', 'coupon', $newId);
                Session::flash('success', 'Coupon created.');
            }
            return $this->redirect('/admin/coupons');
        }
        $coupons = Database::fetchAll("SELECT * FROM {p}coupons ORDER BY id DESC");
        return $this->view('admin/coupons/index', ['title' => 'Coupons', 'active' => 'coupons', 'coupons' => $coupons], 'admin');
    }
}
