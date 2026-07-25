<?php
namespace App\Services;

/**
 * Minimal, dependency-free PDF writer that places a single full-page JPEG
 * onto an A4 or A5 page. Used for QR posters (and any image-based document)
 * so shared hosting needs no PDF library for these.
 *
 * Gujarati text is rendered into the image by GD beforehand, so it appears
 * pixel-perfect in the PDF regardless of PDF font support.
 */
class ImagePdf
{
    // Page sizes in PDF points (1 pt = 1/72 inch).
    private const SIZES = [
        'A4' => [595.28, 841.89],
        'A5' => [419.53, 595.28],
    ];

    /**
     * Build a PDF (as a binary string) containing one JPEG scaled to fill the page.
     * @param string $jpegBinary Raw JPEG bytes.
     */
    public static function fromJpeg(string $jpegBinary, string $size = 'A4'): string
    {
        [$pw, $ph] = self::SIZES[$size] ?? self::SIZES['A4'];

        $info = getimagesizefromstring($jpegBinary);
        if ($info === false) {
            throw new \RuntimeException('Invalid image for PDF.');
        }
        [$iw, $ih] = $info;

        // Fit image within the page preserving aspect ratio, centred.
        $scale = min($pw / $iw, $ph / $ih);
        $dw = $iw * $scale;
        $dh = $ih * $scale;
        $dx = ($pw - $dw) / 2;
        $dy = ($ph - $dh) / 2;

        $objects = [];

        // 1: Catalog
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        // 2: Pages
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        // 3: Page
        $objects[3] = sprintf(
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>",
            $pw, $ph
        );
        // 4: Image XObject
        $imgStream = $jpegBinary;
        $objects[4] = "<< /Type /XObject /Subtype /Image /Width {$iw} /Height {$ih} "
            . "/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($imgStream) . " >>\nstream\n"
            . $imgStream . "\nendstream";
        // 5: Content stream — place image
        $content = sprintf("q\n%.2F 0 0 %.2F %.2F %.2F cm\n/Im0 Do\nQ", $dw, $dh, $dx, $dy);
        $objects[5] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";

        // Assemble the file with a cross-reference table.
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";
        return $pdf;
    }

    /** Convenience: build PDF from a GD image resource. */
    public static function fromGd($gd, string $size = 'A4', int $quality = 92): string
    {
        ob_start();
        imagejpeg($gd, null, $quality);
        $jpeg = (string)ob_get_clean();
        return self::fromJpeg($jpeg, $size);
    }
}
