<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Database;

class SeoController extends Controller
{
    public function sitemap(): string
    {
        header('Content-Type: application/xml; charset=utf-8');
        $urls = [base_url('/'), base_url('/vehicles')];
        foreach (Database::fetchAll("SELECT id FROM {p}vehicles WHERE status='active'") as $v) {
            $urls[] = base_url('/vehicle/' . $v['id']);
        }
        foreach (Database::fetchAll("SELECT slug FROM {p}pages WHERE status='active'") as $p) {
            $urls[] = base_url('/page/' . $p['slug']);
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . e($u) . '</loc></url>' . "\n";
        }
        $xml .= '</urlset>';
        return $xml;
    }

    public function robots(): string
    {
        header('Content-Type: text/plain; charset=utf-8');
        return "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /shop\nDisallow: /agency\nSitemap: " . base_url('/sitemap.xml') . "\n";
    }
}
