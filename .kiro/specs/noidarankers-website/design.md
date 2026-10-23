# Design: NoidaRankers website

## 1. Architecture

A single front controller (`public/index.php`) boots the app and dispatches to controllers through a small router. There is no framework and no Composer; classes autoload from `app/` (`App\` namespace, PSR-4 style).

```
/ (project root)
├── .htaccess              Fallback when the document root is the project root: routes to public/, denies the rest
├── config.sample.php      Copy to config.php (git-ignored); the only file with credentials
├── database.sql           Schema + seed data (no CREATE DATABASE)
├── app/
│   ├── bootstrap.php      Config, error handling, autoloader, helpers
│   ├── helpers.php        e(), url(), asset(), setting(), icon(), csrf_field(), …
│   ├── routes.php         Route table
│   ├── Core/              App, Router, Request, View, Database, Session, Csrf, Auth, Validator,
│   │                      RateLimiter, Mailer, Settings, Seo, ImageProcessor, HtmlSanitizer,
│   │                      Paginator, Csv, Str, Security, Css
│   ├── Controllers/       Public controllers
│   ├── Controllers/Admin/ Admin controllers (ResourceController base for CRUD)
│   ├── Models/            Static repository classes (Lead, Post, Category, Service, Plan, …)
│   └── Views/             layouts/, partials/, pages/, admin/, errors/
├── public/                Web root
│   ├── index.php, .htaccess, favicon.ico, site.webmanifest
│   ├── assets/css|js|img  Versioned (?v=mtime), cached for 1 year
│   └── uploads/           Images only; script execution denied
├── storage/               logs/, cache/, sessions/ (denied)
└── cron/maintenance.php   Optional cleanup job (CLI only)
```

Request flow: `index.php` → `bootstrap.php` → `App::run()`:
1. HTTPS and canonical-host redirects (configurable), `.php` and trailing-slash 301s.
2. Session start (secure cookie params, custom save path with GC).
3. Global CSRF check on every POST.
4. Router match → middleware (`auth` for admin, admin IP allowlist) → controller.
5. Controller → models → `View::render()` (layout + page + partials) with output escaping.
6. Security headers + CSP nonce; HTML is `Cache-Control: no-cache`, admin is `no-store` + `X-Robots-Tag: noindex`.

## 2. Routes

| Method | Path | Handler |
|---|---|---|
| GET | `/` | HomeController@index |
| GET | `/services`, `/services/{slug}` | ServiceController@index/show |
| GET | `/pricing` | PricingController@index |
| GET | `/results`, `/results/{slug}` | CaseStudyController@index/show |
| GET | `/blog`, `/blog/page/{n}` | BlogController@index |
| GET | `/blog/category/{slug}`, `…/page/{n}` | BlogController@category |
| GET | `/blog/{slug}` | BlogController@show |
| GET | `/about`, `/contact`, `/free-seo-audit`, `/thank-you`, `/privacy-policy`, `/terms` | PageController |
| POST | `/contact`, `/free-seo-audit` | LeadController@contact/audit |
| POST | `/subscribe` | SubscribeController@store |
| GET | `/sitemap.xml`, `/robots.txt` | SeoController |
| GET/POST | `/admin/setup`, `/admin/login`; POST `/admin/logout` | Admin\AuthController |
| GET | `/admin` | Admin\DashboardController |
| * | `/admin/leads…` (list, show, status, notes, delete, export) | Admin\LeadsController |
| * | `/admin/{posts,categories,services,plans,testimonials,case-studies}…` | ResourceController subclasses |
| * | `/admin/subscribers…`, `/admin/settings`, `/admin/account`, POST `/admin/uploads/image`, POST `/admin/sample-content/delete` | Admin controllers |

Pagination uses clean paths (`/blog/page/2`). `/blog/page/1` redirects to `/blog`.

## 3. Data model (InnoDB, utf8mb4_unicode_ci)

| Table | Key columns |
|---|---|
| `settings` | `setting_key` PK, `setting_value` |
| `admin_users` | id, name, email (unique), password_hash, last_login_at/ip |
| `login_attempts` | email, ip, success, created_at (indexed by email/ip + time) |
| `rate_limits` | action, ip_hash (HMAC of IP), created_at |
| `leads` | type (`contact`/`audit`), name, phone, email, website, business_type, message, status (`new`/`contacted`/`won`/`lost`), source_page, ip, user_agent, is_sample, timestamps |
| `lead_notes` | lead_id FK (cascade), admin_user_id, note, created_at |
| `subscribers` | email (unique), status, ip, created_at |
| `services` | slug (unique), title, nav_title, summary, icon, tile_color, tile_size, intro, content (HTML), meta_title, meta_description, sort_order, status |
| `faqs` | service_id FK (cascade), question, answer, sort_order |
| `pricing_plans` | name, price_label, billing_period, description, features (one per line), cta_label, is_featured, sort_order, status |
| `testimonials` | author_name, author_role, company, quote, sort_order, status, is_sample |
| `case_studies` | slug, title, client_name, industry, location, summary, challenge, solution, results (HTML), metrics (`value | label` per line), services_used, image fields, meta, status, published_at, is_sample |
| `categories` | name, slug (unique), description |
| `posts` | title, slug (unique), excerpt, content (HTML), image, image_width/height/alt, category_id FK (set null), meta_title, meta_description, status, published_at, is_sample |

Indexed strings are at most VARCHAR(190) so unique keys fit older MySQL limits. Lists such as features and metrics are stored as plain text lines, so seeds need no JSON escaping.

## 4. Key components

- **Database:** a lazy PDO singleton. `ATTR_EMULATE_PREPARES=false`, and ints are bound as `PARAM_INT` so `LIMIT ?` works. Session `time_zone` matches PHP (`Asia/Kolkata`). `insert()`/`update()` helpers only take column names from code.
- **Settings:** `setting($key)` returns the DB value if it is non-empty, otherwise the `config.php` default. `is_placeholder()` detects `[...]` values; placeholders are skipped in JSON-LD, `tel:` and `wa.me` links.
- **Session & CSRF:** cookie `nr_session` (HttpOnly, SameSite=Lax, Secure on HTTPS, strict mode), saved to `storage/sessions` with its own GC. One random token per session is checked with `hash_equals`. On public forms, an expired token re-shows the form with the input kept, rather than losing the lead.
- **Spam controls:** a honeypot field (hidden off-screen, `tabindex=-1`) and a signed timestamp (`HMAC(app.key)`) give a minimum fill time. Either failure gives a silent fake success. `RateLimiter::hit(action, max, window)` counts rows in `rate_limits`, and old rows are cleaned up probabilistically or by cron.
- **Mailer:** PHP `mail()` or a built-in SMTP client (SSL/STARTTLS, AUTH LOGIN). CR/LF is stripped from headers, the subject is RFC 2047 encoded, the body is UTF-8 base64, and Reply-To is the lead's email.
- **Auth:** `password_hash(PASSWORD_DEFAULT)`, rehash on login, and a dummy verify for unknown emails (constant time). Lockout applies after N failures per email or 2N per IP within the window. On login the session ID is regenerated and the CSRF token rotated. Sessions have a 2-hour idle timeout and a user-agent binding. Logout is POST + CSRF.
- **Setup:** only when `admin_users` is empty. Requires a non-default `setup_key` (`hash_equals`), is rate-limited, and shows an environment check (PHP version, extensions, WebP, writable folders).
- **ImageProcessor:** checks the upload error code, `is_uploaded_file`, size, `finfo` MIME (jpeg/png/webp/gif), `getimagesize`, and a pixel limit. It applies EXIF orientation, re-encodes to WebP (q80; JPEG/PNG fallback), writes 1600/800/400-wide variants, and names files `uploads/Y/m/{slug}-{random}.webp`. Width and height are stored for layout stability.
- **HtmlSanitizer:** a DOMDocument allowlist. It removes script/style/iframe/form elements, `on*` attributes, `style` and unsafe URL schemes, turns `h1` into `h2`, and adds `rel="noopener noreferrer"` to `target=_blank` and `loading=lazy` to images.
- **Seo:** a per-request builder for title, description, canonical, robots, OG/Twitter and the JSON-LD `@graph`: Organization+LocalBusiness (`#organization`), WebSite, WebPage, BreadcrumbList, plus Service/FAQPage/Article from controllers.
- **Css:** concatenates and minifies the CSS parts each page needs, caches them in `storage/cache`, and inlines them in `<style nonce>`. There is no render-blocking CSS request.
- **ResourceController:** a field-definition-driven CRUD (list, create, edit, delete, validation, flash messages) shared by posts, categories, services, plans, testimonials and case studies. It has hooks for slugs, images, FAQs and sanitising.
- **Editor:** a contenteditable toolbar (paragraph, H2, H3, bold, italic, lists, quote, link, image upload, HTML source) that syncs to a textarea. Without JS, a plain textarea is used.

## 5. Front end

- Tokens are CSS custom properties for the palette in R2, with radii of 20/24/28px and a pill radius. Fluid type uses `clamp()`. The font stack is `system-ui, -apple-system, "Segoe UI", Roboto, …`.
- Bento grid: `grid-template-columns: repeat(4, 1fr)` at ≥1024px, 2 at ≥640px, otherwise 1. Tile sizes are `wide` (span 2) and `tall` (row span 2), with `grid-auto-flow: dense`. Service tiles use a stretched-link title (descriptive link text, no "Learn more").
- JS (`site.js`, deferred, ~3 KB) handles the mobile nav toggle, double-submit prevention, error-summary focus and Google Analytics loading. GA is only loaded when an ID is set, after the first interaction or 5 s.
- Floating buttons: WhatsApp (all viewports) and Call (≤768px), shown only when the numbers are real.
- Accessibility: skip link, labelled landmarks, `<details>` FAQ accordions, a linked error summary, `aria-invalid`, `aria-describedby`, `:focus-visible` rings, and reduced-motion support.

## 6. SEO details

- Title pattern `{Page} | NoidaRankers`, with descriptions of 120–160 characters. Canonical URL = `app.url` + path. Paginated pages are self-canonical.
- `robots.txt`: `Disallow: /admin`, plus the sitemap URL. Thank-you, 404 and empty category pages are `noindex`.
- The sitemap lists home, static pages, published services, case studies, categories with posts, and posts. `lastmod` comes from DB timestamps or template mtimes.

## 7. Security headers

- PHP sends: `Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-…' [+GA]; style-src 'self' 'nonce-…'; img-src 'self' data: [+GA]; connect-src 'self' [+GA]; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'`. Admin pages allow inline styles for the editor. HSTS is sent on HTTPS.
- `.htaccess` sends X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy and COOP. It also removes X-Powered-By and blocks dotfiles.
- `uploads/.htaccess` denies `*.php*`, `*.phtml` and `*.phar` and turns the PHP engine off. `app/`, `storage/` and `cron/` deny all.

## 8. Error handling

- Production hides errors and logs them to `storage/logs/php-error.log`. Uncaught exceptions render a 500 page, and a DB outage renders a 503 page.
- Validation errors are flashed with old input (PRG). A CSRF failure returns 419 on admin pages and re-shows the form on public forms. Rate limits return 429 with a message.

## 9. Testing strategy (local Apache 2.4 + PHP-FPM 8.4 + MariaDB 10.11)

- Lint every PHP file with PHP 8.2 and 8.4.
- Import `database.sql` into an empty DB, and import it again to prove re-import is safe.
- Run curl-based E2E scripts: all pages (status, one H1, unique titles, canonical, valid JSON-LD), redirects, 404, forms (valid, invalid, CSRF, honeypot, timing, rate limit, email spool), subscribe, admin (setup, lockout, login, CRUD with uploads, WebP variants, CSV), sitemap/robots, headers, compression, caching, upload execution block, and fallback document root mode.
- Run Lighthouse 13 (mobile) on home, a service page and a post, and check total byte weight. Take screenshots for a visual check.
