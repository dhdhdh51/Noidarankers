<?php
/**
 * Global helper functions used by controllers and views.
 */
declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Security;
use App\Core\Session;
use App\Core\Settings;

/** @return mixed */
function config(string $key, $default = null)
{
    return Config::get($key, $default);
}

/** Escape for HTML text and attribute values. @param mixed $value */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** Sub-folder the site is installed in ('' for the domain root). */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $path = (string) parse_url((string) config('app.url', ''), PHP_URL_PATH);
        $base = rtrim($path, '/');
    }
    return $base;
}

/** Root-relative URL for an internal path, e.g. url('/blog'). */
function url(string $path = '/'): string
{
    if (preg_match('#^(https?:|mailto:|tel:|\#)#', $path)) {
        return $path;
    }
    $path = '/' . ltrim($path, '/');
    return base_path() . ($path === '/' && base_path() !== '' ? '' : $path);
}

/** Absolute URL using app.url (used for canonical, OG, sitemap, JSON-LD). */
function absolute_url(string $path = '/'): string
{
    if (preg_match('#^https?://#', $path)) {
        return $path;
    }
    $parts = parse_url((string) config('app.url', ''));
    $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'localhost') . (isset($parts['port']) ? ':' . $parts['port'] : '');
    $relative = url($path);
    return $origin . ($relative === '' ? '/' : $relative);
}

/** Versioned URL for a file in public/assets. */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = PUBLIC_PATH . '/assets/' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return url('/assets/' . $path) . '?v=' . $version;
}

/** URL for an uploaded file stored as "uploads/…" in the database. */
function upload_url(string $path): string
{
    return url('/' . ltrim($path, '/'));
}

function setting(string $key, string $default = ''): string
{
    return Settings::get($key, $default);
}

/** True when a value is empty or still a "[PLACEHOLDER]". */
function is_placeholder(?string $value): bool
{
    $value = trim((string) $value);
    return $value === '' || (bool) preg_match('/\[[^\]]*\]/u', $value);
}

function site_name(): string
{
    return (string) config('site.name', 'NoidaRankers');
}

/** Digits-only phone for tel: links, or '' while it is a placeholder. */
function tel_href(string $phone): string
{
    if (is_placeholder($phone)) {
        return '';
    }
    $digits = preg_replace('/[^0-9+]/', '', $phone);
    return strlen((string) $digits) >= 8 ? 'tel:' . $digits : '';
}

