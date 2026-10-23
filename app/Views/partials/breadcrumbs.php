<?php /** @var array<int, array{0:string,1:string}> $items */ ?>
<?php if (count($items) > 1): ?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
  <ol>
    <?php foreach ($items as $i => [$label, $path]): ?>
      <?php if ($i === count($items) - 1): ?>
      <li><span aria-current="page"><?= e($label) ?></span></li>
      <?php else: ?>
      <li><a href="<?= e(url($path)) ?>"><?= e($label) ?></a></li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ol>
</nav>
<?php endif; ?>
