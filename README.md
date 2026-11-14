# NoidaRankers website

The website and admin panel for **NoidaRankers** (noidarankers.in), an SEO agency in Noida. It is built with plain PHP 8 and MySQL and runs on ordinary shared hosting (cPanel, Hostinger, etc.) or a VPS. There is no framework, no Composer and no build step.

- **Public site:** home, services (6 pages), pricing, results/case studies, blog with categories and pagination, about, contact, free SEO audit, privacy, terms, and a custom 404 page.
- **Lead capture:** contact form and free SEO audit form, with server-side validation, CSRF tokens, a honeypot, a minimum fill time and per-IP rate limiting. Every lead is saved and emailed to you. There is also a footer newsletter form, a floating WhatsApp button and a call button on mobile.
- **Admin panel (`/admin`):** first-run setup, secure login with lockout, dashboard, leads manager (search, status, notes, CSV export), blog editor with image upload (images are converted to WebP automatically), and managers for categories, services and FAQs, pricing, case studies, testimonials, subscribers and site settings.
- **SEO:** unique titles and descriptions, canonical URLs, Open Graph and Twitter tags, clean URLs, `sitemap.xml`, `robots.txt`, and JSON-LD (Organization + LocalBusiness, Service, FAQPage, Article, BreadcrumbList).

## Requirements

| | Minimum |
|---|---|
| PHP | 8.0 or newer (8.2+ recommended), with `pdo_mysql`, `gd` (with WebP), `mbstring` and `fileinfo` |
| Database | MySQL 5.7+ or MariaDB 10.3+ |
| Web server | Apache 2.4 or LiteSpeed with `.htaccess` and `mod_rewrite` (`mod_headers`, `mod_expires`, `mod_deflate`/`mod_brotli` are used when available) |

## Folder structure

```
config.sample.php   Copy to config.php and edit (the only file with passwords)
database.sql        Database tables + starter content
.htaccess           Only used if the domain points at this folder (see step 1)
app/                Application code: Core/, Controllers/, Models/, Views/ (private)
public/             The web root: index.php, .htaccess, assets/, uploads/
storage/            Logs, cache and sessions (private, must be writable)
cron/               Optional maintenance job
```

## Deployment, step by step

### 1. Upload the files

Upload everything in this repository except `.git`. You can do this with your hosting File Manager (upload a ZIP and extract it) or with FTP.

- **Best option:** put the project in a folder above the web root (for example `/home/USER/noidarankers/`) and point the domain's document root at `noidarankers/public`. In cPanel, use Domains → Manage → Document Root.
- **If you cannot change the document root:** upload the project straight into `public_html/`. The root `.htaccess` sends every request into `/public` and blocks access to `app/`, `storage/`, `cron/`, `config.php` and `database.sql`.

### 2. Create the database and import the SQL

1. In cPanel → **MySQL Databases**, create a database and a user, then add the user to the database with **All Privileges**.
2. Open **phpMyAdmin**, select the new database, and go to **Import** → choose `database.sql` → **Go**.

The import creates all tables plus starter content. Re-importing later is safe, because existing rows are kept.

### 3. Create `config.php`

Copy `config.sample.php` to `config.php` in the same folder, then edit it:

| Setting | What to enter |
|---|---|
| `app.url` | `https://noidarankers.in` (no trailing slash) |
| `app.key` | A long random string. Generate one at random.org or run `php -r "echo bin2hex(random_bytes(32));"` |
| `app.setup_key` | Any private value of 12+ characters. You need it once, in step 5 |
| `db.*` | The database name, user and password from step 2. The host is usually `localhost` |
| `site.phone`, `site.email`, `site.whatsapp`, `site.notify_email` | Your contact details. `whatsapp` is the country code plus the number, e.g. `9198XXXXXXXX`. `notify_email` receives new leads |
| `mail.*` | Keep `'transport' => 'mail'`, or switch to `'smtp'` and enter your mailbox's SMTP details (recommended for reliable delivery) |
| `app.force_https` | Keep `true` once SSL is active. Install the free SSL from your host first |

### 4. Check folder permissions

These folders must be writable by PHP (usually `755`; use `775` if uploads fail): `public/uploads/`, `storage/logs/`, `storage/cache/` and `storage/sessions/`.

