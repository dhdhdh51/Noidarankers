<?php
declare(strict_types=1);

namespace App\Core;

/** Request kernel: redirects, routing, middleware, CSRF, errors and headers. */
final class App
{
    private static bool $admin = false;

    public static function run(): void
    {
        ob_start();
        try {
            // Apache "ErrorDocument ... /index.php" hands us its error (e.g. 403 for blocked files).
            $apacheStatus = (int) ($_SERVER['REDIRECT_STATUS'] ?? 0);
            if ($apacheStatus >= 400) {
                throw new HttpException($apacheStatus);
            }
            self::canonicalRedirect();

            $router = new Router();
            (require APP_PATH . '/routes.php')($router);

            $path = Request::path();
            self::cleanUrlRedirect($path);

            $notAllowed = false;
            $route = $router->match(Request::method(), $path, $notAllowed);
            if ($route === null) {
                throw new HttpException($notAllowed ? 405 : 404);
            }
            $options = $route['options'];
            self::$admin = !empty($options['admin']);

            if (empty($options['stateless'])) {
                Session::start();
            }
            if (self::$admin) {
                self::guardAdmin($options);
            }
            // Every POST must carry a valid CSRF token. Public lead/subscribe
            // forms verify it themselves so an expired token never loses a lead.
            if (Request::isPost() && ($options['csrf'] ?? 'auto') !== 'manual' && !Csrf::verify(Csrf::fromRequest())) {
                throw new HttpException(419);
            }

            [$class, $method] = $route['handler'];
            $output = (new $class())->{$method}(...$route['params']);
            if (is_string($output)) {
                echo $output;
            }
        } catch (HttpException $e) {
            self::discardOutput();
            echo self::renderError($e->status(), $e->getMessage());
        } catch (\PDOException $e) {
            self::discardOutput();
            error_log('[db] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            echo self::renderError(503);
        } catch (\Throwable $e) {
            self::discardOutput();
            error_log('[app] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
            echo self::renderError(500);
        }

        Security::sendHeaders(self::$admin);
        if (!headers_sent()) {
            if (self::$admin) {
                header('Cache-Control: no-store, max-age=0');
                header('X-Robots-Tag: noindex, nofollow');
            } elseif (!self::hasHeader('Cache-Control')) {
                header('Cache-Control: private, no-cache');
            }
        }
        ob_end_flush();
    }

    public static function isAdmin(): bool
    {
        return self::$admin;
    }

    /** Redirect to HTTPS and/or the canonical host from app.url (GET requests only). */
    private static function canonicalRedirect(): void
    {
        if (PHP_SAPI === 'cli' || Request::method() !== 'GET') {
            return;
        }
        $parts = parse_url((string) config('app.url', ''));
        if (empty($parts['host'])) {
            return;
        }
        $canonicalHost = strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $wrongScheme = config('app.force_https') && ($parts['scheme'] ?? '') === 'https' && !Request::isHttps();
        $wrongHost = config('app.canonical_redirect') && Request::host() !== '' && Request::host() !== $canonicalHost;
        if ($wrongScheme || $wrongHost) {
            $query = Request::queryString();
            redirect(absolute_url(Request::path()) . ($query !== '' ? '?' . $query : ''), 301);
        }
    }

    /** /about.php -> /about, /index.php -> /, /about/ -> /about */
    private static function cleanUrlRedirect(string $path): void
    {
        if (Request::method() !== 'GET') {
            return;
        }
        $target = null;
        if ($path === '/public' || str_starts_with($path, '/public/')) {
            // Old links that include the /public folder (e.g. after uploading to public_html).
            $path = substr($path, 7) ?: '/';
            $target = $path;
        }
        if (preg_match('#^(.*?)(?:/index)?\.php$#', $path, $m)) {
            $target = $m[1] === '' ? '/' : $m[1];
        } elseif ($path !== '/' && str_ends_with($path, '/')) {
            $target = rtrim($path, '/');
        }
        if ($target !== null && ($target !== $path || $target !== Request::path())) {
            $query = Request::queryString();
            redirect(absolute_url($target) . ($query !== '' ? '?' . $query : ''), 301);
        }
    }

    /** @param array<string, mixed> $options */
    private static function guardAdmin(array $options): void
    {
        $allow = config('app.admin_ip_allowlist', []);
        if (is_array($allow) && $allow !== [] && !in_array(Request::ip(), $allow, true)) {
            throw new HttpException(404);
        }
        if (!empty($options['guest'])) {
            return;
        }
        if (!Auth::check()) {
            if (Request::method() === 'GET') {
                Session::set('intended', Request::path());
                redirect(url('/admin/login'));
            }
            throw new HttpException(419);
        }
    }

    private static function discardOutput(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        if (ob_get_level() === 1) {
            ob_clean();
        }
    }

    private static function hasHeader(string $name): bool
    {
        foreach (headers_list() as $header) {
            if (stripos($header, $name . ':') === 0) {
                return true;
            }
        }
        return false;
    }

    public static function renderError(int $status, string $message = ''): string
    {
        if (!headers_sent()) {
            http_response_code($status);
            if ($status === 405) {
                header('Allow: GET, POST');
            }
        }
        $titles = [
            403 => 'Access denied',
            404 => 'Page not found',
            405 => 'Method not allowed',
            419 => 'Your session has expired',
            429 => 'Too many requests',
            500 => 'Something went wrong',
            503 => 'Service temporarily unavailable',
        ];
        $title = $titles[$status] ?? 'Error';
        try {
            $seo = Seo::page($title, 'The page you requested could not be shown.', Request::path());
            $seo->noindex();
            $view = $status === 404 ? 'errors/404' : 'errors/error';
            if ($status >= 500) {
                // Keep the error page independent from the database.
                return View::render('errors/error', compact('status', 'title', 'message', 'seo'), 'layouts/minimal');
            }
            return View::render($view, compact('status', 'title', 'message', 'seo'));
        } catch (\Throwable $e) {
            error_log('[error-page] ' . $e->getMessage());
            return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="robots" content="noindex">'
                . '<meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($title) . '</title></head>'
                . '<body><main><h1>' . e($title) . '</h1><p><a href="' . e(url('/')) . '">Go to the home page</a></p></main></body></html>';
        }
    }
}
