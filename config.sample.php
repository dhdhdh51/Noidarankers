<?php
/**
 * NoidaRankers configuration
 * -----------------------------------------------------------------------------
 * 1. Copy this file to "config.php" (same folder).
 * 2. Fill in your database details, site URL and contact details.
 * 3. config.php is the ONLY file that should contain passwords or keys.
 *
 * Values written like [YOUR PHONE] are placeholders. While a value is still a
 * placeholder it is shown as-is on the site but left out of links and
 * structured data. Phone, email, WhatsApp, address, social links and the
 * Google Analytics ID can also be changed later in Admin > Settings
 * (Admin values override the defaults below).
 */
return [
    'app' => [
        // 'production' hides errors from visitors; 'development' shows them.
        'env' => 'production',

        // Full public address of the site, without a trailing slash.
        'url' => 'https://noidarankers.in',

        // Long random secret used to sign form tokens.
        // Generate one with:  php -r "echo bin2hex(random_bytes(32));"
        'key' => 'CHANGE-ME-to-a-long-random-string',

        // One-time key needed to create the first admin account at /admin/setup.
        // Change it to any private value of 12+ characters before visiting setup.
        'setup_key' => 'CHANGE-ME',

        'timezone' => 'Asia/Kolkata',

        // Redirect http:// to https:// and send HSTS. Turn on once SSL works.
        'force_https' => true,

        // Redirect other host names (e.g. www.noidarankers.in) to the host in 'url'.
        'canonical_redirect' => true,

        // Set to true only behind Cloudflare / a load balancer, so the real
        // visitor IP and HTTPS status are read from forwarded headers.
        'trust_proxy' => false,

        // Optional: only allow these IP addresses to open /admin. Empty = any IP.
        'admin_ip_allowlist' => [],
    ],

    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'your_database_name',
        'user' => 'your_database_user',
        'pass' => 'your_database_password',
    ],

    // Default contact details (can be overridden in Admin > Settings).
    'site' => [
        'name' => 'NoidaRankers',
        'phone' => '[YOUR PHONE]',               // e.g. +91 XXXXX XXXXX
        'email' => '[YOUR EMAIL]',               // public contact email
        'whatsapp' => '[YOUR WHATSAPP NUMBER]',  // with country code, e.g. 91XXXXXXXXXX
        'notify_email' => '[YOUR EMAIL]',        // where new lead alerts are sent
    ],

    'mail' => [
        // 'mail' uses PHP mail() (works on most shared hosts).
        // 'smtp' is more reliable: use your mailbox's SMTP details.
        'transport' => 'mail',
        'from_email' => 'no-reply@noidarankers.in', // use an address on your own domain
        'from_name' => 'NoidaRankers Website',
        'smtp' => [
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',   // 'tls' (STARTTLS, port 587), 'ssl' (port 465) or ''
            'username' => '',
            'password' => '',
            'timeout' => 15,
        ],
    ],

    'security' => [
        'form_min_seconds' => 3,          // faster submissions are treated as bots
        'lead_rate_limit' => [6, 900],    // max form posts per IP per 15 minutes
        'subscribe_rate_limit' => [6, 3600],
        'login_max_attempts' => 5,        // per email; per IP the limit is double
        'login_lockout_minutes' => 15,
        'session_idle_minutes' => 120,    // admin auto-logout after inactivity
    ],

    'uploads' => [
        'max_mb' => 5,
        'max_width' => 1600,              // larger images are resized
        'webp_quality' => 80,
    ],
];
