<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Lang;
use App\Core\Uploader;
use App\Services\Availability;
use App\Services\Pricing;
use App\Services\BookingService;

class BookingController extends Controller
{
    private function loadVehicle(int $id): ?array
    {
        return Database::fetch(
            "SELECT v.*, a.name AS agency_name, a.mobile AS agency_mobile, c.name AS category_name
             FROM {p}vehicles v LEFT JOIN {p}agencies a ON a.id=v.agency_id
             LEFT JOIN {p}categories c ON c.id=v.category_id
             WHERE v.id=? AND v.status='active'",
            [$id]
        );
    }

    /** GET: render the wizard. POST: create the booking. */
    public function start(array $params): string
    {
        $vehicle = $this->loadVehicle((int)($params['id'] ?? 0));
        if (!$vehicle) { http_response_code(404); return $this->view('errors/404', []); }

        if (Request::isPost()) {
            $this->verifyCsrf();
            return $this->createBooking($vehicle);
        }

        return $this->view('front/booking', [
            'title'   => 'Book ' . $vehicle['name'],
            'vehicle' => $vehicle,
            'gu'      => Lang::isGujarati(),
        ], 'front');
    }

    /** AJAX: check availability + return a price quote. */
    public function availability(array $params): string
    {
        $vehicle = $this->loadVehicle((int)($params['id'] ?? 0));
        if (!$vehicle) { return $this->json(['ok' => false, 'error' => 'Vehicle not found.'], 404); }

        $pickup = (string)Request::post('pickup');
        $drop = (string)Request::post('drop');
        if (!$this->validWindow($pickup, $drop)) {
            return $this->json(['ok' => false, 'error' => 'Please choose a valid pickup and drop time (drop after pickup, in the future).'], 422);
        }

        $units = Availability::availableUnits((int)$vehicle['id'], $pickup, $drop);
        $quote = Pricing::quote($vehicle, $pickup, $drop);
        return $this->json([
            'ok'        => true,
            'available' => $units > 0,
            'units'     => $units,
            'quote'     => $quote,
            'quote_html'=> $this->quoteHtml($quote),
        ]);
    }

    /** AJAX: price quote only. */
    public function quote(array $params): string
    {
        $vehicle = $this->loadVehicle((int)($params['id'] ?? 0));
        if (!$vehicle) { return $this->json(['ok' => false], 404); }
        $pickup = (string)Request::post('pickup');
        $drop = (string)Request::post('drop');
        if (!$this->validWindow($pickup, $drop)) { return $this->json(['ok' => false, 'error' => 'Invalid window.'], 422); }
        return $this->json(['ok' => true, 'quote' => Pricing::quote($vehicle, $pickup, $drop)]);
    }

