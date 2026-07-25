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
            "SELECT v.*, c.name AS category_name FROM {p}vehicles v
             LEFT JOIN {p}categories c ON c.id=v.category_id
             WHERE v.status='active' ORDER BY v.sort_order, v.id DESC LIMIT 8"
        );

        return $this->view('front/home', [
            'title'      => setting('site_name', 'Dwarka Rental') . ' — ' . __('tagline'),
            'categories' => $categories,
            'vehicles'   => $vehicles,
            'gu'         => $gu,
        ], 'front');
    }
}
