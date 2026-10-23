<?php
declare(strict_types=1);

namespace App\Core;

/** Plain-PHP templates in app/Views. Always escape output with e(). */
final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $content = self::partial($template, $data);
        if ($layout === null) {
            return $content;
        }
        return self::partial($layout, array_merge($data, ['content' => $content]));
    }

    /** @param array<string, mixed> $data */
    public static function partial(string $template, array $data = []): string
    {
        if (!preg_match('#^[a-z0-9/_-]+$#', $template)) {
            throw new \InvalidArgumentException('Invalid view name.');
        }
        $file = APP_PATH . '/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View not found: ' . $template);
        }
        $render = static function (string $__file, array $__data): void {
            extract($__data, EXTR_SKIP);
            include $__file;
        };
        ob_start();
        try {
            $render($file, $data);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
