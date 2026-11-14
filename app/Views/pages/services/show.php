<?php
/**
 * @var App\Core\Seo $seo @var array<string, mixed> $service
 * @var array<int, array<string, mixed>> $faqs @var array<int, array<string, mixed>> $related
 */
use App\Core\HtmlSanitizer;
use App\Core\View;

$color = in_array($service['tile_color'], ['blue', 'amber', 'green', 'slate'], true) ? $service['tile_color'] : 'blue';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="tile tile--<?= e($color) ?>">
      <?= View::partial('partials/breadcrumbs', ['items' => $seo->breadcrumbs]) ?>
      <h1><?= e($service['title']) ?></h1>
      <p class="lead"><?= e($service['intro']) ?></p>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= e(url('/free-seo-audit')) ?>">Get a free SEO audit</a>
        <a class="btn btn-secondary" href="<?= e(url('/pricing')) ?>">See pricing</a>
      </div>
    </div>
  </div>
</section>

<section class="section-tight">
  <div class="wrap split">
    <div class="prose">
      <?= HtmlSanitizer::clean((string) $service['content']) ?>
    </div>
    <aside class="split-sticky" aria-labelledby="aside-heading">
      <div class="tile tile--dark">
        <h2 id="aside-heading" class="h3">Free <?= e($service['nav_title']) ?> check</h2>
        <p>Find out what is stopping your business from ranking. We will review your site and send you a clear, prioritised plan.</p>
        <a class="btn btn-primary" href="<?= e(url('/free-seo-audit')) ?>">Request a free audit</a>
      </div>
    </aside>
  </div>
</section>

<?= View::partial('partials/faq', ['faqs' => $faqs, 'heading' => $service['nav_title'] . ' FAQs']) ?>

<?php if ($related): ?>
<section class="section-tight" aria-labelledby="related-heading">
  <div class="wrap">
    <h2 id="related-heading">Related services</h2>
    <?= View::partial('partials/service-tiles', ['services' => $related, 'compact' => true]) ?>
  </div>
</section>
<?php endif; ?>

<?= View::partial('partials/cta', ['heading' => 'Ready to grow with ' . $service['nav_title'] . '?']) ?>
