<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Settings;

final class SettingsController extends AdminController
{
    /** Groups of [key => [label, type, help]]. */
    public static function groups(): array
    {
        return [
            'Contact details' => [
                'phone' => ['Phone number', 'tel', 'Shown on the site and used for the click-to-call button, e.g. +91 98XXX XXXXX.'],
                'whatsapp' => ['WhatsApp number', 'tel', 'Country code + number, digits only, e.g. 9198XXXXXXXX. Shows the floating WhatsApp button.'],
                'email' => ['Public email address', 'email', 'Shown on the contact page and in the footer.'],
                'notify_email' => ['Lead notification email', 'email', 'New contact and audit requests are emailed here.'],
                'opening_hours' => ['Opening hours', 'text', 'e.g. Mon–Sat, 10:00 am – 7:00 pm'],
            ],
            'Address (used on the site and in LocalBusiness schema)' => [
                'legal_name' => ['Business / legal name', 'text', ''],
                'address_street' => ['Street address', 'text', 'e.g. Office 123, Tower B'],
                'address_locality' => ['Sector / locality', 'text', 'e.g. Sector 62'],
                'address_city' => ['City', 'text', ''],
                'address_region' => ['State', 'text', ''],
                'address_postal' => ['PIN code', 'text', ''],
                'address_country' => ['Country code', 'text', 'Two letters, e.g. IN'],
                'google_maps_url' => ['Google Maps link', 'url', 'The "Share" link of your Google Business Profile.'],
                'latitude' => ['Latitude (optional)', 'text', 'e.g. 28.6270'],
                'longitude' => ['Longitude (optional)', 'text', 'e.g. 77.3726'],
            ],
            'Social profiles (full https:// links)' => [
                'social_facebook' => ['Facebook', 'url', ''],
                'social_instagram' => ['Instagram', 'url', ''],
                'social_linkedin' => ['LinkedIn', 'url', ''],
                'social_x' => ['X (Twitter)', 'url', ''],
                'social_youtube' => ['YouTube', 'url', ''],
            ],
            'Analytics and verification' => [
                'ga_id' => ['Google Analytics 4 measurement ID', 'text', 'Looks like G-XXXXXXXXXX. Leave empty to disable analytics.'],
                'gsc_verification' => ['Google Search Console verification code', 'text', 'Only the content="..." value of the HTML tag method.'],
            ],
            'Homepage results (use only real, verifiable numbers)' => [
                'stat1_value' => ['Result 1 value', 'text', 'e.g. 120%'],
                'stat1_label' => ['Result 1 label', 'text', ''],
                'stat2_value' => ['Result 2 value', 'text', ''],
                'stat2_label' => ['Result 2 label', 'text', ''],
                'stat3_value' => ['Result 3 value', 'text', ''],
                'stat3_label' => ['Result 3 label', 'text', ''],
                'stat4_value' => ['Result 4 value', 'text', ''],
                'stat4_label' => ['Result 4 label', 'text', ''],
            ],
            'Legal pages' => [
                'policies_updated' => ['Policies last updated', 'date', 'Shown on the privacy policy and terms pages.'],
            ],
        ];
    }

    public function edit(): string
    {
        $values = [];
        foreach (self::groups() as $fields) {
            foreach (array_keys($fields) as $key) {
                $values[$key] = setting($key);
            }
        }
        return $this->admin('settings', ['groups' => self::groups(), 'values' => $values, 'errors' => []], 'Site settings');
    }

    public function update(): string
    {
        $values = [];
        $errors = [];
        foreach (self::groups() as $fields) {
            foreach ($fields as $key => [$label, $type]) {
                $value = Request::input($key);
                if (mb_strlen($value) > 500) {
                    $errors[$key] = $label . ' is too long.';
                }
                if ($value !== '' && !is_placeholder($value)) {
                    if ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$key] = 'Please enter a valid email address.';
                    } elseif ($type === 'url' && !preg_match('#^https://[^\s]+$#i', $value)) {
                        $errors[$key] = 'Please enter a full link starting with https://';
                    } elseif ($type === 'date' && strtotime($value) === false) {
                        $errors[$key] = 'Please enter a valid date.';
                    }
                }
                $values[$key] = $value;
            }
        }
        if ($values['ga_id'] !== '' && !preg_match('/^G-[A-Z0-9]{4,20}$/i', $values['ga_id'])) {
            $errors['ga_id'] = 'A Google Analytics 4 ID looks like G-XXXXXXXXXX.';
        }
        foreach (['latitude' => 90, 'longitude' => 180] as $key => $limit) {
            if ($values[$key] !== '' && (!is_numeric($values[$key]) || abs((float) $values[$key]) > $limit)) {
                $errors[$key] = 'Please enter a decimal number, e.g. 28.6270.';
            }
        }
        if ($values['whatsapp'] !== '' && !is_placeholder($values['whatsapp']) && strlen((string) preg_replace('/\D/', '', $values['whatsapp'])) < 10) {
            $errors['whatsapp'] = 'Enter the full number with country code, e.g. 9198XXXXXXXX.';
        }
        if ($errors) {
            http_response_code(422);
            return $this->admin('settings', ['groups' => self::groups(), 'values' => $values, 'errors' => $errors], 'Site settings');
        }
        $values['ga_id'] = strtoupper($values['ga_id']);
        Settings::save($values);
        $this->success('Settings saved.');
        redirect(url('/admin/settings'));
        return '';
    }
}
