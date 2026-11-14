<?php /** @var string $title @var string $content */ ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · <?= e(site_name()) ?> admin</title>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</head>
<body class="admin admin-auth">
<main id="main" class="auth-card">
  <a class="logo" href="<?= e(url('/')) ?>"><?= App\Core\View::partial('partials/logo') ?></a>
  <?php $success = flash_message('success'); $error = flash_message('error'); ?>
  <?php if ($success !== ''): ?><p class="alert alert-success" role="status"><?= e($success) ?></p><?php endif; ?>
  <?php if ($error !== ''): ?><p class="alert alert-error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <?= $content ?>
</main>
</body>
</html>
