<?php
/** @var App\Core\Seo $seo @var array<int, array<string, mixed>> $cases */
use App\Core\View;
?>
<section class="page-hero">
  <div class="wrap">
    <div class="tile tile--green">
      <?= View::partial('partials/breadcrumbs', ['items' => $seo->breadcrumbs]) ?>
      <h1>SEO results and case studies</h1>
      <p class="lead">Real projects, the work we did and what changed. Every number we publish comes from the client’s own Google Search Console, Analytics or Business Profile data.</p>
    </div>
  </div>
</section>

<section class="section-tight" aria-labelledby="cases-heading">
  <div class="wrap">
    <h2 id="cases-heading" class="sr-only">Case studies</h2>
    <?php if ($cases): ?>
    <div class="cards">
      <?php foreach ($cases as $case): ?>
        <?= View::partial('partials/case-card', ['case' => $case]) ?>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="empty-state">New case studies are on their way. In the meantime, <a href="<?= e(url('/free-seo-audit')) ?>">request a free SEO audit</a> to see what we would improve for you.</p>
    <?php endif; ?>
  </div>
</section>

<?= View::partial('partials/cta', ['heading' => 'Want results like these?']) ?>
