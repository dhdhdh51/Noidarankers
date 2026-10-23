-- =============================================================================
-- NoidaRankers - database schema and starter content
-- -----------------------------------------------------------------------------
-- Import this file into an EMPTY MySQL 5.7+ / MariaDB 10.3+ database
-- (phpMyAdmin > select your database > Import > choose database.sql > Go).
-- It does not create the database itself, so create that first in your
-- hosting panel. Re-importing is safe: existing tables and rows are kept.
--
-- Rows marked SAMPLE (testimonials, case studies, blog posts, one lead) are
-- examples. Delete them one by one, or all at once from the admin dashboard.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(64) NOT NULL,
  `setting_value` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `last_login_at` DATETIME NULL DEFAULT NULL,
  `last_login_ip` VARCHAR(45) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(190) NOT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_email` (`email`, `created_at`),
  KEY `idx_login_attempts_ip` (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` VARCHAR(40) NOT NULL,
  `ip_hash` CHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rate_limits_lookup` (`action`, `ip_hash`, `created_at`),
  KEY `idx_rate_limits_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('contact','audit') NOT NULL DEFAULT 'contact',
  `status` ENUM('new','contacted','won','lost') NOT NULL DEFAULT 'new',
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `website` VARCHAR(255) NOT NULL DEFAULT '',
  `business_type` VARCHAR(80) NOT NULL DEFAULT '',
  `message` TEXT NULL,
  `source_page` VARCHAR(255) NOT NULL DEFAULT '',
  `ip` VARCHAR(45) NOT NULL DEFAULT '',
  `user_agent` VARCHAR(255) NOT NULL DEFAULT '',
  `is_sample` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_leads_status` (`status`, `created_at`),
  KEY `idx_leads_created` (`created_at`),
  KEY `idx_leads_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lead_notes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lead_id` INT UNSIGNED NOT NULL,
  `admin_user_id` INT UNSIGNED NULL DEFAULT NULL,
  `note` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lead_notes_lead` (`lead_id`, `created_at`),
  CONSTRAINT `fk_lead_notes_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `subscribers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(190) NOT NULL,
  `status` ENUM('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
  `source_page` VARCHAR(255) NOT NULL DEFAULT '',
  `ip` VARCHAR(45) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscribers_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `services` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(120) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `nav_title` VARCHAR(60) NOT NULL,
  `summary` VARCHAR(255) NOT NULL DEFAULT '',
  `icon` VARCHAR(30) NOT NULL DEFAULT 'spark',
  `tile_color` ENUM('blue','amber','green','slate','dark') NOT NULL DEFAULT 'slate',
  `tile_size` ENUM('normal','wide','tall') NOT NULL DEFAULT 'normal',
  `intro` TEXT NULL,
  `content` MEDIUMTEXT NULL,
  `meta_title` VARCHAR(70) NOT NULL DEFAULT '',
  `meta_description` VARCHAR(170) NOT NULL DEFAULT '',
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('published','draft') NOT NULL DEFAULT 'published',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_services_slug` (`slug`),
  KEY `idx_services_order` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `faqs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_id` INT UNSIGNED NOT NULL,
  `question` VARCHAR(255) NOT NULL,
  `answer` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_faqs_service` (`service_id`, `sort_order`),
  CONSTRAINT `fk_faqs_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pricing_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(60) NOT NULL,
  `price_label` VARCHAR(40) NOT NULL DEFAULT '[₹ PRICE]',
  `billing_period` VARCHAR(40) NOT NULL DEFAULT 'per month',
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `features` TEXT NULL,
  `cta_label` VARCHAR(40) NOT NULL DEFAULT 'Get started',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('published','draft') NOT NULL DEFAULT 'published',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pricing_order` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `author_name` VARCHAR(100) NOT NULL,
  `author_role` VARCHAR(100) NOT NULL DEFAULT '',
  `company` VARCHAR(120) NOT NULL DEFAULT '',
  `quote` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('published','draft') NOT NULL DEFAULT 'published',
  `is_sample` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_testimonials_order` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `case_studies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(160) NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `client_name` VARCHAR(120) NOT NULL DEFAULT '',
  `industry` VARCHAR(80) NOT NULL DEFAULT '',
  `location` VARCHAR(80) NOT NULL DEFAULT '',
  `summary` VARCHAR(300) NOT NULL DEFAULT '',
  `challenge` MEDIUMTEXT NULL,
  `solution` MEDIUMTEXT NULL,
  `results` MEDIUMTEXT NULL,
  `metrics` TEXT NULL,
  `services_used` VARCHAR(255) NOT NULL DEFAULT '',
  `image` VARCHAR(255) NOT NULL DEFAULT '',
  `image_width` INT UNSIGNED NOT NULL DEFAULT 0,
  `image_height` INT UNSIGNED NOT NULL DEFAULT 0,
  `image_alt` VARCHAR(190) NOT NULL DEFAULT '',
  `meta_title` VARCHAR(70) NOT NULL DEFAULT '',
  `meta_description` VARCHAR(170) NOT NULL DEFAULT '',
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('published','draft') NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL DEFAULT NULL,
  `is_sample` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_case_studies_slug` (`slug`),
  KEY `idx_case_studies_list` (`status`, `sort_order`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `excerpt` VARCHAR(300) NOT NULL DEFAULT '',
  `content` MEDIUMTEXT NULL,
  `image` VARCHAR(255) NOT NULL DEFAULT '',
  `image_width` INT UNSIGNED NOT NULL DEFAULT 0,
  `image_height` INT UNSIGNED NOT NULL DEFAULT 0,
  `image_alt` VARCHAR(190) NOT NULL DEFAULT '',
  `category_id` INT UNSIGNED NULL DEFAULT NULL,
  `meta_title` VARCHAR(70) NOT NULL DEFAULT '',
  `meta_description` VARCHAR(170) NOT NULL DEFAULT '',
  `status` ENUM('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL DEFAULT NULL,
  `is_sample` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_posts_slug` (`slug`),
  KEY `idx_posts_published` (`status`, `published_at`),
  KEY `idx_posts_category` (`category_id`, `status`, `published_at`),
  CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- Starter content
-- -----------------------------------------------------------------------------

-- categories
INSERT IGNORE INTO `categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Local SEO', 'local-seo', 'Practical guides to ranking in Google Maps and local search in Noida and Delhi NCR.'),
(2, 'Technical SEO', 'technical-seo', 'Site speed, crawling, indexing and the technical foundations of good rankings.'),
(3, 'Content Marketing', 'content-marketing', 'How to plan and write content that ranks and turns readers into enquiries.'),
(4, 'Google Business Profile', 'google-business-profile', 'Tips for getting more calls and direction requests from your Google Business Profile.');

-- services
INSERT IGNORE INTO `services` (`id`, `slug`, `title`, `nav_title`, `summary`, `icon`, `tile_color`, `tile_size`, `intro`, `content`, `meta_title`, `meta_description`, `sort_order`, `status`) VALUES
(1, 'local-seo', 'Local SEO Services in Noida', 'Local SEO', 'Appear in the Google Map Pack and local results when people near you search for what you sell.', 'map-pin', 'blue', 'wide', 'Most customers search on Google before they call, visit or book. Our local SEO service helps your business show up when people in Noida, Greater Noida and the rest of Delhi NCR search for your services on Google Search and Google Maps.', '<h2>What our local SEO service includes</h2>
<ul>
<li><strong>Local keyword research:</strong> the real phrases people use, such as "dentist near me", "CA in Sector 62" or "best coaching in Noida".</li>
<li><strong>Google Business Profile optimisation:</strong> categories, services, photos, posts and Q&amp;A set up to match those searches.</li>
<li><strong>Location and service pages:</strong> helpful pages for each service and area you cover, written for people first.</li>
<li><strong>Citations and NAP consistency:</strong> your name, address and phone number listed the same way on trusted directories.</li>
<li><strong>Review strategy:</strong> a simple, policy-safe process for asking happy customers for reviews and replying to every one.</li>
<li><strong>Local links:</strong> mentions from local associations, events, suppliers and Noida news and community sites.</li>
<li><strong>Clear monthly reporting:</strong> calls, direction requests, website enquiries and rankings, explained in plain language.</li>
</ul>
<h2>How Google ranks local businesses</h2>
<p>Google says local results are based mainly on three things: <strong>relevance</strong> (how well your profile and website match the search), <strong>distance</strong> (how close you are to the person searching) and <strong>prominence</strong> (how well known and trusted your business is, based on reviews, links and mentions). We cannot change where your office is, so we focus on relevance and prominence, which you can improve.</p>
<h2>Who local SEO is for</h2>
<p>Local SEO works best for businesses that serve customers in a specific area: clinics and dentists, restaurants and cafés, coaching institutes, real estate agents, salons and gyms, home-service providers, CAs, lawyers and other professional firms in Noida and nearby cities.</p>
<h2>What to expect</h2>
<p>Local SEO builds up over time. Quick wins such as fixing your Google Business Profile can help within weeks, while reviews, links and content keep adding value month after month. We will always tell you honestly what is working, and we never promise a guaranteed number one ranking, because nobody can.</p>', 'Local SEO Services in Noida | NoidaRankers', 'Local SEO services in Noida to help your business show up in Google Maps and local search. Google Business Profile, location pages, citations and reviews.', 1, 'published'),
(2, 'technical-seo', 'Technical SEO Services', 'Technical SEO', 'Fix the speed, crawling and indexing problems that stop good pages from ranking.', 'gear', 'amber', 'normal', 'Great content cannot rank if Google cannot crawl it, understand it or load it quickly. Technical SEO makes sure your website is fast, secure and easy for search engines to read, so every other part of your SEO works harder.', '<h2>What we check and fix</h2>
<ul>
<li><strong>Crawling and indexing:</strong> robots.txt, XML sitemaps, noindex tags, canonical tags and Google Search Console coverage reports.</li>
<li><strong>Page speed and Core Web Vitals:</strong> image compression, caching, script loading and server response time, measured on real mobile devices.</li>
<li><strong>Mobile experience:</strong> layouts, tap targets and fonts that work well on the phones your customers use.</li>
<li><strong>Site structure and internal links:</strong> a clear hierarchy so important pages get found and receive authority.</li>
<li><strong>Structured data:</strong> schema markup for your business, services, articles, FAQs and breadcrumbs.</li>
<li><strong>Redirects and errors:</strong> broken links, redirect chains, 404 pages and HTTPS issues.</li>
<li><strong>Duplicate and thin content:</strong> URL parameters, near-identical pages and pages that add little value.</li>
</ul>
<h2>How we work</h2>
<p>We start with a full crawl of your site and a review of your Google Search Console data. You get a prioritised list of issues, ranked by impact and effort. We can fix the issues ourselves, or work alongside your developer with clear, step-by-step tickets.</p>
<h2>Platforms we work with</h2>
<p>WordPress, Shopify, Wix, Webflow, custom PHP sites and modern JavaScript frameworks. Every platform has its own quirks, and we adapt our recommendations to what your site can actually support.</p>
<h2>Why it matters</h2>
<p>Technical problems are often invisible to visitors but costly in search. A single wrong setting can hide a whole section of your website from Google. Fixing the foundations first means your content and links can deliver their full value.</p>', 'Technical SEO Services for Faster, Crawlable Sites', 'Technical SEO services: site speed, Core Web Vitals, crawling, indexing, structured data and redirects fixed so Google can find and rank your pages.', 2, 'published'),
(3, 'google-business-profile', 'Google Business Profile Management in Noida', 'Google Business Profile', 'Turn your Google Business Profile into a steady source of calls, visits and reviews.', 'store', 'green', 'normal', 'Your Google Business Profile is often the first thing customers see when they search for your business or your services. We set it up properly, keep it active and help you earn more reviews, so more of those searches turn into calls and visits.', '<h2>What we manage for you</h2>
<ul>
<li><strong>Setup and verification help:</strong> claiming, creating or recovering your profile and guiding you through Google’s verification steps.</li>
<li><strong>Categories and services:</strong> the right primary and secondary categories, plus a complete list of your services and products.</li>
<li><strong>Photos and updates:</strong> regular, genuine photos and Google posts that show what your business does.</li>
<li><strong>Questions and answers:</strong> the questions customers actually ask, answered clearly on your profile.</li>
<li><strong>Reviews:</strong> a simple way for happy customers to leave a review, and professional replies to every review, good or bad.</li>
<li><strong>Spam and accuracy checks:</strong> monitoring for wrong edits to your profile and reporting fake listings that compete unfairly.</li>
<li><strong>Performance tracking:</strong> calls, website clicks, direction requests and search terms, reported every month.</li>
</ul>
<h2>Why your profile matters</h2>
<p>For searches with local intent, Google often shows a map with three businesses before any normal results. Being in that group can bring in a large share of calls and visits. A complete, active and well-reviewed profile gives you the best chance of being chosen.</p>
<h2>Playing by the rules</h2>
<p>Google has strict guidelines for business profiles. We never add keywords to your business name, create fake locations or buy reviews. These shortcuts can get a profile suspended, and recovering it takes time you cannot afford to lose.</p>', 'Google Business Profile Management in Noida', 'Google Business Profile management in Noida: setup, verification help, categories, photos, posts, reviews and monthly tracking of calls and direction requests.', 3, 'published'),
(4, 'content-writing', 'SEO Content Writing Services', 'Content Writing', 'Service pages and blog articles that answer real questions and bring in qualified visitors.', 'pen', 'slate', 'normal', 'People search because they have a question or a problem to solve. Our content writing service creates pages and articles that answer those questions clearly, show your expertise and guide readers towards contacting you.', '<h2>Content we write</h2>
<ul>
<li><strong>Service pages:</strong> clear pages for each service, covering what you offer, who it is for, pricing guidance and next steps.</li>
<li><strong>Location pages:</strong> genuinely useful pages for the areas you serve, not copy-and-paste pages with the city name swapped.</li>
<li><strong>Blog articles and guides:</strong> answers to the questions your customers ask before they buy.</li>
<li><strong>Content refreshes:</strong> updating older pages that have dropped in rankings or no longer reflect your business.</li>
<li><strong>On-page optimisation:</strong> titles, meta descriptions, headings, internal links and images on every page we publish.</li>
</ul>
<h2>Our process</h2>
<ol>
<li><strong>Research:</strong> we study what your customers search for, what already ranks and where the gaps are.</li>
<li><strong>Brief:</strong> each piece gets a brief with its purpose, target search terms, outline and key points to include.</li>
<li><strong>Writing and editing:</strong> content is written in clear, simple English and checked for accuracy and tone.</li>
<li><strong>Your review:</strong> you approve every piece, so the facts and the voice are right for your business.</li>
<li><strong>Publishing and tracking:</strong> we publish, link it from related pages and track how it performs.</li>
</ol>
<h2>Written for people first</h2>
<p>Google rewards content that is helpful, reliable and created for people rather than search engines. That means showing real experience, being specific and answering the question properly, not stuffing keywords into every sentence.</p>', 'SEO Content Writing Services | NoidaRankers', 'SEO content writing for service pages, location pages and blog articles. Researched, clearly written and optimised to rank and bring in enquiries.', 4, 'published'),
(5, 'link-building', 'Link Building Services', 'Link Building', 'Earn relevant, high-quality links from real websites, without risky shortcuts.', 'link', 'slate', 'normal', 'Links from other trusted websites are still one of the strongest signals Google uses to decide which pages deserve to rank. We earn those links through outreach, local partnerships and content worth citing, never through spam.', '<h2>How we earn links</h2>
<ul>
<li><strong>Local partnerships:</strong> associations, chambers of commerce, suppliers, events and community groups in Noida and NCR.</li>
<li><strong>Digital PR:</strong> useful data, guides and stories that journalists and bloggers want to reference.</li>
<li><strong>Relevant guest articles:</strong> expert articles for real industry websites with genuine readers.</li>
<li><strong>Unlinked mentions:</strong> finding places that already mention your business and asking them to add a link.</li>
<li><strong>Broken link building:</strong> offering your content as a replacement for broken links on relevant pages.</li>
<li><strong>Competitor gap analysis:</strong> finding the good links your competitors have and you do not.</li>
</ul>
<h2>What we avoid</h2>
<p>We do not buy links, use private blog networks or join link exchanges. These methods go against Google’s spam policies and can lead to rankings being lost overnight. Every link we build should still make sense if search engines did not exist.</p>
<h2>Full transparency</h2>
<p>You will see every link we earn: the website, the page, the date it went live and why it is relevant. No hidden lists, no vague totals.</p>', 'White-Hat Link Building Services | NoidaRankers', 'White-hat link building through outreach, digital PR and local partnerships. Relevant, high-quality links reported in full every month. No paid links.', 5, 'published'),
(6, 'seo-audit', 'SEO Audit Services', 'SEO Audit', 'A clear, prioritised report on what is holding your website back, and what to fix first.', 'search', 'dark', 'wide', 'An SEO audit shows you exactly where your website stands today: what is working, what is broken and which changes will make the biggest difference. Start with our free audit, or ask for a full in-depth audit for larger websites.', '<h2>What the audit covers</h2>
<ul>
<li><strong>Technical health:</strong> indexing, crawl errors, speed, mobile experience, HTTPS and structured data.</li>
<li><strong>On-page SEO:</strong> titles, meta descriptions, headings, content quality and internal links on your key pages.</li>
<li><strong>Keywords and content gaps:</strong> what you rank for now, and valuable searches where you are missing out.</li>
<li><strong>Local presence:</strong> your Google Business Profile, reviews and citation consistency.</li>
<li><strong>Backlinks:</strong> the quality of sites linking to you, and any risky links.</li>
<li><strong>Competitors:</strong> how you compare with the businesses that rank above you.</li>
</ul>
<h2>What you receive</h2>
<p>A plain-English report with a prioritised action plan. Every issue is marked by impact and effort, so you know what to fix first. We walk you through the findings on a call and answer your questions.</p>
<h2>Free audit or full audit?</h2>
<p>The <a href="/free-seo-audit">free SEO audit</a> covers the most important checks for your homepage and key service pages. A full audit goes page by page through larger websites, online stores and sites that have lost traffic after a Google update.</p>', 'SEO Audit Services: Find What Holds You Back', 'SEO audit covering technical health, on-page SEO, content gaps, local presence and backlinks, with a prioritised action plan. Start with a free audit.', 6, 'published');

-- faqs
INSERT IGNORE INTO `faqs` (`id`, `service_id`, `question`, `answer`, `sort_order`) VALUES
(1, 1, 'How long does local SEO take to work?', 'Fixes to your Google Business Profile and website can make a difference within a few weeks. Stronger, lasting results usually build over three to six months as reviews, links and content grow. Competitive searches can take longer.', 1),
(2, 1, 'Do I need an office address in Noida to rank locally?', 'To appear in Google Maps you need a Google Business Profile with either a real address where you meet customers or a service area if you visit customers instead. Virtual offices and fake addresses break Google’s rules and can get a profile suspended.', 2),
(3, 1, 'Can you guarantee a number one ranking on Google?', 'No. Google does not let anyone buy or guarantee organic or Maps rankings, and any agency that promises this is taking a risk with your business. We focus on measurable improvements in visibility, calls and enquiries.', 3),
(4, 1, 'What do you need from me to get started?', 'Access to your Google Business Profile and website, a list of your main services and the areas you serve, and a short call so we understand your customers. We take care of the rest and keep you updated.', 4),
(5, 2, 'What is technical SEO?', 'Technical SEO covers everything that helps search engines crawl, understand and load your website: speed, mobile friendliness, indexing settings, site structure, structured data and security. It is the foundation that content and links are built on.', 1),
(6, 2, 'Will technical changes break my website?', 'We test changes on a staging copy where possible, make one change at a time and keep a record of everything we do, so any change can be reversed quickly. We never touch your live site without agreeing the plan with you first.', 2),
(7, 2, 'Do you need access to my website?', 'For the audit we only need Google Search Console access. To make fixes ourselves we need admin or developer access, or we can hand clear instructions to your own developer.', 3),
(8, 2, 'How do you measure technical improvements?', 'We track indexed pages, crawl errors and Core Web Vitals in Google Search Console, page speed in lab tests, and the impact on rankings and organic traffic in your monthly report.', 4),
(9, 3, 'Does a Google Business Profile cost money?', 'No. A Google Business Profile is free. You only pay for our time to set it up, optimise it and manage it each month.', 1),
(10, 3, 'Can you help if my profile is suspended?', 'Yes. We review why the profile may have been suspended, fix anything that breaks Google’s guidelines and help you submit a reinstatement request with the right evidence.', 2),
(11, 3, 'How can I get more Google reviews?', 'Ask every happy customer, at the right moment, with a short link that opens the review form directly. Never buy reviews, offer rewards for them or post reviews yourself, as Google does not allow it.', 3),
(12, 3, 'How often should my profile be updated?', 'Little and often works best. We aim to add new photos and posts regularly, keep your hours and services accurate and reply to new reviews quickly.', 4),
(13, 4, 'Who writes the content?', 'Every piece is planned around research into what your customers search for, then written and edited by people. You review and approve each piece before it goes live.', 1),
(14, 4, 'How long should a blog article be?', 'As long as it needs to be to answer the question well. Some topics need 600 words, others need 2,000. We decide the length by looking at what the searcher needs, not a word count target.', 2),
(15, 4, 'Can you update my existing pages instead?', 'Yes. Refreshing pages that already have some rankings is often the quickest way to grow traffic. We look at what is missing, out of date or unclear and improve it.', 3),
(16, 4, 'Do you write in Hindi as well as English?', 'Tell us which languages your customers search in. If there is demand in Hindi or Hinglish, we can plan content for it alongside your English pages.', 4),
(17, 5, 'Are your links safe?', 'Yes. We only build links through genuine outreach, partnerships and content. We do not buy links or use private blog networks, which break Google’s spam policies.', 1),
(18, 5, 'How many links will I get each month?', 'It depends on your industry, your plan and how much linkable content you have. We focus on relevance and quality, because one strong link from a trusted site is worth more than dozens of weak ones.', 2),
(19, 5, 'How long do links take to make a difference?', 'Search engines need time to find and trust new links. Most businesses see the effect build gradually over a few months, especially when links are combined with strong content and technical SEO.', 3),
(20, 5, 'Will I see a list of every link?', 'Yes. Your monthly report lists every new link with the website, the page and the date it went live.', 4),
(21, 6, 'Is the free SEO audit really free?', 'Yes. The free audit is a no-obligation review of your website’s most important SEO issues. There is no payment and no contract. If you would like help fixing the issues afterwards, we will send you a proposal.', 1),
(22, 6, 'How long does an audit take?', 'The free audit usually takes a few working days. A full audit of a larger website takes longer, depending on the number of pages, and we agree the timeline with you first.', 2),
(23, 6, 'What do you need from me?', 'Your website address and, ideally, view access to Google Search Console and Google Analytics. Data from these tools makes the audit more accurate.', 3),
(24, 6, 'What happens after the audit?', 'We explain the findings on a short call. You can use the report with your own team or developer, or ask us to handle the fixes as part of a monthly SEO plan.', 4);

-- pricing_plans
INSERT IGNORE INTO `pricing_plans` (`id`, `name`, `price_label`, `billing_period`, `description`, `features`, `cta_label`, `is_featured`, `sort_order`, `status`) VALUES
(1, 'Starter', '[₹ PRICE]', 'per month', 'For small local businesses getting started with SEO.', 'Google Business Profile optimisation
Up to [X] target keywords
On-page SEO for up to [X] pages
Local citation setup
Monthly ranking and traffic report', 'Choose Starter', 0, 1, 'published'),
(2, 'Growth', '[₹ PRICE]', 'per month', 'For businesses ready to grow enquiries from Google every month.', 'Everything in Starter
Up to [X] target keywords
[X] SEO articles per month
Technical SEO fixes
Local link building
Monthly strategy call', 'Choose Growth', 1, 2, 'published'),
(3, 'Pro', '[₹ PRICE]', 'per month', 'For competitive markets and businesses with several locations.', 'Everything in Growth
Up to [X] target keywords
[X] SEO articles per month
Advanced technical SEO
Digital PR and outreach
Dedicated account manager', 'Choose Pro', 0, 3, 'published');

-- testimonials
INSERT IGNORE INTO `testimonials` (`id`, `author_name`, `author_role`, `company`, `quote`, `sort_order`, `status`, `is_sample`) VALUES
(1, '[CLIENT NAME]', '[ROLE]', '[COMPANY], Noida', 'SAMPLE testimonial: replace this with a real quote from a client. Ask them what changed after working with you, such as more calls, more enquiries or better rankings.', 1, 'published', 1),
(2, '[CLIENT NAME]', '[ROLE]', '[COMPANY], Greater Noida', 'SAMPLE testimonial: a short, specific quote works best. Mention the service you provided and one real result the client noticed.', 2, 'published', 1),
(3, '[CLIENT NAME]', '[ROLE]', '[COMPANY], Delhi NCR', 'SAMPLE testimonial: always get the client’s permission before publishing their name and words. Delete this entry in Admin > Testimonials.', 3, 'published', 1);

-- case_studies
INSERT IGNORE INTO `case_studies` (`id`, `slug`, `title`, `client_name`, `industry`, `location`, `summary`, `challenge`, `solution`, `results`, `metrics`, `services_used`, `image`, `image_width`, `image_height`, `image_alt`, `meta_title`, `meta_description`, `sort_order`, `status`, `published_at`, `is_sample`) VALUES
(1, 'sample-local-seo-case-study', 'SAMPLE: Local SEO for a [BUSINESS TYPE] in Noida', '[CLIENT NAME]', '[INDUSTRY]', 'Sector [XX], Noida', 'SAMPLE case study template. Replace it with a real project: who the client was, what was holding them back and what changed.', '<p>Describe the client’s starting point in two or three sentences. For example: the business did not appear in Google Maps for its main services, and most enquiries came from paid ads.</p>', '<ul>
<li>Google Business Profile optimisation: categories, services, photos and weekly posts.</li>
<li>New service and location pages for the client’s main services.</li>
<li>Citation clean-up so the name, address and phone number matched everywhere.</li>
<li>A review request process for happy customers.</li>
</ul>', '<p>Summarise the outcome in plain words, then add the real numbers in the metrics field. Only publish figures you can show from Google Business Profile, Search Console or Analytics.</p>', '[X]% | more calls from Google Maps
[X] | keywords in the local top 3
[X]x | more direction requests', 'local-seo,google-business-profile', '', 0, 0, '', 'SAMPLE: Local SEO Case Study', 'SAMPLE case study template showing how a local business in Noida can be presented. Replace with a real project and real results.', 1, 'published', '2026-10-23 10:00:00', 1),
(2, 'sample-technical-seo-case-study', 'SAMPLE: Technical SEO and Content for a [BUSINESS TYPE] Website', '[CLIENT NAME]', '[INDUSTRY]', 'Delhi NCR', 'SAMPLE case study template for a technical SEO and content project. Replace it with your own client story and verified numbers.', '<p>Describe the problem. For example: organic traffic had dropped after a website redesign, and many old pages returned errors.</p>', '<ul>
<li>Full technical audit and redirect map for old URLs.</li>
<li>Page speed improvements: image compression, caching and fewer scripts.</li>
<li>Rewritten service pages and new articles answering customer questions.</li>
<li>Internal linking between related pages.</li>
</ul>', '<p>Explain what changed and over what period. Link to the services you used, and keep the claims specific and verifiable.</p>', '[X]% | growth in organic traffic
[X] | pages fixed or redirected
[X]s | faster mobile load time', 'technical-seo,content-writing', '', 0, 0, '', 'SAMPLE: Technical SEO Case Study', 'SAMPLE case study template for a technical SEO and content project. Replace with a real client story and verified results.', 2, 'published', '2026-10-23 09:00:00', 1);

-- posts
INSERT IGNORE INTO `posts` (`id`, `title`, `slug`, `excerpt`, `content`, `image`, `image_width`, `image_height`, `image_alt`, `category_id`, `meta_title`, `meta_description`, `status`, `published_at`, `is_sample`) VALUES
(1, 'Google Business Profile Checklist for Noida Businesses', 'google-business-profile-checklist-noida', 'A step-by-step checklist to set up and optimise your Google Business Profile so you show up in Google Maps when people nearby search for your services.', '<p>When someone in Noida searches for "dentist near me" or "CA in Sector 18", Google usually shows a map with three businesses before any websites. Those three spots are decided largely by Google Business Profiles. This checklist covers the basics every local business should get right.</p>
<h2>1. Claim and verify your profile</h2>
<p>Search for your business name on Google Maps. If a profile exists, claim it. If not, create one at business.google.com. Google will ask you to verify that you run the business, often with a video of your premises or a phone call. Until you are verified, many features stay locked.</p>
<h2>2. Use your real business name</h2>
<p>Enter your business name exactly as it appears on your signboard and invoices. Adding keywords such as "Best SEO Company Noida" to the name goes against Google’s guidelines and can get the profile suspended.</p>
<h2>3. Choose the right categories</h2>
<p>Your primary category has a big influence on which searches you appear for. Pick the most specific category that describes your main service, then add secondary categories for other services you genuinely offer.</p>
<h2>4. Keep your address and phone number consistent</h2>
<p>Use the same name, address and phone number on your profile, your website and directories such as Justdial and IndiaMART. Inconsistent details confuse both customers and Google. If you visit customers rather than receive them, set a service area and hide your address.</p>
<h2>5. Add your services, hours and attributes</h2>
<ul>
<li>List every service with a short description.</li>
<li>Set accurate opening hours, including special hours for festivals such as Diwali and Holi.</li>
<li>Add attributes such as wheelchair access, online appointments or women-led, where they apply.</li>
</ul>
<h2>6. Upload real photos regularly</h2>
<p>Add photos of your storefront, interior, team and work. Real photos help customers recognise you and trust you. Avoid stock images. Adding a few new photos every month keeps the profile fresh.</p>
<h2>7. Ask for reviews the right way</h2>
<p>Reviews are one of the strongest local ranking signals and the first thing many customers read. Ask every happy customer, ideally soon after a good experience, and share the direct review link from your profile. Never buy reviews or offer discounts in exchange for them.</p>
<h2>8. Reply to every review</h2>
<p>Thank people for positive reviews and respond calmly and helpfully to negative ones. Future customers read your replies as closely as the reviews themselves.</p>
<h2>9. Post updates and answer questions</h2>
<p>Use Google posts for offers, events and news. Check the Questions and Answers section and add answers to common questions yourself, so customers get accurate information.</p>
<h2>10. Track what is working</h2>
<p>The performance section of your profile shows calls, website clicks, direction requests and the searches people used to find you. Check it every month to see which changes are paying off.</p>
<p>Want a second opinion? Our <a href="/free-seo-audit">free SEO audit</a> includes a review of your Google Business Profile with a list of quick wins.</p>', '', 0, 0, '', 4, 'Google Business Profile Checklist for Noida Businesses', 'A 10-step Google Business Profile checklist for Noida businesses: verification, categories, reviews, photos and tracking, to help you show up in Google Maps.', 'published', '2026-10-23 10:00:00', 1),
(2, 'Technical SEO Basics: 10 Checks for Small Business Websites', 'technical-seo-basics-checklist', 'Ten technical SEO checks you can run on your own website today, from indexing and HTTPS to page speed and structured data.', '<p>Technical SEO sounds complicated, but the basics are straightforward. These ten checks catch the problems we find most often on small business websites. You do not need to be a developer to run most of them.</p>
<h2>1. Is your site in Google’s index?</h2>
<p>Search Google for <code>site:yourdomain.com</code>. If important pages are missing, Google may not be able to find or index them. Google Search Console’s page indexing report shows exactly which pages are indexed and why others are not.</p>
<h2>2. Check robots.txt</h2>
<p>Open <code>yourdomain.com/robots.txt</code>. A line such as <code>Disallow: /</code> blocks your entire site from being crawled. This sometimes stays behind after a website is launched from a staging server.</p>
<h2>3. Look for stray noindex tags</h2>
<p>A <code>noindex</code> meta tag tells Google not to show a page in search results. Make sure it is not set on pages you want to rank. In WordPress, check that "Discourage search engines" is switched off.</p>
<h2>4. Submit an XML sitemap</h2>
<p>A sitemap lists the pages you want indexed. Most website platforms create one automatically. Submit it in Google Search Console so Google can find new pages faster.</p>
<h2>5. Use HTTPS everywhere</h2>
<p>Your site should load on <code>https://</code>, and every <code>http://</code> address should redirect there. Browsers warn visitors about sites without HTTPS, which hurts trust and enquiries.</p>
<h2>6. Pick one version of your domain</h2>
<p>Your site should be available at one address only, either with or without <code>www</code>. The other version should redirect permanently with a 301 redirect, and canonical tags should point to the preferred version.</p>
<h2>7. Test your page speed on mobile</h2>
<p>Run your key pages through Google PageSpeed Insights. Large images, too many plugins and slow hosting are common causes of poor scores. Compressing images and using modern formats such as WebP is often the quickest win.</p>
<h2>8. Fix broken links and redirect old pages</h2>
<p>Broken links waste visitors’ time and crawl budget. When you remove or rename a page, redirect the old address to the most relevant new page.</p>
<h2>9. Give every page a unique title and description</h2>
<p>Each page needs its own title tag and meta description that describe what it offers. Duplicate titles make it harder for Google to know which page to show.</p>
<h2>10. Add structured data</h2>
<p>Schema markup helps search engines understand your business details, services, articles and FAQs. Test it with Google’s Rich Results Test.</p>
<p>If this list raised more questions than answers, our <a href="/services/technical-seo">technical SEO service</a> can handle the fixes for you.</p>', '', 0, 0, '', 2, 'Technical SEO Basics: 10 Checks for Small Websites', 'Ten technical SEO checks for small business websites: indexing, robots.txt, sitemaps, HTTPS, page speed, broken links, titles and structured data.', 'published', '2026-10-23 09:00:00', 1),
(3, 'How to Write Service Pages That Rank and Convert', 'write-service-pages-that-rank', 'A simple structure for service pages that answer customer questions, rank on Google and turn visitors into enquiries.', '<p>Your service pages do two jobs. They need to rank for the searches your customers make, and they need to persuade the people who land on them to get in touch. Many business websites try to cover every service on one page and end up doing neither job well.</p>
<h2>Give every main service its own page</h2>
<p>If you offer root canal treatment, braces and teeth whitening, each deserves its own page. A focused page can match the search intent closely and answer specific questions, which a long list on a single page cannot.</p>
<h2>Start with the searcher’s question</h2>
<p>Before writing, search for the service yourself and look at what ranks. Note the questions people ask, the terms they use and what the top pages cover. Your page should answer those questions at least as well, and ideally better.</p>
<h2>A structure that works</h2>
<ol>
<li><strong>A clear headline</strong> that says what the service is and where you offer it.</li>
<li><strong>A short introduction</strong> explaining who the service is for and the problem it solves.</li>
<li><strong>What is included</strong>, in a scannable list.</li>
<li><strong>How it works</strong>, step by step, so the process feels simple.</li>
<li><strong>Proof</strong>, such as real reviews, qualifications, photos of your work or case studies.</li>
<li><strong>Pricing guidance</strong>, even if it is a range or a starting price.</li>
<li><strong>Frequently asked questions</strong> that deal with common worries.</li>
<li><strong>A clear call to action</strong> with a phone number, WhatsApp button or form.</li>
</ol>
<h2>Write like you speak to customers</h2>
<p>Use plain language and short paragraphs. Explain technical terms. Write "you" more than "we". Your page should sound like your best salesperson explaining the service to a customer.</p>
<h2>Optimise without over-optimising</h2>
<p>Use your main search term in the title, the main heading and naturally in the first paragraph. After that, focus on covering the topic well. Repeating the same phrase in every sentence makes the page harder to read and does not help rankings.</p>
<h2>Link your pages together</h2>
<p>Link each service page to related services, relevant blog articles and your contact page. Internal links help visitors find what they need and help Google understand how your pages relate to each other.</p>
<p>Need help planning or writing your service pages? Take a look at our <a href="/services/content-writing">content writing service</a>.</p>', '', 0, 0, '', 3, 'How to Write Service Pages That Rank and Convert', 'A proven structure for service pages that answer customer questions, rank on Google and turn visitors into enquiries, with tips on tone and internal links.', 'published', '2026-10-23 08:00:00', 1);

-- leads
INSERT IGNORE INTO `leads` (`id`, `type`, `status`, `name`, `phone`, `email`, `website`, `business_type`, `message`, `source_page`, `ip`, `user_agent`, `is_sample`) VALUES
(1, 'audit', 'new', 'SAMPLE Lead (delete me)', '+91 00000 00000', 'sample@example.com', 'https://example.com', 'Clinic / Hospital', 'This is a SAMPLE lead so you can see how the leads manager works. Delete it any time.', '/free-seo-audit', '', '', 1);
