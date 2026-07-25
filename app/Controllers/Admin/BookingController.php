<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\ActivityLog;
use App\Services\CommissionEngine;
use App\Services\BookingService;
use App\Services\Availability;
use App\Services\Pricing;
use App\Services\Whatsapp;

class BookingController extends Controller
{
    public function index(): string
    {
        $f = [
            'status'  => Request::query('status', ''),
            'payment' => Request::query('payment', ''),
            'shop'    => Request::query('shop', ''),
            'agency'  => Request::query('agency', ''),
            'from'    => Request::query('from', ''),
            'to'      => Request::query('to', ''),
            'q'       => Request::query('q', ''),
        ];
        $where = '1'; $params = [];
        if ($f['status'])  { $where .= ' AND b.status=?'; $params[] = $f['status']; }
        if ($f['payment']) { $where .= ' AND b.payment_status=?'; $params[] = $f['payment']; }
        if ($f['shop'])    { $where .= ' AND b.shop_id=?'; $params[] = (int)$f['shop']; }
        if ($f['agency'])  { $where .= ' AND b.agency_id=?'; $params[] = (int)$f['agency']; }
        if ($f['from'])    { $where .= ' AND b.created_at>=?'; $params[] = $f['from'] . ' 00:00:00'; }
        if ($f['to'])      { $where .= ' AND b.created_at<=?'; $params[] = $f['to'] . ' 23:59:59'; }
        if ($f['q'])       { $where .= ' AND (b.code LIKE ? OR b.customer_name LIKE ? OR b.customer_mobile LIKE ?)'; array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%"); }

        $bookings = Database::fetchAll(
            "SELECT b.*, v.name AS vehicle_name, s.name AS shop_name, a.name AS agency_name
             FROM {p}bookings b
             LEFT JOIN {p}vehicles v ON v.id=b.vehicle_id
             LEFT JOIN {p}shops s ON s.id=b.shop_id
             LEFT JOIN {p}agencies a ON a.id=b.agency_id
             WHERE {$where} ORDER BY b.id DESC LIMIT 500",
            $params
        );
        return $this->view('admin/bookings/index', [
            'title' => 'Bookings', 'active' => 'bookings', 'bookings' => $bookings, 'f' => $f,
            'shops' => Database::fetchAll("SELECT id,name FROM {p}shops ORDER BY name"),
            'agencies' => Database::fetchAll("SELECT id,name FROM {p}agencies ORDER BY name"),
        ], 'admin');
    }

    public function show(array $p): string
    {
        $b = Database::fetch(
            "SELECT b.*, v.name AS vehicle_name, s.name AS shop_name, a.name AS agency_name, a.mobile AS agency_mobile
             FROM {p}bookings b
             LEFT JOIN {p}vehicles v ON v.id=b.vehicle_id
             LEFT JOIN {p}shops s ON s.id=b.shop_id
             LEFT JOIN {p}agencies a ON a.id=b.agency_id
             WHERE b.id=?", [$p['id']]
        );
        if (!$b) { Session::flash('error', 'Booking not found.'); return $this->redirect('/admin/bookings'); }
        $payments = Database::fetchAll("SELECT * FROM {p}payments WHERE booking_id=? ORDER BY id DESC", [$b['id']]);
        $ledger   = Database::fetchAll("SELECT * FROM {p}commission_ledger WHERE booking_id=? ORDER BY id", [$b['id']]);
        return $this->view('admin/bookings/show', [
            'title' => 'Booking ' . $b['code'], 'active' => 'bookings', 'b' => $b, 'payments' => $payments, 'ledger' => $ledger,
        ], 'admin');
    }

    /** Change status: confirm, picked_up, returned, completed, cancel (with refund + reversal), no_show. */
    public function updateStatus(array $p): string
    {
        $this->verifyCsrf();
        $b = Database::fetch("SELECT * FROM {p}bookings WHERE id=?", [$p['id']]);
        if (!$b) { Session::flash('error', 'Booking not found.'); return $this->redirect('/admin/bookings'); }

        $status = Request::post('status');
        $allowed = ['confirmed','picked_up','returned','completed','cancelled','no_show'];
        if (!in_array($status, $allowed, true)) {
            Session::flash('error', 'Invalid status.');
            return $this->redirect('/admin/bookings/' . $b['id']);
        }

        if ($status === 'cancelled') {
            $refund = (float)Request::post('refund_amount', 0);
            // reverse() runs its own transaction; keep the refund/update steps outside it.
            CommissionEngine::reverse((int)$b['id']);
            $update = ['status' => 'cancelled'];
            if ($refund > 0) {
                $update['refund_amount'] = round((float)$b['refund_amount'] + $refund, 2);
                $update['payment_status'] = $refund >= (float)$b['paid_amount'] ? 'refunded' : 'partially_refunded';
                Database::insert('payments', [
                    'booking_id' => $b['id'], 'gateway' => 'cash', 'amount' => -$refund,
                    'status' => 'refunded', 'response_json' => json_encode(['refund' => true]),
                ]);
            }
            Database::update('bookings', $update, ['id' => $b['id']]);
            Whatsapp::notify('booking_cancelled', $b['customer_mobile'], ['booking_code' => $b['code'], 'amount' => money(Request::post('refund_amount', 0))]);
            if ((float)Request::post('refund_amount', 0) > 0) {
                Whatsapp::notify('refund_processed', $b['customer_mobile'], ['booking_code' => $b['code'], 'amount' => money(Request::post('refund_amount', 0))]);
            }
            Session::flash('success', 'Booking cancelled' . ($refund > 0 ? ' with refund ' . money($refund) : '') . '. Commission reversed.');
        } else {
            // If confirming a pending booking, run confirmation (commission + notifications).
            if ($status === 'confirmed' && $b['status'] === 'pending_payment') {
                BookingService::confirm((int)$b['id']);
            } else {
                $update = ['status' => $status];
                if ($status === 'picked_up' && !$b['picked_up_at']) { $update['picked_up_at'] = now(); }
                if ($status === 'returned' && !$b['returned_at']) { $update['returned_at'] = now(); }
                Database::update('bookings', $update, ['id' => $b['id']]);
            }
            Session::flash('success', 'Status updated to ' . str_replace('_', ' ', $status) . '.');
        }
        ActivityLog::record('booking.status', 'booking', $b['id'], ['status' => $b['status']], ['status' => $status]);
        return $this->redirect('/admin/bookings/' . $b['id']);
    }

    /** Walk-in booking creation by admin. */
    public function create(): string
    {
        if (Request::isPost()) {
            $this->verifyCsrf();
            $vehicleId = (int)Request::post('vehicle_id');
            $vehicle = Database::fetch("SELECT * FROM {p}vehicles WHERE id=?", [$vehicleId]);
            $pickup = (string)Request::post('pickup');
            $drop = (string)Request::post('drop');
            if (!$vehicle || strtotime($drop) <= strtotime($pickup)) {
                Session::flash('error', 'Select a vehicle and a valid time window.');
                return $this->redirect('/admin/bookings/create');
            }
            if (!Availability::isAvailable($vehicleId, $pickup, $drop)) {
                Session::flash('error', 'Vehicle not available for this window.');
                return $this->redirect('/admin/bookings/create');
            }
            $quote = Pricing::quote($vehicle, $pickup, $drop);
            $paid = (float)Request::post('paid_amount', 0);
            $code = BookingService::nextCode();
            $id = Database::insert('bookings', [
                'code' => $code, 'shop_id' => Request::post('shop_id') ?: null,
                'agency_id' => $vehicle['agency_id'] ?: null, 'vehicle_id' => $vehicleId,
                'pickup_at' => date('Y-m-d H:i:s', strtotime($pickup)), 'drop_at' => date('Y-m-d H:i:s', strtotime($drop)),
                'duration_hours' => $quote['hours'], 'base_amount' => $quote['base'], 'deposit' => $quote['deposit'],
                'tax_amount' => $quote['gst'], 'total_amount' => $quote['total'], 'advance_amount' => $quote['advance'],
                'balance_amount' => max(0, $quote['total'] - $paid), 'paid_amount' => $paid,
                'customer_name' => Request::post('customer_name'), 'customer_mobile' => preg_replace('/[^0-9]/', '', (string)Request::post('customer_mobile')),
                'riders' => 1, 'terms_accepted' => 1, 'source' => 'walkin',
                'status' => 'pending_payment', 'payment_status' => $paid > 0 ? 'advance_paid' : 'unpaid',
            ]);
            if ($paid >= $quote['advance'] - 0.01) {
                BookingService::confirm($id);
            }
            ActivityLog::record('booking.create_walkin', 'booking', $id);
            Session::flash('success', 'Walk-in booking created: ' . $code);
            return $this->redirect('/admin/bookings/' . $id);
        }

        return $this->view('admin/bookings/create', [
            'title' => 'Walk-in Booking', 'active' => 'bookings',
            'vehicles' => Database::fetchAll("SELECT id,name,price_day,price_hour,deposit,agency_id FROM {p}vehicles WHERE status='active' ORDER BY name"),
            'shops' => Database::fetchAll("SELECT id,name FROM {p}shops WHERE status='active' ORDER BY name"),
        ], 'admin');
    }
}