    private function createBooking(array $vehicle): string
    {
        $pickup = (string)Request::post('pickup');
        $drop   = (string)Request::post('drop');
        $name   = trim((string)Request::post('customer_name'));
        $mobile = preg_replace('/[^0-9]/', '', (string)Request::post('customer_mobile'));

        $errors = [];
        if (!$this->validWindow($pickup, $drop)) { $errors[] = 'Choose a valid pickup and drop time.'; }
        if ($name === '') { $errors[] = 'Name is required.'; }
        if (!preg_match('/^[6-9]\d{9}$/', $mobile)) { $errors[] = 'Valid mobile is required.'; }
        if (!Request::post('terms')) { $errors[] = 'Please accept the Terms & Conditions.'; }

        // OTP must be verified for this mobile.
        if (Session::get('otp_verified_mobile') !== $mobile) {
            $errors[] = 'Please verify your mobile with OTP.';
        }

        // Availability (double-booking guard).
        if ($this->validWindow($pickup, $drop) && !Availability::isAvailable((int)$vehicle['id'], $pickup, $drop)) {
            $errors[] = 'Sorry, this vehicle is no longer available for the selected time.';
        }

        // DL image mandatory.
        $dl = Request::file('dl_image');
        if (!$dl || ($dl['error'] ?? 1) !== UPLOAD_ERR_OK) {
            $errors[] = 'Driving Licence photo is required.';
        }

        if ($errors) {
            Session::flash('errors', array_map(fn($e) => [$e], $errors));
            Session::flash('old', Request::all());
            return $this->redirect('/book/' . $vehicle['id']);
        }

        // Upload KYC.
        $dlRes = Uploader::handle($dl, 'kyc', ['jpg','jpeg','png','webp','pdf']);
        if (!$dlRes['ok']) {
            Session::flash('errors', [[$dlRes['error']]]);
            return $this->redirect('/book/' . $vehicle['id']);
        }
        $idPath = null;
        $idFile = Request::file('id_image');
        if ($idFile && ($idFile['error'] ?? 1) === UPLOAD_ERR_OK) {
            $idRes = Uploader::handle($idFile, 'kyc', ['jpg','jpeg','png','webp','pdf']);
            if ($idRes['ok']) { $idPath = $idRes['path']; }
        }

        // Pricing.
        $quote = Pricing::quote($vehicle, $pickup, $drop);

        // Shop attribution: session first, then cookie, else direct.
        [$shopId, $source] = $this->resolveShop();

        // Customer (find or create).
        $customerId = $this->upsertCustomer($mobile, $name, Request::post('customer_alt_mobile'), Request::post('customer_address'));

        $code = BookingService::nextCode();
        $bookingId = Database::insert('bookings', [
            'code'                => $code,
            'customer_id'         => $customerId,
            'shop_id'             => $shopId,
            'agency_id'           => $vehicle['agency_id'] ?: null,
            'vehicle_id'          => $vehicle['id'],
            'pickup_at'           => date('Y-m-d H:i:s', strtotime($pickup)),
            'drop_at'             => date('Y-m-d H:i:s', strtotime($drop)),
            'duration_hours'      => $quote['hours'],
            'base_amount'         => $quote['base'],
            'deposit'             => $quote['deposit'],
            'tax_amount'          => $quote['gst'],
            'discount_amount'     => $quote['discount'],
            'extra_charges'       => 0,
            'total_amount'        => $quote['total'],
            'advance_amount'      => $quote['advance'],
            'balance_amount'      => $quote['balance'],
            'paid_amount'         => 0,
            'riders'              => max(1, (int)Request::post('riders', 1)),
            'customer_name'       => $name,
            'customer_mobile'     => $mobile,
            'customer_alt_mobile' => Request::post('customer_alt_mobile') ?: null,
            'customer_address'    => Request::post('customer_address') ?: null,
            'dl_image'            => $dlRes['path'],
            'id_image'            => $idPath,
            'terms_accepted'      => 1,
            'source'              => $source,
            'status'              => 'pending_payment',
            'payment_status'      => 'unpaid',
        ]);

        Session::set('booking_' . $code, $bookingId);
        Session::forget('otp_verified_mobile');

        return $this->redirect('/checkout/' . $code);
    }

    /** Payment page (methods rendered here; gateway logic in PaymentController — Phase 4). */
    public function checkout(array $params): string
    {
        $booking = $this->loadBooking($params['code'] ?? '');
        if (!$booking) { http_response_code(404); return $this->view('errors/404', []); }
        if ($booking['status'] !== 'pending_payment') {
            return $this->redirect('/booking/' . $booking['code'] . '/success');
        }
        $vehicle = Database::fetch("SELECT * FROM {p}vehicles WHERE id=?", [$booking['vehicle_id']]);
        $agency  = $booking['agency_id'] ? Database::fetch("SELECT * FROM {p}agencies WHERE id=?", [$booking['agency_id']]) : null;

        return $this->view('front/checkout', [
            'title'   => 'Payment — ' . $booking['code'],
            'booking' => $booking,
            'vehicle' => $vehicle,
            'agency'  => $agency,
            'gu'      => Lang::isGujarati(),
        ], 'front');
    }

    public function success(array $params): string
    {
        $booking = $this->loadBooking($params['code'] ?? '');
        if (!$booking) { http_response_code(404); return $this->view('errors/404', []); }
        $vehicle = Database::fetch("SELECT * FROM {p}vehicles WHERE id=?", [$booking['vehicle_id']]);
        $agency  = $booking['agency_id'] ? Database::fetch("SELECT * FROM {p}agencies WHERE id=?", [$booking['agency_id']]) : null;
        $shop    = $booking['shop_id'] ? Database::fetch("SELECT * FROM {p}shops WHERE id=?", [$booking['shop_id']]) : null;

        return $this->view('front/success', [
            'title'   => 'Booking ' . $booking['code'],
            'booking' => $booking,
            'vehicle' => $vehicle,
            'agency'  => $agency,
            'shop'    => $shop,
            'gu'      => Lang::isGujarati(),
        ], 'front');
    }

