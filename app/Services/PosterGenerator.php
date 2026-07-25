<?php
namespace App\Services;

use App\Core\Qr;
use App\Core\Settings;

/**
 * Renders a printable "SCAN TO RENT" poster for a shop: platform logo, shop
 * name (English + Gujarati), a large QR code linking to /s/{code}, a headline,
 * and the contact number. Output as a GD image, PNG bytes, or PDF (A4/A5).
 *
 * Admin can supply a custom background design (a full-bleed image); otherwise
 * a clean branded gradient is drawn.
 */
class PosterGenerator
{
    // Render at print resolution (~150 DPI for A-series ratio 1:1.414).
    private const W = 1240;   // px
    private const H = 1754;   // px (A4 ratio)

    private string $font;

    public function __construct()
    {
        $this->font = BASE_PATH . '/assets/fonts/NotoSansGujarati.ttf';
    }

    /**
     * @param array $shop  Row from shops table (needs code, name, name_gu, mobile).
     * @param string|null $bgPath Absolute path to a custom background image.
     * @return \GdImage
     */
    public function render(array $shop, ?string $bgPath = null)
    {
        $w = self::W; $h = self::H;
        $im = imagecreatetruecolor($w, $h);

        $primary = self::hexToRgb(Settings::get('primary_color', '#0d6efd'));
        $secondary = self::hexToRgb(Settings::get('secondary_color', '#20c997'));

        // Background
        if ($bgPath && is_file($bgPath)) {
            $bg = self::loadImage($bgPath);
            if ($bg) {
                imagecopyresampled($im, $bg, 0, 0, 0, 0, $w, $h, imagesx($bg), imagesy($bg));
                imagedestroy($bg);
            }
        } else {
            // Vertical gradient from primary -> darker.
            for ($y = 0; $y < $h; $y++) {
                $t = $y / $h;
                $r = (int)($primary[0] * (1 - $t * 0.55));
                $g = (int)($primary[1] * (1 - $t * 0.55));
                $b = (int)($primary[2] * (1 - $t * 0.55));
                $c = imagecolorallocate($im, $r, $g, $b);
                imageline($im, 0, $y, $w, $y, $c);
            }
        }

        $white = imagecolorallocate($im, 255, 255, 255);
        $ink   = imagecolorallocate($im, 20, 30, 50);
        $accent = imagecolorallocate($im, $secondary[0], $secondary[1], $secondary[2]);

        // Header: platform name (no emoji — the Gujarati font has no emoji glyphs).
        $siteName = Settings::get('site_name', 'Dwarka Rental');
        $siteNameGu = Settings::get('site_name_gu', '');
        $this->centerText($im, $siteName, 58, 130, $white);
        if ($siteNameGu !== '') {
            $this->centerText($im, $siteNameGu, 38, 195, $accent);
        }

        // Headline
        $this->centerText($im, 'SCAN TO RENT', 82, 350, $white);
        $this->centerText($im, 'BIKE  •  ACTIVA  •  CAR', 42, 420, $white);
        $this->centerText($im, 'બાઇક • એક્ટિવા • કાર ભાડે લો', 40, 480, $accent);

        // White card holding the QR — sized so its contents never overflow.
        $cardX = 210; $cardW = $w - 420;
        $qrSize = $cardW - 140;
        $cardY = 545;
        $cardH = $qrSize + 200;                 // 70 top pad + qr + 130 for the code line
        $this->roundedRect($im, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, 40, $white);

        // QR code
        $siteUrl = trim(Settings::get('site_url', ''));
        $base = $siteUrl !== '' ? rtrim($siteUrl, '/') : 'https://' . ($_SERVER['HTTP_HOST'] ?? 'example.com');
        $url = $base . '/s/' . $shop['code'];
        $qr = Qr::toImage($url, 12, 1);
        imagecopyresampled($im, $qr, $cardX + 70, $cardY + 70, 0, 0, $qrSize, $qrSize, imagesx($qr), imagesy($qr));
        imagedestroy($qr);

        // Shop code under QR (inside the card)
        $this->centerText($im, $shop['code'], 44, $cardY + $qrSize + 155, $ink, $cardX, $cardW);

        // Shop name (English + Gujarati) below the card
        $nameY = $cardY + $cardH + 95;
        $this->centerText($im, $shop['name'], 54, $nameY, $white);
        if (!empty($shop['name_gu'])) {
            $this->centerText($im, $shop['name_gu'], 44, $nameY + 70, $accent);
        }

        // Contact + footer, anchored to the bottom.
        $contact = $shop['mobile'] ?: Settings::get('contact_mobile', '');
        if ($contact) {
            $this->centerText($im, 'Call / WhatsApp: ' . $contact, 42, $h - 110, $white);
        }
        $this->centerText($im, 'Powered by ' . $siteName, 28, $h - 50, $accent);

        return $im;
    }

    public function png(array $shop, ?string $bgPath = null): string
    {
        $im = $this->render($shop, $bgPath);
        ob_start();
        imagepng($im);
        $bytes = (string)ob_get_clean();
        imagedestroy($im);
        return $bytes;
    }

    public function pdf(array $shop, string $size = 'A4', ?string $bgPath = null): string
    {
        $im = $this->render($shop, $bgPath);
        $pdf = ImagePdf::fromGd($im, $size);
        imagedestroy($im);
        return $pdf;
    }

    // -- drawing helpers ----------------------------------------------------

    private function centerText($im, string $text, int $fontSize, int $y, int $color, ?int $areaX = null, ?int $areaW = null): void
    {
        if ($text === '') { return; }
        $box = imagettfbbox($fontSize, 0, $this->font, $text);
        $textW = $box[2] - $box[0];
        $areaX = $areaX ?? 0;
        $areaW = $areaW ?? self::W;
        $x = $areaX + (int)(($areaW - $textW) / 2);
        imagettftext($im, $fontSize, 0, $x, $y, $color, $this->font, $text);
    }

    private function roundedRect($im, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        imagefilledellipse($im, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
    }

    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function loadImage(string $path)
    {
        $info = @getimagesize($path);
        if (!$info) { return null; }
        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => null,
        };
    }
}
