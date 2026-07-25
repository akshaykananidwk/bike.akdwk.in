<?php
namespace App\Core;

/**
 * Thin wrapper over the bundled pure-PHP phpqrcode library (lib/phpqrcode).
 * No external API dependency — QR codes are rendered locally.
 */
class Qr
{
    private static bool $loaded = false;

    private static function load(): void
    {
        if (!self::$loaded) {
            require_once BASE_PATH . '/lib/phpqrcode/phpqrcode.php';
            self::$loaded = true;
        }
    }

    /**
     * Run a phpqrcode call with error display suppressed so its PHP 8.4
     * deprecation notices can never pollute binary output (PNG/PDF/ZIP).
     */
    private static function silently(callable $fn)
    {
        $prev = ini_get('display_errors');
        ini_set('display_errors', '0');
        try {
            return $fn();
        } finally {
            ini_set('display_errors', $prev);
        }
    }

    /** Write a QR PNG for $text to $file. $size = pixels per module. */
    public static function toFile(string $text, string $file, int $size = 8, int $margin = 2): void
    {
        self::load();
        $dir = dirname($file);
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        self::silently(fn() => \QRcode::png($text, $file, QR_ECLEVEL_M, $size, $margin));
    }

    /** Return QR PNG as a GD image resource. */
    public static function toImage(string $text, int $size = 8, int $margin = 2)
    {
        self::load();
        // The library can return the raw frame; render to a temp file then load.
        $tmp = tempnam(sys_get_temp_dir(), 'qr');
        self::silently(fn() => \QRcode::png($text, $tmp, QR_ECLEVEL_M, $size, $margin));
        $img = imagecreatefrompng($tmp);
        @unlink($tmp);
        return $img;
    }

    /** Return QR PNG as a base64 data URI (for inline <img>). */
    public static function toDataUri(string $text, int $size = 6, int $margin = 2): string
    {
        self::load();
        $tmp = tempnam(sys_get_temp_dir(), 'qr');
        self::silently(fn() => \QRcode::png($text, $tmp, QR_ECLEVEL_M, $size, $margin));
        $data = base64_encode((string)file_get_contents($tmp));
        @unlink($tmp);
        return 'data:image/png;base64,' . $data;
    }
}
