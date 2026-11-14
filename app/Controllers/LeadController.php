<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Security;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Lead;

/** Handles the contact form, the free SEO audit form and newsletter sign-ups. */
final class LeadController extends Controller
{
    public function contact(): void
    {
        $this->handleLead('contact', '/contact');
    }

    public function audit(): void
    {
        $this->handleLead('audit', '/free-seo-audit');
    }

    private function handleLead(string $type, string $formPath): void
    {
        $fields = ['name', 'phone', 'email', 'website', 'business_type', 'message'];
        $input = [];
        foreach ($fields as $field) {
            $input[$field] = Request::input($field);
        }
        $input['email'] = mb_strtolower($input['email']);
        $back = url($formPath) . '#lead-form';

        // Expired or missing CSRF token: keep the visitor's input and ask them to resend.
        if (!Csrf::verify(Csrf::fromRequest())) {
            Session::flash('old', $input);
            Session::flash('errors', ['form' => 'For your security, please check your details and press the button again.']);
            redirect($back);
        }
        // Bots: honeypot filled in, missing/forged timer, or submitted too quickly. Pretend success.
        if ($this->looksLikeBot()) {
            redirect(url('/thank-you') . '?type=' . $type);
        }
        [$max, $window] = $this->limit('security.lead_rate_limit', [6, 900]);
        if (!RateLimiter::hit('lead', $max, $window)) {
            Session::flash('old', $input);
            Session::flash('errors', ['form' => 'You have sent several requests in a short time. Please wait a few minutes, or call or WhatsApp us instead.']);
            redirect($back);
        }

        $v = new Validator($input);
        $v->required('name', 'Please enter your name.')->length('name', 2, 100, 'Your name');
        $v->required('phone', 'Please enter your phone number.')->phone('phone');
        $v->required('email', 'Please enter your email address.')->email('email');
        if ($type === 'audit') {
            $v->required('website', 'Please enter the website you would like us to audit.');
        }
        $v->url('website');
        $v->required('business_type', 'Please choose your type of business.')
            ->in('business_type', Lead::BUSINESS_TYPES, 'Please choose your type of business from the list.');
        if ($type === 'contact') {
            $v->required('message', 'Please tell us how we can help.');
        }
        $v->length('message', 0, 2000, 'Your message');

        if ($v->fails()) {
            Session::flash('old', $input);
            Session::flash('errors', $v->errors());
            redirect($back);
        }

        $data = $v->data();
        $id = Lead::create([
            'type' => $type,
            'status' => 'new',
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => mb_strtolower($data['email']),
            'website' => $data['website'],
            'business_type' => $data['business_type'],
            'message' => $data['message'],
            'source_page' => $formPath,
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
        $this->notifyAdmin($id, $type, $data);

        Session::flash('lead_name', $data['name']);
        redirect(url('/thank-you') . '?type=' . $type);
    }

    /** @param array<string, string> $data */
    private function notifyAdmin(int $id, string $type, array $data): void
    {
        $to = setting('notify_email');
        $label = $type === 'audit' ? 'Free SEO audit request' : 'Contact form message';
        $body = $label . " received on " . date('j M Y, g:i a') . "\n\n"
            . 'Name: ' . $data['name'] . "\n"
            . 'Phone: ' . $data['phone'] . "\n"
            . 'Email: ' . $data['email'] . "\n"
            . 'Website: ' . ($data['website'] !== '' ? $data['website'] : '-') . "\n"
            . 'Business type: ' . $data['business_type'] . "\n\n"
            . "Message:\n" . ($data['message'] !== '' ? $data['message'] : '-') . "\n\n"
            . 'View and update this lead: ' . absolute_url('/admin/leads/' . $id) . "\n";
        Mailer::send($to, '[' . site_name() . '] ' . $label . ' from ' . $data['name'], $body, $data['email']);
    }

    public function subscribe(): void
    {
        $email = mb_strtolower(Request::input('email'));
        $return = safe_return_path(Request::input('return'), '/');
        $back = url($return) . '#newsletter';

        if (!Csrf::verify(Csrf::fromRequest())) {
            Session::flash('old', ['newsletter_email' => $email]);
            Session::flash('newsletter_error', 'For your security, please press Subscribe again.');
            redirect($back);
        }
        if ($this->looksLikeBot()) {
            Session::flash('newsletter_success', 'Thanks! You are subscribed to our monthly SEO tips.');
            redirect($back);
        }
        [$max, $window] = $this->limit('security.subscribe_rate_limit', [6, 3600]);
        if (!RateLimiter::hit('subscribe', $max, $window)) {
            Session::flash('newsletter_error', 'Too many attempts. Please try again later.');
            redirect($back);
        }
        $v = new Validator(['email' => $email]);
        $v->required('email', 'Please enter your email address.')->email('email');
        if ($v->fails()) {
            Session::flash('old', ['newsletter_email' => $email]);
            Session::flash('newsletter_error', $v->errors()['email']);
            redirect($back);
        }
        Database::run(
            "INSERT INTO subscribers (email, status, source_page, ip) VALUES (?, 'subscribed', ?, ?)
             ON DUPLICATE KEY UPDATE status = 'subscribed'",
            [$email, $return, Request::ip()]
        );
        Session::flash('newsletter_success', 'Thanks! You are subscribed to our monthly SEO tips.');
        redirect($back);
    }

    private function looksLikeBot(): bool
    {
        if (Request::input('company_fax') !== '') {
            return true;
        }
        $age = Security::formAge(Request::input('_t'));
        if ($age === null) {
            return true;
        }
        $min = max(0, (int) config('security.form_min_seconds', 3));
        return $age < $min;
    }

    /** @param array{0:int,1:int} $default @return array{0:int,1:int} */
    private function limit(string $key, array $default): array
    {
        $value = config($key, $default);
        if (!is_array($value) || count($value) !== 2) {
            return $default;
        }
        return [max(1, (int) $value[0]), max(60, (int) $value[1])];
    }
}
