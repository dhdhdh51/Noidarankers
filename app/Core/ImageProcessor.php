<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validates uploaded images, fixes orientation, resizes and converts them to
 * WebP (JPEG/PNG fallback when the server's GD has no WebP support).
 * Creates 400px and 800px wide variants for responsive srcset.
 */
final class ImageProcessor
{
    private const MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const MAX_PIXELS = 25000000; // 25 megapixels keeps memory use safe on shared hosting
    private const VARIANTS = [400, 800];

    /**
     * @param array<string, mixed> $file  Entry from $_FILES
     * @return array{path:string, width:int, height:int}
     * @throws \RuntimeException with a message safe to show to the admin
     */
    public static function upload(array $file, string $name): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::uploadError($error));
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('The upload could not be verified. Please try again.');
        }
        $maxBytes = (int) config('uploads.max_mb', 5) * 1024 * 1024;
        if ((int) ($file['size'] ?? 0) > $maxBytes || filesize($tmp) > $maxBytes) {
            throw new \RuntimeException('The image is too large. The maximum size is ' . (int) config('uploads.max_mb', 5) . ' MB.');
        }
        return self::process($tmp, $name);
    }

    /** @return array{path:string, width:int, height:int} */
    public static function process(string $sourceFile, string $name): array
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('The GD image extension is not enabled on this server.');
        }
        $mime = self::detectMime($sourceFile);
        if (!in_array($mime, self::MIME, true)) {
            throw new \RuntimeException('Only JPG, PNG, WebP or GIF images can be uploaded.');
        }
        $info = @getimagesize($sourceFile);
        if ($info === false || $info[0] < 1 || $info[1] < 1) {
            throw new \RuntimeException('This file is not a valid image.');
        }
        [$width, $height] = $info;
        if ($width * $height > self::MAX_PIXELS) {
            throw new \RuntimeException('The image dimensions are too large. Please upload an image under 25 megapixels.');
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourceFile),
            'image/png' => @imagecreatefrompng($sourceFile),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourceFile) : false,
            'image/gif' => @imagecreatefromgif($sourceFile),
            default => false,
        };
        if ($image === false) {
            throw new \RuntimeException('The image could not be read. Please try a different file.');
        }
        if ($mime === 'image/jpeg') {
            $image = self::fixOrientation($image, $sourceFile);
        }
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        $width = imagesx($image);
        $height = imagesy($image);

        $useWebp = function_exists('imagewebp') && (gd_info()['WebP Support'] ?? false);
        $hasAlpha = in_array($mime, ['image/png', 'image/webp', 'image/gif'], true);
        $ext = $useWebp ? 'webp' : ($hasAlpha ? 'png' : 'jpg');

        $dir = 'uploads/' . date('Y') . '/' . date('m');
        $absDir = PUBLIC_PATH . '/' . $dir;
        if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) {
            throw new \RuntimeException('The uploads folder is not writable. Please check folder permissions.');
        }
        $slug = Str::slug($name) ?: 'image';
        $base = substr($slug, 0, 60) . '-' . bin2hex(random_bytes(4));

        $maxWidth = max(400, (int) config('uploads.max_width', 1600));
        $mainWidth = min($width, $maxWidth);
        $mainHeight = (int) round($height * $mainWidth / $width);

        try {
            self::save($image, $width, $height, $mainWidth, $mainHeight, $absDir . '/' . $base . '.' . $ext, $ext);
            foreach (self::VARIANTS as $variantWidth) {
                if ($variantWidth < $mainWidth) {
                    $variantHeight = (int) round($height * $variantWidth / $width);
                    self::save($image, $width, $height, $variantWidth, $variantHeight, $absDir . '/' . $base . '-' . $variantWidth . '.' . $ext, $ext);
                }
            }
        } finally {
            imagedestroy($image);
        }

        return ['path' => $dir . '/' . $base . '.' . $ext, 'width' => $mainWidth, 'height' => $mainHeight];
    }

    /** Delete an uploaded image and its variants. */
    public static function delete(?string $path): void
    {
        if ($path === null || $path === '' || !str_starts_with($path, 'uploads/') || str_contains($path, '..')) {
            return;
        }
        $info = pathinfo($path);
        $files = [PUBLIC_PATH . '/' . $path];
        foreach (self::VARIANTS as $w) {
            $files[] = PUBLIC_PATH . '/' . $info['dirname'] . '/' . $info['filename'] . '-' . $w . '.' . ($info['extension'] ?? 'webp');
        }
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /** @param \GdImage|resource $src */
    private static function save($src, int $srcW, int $srcH, int $dstW, int $dstH, string $file, string $ext): void
    {
        $dst = imagecreatetruecolor($dstW, $dstH);
        if ($ext === 'jpg') {
            imagefill($dst, 0, 0, (int) imagecolorallocate($dst, 255, 255, 255));
        } else {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, (int) imagecolorallocatealpha($dst, 0, 0, 0, 127));
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
        $quality = max(40, min(95, (int) config('uploads.webp_quality', 80)));
        $ok = match ($ext) {
            'webp' => imagewebp($dst, $file, $quality),
            'png' => imagepng($dst, $file, 7),
            default => imagejpeg($dst, $file, 82),
        };
        imagedestroy($dst);
        if (!$ok) {
            throw new \RuntimeException('The image could not be saved. Please check folder permissions.');
        }
        @chmod($file, 0644);
    }

    /** @param \GdImage|resource $image @return \GdImage|resource */
    private static function fixOrientation($image, string $file)
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($file);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);
            if ($rotated !== false) {
                imagedestroy($image);
                return $rotated;
            }
        }
        return $image;
    }

    private static function detectMime(string $file): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = (string) finfo_file($finfo, $file);
                finfo_close($finfo);
                return $mime;
            }
        }
        $info = @getimagesize($file);
        return is_array($info) ? (string) ($info['mime'] ?? '') : '';
    }

    private static function uploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The image is larger than the server allows.',
            UPLOAD_ERR_PARTIAL => 'The image was only partly uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE => 'No image was selected.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'The server could not store the upload (temporary folder problem).',
            default => 'The image could not be uploaded.',
        };
    }
}
