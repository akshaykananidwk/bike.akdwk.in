<?php
namespace App\Core;

/**
 * Secure file upload handling: extension whitelist, real MIME verification,
 * size limit, random filenames, stored under /uploads (PHP execution blocked
 * there by .htaccess).
 */
class Uploader
{
    private const MAX_BYTES = 5 * 1024 * 1024; // 5 MB

    private const ALLOWED = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'pdf'  => 'application/pdf',
    ];

    /**
     * @return array{ok:bool, path?:string, error?:string}
     *   path is relative to /uploads (store this in the DB).
     */
    public static function handle(array $file, string $subdir, array $allowExt = ['jpg', 'jpeg', 'png', 'webp', 'pdf']): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['ok' => false, 'error' => 'Invalid upload.'];
        }
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['ok' => false, 'error' => 'No file selected.'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['ok' => false, 'error' => 'File is too large.'];
            default:
                return ['ok' => false, 'error' => 'Upload failed.'];
        }

        if ($file['size'] > self::MAX_BYTES) {
            return ['ok' => false, 'error' => 'File exceeds 5 MB limit.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowExt, true) || !isset(self::ALLOWED[$ext])) {
            return ['ok' => false, 'error' => 'File type not allowed.'];
        }

        // Verify the real MIME type, not just the extension.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);
        if ($realMime !== self::ALLOWED[$ext]) {
            // jpeg/jpg both map to image/jpeg — already handled by the map.
            return ['ok' => false, 'error' => 'File content does not match its extension.'];
        }

        $dir = BASE_PATH . '/uploads/' . trim($subdir, '/');
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['ok' => false, 'error' => 'Upload directory is not writable.'];
        }

        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            // Fallback for CLI/testing contexts.
            if (!@rename($file['tmp_name'], $dest)) {
                return ['ok' => false, 'error' => 'Could not save file.'];
            }
        }
        @chmod($dest, 0644);

        return ['ok' => true, 'path' => trim($subdir, '/') . '/' . $name];
    }

    public static function delete(?string $relativePath): void
    {
        if (!$relativePath) { return; }
        $full = BASE_PATH . '/uploads/' . ltrim($relativePath, '/');
        $real = realpath($full);
        $base = realpath(BASE_PATH . '/uploads');
        if ($real && $base && strpos($real, $base) === 0 && is_file($real)) {
            @unlink($real);
        }
    }
}
