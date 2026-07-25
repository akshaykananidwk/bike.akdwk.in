<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\ReviewService;

class ReviewController extends Controller
{
    /** Token-gated review form (link sent by WhatsApp after the ride). */
    public function show(array $params): string
    {
        $booking = ReviewService::bookingByToken((string)($params['token'] ?? ''));
        if (!$booking) { http_response_code(404); return $this->view('errors/404', []); }

        $existing = ReviewService::existingFor((int)$booking['id']);
        $submitted = false;

        if (Request::isPost()) {
            $this->verifyCsrf();
            $res = ReviewService::submit($booking, (int)Request::post('rating', 5), (string)Request::post('comment'));
            if (!$res['ok']) {
                Session::flash('error', $res['error']);
            } else {
                $submitted = true;
                $existing = ReviewService::existingFor((int)$booking['id']);
            }
        }

        return $this->view('front/review', [
            'title'     => 'Rate your ride — ' . setting('site_name', 'Dwarka Rental'),
            'meta'      => '<meta name="robots" content="noindex">',
            'booking'   => $booking,
            'existing'  => $existing,
            'submitted' => $submitted,
            'googleUrl' => setting('google_review_url', ''),
        ], 'front');
    }

    /** Public reviews page (social proof + SEO). */
    public function index(): string
    {
        $reviews = Database::fetchAll(
            "SELECT r.*, v.name AS vehicle_name, p.name AS package_name
             FROM {p}reviews r
             LEFT JOIN {p}vehicles v ON v.id=r.vehicle_id
             LEFT JOIN {p}packages p ON p.id=r.package_id
             WHERE r.status='approved' ORDER BY r.id DESC LIMIT 60"
        );
        $rating = ReviewService::siteRating();
        return $this->view('front/reviews', [
            'title'   => 'Customer Reviews — ' . setting('site_name', 'Dwarka Rental'),
            'meta'    => '<meta name="description" content="Real customer reviews for bike, car, taxi and tempo rental in Dwarka.">',
            'reviews' => $reviews,
            'rating'  => $rating,
        ], 'front');
    }
}
