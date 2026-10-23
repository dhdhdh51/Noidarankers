<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\ImageProcessor;

/** Image upload for the content editor (returns JSON). */
final class UploadController extends AdminController
{
    public function image(): string
    {
        header('Content-Type: application/json; charset=UTF-8');
        $file = $_FILES['image'] ?? null;
        if (!is_array($file)) {
            http_response_code(422);
            return (string) json_encode(['error' => 'No image was received.']);
        }
        try {
            $name = pathinfo((string) ($file['name'] ?? 'image'), PATHINFO_FILENAME);
            $image = ImageProcessor::upload($file, $name);
        } catch (\RuntimeException $e) {
            http_response_code(422);
            return (string) json_encode(['error' => $e->getMessage()]);
        }
        return (string) json_encode([
            'url' => upload_url($image['path']),
            'width' => $image['width'],
            'height' => $image['height'],
        ], JSON_UNESCAPED_SLASHES);
    }
}
