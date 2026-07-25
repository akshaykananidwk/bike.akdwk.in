<?php
namespace App\Services;

use App\Core\Settings;

/**
 * Commission/settlement statement PDF, rendered via GD (Gujarati-safe) and
 * embedded into a PDF page.
 */
class StatementPdf
{
    private const W = 1240;
    private const H = 1754;

    public static function shop(array $shop, array $rows): string
    {
        return (new self())->render('Commission Statement', $shop, $rows, 'Commission');
    }

    public static function agency(array $agency, array $rows): string
    {
        return (new self())->render('Settlement Statement', $agency, $rows, 'Settlement');
    }

    private function render(string $heading, array $owner, array $rows, string $amountLabel): string
    {
        $font = BASE_PATH . '/assets/fonts/NotoSansGujarati.ttf';
        $im = imagecreatetruecolor(self::W, self::H);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
        $rgb = sscanf(ltrim(Settings::get('primary_color', '#0d6efd'), '#'), '%02x%02x%02x');
        $p = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
        $ink = imagecolorallocate($im, 30, 41, 59);
        $muted = imagecolorallocate($im, 120, 130, 145);
        $line = imagecolorallocate($im, 225, 230, 236);
        $white = imagecolorallocate($im, 255, 255, 255);

        imagefilledrectangle($im, 0, 0, self::W, 150, $p);
        imagettftext($im, 40, 0, 70, 70, $white, $font, Settings::get('site_name', 'Dwarka Rental'));
        imagettftext($im, 26, 0, 70, 120, $white, $font, $heading);

        imagettftext($im, 28, 0, 70, 220, $ink, $font, ($owner['name'] ?? '') . '  (' . ($owner['code'] ?? '') . ')');
        imagettftext($im, 22, 0, 70, 260, $muted, $font, 'Generated: ' . date('d M Y, h:i A'));

        $y = 330;
        imagefilledrectangle($im, 70, $y - 30, self::W - 70, $y + 10, imagecolorallocate($im, 241, 245, 249));
        imagettftext($im, 22, 0, 90, $y, $ink, $font, 'Date');
        imagettftext($im, 22, 0, 380, $y, $ink, $font, 'Booking');
        imagettftext($im, 22, 0, 720, $y, $ink, $font, 'Type');
        $this->right($im, $font, 'Amount', 22, self::W - 90, $y, $ink);
        $y += 55;

        $total = 0.0;
        foreach (array_slice($rows, 0, 40) as $r) {
            $sign = ($r['entry_type'] ?? 'credit') === 'reversal' ? -1 : 1;
            $total += $sign * (float)$r['amount'];
            imagettftext($im, 20, 0, 90, $y, $muted, $font, date('d M Y', strtotime($r['created_at'])));
            imagettftext($im, 20, 0, 380, $y, $ink, $font, (string)($r['code'] ?? ''));
            imagettftext($im, 20, 0, 720, $y, $ink, $font, (string)($r['entry_type'] ?? ''));
            $amt = ($sign < 0 ? '-' : '') . Settings::get('currency_symbol', 'Rs.') . number_format((float)$r['amount'], 2);
            $this->right($im, $font, $amt, 20, self::W - 90, $y, $sign < 0 ? imagecolorallocate($im, 200, 30, 30) : $ink);
            imageline($im, 70, $y + 15, self::W - 70, $y + 15, $line);
            $y += 48;
            if ($y > self::H - 200) { break; }
        }

        $y += 30;
        imagettftext($im, 30, 0, 500, $y, $p, $font, 'Net ' . $amountLabel . ':');
        $this->right($im, $font, Settings::get('currency_symbol', 'Rs.') . number_format($total, 2), 30, self::W - 90, $y, $p);

        $pdf = ImagePdf::fromGd($im, 'A4');
        imagedestroy($im);
        return $pdf;
    }

    private function right($im, string $font, string $t, int $size, int $x, int $y, int $color): void
    {
        $b = imagettfbbox($size, 0, $font, $t);
        imagettftext($im, $size, 0, $x - ($b[2] - $b[0]), $y, $color, $font, $t);
    }
}