### 5. Create your admin account

1. Open `https://noidarankers.in/admin/setup`.
2. The page shows a server check, which should be all green.
3. Enter the `setup_key` from `config.php`, your name, your email and a password of 12+ characters.

Setup switches itself off once the first admin exists. Later, sign in at `https://noidarankers.in/admin`.

### 6. Fill in your details

On the admin dashboard, the **Launch checklist** lists everything that still uses a placeholder:

- **Settings:** phone, WhatsApp, emails, full address and PIN code, opening hours, Google Maps link, social links, the Google Analytics 4 ID, Search Console verification, and the four homepage result numbers.
- **Pricing:** replace `[₹ PRICE]` and the `[X]` placeholders in the plan features.
- **Sample content:** testimonials, case studies, the three starter blog posts and one lead are marked **SAMPLE**. Edit them (saving removes the SAMPLE label), or click **Delete all sample content** on the dashboard.

Values in `[BRACKETS]` are shown on the site as placeholders, but they are left out of links and structured data.

### 7. Optional: cron job

The site cleans up after itself, but a daily cron keeps things tidy. In cPanel → **Cron Jobs**, set it to run **once per day**:

```
/usr/local/bin/php /home/USER/noidarankers/cron/maintenance.php >/dev/null 2>&1
```

Adjust the PHP path and folder to match your host.

### 8. After launch

1. Submit `https://noidarankers.in/sitemap.xml` in Google Search Console.
2. Send a test enquiry through `/contact` and confirm the email arrives. If it does not, switch to SMTP in `config.php`. Check `storage/logs/mail.log` for errors.
3. Test with Google's Rich Results Test and PageSpeed Insights.

## Day-to-day use

| Task | Where |
|---|---|
| See and manage leads, add notes, export CSV | Admin → Leads |
| Write a blog post (with images) | Admin → Blog posts → Add. A future publish date schedules the post |
| Change prices | Admin → Pricing |
| Edit service pages and their FAQs | Admin → Services |
| Add a case study or testimonial | Admin → Case studies / Testimonials |
| Change contact details, address or analytics | Admin → Settings |
| Change your password | Admin → Your account |

Uploaded images are checked, resized to 1600px wide, converted to WebP, and given 800px and 400px versions for fast mobile loading.

## Security notes

- All database queries use PDO prepared statements, and all output is escaped. Blog and service HTML is sanitised against an allowlist.
- Every form has a CSRF token. Sessions use HttpOnly, SameSite and Secure cookies with strict mode.
- Admin login uses `password_hash()`, regenerates the session ID, locks out after 5 failed attempts per email (10 per IP) for 15 minutes, and signs you out after 2 hours of inactivity.
- Security headers are sent on every page: a nonce-based Content-Security-Policy, X-Frame-Options, X-Content-Type-Options, Referrer-Policy and Permissions-Policy, plus HSTS on HTTPS.
- PHP can never run inside `public/uploads/`. Application folders are not web-accessible.
- To restrict `/admin` to your own IP address, set `app.admin_ip_allowlist` in `config.php`.

## Troubleshooting

| Problem | Fix |
|---|---|
| "Almost there – config.php is missing" | Create `config.php` (step 3) |
| 500 Internal Server Error right after upload | Your host may not allow `Options` in `.htaccess`. Delete the `Options …` lines in both `.htaccess` files |
| Pages other than the home page return 404 | `mod_rewrite` / `.htaccess` is not enabled. Ask your host, or check that `.htaccess` files were uploaded (they are hidden files) |
| Site installed in a sub-folder | Set `app.url` to include the folder, set `RewriteBase /folder/` in `public/.htaccess`, and update the `ErrorDocument` path |
| "Service temporarily unavailable" | The database details in `config.php` are wrong, or the SQL was not imported |
| Image upload fails | Make `public/uploads/` writable and check that GD is enabled. Large photos must be under 5 MB and 25 megapixels |
| Lead emails not arriving | Use SMTP in `config.php`, use a `from_email` on your own domain, and check `storage/logs/mail.log` |
| Behind Cloudflare: redirect loop | Set `app.trust_proxy` to `true` |
