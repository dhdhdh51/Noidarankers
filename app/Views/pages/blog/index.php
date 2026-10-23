<?php
/**
 * @var App\Core\Seo $seo @var array<int, array<string, mixed>> $posts @var App\Core\Paginator $pager
 * @var callable(int):string $link @var array<int, array<string, mixed>> $categories @var array<string, mixed>|null $category
 */
use App\Core\View;

$heading = $category ? $category['name'] . ' articles' : 'SEO blog';
$intro = $category && $category['description'] !== ''
    ? $category['description']
    : 'Practical SEO advice for business owners in Noida and Delhi NCR. No jargon, just what works on Google today.';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="tile tile--slate">
      <?= View::partial('partials/breadcrumbs', ['items' => $seo->breadcrumbs]) ?>
      <h1><?= e($heading) ?><?= $pager->page > 1 ? ' <span class="muted">(page ' . (int) $pager->page . ')</span>' : '' ?></h1>
      <p class="lead"><?= e($intro) ?></p>
    </div>
  </div>
</section>

<section class="section-tight" aria-labelledby="posts-heading">
  <div class="wrap">
    <h2 id="posts-heading" class="sr-only">Articles</h2>
    <?php if ($categories): ?>
    <nav aria-label="Blog categories">
      <ul class="chips">
        <li><a href="<?= e(url('/blog')) ?>"<?= $category === null ? ' aria-current="page"' : '' ?>>All articles</a></li>
        <?php foreach ($categories as $cat): ?>
        <li><a href="<?= e(url('/blog/category/' . $cat['slug'])) ?>"<?= $category && (int) $category['id'] === (int) $cat['id'] ? ' aria-current="page"' : '' ?>><?= e($cat['name']) ?> (<?= (int) $cat['post_count'] ?>)</a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php endif; ?>

    <?php if ($posts): ?>
    <div class="cards">
      <?php foreach ($posts as $post): ?>
        <?= View::partial('partials/post-card', ['post' => $post]) ?>
      <?php endforeach; ?>
    </div>
    <?= View::partial('partials/pagination', ['pager' => $pager, 'link' => $link]) ?>
    <?php else: ?>
    <p class="empty-state">No articles here yet. Check back soon, or <a href="<?= e(url('/blog')) ?>">browse all articles</a>.</p>
    <?php endif; ?>
  </div>
</section>

<?= View::partial('partials/cta') ?>
