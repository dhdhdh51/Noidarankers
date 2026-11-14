<?php
use App\Core\Request;
use App\Core\View;

$current = Request::path();
$links = [
    '/services' => 'Services',
    '/pricing' => 'Pricing',
    '/results' => 'Results',
    '/blog' => 'Blog',
    '/about' => 'About',
    '/contact' => 'Contact',
];
?>
<header class="site-header">
  <div class="wrap header-inner">
    <a class="logo" href="<?= e(url('/')) ?>" aria-label="<?= e(site_name()) ?> home"><?= View::partial('partials/logo') ?></a>
    <a class="btn btn-primary btn-sm header-cta" href="<?= e(url('/free-seo-audit')) ?>">Free SEO audit</a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
      <span class="sr-only">Menu</span><?= icon('menu') ?>
    </button>
    <nav class="primary-nav" id="primary-nav" aria-label="Main">
      <ul>
        <?php foreach ($links as $href => $label):
            $active = $current === $href || str_starts_with($current, $href . '/'); ?>
        <li><a href="<?= e(url($href)) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach; ?>
        <li class="nav-cta"><a href="<?= e(url('/free-seo-audit')) ?>">Free SEO audit</a></li>
      </ul>
    </nav>
  </div>
</header>
