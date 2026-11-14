<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Security;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;

/** First-run setup, login and logout. */
final class AuthController extends Controller
{
    public function setupForm(): string
    {
        if (Auth::hasAdmin()) {
            throw new HttpException(404);
        }
        return $this->setupView([], []);
    }

    public function setup(): string
    {
        if (Auth::hasAdmin()) {
            throw new HttpException(404);
        }
        $input = ['name' => Request::input('name'), 'email' => mb_strtolower(Request::input('email'))];
        if (!RateLimiter::hit('setup', 10, 3600)) {
            return $this->setupView($input, ['setup_key' => 'Too many attempts. Please wait an hour and try again.']);
        }
        $errors = [];
        $configured = (string) config('app.setup_key', '');
        if ($configured === '' || str_starts_with($configured, 'CHANGE-ME') || strlen($configured) < 12) {
            $errors['setup_key'] = 'First set a private "setup_key" (12+ characters) in config.php, then try again.';
        } elseif (!hash_equals($configured, Request::raw('setup_key'))) {
            $errors['setup_key'] = 'The setup key does not match the one in config.php.';
        }
        $v = new Validator($input);
        $v->required('name', 'Please enter your name.')->length('name', 2, 100, 'Name');
        $v->required('email', 'Please enter your email address.')->email('email');
        $errors += $v->errors();
        $password = Request::raw('password');
        if (mb_strlen($password) < 12) {
            $errors['password'] = 'Use a password of at least 12 characters.';
        } elseif ($password !== Request::raw('password_confirm')) {
            $errors['password_confirm'] = 'The two passwords do not match.';
        }
        if ($errors) {
            http_response_code(422);
            return $this->setupView($input, $errors);
        }
        $id = Database::insert('admin_users', [
            'name' => $input['name'],
            'email' => $input['email'],
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        Auth::login($id);
        Session::flash('success', 'Your admin account is ready. Welcome to NoidaRankers!');
        redirect(url('/admin'));
        return '';
    }

    /** @param array<string, string> $input @param array<string, string> $errors */
    private function setupView(array $input, array $errors): string
    {
        $uploads = PUBLIC_PATH . '/uploads';
        $checks = [
            ['PHP 8.0 or newer (you have ' . PHP_VERSION . ')', PHP_VERSION_ID >= 80000],
            ['PDO MySQL extension', extension_loaded('pdo_mysql')],
            ['GD image extension', extension_loaded('gd')],
            ['WebP support in GD', function_exists('imagewebp')],
            ['mbstring extension', extension_loaded('mbstring')],
            ['fileinfo extension', extension_loaded('fileinfo')],
            ['public/uploads is writable', is_dir($uploads) && is_writable($uploads)],
            ['storage/ is writable', is_writable(STORAGE_PATH . '/cache') && is_writable(STORAGE_PATH . '/logs')],
            ['app.key in config.php is set', !Security::usingDefaultKey()],
        ];
        return View::render('admin/auth/setup', ['input' => $input, 'errors' => $errors, 'checks' => $checks, 'title' => 'Set up your admin account'], 'layouts/admin-auth');
    }

    public function loginForm(): string
    {
        if (!Auth::hasAdmin()) {
            redirect(url('/admin/setup'));
        }
        if (Auth::check()) {
            redirect(url('/admin'));
        }
        return View::render('admin/auth/login', ['email' => '', 'error' => '', 'title' => 'Sign in'], 'layouts/admin-auth');
    }

    public function login(): string
    {
        $email = Request::input('email');
        $result = Auth::attempt($email, Request::raw('password'));
        if (!$result['ok']) {
            http_response_code(str_starts_with($result['error'], 'Too many') ? 429 : 401);
            return View::render('admin/auth/login', ['email' => $email, 'error' => $result['error'], 'title' => 'Sign in'], 'layouts/admin-auth');
        }
        $intended = (string) Session::get('intended', '');
        Session::forget('intended');
        redirect(url(str_starts_with($intended, '/admin') ? $intended : '/admin'));
        return '';
    }

    public function logout(): void
    {
        Auth::logout();
        Session::start();
        Session::flash('success', 'You have been signed out.');
        redirect(url('/admin/login'));
    }
}
