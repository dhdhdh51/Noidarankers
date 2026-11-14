<?php
/** @var array<string, mixed> $case @var string|null $headingTag */
$tag = ($headingTag ?? 'h3') === 'h2' ? 'h2' : 'h3';
$metrics = array_slice(metric_lines((string) ($case['metrics'] ?? '')), 0, 2);
?>
<article class="card">
  <?php if (!empty($case['image'])): ?>
    <?= picture_tag((string) $case['image'], (int) $case['image_width'], (int) $case['image_height'], (string) ($case['image_alt'] ?: $case['title']), '(min-width: 1024px) 380px, (min-width: 640px) 50vw, 100vw') ?>
  <?php else: ?>
    <div class="card-art tile--green" aria-hidden="true"><?= icon('chart') ?></div>
  <?php endif; ?>
  <div class="card-body">
    <span class="chip<?= !empty($case['is_sample']) ? ' chip-sample' : '' ?>"><?= e(trim(($case['industry'] ?? '') . ' · ' . ($case['location'] ?? ''), ' ·')) ?></span>
    <<?= $tag ?>><a href="<?= e(url('/results/' . $case['slug'])) ?>"><?= e($case['title']) ?></a></<?= $tag ?>>
    <p><?= e(excerpt((string) $case['summary'], 150)) ?></p>
    <?php if ($metrics): ?>
    <ul class="metric-list card-meta">
      <?php foreach ($metrics as $metric): ?><li><?= e($metric['value']) ?> <span class="muted"><?= e($metric['label']) ?></span></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</article>