/** wa.me link with an optional prefilled message, or '' while it is a placeholder. */
function whatsapp_href(string $number, string $text = ''): string
{
    if (is_placeholder($number)) {
        return '';
    }
    $digits = preg_replace('/\D/', '', $number);
    if (strlen((string) $digits) < 10) {
        return '';
    }
    return 'https://wa.me/' . $digits . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

function mailto_href(string $email): string
{
    return !is_placeholder($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $email : '';
}

function ga_id(): string
{
    $id = strtoupper(trim(setting('ga_id')));
    return preg_match('/^G-[A-Z0-9]{4,20}$/', $id) ? $id : '';
}

function csrf_field(): string
{
    return Csrf::field();
}

function csp_nonce(): string
{
    return Security::nonce();
}

/** Hidden spam-protection fields: honeypot + signed timer. */
function spam_fields(): string
{
    return '<div class="hp" aria-hidden="true"><label for="hp-' . e(substr(Security::nonce(), 0, 6)) . '">Leave this field empty</label>'
        . '<input id="hp-' . e(substr(Security::nonce(), 0, 6)) . '" type="text" name="company_fax" value="" tabindex="-1" autocomplete="off"></div>'
        . '<input type="hidden" name="_t" value="' . e(Security::formTimer()) . '">';
}

/** Previously submitted value (after a validation error). */
function old(string $key, string $default = ''): string
{
    $old = Session::flashed('old', []);
    return is_array($old) && isset($old[$key]) && is_string($old[$key]) ? $old[$key] : $default;
}

/** @return array<string, string> */
function errors(): array
{
    $errors = Session::flashed('errors', []);
    return is_array($errors) ? $errors : [];
}

function flash_message(string $key = 'success'): string
{
    $value = Session::flashed($key, '');
    return is_string($value) ? $value : '';
}

/** Send a redirect and stop. */
function redirect(string $location, int $status = 303): void
{
    if (!preg_match('#^https?://#', $location) && !str_starts_with($location, '/')) {
        $location = url($location);
    }
    header('Location: ' . $location, true, $status);
    exit;
}

/** Only allow redirects back to a local path (prevents open redirects). */
function safe_return_path(string $path, string $fallback = '/'): string
{
    if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\')) {
        return $fallback;
    }
    return (string) preg_replace('/[\r\n]/', '', $path);
}

function format_date(?string $datetime, string $format = 'j M Y'): string
{
    if ($datetime === null || $datetime === '') {
        return '';
    }
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '';
}

function iso_date(?string $datetime): string
{
    $ts = $datetime ? strtotime($datetime) : false;
    return $ts ? date('c', $ts) : '';
}

function reading_time(string $html): int
{
    $words = str_word_count(strip_tags($html));
    return max(1, (int) ceil($words / 200));
}

function excerpt(string $text, int $length = 160): string
{
    $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    $cut = mb_substr($text, 0, $length - 1);
    $space = mb_strrpos($cut, ' ');
    return rtrim($space !== false && $space > $length * 0.6 ? mb_substr($cut, 0, $space) : $cut, " ,.;:-") . '…';
}

/** Split a textarea into non-empty trimmed lines. @return array<int, string> */
function lines(?string $text): array
{
    $out = [];
    foreach (preg_split('/\R/u', (string) $text) ?: [] as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

/** Parse "value | label" lines into metric pairs. @return array<int, array{value:string,label:string}> */
function metric_lines(?string $text): array
{
    $out = [];
    foreach (lines($text) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        $out[] = ['value' => $parts[0], 'label' => $parts[1] ?? ''];
    }
    return $out;
}

/** Inline SVG icon from app/Views/partials/icons.php. */
function icon(string $name, string $class = 'icon'): string
{
    static $icons = null;
    if ($icons === null) {
        $icons = require APP_PATH . '/Views/partials/icons.php';
    }
    $paths = $icons[$name] ?? $icons['spark'];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
}

/** Responsive <img> for an uploaded WebP with its 400/800 variants. */
function picture_tag(string $path, int $width, int $height, string $alt, string $sizes, bool $lazy = true, string $class = ''): string
{
    if ($path === '' || $width <= 0 || $height <= 0) {
        return '';
    }
    $srcset = [];
    $info = pathinfo($path);
    foreach ([400, 800] as $w) {
        $variant = ($info['dirname'] ?? '') . '/' . ($info['filename'] ?? '') . '-' . $w . '.' . ($info['extension'] ?? 'webp');
        if ($w < $width && is_file(PUBLIC_PATH . '/' . ltrim($variant, '/'))) {
            $srcset[] = upload_url($variant) . ' ' . $w . 'w';
        }
    }
    $srcset[] = upload_url($path) . ' ' . $width . 'w';
    $attrs = [
        'src' => upload_url($path),
        'srcset' => implode(', ', $srcset),
        'sizes' => $sizes,
        'width' => (string) $width,
        'height' => (string) $height,
        'alt' => $alt,
        'decoding' => 'async',
    ];
    if ($lazy) {
        $attrs['loading'] = 'lazy';
    } else {
        $attrs['fetchpriority'] = 'high';
    }
    if ($class !== '') {
        $attrs['class'] = $class;
    }
    $html = '<img';
    foreach ($attrs as $k => $v) {
        $html .= ' ' . $k . '="' . e($v) . '"';
    }
    return $html . '>';
}

/** Inline, minified CSS for the given parts of public/assets/css. @param array<int, string> $parts */
function inline_css(array $parts): string
{
    return App\Core\Css::inline($parts);
}
