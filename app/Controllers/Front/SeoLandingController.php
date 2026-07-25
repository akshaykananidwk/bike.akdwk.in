<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Database;

/**
 * Programmatic local-SEO landing pages: service × location.
 * e.g. /rent/bike-rental-in-dwarka, /rent/taxi-service-in-okha …
 * Each page is a real, indexable URL with unique title/H1/content + matching
 * vehicles, and is listed in sitemap.xml.
 */
class SeoLandingController extends Controller
{
    /** service slug => [Label, category slug, search phrase] */
    public const SERVICES = [
        'bike-rental'       => ['Bike Rental', 'bike', 'bike on rent'],
        'scooty-rental'     => ['Scooty Rental', 'scooty', 'scooty on rent'],
        'activa-rental'     => ['Activa Rental', 'activa', 'Activa on rent'],
        'car-rental'        => ['Car Rental', 'car', 'car on rent'],
        'self-drive-car'    => ['Self-Drive Car', 'car', 'self drive car'],
        'taxi-service'      => ['Taxi Service', 'taxi', 'taxi & cab'],
        'cab-booking'       => ['Cab Booking', 'taxi', 'cab booking'],
        'car-with-driver'   => ['Car with Driver', 'car-with-driver', 'car with driver'],
        'tempo-traveller'   => ['Tempo Traveller', 'tempo-traveller', 'tempo traveller'],
        'cycle-rental'      => ['Cycle Rental', 'cycle', 'cycle on rent'],
    ];

    /** location slug => Display name */
    public const LOCATIONS = [
        'dwarka'      => 'Dwarka',
        'okha'        => 'Okha',
        'mithapur'    => 'Mithapur',
        'beyt-dwarka' => 'Beyt Dwarka',
        'nageshwar'   => 'Nageshwar',
        'gomti-ghat'  => 'Gomti Ghat',
    ];

    /** All landing-page slugs (used by the sitemap). */
    public static function slugs(): array
    {
        $out = [];
        foreach (self::SERVICES as $s => $_) {
            foreach (self::LOCATIONS as $l => $__) {
                $out[] = "{$s}-in-{$l}";
            }
        }
        return $out;
    }

    public function show(array $params): string
    {
        $slug = strtolower(trim($params['slug'] ?? ''));
        // Parse "{service}-in-{location}".
        if (!preg_match('/^(.*)-in-(' . implode('|', array_keys(self::LOCATIONS)) . ')$/', $slug, $m)) {
            http_response_code(404);
            return $this->view('errors/404', []);
        }
        $serviceSlug = $m[1];
        $locSlug = $m[2];
        if (!isset(self::SERVICES[$serviceSlug])) {
            http_response_code(404);
            return $this->view('errors/404', []);
        }

        [$serviceLabel, $catSlug, $phrase] = self::SERVICES[$serviceSlug];
        $location = self::LOCATIONS[$locSlug];

        $vehicles = Database::fetchAll(
            "SELECT v.*, c.name AS category_name FROM {p}vehicles v
             LEFT JOIN {p}categories c ON c.id=v.category_id
             WHERE v.status='active' AND (c.slug=? OR ?='') ORDER BY v.price_day ASC LIMIT 8",
            [$catSlug, $catSlug]
        );

        $title = "{$serviceLabel} in {$location} — Book Online | " . setting('site_name', 'Dwarka Rental');
        $meta = '<meta name="description" content="' . e("Book {$phrase} in {$location}, Devbhoomi Dwarka. Instant online booking, verified vehicles, best price. {$serviceLabel} for Dwarka darshan, local sightseeing & outstation trips.") . '">';

        return $this->view('front/landing', [
            'title'        => $title,
            'meta'         => $meta,
            'serviceLabel' => $serviceLabel,
            'phrase'       => $phrase,
            'location'     => $location,
            'locSlug'      => $locSlug,
            'serviceSlug'  => $serviceSlug,
            'vehicles'     => $vehicles,
            'services'     => self::SERVICES,
            'locations'    => self::LOCATIONS,
        ], 'front');
    }
}
