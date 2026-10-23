<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Session;
use App\Core\View;

abstract class AdminController extends Controller
{
    /** @param array<string, mixed> $data */
    protected function admin(string $template, array $data = [], string $title = 'Admin'): string
    {
        $data['title'] = $title;
        $data['user'] = Auth::user();
        $data['newLeads'] = (int) \App\Core\Database::value("SELECT COUNT(*) FROM leads WHERE status = 'new'");
        return View::render('admin/' . $template, $data, 'layouts/admin');
    }

    protected function success(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function error(string $message): void
    {
        Session::flash('error', $message);
    }

    protected function id(string $id): int
    {
        $value = (int) $id;
        if ($value <= 0) {
            throw new HttpException(404);
        }
        return $value;
    }
}
