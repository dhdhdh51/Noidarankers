<?php
/** @var string $title @var string $content @var array<string,mixed>|null $user @var int $newLeads */
use App\Core\Request;
use App\Core\View;

$path = Request::path();
$nav = [
    '/admin' => ['Dashboard', 'chart'],
    '/admin/leads' => ['Leads', 'phone'],
    '/admin/posts' => ['Blog posts', 'pen'],
    '/admin/categories' => ['Categories', 'clipboard'],
    '/admin/services' => ['Services', 'gear'],
    '/admin/plans' => ['Pricing', 'star'],
    '/admin/case-studies' => ['Case studies', 'trend'],
    '/admin/testimonials' => ['Testimonials', 'megaphone'],
    '/admin/subscribers' => ['Subscribers', 'mail'],
    '/admin/settings' => ['Settings', 'shield'],
    '/admin/account' => ['Your account', 'store'],
];
$success = flash_message('success');
$error = flash_message('error');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(App\Core\Csrf::token()) ?>">
<meta name="upload-url" content="<?= e(url('/admin/uploads/image')) ?>">
<title><?= e($title) ?> · <?= e(site_name()) ?> admin</title>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/prose.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</head>
<body class="admin">
<a class="skip-link" href="#main">Skip to content</a>
<header class="admin-topbar">
  <button class="admin-burger" type="button" aria-label="Open menu" aria-controls="admin-side" aria-expanded="false"><?= icon('menu') ?></button>
  <a class="logo" href="<?= e(url('/admin')) ?>"><?= View::partial('partials/logo') ?></a>
  <?php if ($newLeads > 0): ?><a class="topbar-leads" href="<?= e(url('/admin/leads?status=new')) ?>" aria-label="<?= (int) $newLeads ?> new leads"><?= icon('phone') ?><span class="count"><?= (int) $newLeads ?></span></a><?php endif; ?>
</header>
<div class="admin-shell">
  <div class="admin-overlay" hidden></div>
  <aside class="admin-side" id="admin-side">
    <a class="logo side-logo" href="<?= e(url('/admin')) ?>"><?= View::partial('partials/logo') ?></a>
    <nav aria-label="Admin">
      <ul class="admin-menu">
        <?php foreach ($nav as $href => [$label, $iconName]):
            $active = $href === '/admin' ? $path === '/admin' : ($path === $href || str_starts_with($path, $href . '/')); ?>
        <li><a href="<?= e(url($href)) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= icon($iconName) ?><span><?= e($label) ?></span>
          <?php if ($href === '/admin/leads' && $newLeads > 0): ?><span class="count" aria-label="<?= (int) $newLeads ?> new"><?= (int) $newLeads ?></span><?php endif; ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </aside>
  <div class="admin-main">
    <header class="admin-top">
      <h1><?= e($title) ?></h1>
      <div class="admin-user">
        <?php if ($user): ?><span class="muted"><?= e($user['name']) ?></span><?php endif; ?>
        <a class="btn btn-secondary btn-sm" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View site<span class="sr-only"> (opens in a new tab)</span></a>
        <form method="post" action="<?= e(url('/admin/logout')) ?>">
          <?= csrf_field() ?>
          <button class="btn btn-outline btn-sm" type="submit">Sign out</button>
        </form>
      </div>
    </header>
    <main id="main" tabindex="-1">
      <?php if ($success !== ''): ?><p class="alert alert-success" role="status"><?= e($success) ?></p><?php endif; ?>
      <?php if ($error !== ''): ?><p class="alert alert-error" role="alert"><?= e($error) ?></p><?php endif; ?>
      <?= $content ?>
    </main>
  </div>
</div>
</body>
</html>
