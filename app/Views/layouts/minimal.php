<?php
/** Layout without database calls, used for 5xx error pages. @var App\Core\Seo $seo @var string $content */
?>
<!doctype html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($seo->fullTitle()) ?></title>
<meta name="robots" content="noindex, follow">
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<style nonce="<?= e(csp_nonce()) ?>"><?= inline_css(['base', 'components']) ?></style>
</head>
<body>
<header class="site-header"><div class="wrap header-inner"><a class="logo" href="<?= e(url('/')) ?>"><?= App\Core\View::partial('partials/logo') ?></a></div></header>
<main id="main"><?= $content ?></main>
</body>
</html>
