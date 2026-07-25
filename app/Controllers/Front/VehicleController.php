<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Lang;
use App\Services\Availability;

class VehicleController extends Controller
{
    public function index(): string
    {
        $category = Request::query('category', '');
        $transmission = Request::query('transmission', '');
        $fuel = Request::query('fuel', '');
        $sort = Request::query('sort', '');
        $pickup = Request::query('pickup', '');
        $drop = Request::query('drop', '');

        $where = "v.status='active'";
        $params = [];
        if ($category !== '') {
            $where .= " AND c.slug = ?";
            $params[] = $category;
        }
        if (in_array($transmission, ['gear','non_gear','manual','automatic'], true)) {
            $where .= " AND v.transmission = ?";
            $params[] = $transmission;
        }
        if (in_array($fuel, ['petrol','diesel','ev','cng','none'], true)) {
            $where .= " AND v.fuel = ?";
            $params[] = $fuel;
        }
        $order = match ($sort) {
            'price_low'  => 'v.price_day ASC',
            'price_high' => 'v.price_day DESC',
            default      => 'v.sort_order ASC, v.id DESC',
        };

        $vehicles = Database::fetchAll(
            "SELECT v.*, c.name AS category_name, c.name_gu AS category_name_gu, c.slug AS category_slug
             FROM {p}vehicles v LEFT JOIN {p}categories c ON c.id=v.category_id
             WHERE {$where} ORDER BY {$order}",
            $params
        );

        // If a time window was provided, annotate availability.
        if ($pickup && $drop) {
            foreach ($vehicles as &$v) {
                $v['available_now'] = Availability::availableUnits((int)$v['id'], $pickup, $drop) > 0;
            }
            unset($v);
        }

        $categories = Database::fetchAll("SELECT * FROM {p}categories WHERE status='active' ORDER BY sort_order");

        return $this->view('front/vehicles', [
            'title'      => __('vehicles') . ' — ' . setting('site_name', 'Dwarka Rental'),
            'vehicles'   => $vehicles,
            'categories' => $categories,
            'filters'    => compact('category', 'transmission', 'fuel', 'sort', 'pickup', 'drop'),
            'gu'         => Lang::isGujarati(),
        ], 'front');
    }

    public function show(array $params): string
    {
        $id = (int)($params['id'] ?? 0);
        $vehicle = Database::fetch(
            "SELECT v.*, c.name AS category_name, c.name_gu AS category_name_gu, a.name AS agency_name, a.mobile AS agency_mobile
             FROM {p}vehicles v
             LEFT JOIN {p}categories c ON c.id=v.category_id
             LEFT JOIN {p}agencies a ON a.id=v.agency_id
             WHERE v.id=? AND v.status='active'",
            [$id]
        );
        if (!$vehicle) {
            http_response_code(404);
            return $this->view('errors/404', []);
        }
        $images = Database::fetchAll("SELECT * FROM {p}vehicle_images WHERE vehicle_id=? ORDER BY sort_order", [$id]);

        $meta = '<meta name="description" content="' . e(strip_tags((string)$vehicle['description'])) . '">'
            . '<meta property="og:title" content="' . e($vehicle['name']) . '">'
            . '<script type="application/ld+json">' . json_encode([
                '@context' => 'https://schema.org', '@type' => 'Product',
                'name' => $vehicle['name'], 'description' => strip_tags((string)$vehicle['description']),
                'offers' => ['@type' => 'Offer', 'price' => $vehicle['price_day'], 'priceCurrency' => setting('currency_code', 'INR')],
            ], JSON_UNESCAPED_UNICODE) . '</script>';

        return $this->view('front/vehicle_detail', [
            'title'   => $vehicle['name'] . ' — ' . setting('site_name', 'Dwarka Rental'),
            'meta'    => $meta,
            'vehicle' => $vehicle,
            'images'  => $images,
            'gu'      => Lang::isGujarati(),
        ], 'front');
    }
}
