<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;

final class AccountController extends AdminController
{
    public function edit(): string
    {
        return $this->admin('account', ['input' => Auth::user() ?? [], 'errors' => []], 'Your account');
    }

    public function update(): string
    {
        $user = Database::one('SELECT * FROM admin_users WHERE id = ?', [Auth::id()]);
        $input = ['name' => Request::input('name'), 'email' => mb_strtolower(Request::input('email'))];
        $v = new Validator($input);
        $v->required('name', 'Please enter your name.')->length('name', 2, 100, 'Name');
        $v->required('email', 'Please enter your email address.')->email('email');
        $errors = $v->errors();
        if (!password_verify(Request::raw('current_password'), (string) ($user['password_hash'] ?? ''))) {
            $errors['current_password'] = 'Your current password is incorrect.';
        }
        $new = Request::raw('new_password');
        if ($new !== '' && mb_strlen($new) < 12) {
            $errors['new_password'] = 'Use a new password of at least 12 characters.';
        } elseif ($new !== '' && $new !== Request::raw('new_password_confirm')) {
            $errors['new_password_confirm'] = 'The two new passwords do not match.';
        }
        $taken = (int) Database::value('SELECT COUNT(*) FROM admin_users WHERE email = ? AND id <> ?', [$input['email'], Auth::id()]);
        if ($taken > 0) {
            $errors['email'] = 'Another admin already uses this email address.';
        }
        if ($errors) {
            http_response_code(422);
            return $this->admin('account', ['input' => $input, 'errors' => $errors], 'Your account');
        }
        $data = $input;
        if ($new !== '') {
            $data['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
        }
        Database::update('admin_users', $data, ['id' => Auth::id()]);
        if ($new !== '') {
            \App\Core\Session::regenerate();
        }
        $this->success($new !== '' ? 'Account and password updated.' : 'Account updated.');
        redirect(url('/admin/account'));
        return '';
    }
}