    /** Invoice PDF — implemented in Phase 4 (InvoiceService). */
    public function invoice(array $params): string
    {
        $booking = $this->loadBooking($params['code'] ?? '');
        if (!$booking) { http_response_code(404); return 'Not found'; }
        if (class_exists(\App\Services\InvoiceService::class)) {
            $pdf = \App\Services\InvoiceService::generate((int)$booking['id']);
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="invoice-' . $booking['code'] . '.pdf"');
            echo $pdf;
            return '';
        }
        return $this->redirect('/booking/' . $booking['code'] . '/success');
    }

    /** "My Bookings" — mobile + OTP lookup. */
    public function myBookings(): string
    {
        $bookings = [];
        $mobile = null;
        if (Session::get('otp_verified_mobile')) {
            $mobile = Session::get('otp_verified_mobile');
            $bookings = Database::fetchAll(
                "SELECT b.*, v.name AS vehicle_name FROM {p}bookings b
                 LEFT JOIN {p}vehicles v ON v.id=b.vehicle_id
                 WHERE b.customer_mobile=? ORDER BY b.id DESC",
                [$mobile]
            );
        }
        return $this->view('front/my_bookings', [
            'title'    => 'My Bookings',
            'bookings' => $bookings,
            'mobile'   => $mobile,
            'gu'       => Lang::isGujarati(),
        ], 'front');
    }

    // -- helpers ------------------------------------------------------------

    private function loadBooking(string $code): ?array
    {
        return Database::fetch("SELECT * FROM {p}bookings WHERE code=?", [$code]);
    }

    private function validWindow(string $pickup, string $drop): bool
    {
        $p = strtotime($pickup);
        $d = strtotime($drop);
        return $p && $d && $d > $p && $p > (time() - 300);
    }

    private function resolveShop(): array
    {
        $shopId = (int)Session::get('ref_shop_id', 0);
        if ($shopId) {
            return [$shopId, 'qr'];
        }
        $cookie = $_COOKIE['ref_shop'] ?? '';
        if ($cookie !== '') {
            $shop = Database::fetch("SELECT id FROM {p}shops WHERE code=? AND status='active'", [$cookie]);
            if ($shop) { return [(int)$shop['id'], 'qr']; }
        }
        return [null, 'direct'];
    }

    private function upsertCustomer(string $mobile, string $name, $alt, $address): int
    {
        $existing = Database::fetch("SELECT id FROM {p}customers WHERE mobile=?", [$mobile]);
        if ($existing) {
            Database::update('customers', [
                'name' => $name, 'alt_mobile' => $alt ?: null, 'address' => $address ?: null,
            ], ['id' => $existing['id']]);
            return (int)$existing['id'];
        }
        return Database::insert('customers', [
            'mobile' => $mobile, 'name' => $name, 'alt_mobile' => $alt ?: null, 'address' => $address ?: null,
        ]);
    }

    private function quoteHtml(array $q): string
    {
        $rows = [
            ['Base rental (' . $q['days'] . ' day' . ($q['days'] > 1 ? 's' : '') . ')', money($q['base'])],
        ];
        if ($q['gst'] > 0) { $rows[] = ['GST (' . $q['gst_percent'] . '%)', money($q['gst'])]; }
        $rows[] = ['Security deposit (refundable)', money($q['deposit'])];
        $html = '<table class="table table-sm mb-0">';
        foreach ($rows as [$l, $v]) { $html .= "<tr><td>{$l}</td><td class='text-end'>{$v}</td></tr>"; }
        $html .= "<tr class='fw-bold border-top'><td>Total</td><td class='text-end'>" . money($q['total']) . "</td></tr>";
        $html .= "<tr class='text-primary'><td>Pay now (advance " . $q['advance_percent'] . "%)</td><td class='text-end'>" . money($q['advance']) . "</td></tr>";
        $html .= "<tr><td>Balance at pickup</td><td class='text-end'>" . money($q['balance']) . "</td></tr>";
        $html .= '</table>';
        return $html;
    }
}
