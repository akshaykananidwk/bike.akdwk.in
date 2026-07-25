<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Settings;

/**
 * Renders a booking invoice/receipt as a PDF. To guarantee correct Gujarati
 * rendering on shared hosting (no complex-script PDF library needed), the
 * invoice is drawn with GD + Noto Sans Gujarati and embedded into a PDF.
 */
class InvoiceService
{
    private const W = 1240;   // A4 @ ~150dpi
    private const H = 1754;
    private string $font;

    public function __construct()
    {
        $this->font = BASE_PATH . '/assets/fonts/NotoSansGujarati.ttf';
    }

    public static function generate(int $bookingId): string
    {
        return (new self())->build($bookingId);
    }

    public function build(int $bookingId): string
    {
        $b = Database::fetch("SELECT * FROM {p}bookings WHERE id=?", [$bookingId]);
        if (!$b) { throw new \RuntimeException('Booking not found.'); }
        $vehicle = Database::fetch("SELECT * FROM {p}vehicles WHERE id=?", [$b['vehicle_id']]);
        $agency  = $b['agency_id'] ? Database::fetch("SELECT * FROM {p}agencies WHERE id=?", [$b['agency_id']]) : null;
        $shop    = $b['shop_id'] ? Database::fetch("SELECT * FROM {p}shops WHERE id=?", [$b['shop_id']]) : null;

        $im = imagecreatetruecolor(self::W, self::H);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $white);

        $primary = $this->hex(Settings::get('primary_color', '#0d6efd'));
        $ink   = imagecolorallocate($im, 30, 41, 59);
        $muted = imagecolorallocate($im, 120, 130, 145);
        $line  = imagecolorallocate($im, 220, 225, 232);
        $pcol  = imagecolorallocate($im, $primary[0], $primary[1], $primary[2]);

        // Header band
        imagefilledrectangle($im, 0, 0, self::W, 190, $pcol);
        $this->text($im, Settings::get('site_name', 'Dwarka Rental'), 46, 70, 90, $white);
        $siteGu = Settings::get('site_name_gu', '');
        if ($siteGu) { $this->text($im, $siteGu, 30, 70, 140, $white); }
        $this->textRight($im, 'INVOICE / RECEIPT', 34, self::W - 70, 80, $white);
        $this->textRight($im, $b['code'], 40, self::W - 70, 135, $white);

        $y = 260;
        // Meta
        $this->text($im, 'Date: ' . date('d M Y, h:i A', strtotime($b['created_at'])), 26, 70, $y, $muted);
        $status = strtoupper(str_replace('_', ' ', $b['payment_status']));
        $this->textRight($im, 'Payment: ' . $status, 26, self::W - 70, $y, $ink);
        $y += 60;

        // Customer + pickup boxes
        $this->text($im, 'BILLED TO', 24, 70, $y, $pcol);
        $this->text($im, 'RENTAL', 24, 680, $y, $pcol);
        $y += 45;
        $this->text($im, (string)$b['customer_name'], 30, 70, $y, $ink);
        $this->text($im, (string)($vehicle['name'] ?? ''), 30, 680, $y, $ink);
        $y += 45;
        $this->text($im, 'Mob: ' . $b['customer_mobile'], 26, 70, $y, $muted);
        $this->text($im, 'Pickup: ' . date('d M Y h:iA', strtotime($b['pickup_at'])), 26, 680, $y, $muted);
        $y += 42;
        if ($b['customer_address']) { $this->text($im, substr((string)$b['customer_address'], 0, 40), 24, 70, $y, $muted); }
        $this->text($im, 'Drop: ' . date('d M Y h:iA', strtotime($b['drop_at'])), 26, 680, $y, $muted);
        $y += 60;

        // Table header
        imagefilledrectangle($im, 70, $y, self::W - 70, $y + 50, imagecolorallocate($im, 241, 245, 249));
        $this->text($im, 'Description', 26, 90, $y + 35, $ink);
        $this->textRight($im, 'Amount', 26, self::W - 90, $y + 35, $ink);
        $y += 80;

        $rows = [
            ['Base rental (' . rtrim(rtrim(number_format($b['duration_hours'], 1), '0'), '.') . ' hrs)', (float)$b['base_amount']],
        ];
        if ((float)$b['tax_amount'] > 0) { $rows[] = ['GST (' . Settings::get('gst_percent', 0) . '%)', (float)$b['tax_amount']]; }
        if ((float)$b['discount_amount'] > 0) { $rows[] = ['Discount', -(float)$b['discount_amount']]; }
        if ((float)$b['extra_charges'] > 0) { $rows[] = ['Extra charges', (float)$b['extra_charges']]; }
        $rows[] = ['Security deposit (refundable)', (float)$b['deposit']];

        foreach ($rows as [$label, $amt]) {
            $this->text($im, $label, 26, 90, $y, $ink);
            $this->textRight($im, $this->money($amt), 26, self::W - 90, $y, $ink);
            imageline($im, 70, $y + 20, self::W - 70, $y + 20, $line);
            $y += 60;
        }

        $y += 20;
        // Totals
        $this->totalRow($im, 'Total', $this->money($b['total_amount']), $y, $ink, true); $y += 55;
        $this->totalRow($im, 'Paid', $this->money($b['paid_amount']), $y, $pcol, false); $y += 50;
        $this->totalRow($im, 'Balance (at pickup)', $this->money($b['balance_amount']), $y, $ink, false); $y += 90;

        // Agency / shop
        if ($agency) {
            $this->text($im, 'Agency: ' . $agency['name'] . '  •  ' . ($agency['mobile'] ?? ''), 24, 70, $y, $muted);
            $y += 40;
        }
        if ($shop) {
            $this->text($im, 'Booked via: ' . $shop['name'], 24, 70, $y, $muted);
            $y += 40;
        }

        // Footer
        $this->textCenter($im, 'Thank you for choosing ' . Settings::get('site_name', 'Dwarka Rental') . '!', 26, self::H - 120, $muted);
        $this->textCenter($im, 'આભાર! સલામત મુસાફરી કરો.', 26, self::H - 75, $pcol);

        $pdf = ImagePdf::fromGd($im, 'A4');
        imagedestroy($im);
        return $pdf;
    }

    // -- helpers ------------------------------------------------------------
    private function money($v): string { return Settings::get('currency_symbol', 'Rs.') . number_format((float)$v, 2); }
    private function text($im, string $t, int $size, int $x, int $y, int $color): void { imagettftext($im, $size, 0, $x, $y, $color, $this->font, $t); }
    private function textRight($im, string $t, int $size, int $x, int $y, int $color): void {
        $b = imagettfbbox($size, 0, $this->font, $t); $w = $b[2] - $b[0];
        imagettftext($im, $size, 0, $x - $w, $y, $color, $this->font, $t);
    }
    private function textCenter($im, string $t, int $size, int $y, int $color): void {
        $b = imagettfbbox($size, 0, $this->font, $t); $w = $b[2] - $b[0];
        imagettftext($im, $size, 0, (int)((self::W - $w) / 2), $y, $color, $this->font, $t);
    }
    private function totalRow($im, string $label, string $value, int $y, int $color, bool $bold): void {
        $size = $bold ? 32 : 28;
        $this->text($im, $label, $size, 680, $y, $color);
        $this->textRight($im, $value, $size, self::W - 90, $y, $color);
    }
    private function hex(string $hex): array {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
        return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
    }
}
