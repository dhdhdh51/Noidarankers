<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal router. Patterns use {name} for slugs and {id}/{n} for numbers:
 *   $router->get('/blog/{slug}', [BlogController::class, 'show']);
 */
final class Router
{
    /** @var array<int, array{method:string, regex:string, handler:array{0:string,1:string}, options:array<string,mixed>}> */
    private array $routes = [];

    /** @param array{0:string,1:string} $handler @param array<string,mixed> $options */
    public function get(string $pattern, array $handler, array $options = []): void
    {
        $this->add('GET', $pattern, $handler, $options);
    }

    /** @param array{0:string,1:string} $handler @param array<string,mixed> $options */
    public function post(string $pattern, array $handler, array $options = []): void
    {
        $this->add('POST', $pattern, $handler, $options);
    }

    /** @param array{0:string,1:string} $handler @param array<string,mixed> $options */
    public function add(string $method, string $pattern, array $handler, array $options = []): void
    {
        $regex = preg_replace_callback('/\{(\w+)\}/', static function (array $m): string {
            return in_array($m[1], ['id', 'n'], true) ? '([0-9]{1,9})' : '([a-z0-9]+(?:-[a-z0-9]+)*)';
        }, $pattern);
        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'options' => $options,
        ];
    }

    /**
     * @return array{handler:array{0:string,1:string}, params:array<int,string>, options:array<string,mixed>}|null
     */
    public function match(string $method, string $path, bool &$methodNotAllowed = false): ?array
    {
        $methodNotAllowed = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $methodNotAllowed = true;
                continue;
            }
            array_shift($m);
            return ['handler' => $route['handler'], 'params' => $m, 'options' => $route['options']];
        }
        return null;
    }
}
