<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\BookingService;

/**
 * Tour packages: Dwarka darshan, outstation trips and airport/station
 * transfers. These are fixed-price, driver-included trips (no availability
 * calendar) so the booking flow is a short single-step form.
 */
class PackageController extends Controller
{
    public function index(): string
    {
        $type = Request::query('type', '');
        $where = "status='active'";
        $params = [];
        if (in_array($type, ['darshan', 'outstation', 'transfer', 'custom'], true)) {
            $where .= ' AND type=?';
            $params[] = $type;
        }
        $packages = Database::fetchAll("SELECT * FROM {p}packages WHERE {$where} ORDER BY sort_order, id", $params);

        return $this->view('front/packages', [
            'title'    => 'Dwarka Darshan Packages & Taxi Tours — ' . setting('site_name', 'Dwarka Rental'),
            'meta'     => '<meta name="description" content="Book Dwarka darshan packages, Dwarka to Somnath taxi, Beyt Dwarka &amp; Nageshwar tours and Jamnagar airport transfers at fixed prices. Car with driver, fuel and tolls included.">',
            'packages' => $packages,
            'type'     => $type,
        ], 'front');
    }

    public function show(array $params): string
    {
        $pkg = Database::fetch("SELECT * FROM {p}packages WHERE slug=? AND status='active'", [$params['slug'] ?? '']);
        if (!$pkg) { http_response_code(404); return $this->view('errors/404', []); }

        $reviews = Database::fetchAll(
            "SELECT * FROM {p}reviews WHERE package_id=? AND status='approved' ORDER BY id DESC LIMIT 10",
            [$pkg['id']]
        );
        $rating = Database::fetch(
            "SELECT AVG(rating) avg_rating, COUNT(*) cnt FROM {p}reviews WHERE package_id=? AND status='approved'",
            [$pkg['id']]
        );

        $meta = '<meta name="description" content="'
            . e(($pkg['short_desc'] ?: $pkg['name']) . ' — book online at a fixed price. Car with driver, fuel and tolls included.')
            . '">';
        $ld = [
            '@context' => 'https://schema.org', '@type' => 'Product',
            'name' => $pkg['name'], 'description' => strip_tags((string)$pkg['short_desc']),
            'offers' => ['@type' => 'Offer', 'price' => $pkg['price'], 'priceCurrency' => setting('currency_code', 'INR'), 'availability' => 'https://schema.org/InStock'],
        ];
        if ((int)($rating['cnt'] ?? 0) > 0) {
            $ld['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => round((float)$rating['avg_rating'], 1), 'reviewCount' => (int)$rating['cnt']];
        }
        $meta .= '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE) . '</script>';

        return $this->view('front/package_detail', [
            'title'   => $pkg['name'] . ' — ' . money($pkg['price']) . ' | ' . setting('site_name', 'Dwarka Rental'),
            'meta'    => $meta,
            'pkg'     => $pkg,
            'reviews' => $reviews,
            'rating'  => $rating,
        ], 'front');
    }

    /** Create a package booking (short form: date, pax, name, mobile). */
    public function book(array $params): string
    {
        $pkg = Database::fetch("SELECT * FROM {p}packages WHERE slug=? AND status='active'", [$params['slug'] ?? '']);
        if (!$pkg) { http_response_code(404); return $this->view('errors/404', []); }
        $this->verifyCsrf();

        $date   = (string)Request::post('travel_date');
        $name   = trim((string)Request::post('customer_name'));
        $mobile = preg_replace('/[^0-9]/', '', (string)Request::post('customer_mobile'));
        $pax    = max(1, (int)Request::post('pax', 1));

        $errors = [];
        if (!$date || strtotime($date) === false)          { $errors[] = 'Choose your travel date.'; }
        if ($name === '')                                   { $errors[] = 'Name is required.'; }
        if (!preg_match('/^[6-9]\d{9}$/', (string)$mobile)) { $errors[] = 'Enter a valid 10-digit mobile number.'; }
        if (!Request::post('terms'))                        { $errors[] = 'Please accept the Terms & Conditions.'; }
        if (otp_required() && Session::get('otp_verified_mobile') !== $mobile) {
            $errors[] = 'Please verify your mobile with OTP.';
        }
        if ($errors) {
            Session::flash('errors', array_map(fn($e) => [$e], $errors));
            Session::flash('old', Request::all());
            return $this->redirect('/package/' . $pkg['slug']);
        }

        $start = date('Y-m-d H:i:s', strtotime($date . ' 09:00'));
        $price = (float)$pkg['price'];
        $advance = round($price * (float)$pkg['advance_percent'] / 100, 2);

        // Shop attribution (same QR referral rules as vehicle bookings).
        $shopId = (int)Session::get('ref_shop_id', 0) ?: null;
        if (!$shopId && !empty($_COOKIE['ref_shop'])) {
            $s = Database::fetch("SELECT id FROM {p}shops WHERE code=? AND status='active'", [$_COOKIE['ref_shop']]);
            if ($s) { $shopId = (int)$s['id']; }
        }

        $code = BookingService::nextCode();
        $bookingId = Database::insert('bookings', [
            'code'            => $code,
            'package_id'      => $pkg['id'],
            'booking_type'    => 'package',
            'shop_id'         => $shopId,
            'agency_id'       => $pkg['agency_id'] ?: null,
            'pickup_at'       => $start,
            'drop_at'         => date('Y-m-d H:i:s', strtotime($start . ' +1 day')),
            'duration_hours'  => 24,
            'base_amount'     => $price,
            'deposit'         => 0,
            'total_amount'    => $price,
            'advance_amount'  => $advance,
            'balance_amount'  => round($price - $advance, 2),
            'paid_amount'     => 0,
            'pax'             => $pax,
            'customer_name'   => $name,
            'customer_mobile' => $mobile,
            'customer_address'=> Request::post('pickup_address') ?: null,
            'terms_accepted'  => 1,
            'source'          => $shopId ? 'qr' : 'direct',
            'status'          => 'pending_payment',
            'payment_status'  => 'unpaid',
            'notes'           => 'Package: ' . $pkg['name'],
        ]);

        Session::forget('otp_verified_mobile');
        return $this->redirect('/checkout/' . $code);
    }
}
