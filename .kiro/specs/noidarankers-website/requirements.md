# Requirements: NoidaRankers website

NoidaRankers (noidarankers.in) is an SEO agency based in Noida, India. The site is a production PHP 8 + MySQL application with a public marketing site and an admin panel. It must run on ordinary shared/VPS hosting (Apache or LiteSpeed with `.htaccess`, PHP 8.0+, MySQL 5.7+/MariaDB 10.3+, phpMyAdmin, cron).

Acceptance criteria use EARS notation ("WHEN … THEN the system SHALL …").

## R1. Platform and configuration
**User story:** As the owner, I want to deploy on cheap hosting by uploading files, importing one SQL file and editing one config file.

1. The system SHALL use PHP 8.0+ with no framework and no Composer dependencies, in an MVC-style structure (front controller, router, controllers, models, views).
2. The system SHALL use MySQL/MariaDB through PDO with prepared statements only (native prepares, exceptions on error, utf8mb4).
3. The system SHALL read DB credentials, site URL, phone, email, WhatsApp number, mail and security options only from `config.php`. A `config.sample.php` SHALL be provided, and `config.php` SHALL be git-ignored.
4. The system SHALL ship `database.sql` (schema + seed data) that imports cleanly through phpMyAdmin into an existing empty database (no `CREATE DATABASE`).
5. WHEN `config.php` is missing THEN the system SHALL show a setup message instead of a PHP error.
6. The system SHALL work with the document root pointing at `public/` (recommended) or at the project root (fallback through a root `.htaccess` that hides application files).

## R2. Design system
1. The system SHALL use a white background, text `#0f172a`, one accent `#2563eb`, tiles `#dbeafe`/`#1e3a8a`, `#fef3c7`/`#78350f`, `#dcfce7`/`#14532d`, `#f1f5f9`, and a dark tile `#0f172a` with white text.
2. The system SHALL use the system font stack only. No web fonts and no icon fonts; icons SHALL be inline SVG.
3. Buttons SHALL be rounded pills, tiles SHALL use 20–28px radii, and headings SHALL be bold.
4. The services Bento grid SHALL show 4 columns on desktop, 2 on tablet and 1 on mobile.
5. The site SHALL be mobile-first and accessible: semantic HTML, real links and buttons, alt text, visible focus states, and text contrast of at least 4.5:1.
6. The site SHALL NOT use React, jQuery, CSS frameworks or sliders.

## R3. Public pages
1. Home SHALL contain: the hero with H1 "Rank higher on Google. Get more customers from Noida.", Bento services, results, how it works (Audit, Optimise, Rank), pricing preview, testimonials from the DB, the latest 3 blog posts, and a final CTA band.
2. Services SHALL have an index page and one page per service (Local SEO, Technical SEO, Content Writing, Link Building, Google Business Profile, SEO Audit), each with its own content, FAQ and CTA, loaded from the DB.
3. Pricing SHALL show 3 plans (Starter, Growth, Pro) loaded from the DB.
4. Results SHALL have a case-study list page and detail pages loaded from the DB.
5. Blog SHALL have a paginated list, a category filter, and a post page with related posts.
6. The site SHALL have About, Contact, Free SEO Audit, Thank-you, Privacy Policy and Terms pages, and a custom 404 page that returns HTTP 404.
7. WHEN a draft, missing or unpublished item is requested THEN the system SHALL return the 404 page.

## R4. Lead capture and engagement
1. The Contact form and the Free SEO Audit form SHALL collect name, phone, email, website URL, business type and message.
2. WHEN a form is submitted THEN the system SHALL validate it server-side, verify the CSRF token, reject bots through a honeypot field and a minimum fill time, and rate-limit by IP.
3. WHEN validation fails THEN the system SHALL redisplay the form with field-level errors and the user's input preserved.
4. WHEN a valid lead is submitted THEN the system SHALL save it with status `new`, email a notification to the admin, and redirect to a thank-you page (Post/Redirect/Get).
5. IF sending the email fails THEN the lead SHALL still be saved and the failure SHALL be logged.
6. The site SHALL show a floating WhatsApp button and, on mobile, a click-to-call button, with numbers taken from config/settings. They SHALL be hidden while the number is still a placeholder.
7. The footer SHALL contain a newsletter form that stores unique emails in the DB, with CSRF, honeypot and rate limiting.

