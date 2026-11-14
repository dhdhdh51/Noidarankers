<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\View;

abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function view(string $template, array $data = [], ?string $layout = 'layouts/main'): string
    {
        return View::render($template, $data, $layout);
    }

    protected function notFound(): HttpException
    {
        return new HttpException(404);
    }

    /** Parse a page number from the route; 1 when absent. */
    protected function pageNumber(string $n): int
    {
        return max(0, (int) $n);
    }
}
