<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Lang;

class HomeController extends Controller
{
    public function index(): string
    {
        $gu = Lang::isGujarati();
        $categories = Database::fetchAll("SELECT * FROM {p}categories WHERE status='active' ORDER BY sort_order, id");
        $vehicles = Database::fetchAll(
            "SELECT v.*, c.name AS category_name,
                    (SELECT AVG(r.rating) FROM {p}reviews r WHERE r.vehicle_id=v.id AND r.status='approved') AS avg_rating,
                    (SELECT COUNT(*) FROM {p}reviews r WHERE r.vehicle_id=v.id AND r.status='approved') AS review_count
             FROM {p}vehicles v
             LEFT JOIN {p}categories c ON c.id=v.category_id
             WHERE v.status='active' ORDER BY v.sort_order, v.id DESC LIMIT 8"
        );

        $packages = setting('packages_enabled', '1') === '1'
            ? Database::fetchAll("SELECT * FROM {p}packages WHERE status='active' ORDER BY sort_order, id LIMIT 3")
            : [];

        return $this->view('front/home', [
            'title'        => setting('site_name', 'Dwarka Rental') . ' — Bike, Car, Taxi & Tempo Rental in Dwarka',
            'categories'   => $categories,
            'vehicles'     => $vehicles,
            'packages'     => $packages,
            'vehicleCount' => count($vehicles),
            'gu'           => $gu,
            'splash'       => true, // 🙏 જય દ્વારકાધીશ welcome screen
        ], 'front');
    }
}