## R5. Admin panel (`/admin`)
1. WHEN no admin user exists THEN `/admin/setup` SHALL allow creating one, protected by the `setup_key` from `config.php`. Once an admin exists, setup SHALL be unavailable.
2. Login SHALL use `password_hash`/`password_verify`, regenerate the session ID on login, support logout, and lock out after repeated failures per email and per IP.
3. All admin routes except login and setup SHALL require authentication. Sessions SHALL expire after inactivity.
4. The dashboard SHALL show counts of new leads, total leads, blog posts and subscribers.
5. The leads manager SHALL support list, search, status filter (new, contacted, won, lost), detail view, status change, notes, and CSV export.
6. The blog manager SHALL support create, edit and delete with title, slug, excerpt, content (simple editor), featured image upload, category, meta title, meta description, and draft/published status.
7. The system SHALL provide CRUD managers for pricing plans, testimonials, case studies, services (with FAQs) and blog categories, plus a subscribers list with CSV export.
8. Site settings SHALL allow editing phone, email, WhatsApp, address, social links and the Google Analytics ID. Settings saved in admin SHALL override `config.php` defaults.

## R6. SEO
1. Every page SHALL have a unique title and meta description, a canonical URL, and Open Graph and Twitter tags.
2. URLs SHALL be clean (no `.php`). WHEN a `.php` URL or a trailing-slash URL is requested THEN the system SHALL 301-redirect to the clean URL.
3. `/sitemap.xml` SHALL be generated from published content, and `/robots.txt` SHALL reference it and block `/admin`.
4. The site SHALL output JSON-LD: Organization + LocalBusiness (Noida) on all pages, Service on service pages, FAQPage where FAQs are shown, Article on posts and case studies, and BreadcrumbList on inner pages.
5. Each page SHALL have exactly one H1, a correct heading hierarchy, visible breadcrumbs on inner pages, and internal links between services, posts and case studies.
6. Thank-you, admin and empty listing pages SHALL be `noindex`.

## R7. Performance
1. Mobile Lighthouse scores SHALL target 95+ for Performance, Accessibility, Best Practices and SEO.
2. The homepage SHALL weigh under 200 KB in total.
3. CSS SHALL be inlined in the page, and all JS SHALL be deferred.
4. Images SHALL be lazy-loaded (except the main image) and SHALL have width/height attributes.
5. WHEN an image is uploaded THEN the system SHALL resize it and convert it to WebP (with a fallback when WebP is unavailable).
6. `.htaccess` SHALL enable brotli/gzip compression and long-lived caching for static assets.

## R8. Security
1. All SQL SHALL use PDO prepared statements, and all output SHALL be escaped. Rich content SHALL be sanitised against an allowlist.
2. Every POST form SHALL carry a CSRF token that is verified server-side.
3. Session cookies SHALL be HttpOnly, SameSite=Lax, Secure on HTTPS, with strict mode.
4. Uploads SHALL be validated (MIME by content, size, dimensions), re-encoded, randomly named, and stored in a folder where scripts cannot run.
5. Responses SHALL send CSP (nonce-based), X-Frame-Options, X-Content-Type-Options, Referrer-Policy and Permissions-Policy, plus HSTS on HTTPS.
6. Application folders (`app/`, `storage/`, `cron/`, `config.php`, `database.sql`) SHALL NOT be web-accessible, and admin responses SHALL be `noindex` and uncached.
7. No credentials SHALL appear in code outside `config.php`.

## R9. Content rules
1. The system SHALL NOT contain invented clients, statistics or reviews.
2. Unknown facts SHALL use clearly marked placeholders (`[X]%`, `[YOUR PHONE]`, `[YOUR EMAIL]`, `[₹ PRICE]`) that are editable from admin or `config.php`.
3. Placeholder values SHALL be omitted from structured data and from `tel:`/WhatsApp links.
4. Seed rows SHALL be marked SAMPLE, and the admin SHALL be able to delete all sample content in one action.

## R10. Deliverables and verification
1. The repository SHALL include the complete code, `database.sql`, `config.sample.php`, `.htaccess` files, an optional cron script, and a README with step-by-step deployment instructions.
2. Before handover, every form, the admin flow, the sitemap, uploads, security headers and Lighthouse scores SHALL be tested on Apache + PHP-FPM + MariaDB, and remaining owner inputs SHALL be listed.
