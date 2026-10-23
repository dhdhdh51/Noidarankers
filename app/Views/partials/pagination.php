<?php
/** @var App\Core\Paginator $pager @var callable(int):string $link */
?>
<?php if ($pager->pages > 1): ?>
<nav class="pagination" aria-label="Pagination">
  <ul>
    <?php if ($pager->hasPrev()): ?>
    <li><a href="<?= e($link($pager->page - 1)) ?>" rel="prev">Previous<span class="sr-only"> page</span></a></li>
    <?php endif; ?>
    <?php foreach ($pager->window() as $n): ?>
      <?php if ($n === null): ?>
      <li><span aria-hidden="true">…</span></li>
      <?php elseif ($n === $pager->page): ?>
      <li><span aria-current="page"><span class="sr-only">Page </span><?= $n ?></span></li>
      <?php else: ?>
      <li><a href="<?= e($link($n)) ?>"><span class="sr-only">Page </span><?= $n ?></a></li>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($pager->hasNext()): ?>
    <li><a href="<?= e($link($pager->page + 1)) ?>" rel="next">Next<span class="sr-only"> page</span></a></li>
    <?php endif; ?>
  </ul>
</nav>
<?php endif; ?>
