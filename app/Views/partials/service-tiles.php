<?php
/** @var array<int, array<string, mixed>> $services @var string|null $headingLevel */
$tag = ($headingLevel ?? 'h3') === 'h2' ? 'h2' : 'h3';
?>
<div class="bento<?= !empty($compact) ? ' bento--3' : '' ?>">
  <?php foreach ($services as $service):
      $color = in_array($service['tile_color'], ['blue', 'amber', 'green', 'slate', 'dark'], true) ? $service['tile_color'] : 'slate';
      $size = in_array($service['tile_size'], ['wide', 'tall'], true) ? ' tile--' . $service['tile_size'] : ''; ?>
  <article class="tile tile--link tile--<?= e($color) ?><?= e($size) ?>">
    <span class="tile-icon"><?= icon((string) $service['icon']) ?></span>
    <div class="tile-body stack-sm">
      <<?= $tag ?>><a href="<?= e(url('/services/' . $service['slug'])) ?>"><?= e($service['nav_title']) ?></a></<?= $tag ?>>
      <p><?= e($service['summary']) ?></p>
    </div>
    <span class="tile-more" aria-hidden="true">Explore <?= e($service['nav_title']) ?> <?= icon('arrow-right') ?></span>
  </article>
  <?php endforeach; ?>
</div>
