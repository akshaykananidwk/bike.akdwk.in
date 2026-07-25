<?php
namespace App\Controllers\Front;

use App\Core\Controller;

/**
 * Serves PWA assets dynamically (so URLs respect the configured site URL).
 */
class PwaController extends Controller
{
    public function manifest(): string
    {
        header('Content-Type: application/manifest+json');
        $primary = setting('primary_color', '#0d6efd');
        $name = setting('site_name', 'Dwarka Rental');
        return json_encode([
            'name'             => $name,
            'short_name'       => $name,
            'start_url'        => base_url('/'),
            'display'          => 'standalone',
            'background_color' => '#ffffff',
            'theme_color'      => $primary,
            'description'      => 'Rent bikes and cars in Dwarka — scan, book, ride.',
            'icons'            => [
                ['src' => $this->iconUri($primary, 192), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => $this->iconUri($primary, 512), 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ], JSON_UNESCAPED_SLASHES);
    }

    public function serviceWorker(): string
    {
        header('Content-Type: application/javascript');
        $v = setting('asset_version', '1');
        $offline = base_url('/');
        return <<<JS
// Dwarka Rental service worker (v{$v})
const CACHE = 'dwk-v{$v}';
self.addEventListener('install', e => { self.skipWaiting(); });
self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))));
});
self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;
  e.respondWith(
    fetch(e.request).then(res => {
      const copy = res.clone();
      caches.open(CACHE).then(c => c.put(e.request, copy)).catch(()=>{});
      return res;
    }).catch(() => caches.match(e.request).then(r => r || caches.match('{$offline}')))
  );
});
JS;
    }

    /** Generate a simple branded PNG icon as a data URI. */
    private function iconUri(string $hex, int $size): string
    {
        $rgb = sscanf(ltrim($hex, '#'), '%02x%02x%02x');
        $im = imagecreatetruecolor($size, $size);
        $bg = imagecolorallocate($im, $rgb[0] ?? 13, $rgb[1] ?? 110, $rgb[2] ?? 253);
        imagefill($im, 0, 0, $bg);
        $white = imagecolorallocate($im, 255, 255, 255);
        $font = BASE_PATH . '/assets/fonts/NotoSansGujarati.ttf';
        $txt = 'DR';
        $fs = (int)($size * 0.4);
        $box = imagettfbbox($fs, 0, $font, $txt);
        $x = (int)(($size - ($box[2] - $box[0])) / 2);
        $y = (int)(($size + ($box[1] - $box[7])) / 2);
        imagettftext($im, $fs, 0, $x, $y, $white, $font, $txt);
        ob_start();
        imagepng($im);
        $data = base64_encode((string)ob_get_clean());
        imagedestroy($im);
        return 'data:image/png;base64,' . $data;
    }
}
