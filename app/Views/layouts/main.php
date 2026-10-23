<?php
/** @var App\Core\Seo $seo @var string $content @var array<int,string>|null $css */
use App\Core\View;

$ga = ga_id();
?>
<!doctype html>
<html lang="en-IN"<?= $ga !== '' ? ' data-ga="' . e($ga) . '"' : '' ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?= $seo->head() ?>

<meta name="theme-color" content="#ffffff">
<link rel="icon" href="<?= e(url('/favicon.ico')) ?>" sizes="32x32">
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= e(asset('img/apple-touch-icon.png')) ?>">
<link rel="manifest" href="<?= e(url('/site.webmanifest')) ?>">
<style nonce="<?= e(csp_nonce()) ?>"><?= inline_css(array_merge(['base', 'components'], $css ?? [])) ?></style>
<script nonce="<?= e(csp_nonce()) ?>">document.documentElement.classList.add('js');</script>
<script src="<?= e(asset('js/site.js')) ?>" defer></script>
<?= $seo->jsonLd() ?>

</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<?= View::partial('partials/header') ?>
<main id="main" tabindex="-1">
<?= $content ?>
</main>
<?= View::partial('partials/footer') ?>
<?= View::partial('partials/floating') ?>
</body>
</html>
