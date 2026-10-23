<?php
/**
 * @var App\Core\Seo $seo @var array<string, mixed> $case @var array<int, array{value:string,label:string}> $metrics
 * @var array<int, array<string, mixed>> $services @var array<int, array<string, mixed>> $others
 */
use App\Core\HtmlSanitizer;
use App\Core\View;

$statColors = ['blue', 'amber', 'green', 'slate'];
?>
<section class="page-hero">
  <div class="wrap">
    <div class="tile tile--green">
      <?= View::partial('partials/breadcrumbs', ['items' => $seo->breadcrumbs]) ?>
      <h1><?= e($case['title']) ?></h1>
      <p class="lead"><?= e($case['summary']) ?></p>
      <ul class="hero-points">
        <?php if ($case['client_name'] !== ''): ?><li>Client: <?= e($case['client_name']) ?></li><?php endif; ?>
        <?php if ($case['industry'] !== ''): ?><li>Industry: <?= e($case['industry']) ?></li><?php endif; ?>
        <?php if ($case['location'] !== ''): ?><li>Location: <?= e($case['location']) ?></li><?php endif; ?>
      </ul>
    </div>
  </div>
</section>

<?php if ($case['image'] !== ''): ?>
<div class="wrap">
  <figure class="post-figure">
    <?= picture_tag((string) $case['image'], (int) $case['image_width'], (int) $case['image_height'], (string) ($case['image_alt'] ?: $case['title']), '(min-width: 1240px) 1200px, 100vw', false) ?>
  </figure>
</div>
<?php endif; ?>

<?php if ($metrics): ?>
<section class="section-tight" aria-labelledby="metrics-heading">
  <div class="wrap">
    <h2 id="metrics-heading">Key results</h2>
    <div class="stats">
      <?php foreach ($metrics as $i => $metric): ?>
      <div class="tile stat tile--<?= e($statColors[$i % 4]) ?>">
        <p class="stat-value"><?= e($metric['value']) ?></p>
        <p class="stat-label"><?= e($metric['label']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section-tight">
  <div class="wrap split">
    <div class="prose">
      <?php if (trim(strip_tags((string) $case['challenge'])) !== ''): ?>
      <h2>The challenge</h2>
      <?= HtmlSanitizer::clean((string) $case['challenge']) ?>
      <?php endif; ?>
      <?php if (trim(strip_tags((string) $case['solution'])) !== ''): ?>
      <h2>What we did</h2>
      <?= HtmlSanitizer::clean((string) $case['solution']) ?>
      <?php endif; ?>
      <?php if (trim(strip_tags((string) $case['results'])) !== ''): ?>
      <h2>The results</h2>
      <?= HtmlSanitizer::clean((string) $case['results']) ?>
      <?php endif; ?>
    </div>
    <?php if ($services): ?>
    <aside class="split-sticky" aria-labelledby="used-heading">
      <div class="tile tile--slate">
        <h2 id="used-heading" class="h3">Services used</h2>
        <ul class="check-list">
          <?php foreach ($services as $service): ?>
          <li><?= icon('check') ?><a href="<?= e(url('/services/' . $service['slug'])) ?>"><?= e($service['nav_title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </aside>
    <?php endif; ?>
  </div>
</section>

<?php if ($others): ?>
<section class="section-tight" aria-labelledby="more-cases">
  <div class="wrap">
    <h2 id="more-cases">More case studies</h2>
    <div class="cards">
      <?php foreach ($others as $other): ?>
        <?= View::partial('partials/case-card', ['case' => $other]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?= View::partial('partials/cta') ?>
