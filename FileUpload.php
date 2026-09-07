<?php
/**
 * Image upload helper.
 * Restricts to jpg/jpeg/png/webp, max 5MB, stored under backend/uploads/items/
 * with a unique generated filename. Returns the path relative to the uploads dir.
 */
declare(strict_types=1);

namespace Findly\Utils;

class FileUpload
{
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * @return string|null relative path (e.g. items/xxx.jpg) or null if no file sent
     * @throws \RuntimeException on validation/upload failure
     */
    public static function upload(?array $file): ?string
    {
        if ($file === null || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Image upload failed (error code ' . (int) $file['error'] . ')');
        }
        if ((int) $file['size'] > UPLOAD_MAX_SIZE) {
            throw new \RuntimeException('Image must be smaller than 5MB');
        }

        $mime = mime_content_type($file['tmp_name']);
        $ext = self::ALLOWED_MIME[$mime] ?? null;
        if ($ext === null) {
            throw new \RuntimeException('Only JPG, PNG or WebP images are allowed');
        }
        $nameExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($nameExt, self::ALLOWED_EXT, true)) {
            throw new \RuntimeException('Only JPG, PNG or WebP images are allowed');
        }

        $dir = FINDLY_ROOT . '/uploads/items';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $filename = 'item_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            if (!@rename($file['tmp_name'], $dir . '/' . $filename)) {
                throw new \RuntimeException('Failed to save the uploaded image');
            }
        }
        return 'items/' . $filename;
    }
}
