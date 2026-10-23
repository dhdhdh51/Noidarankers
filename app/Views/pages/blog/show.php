<?php
/** @var App\Core\Seo $seo @var array<string, mixed> $post @var array<int, array<string, mixed>> $related */
use App\Core\HtmlSanitizer;
use App\Core\View;

$content = HtmlSanitizer::clean((string) $post['content']);
?>
<article>
  <header class="page-hero">
    <div class="wrap">
      <div class="post-header">
        <?= View::partial('partials/breadcrumbs', ['items' => $seo->breadcrumbs]) ?>
        <h1><?= e($post['title']) ?></h1>
        <p class="post-meta">
          <?php if (!empty($post['category_slug'])): ?>
          <a class="chip" href="<?= e(url('/blog/category/' . $post['category_slug'])) ?>"><?= e($post['category_name']) ?></a>
          <?php endif; ?>
          <span>Published <time datetime="<?= e(iso_date((string) $post['published_at'])) ?>"><?= e(format_date((string) $post['published_at'])) ?></time></span>
          <?php if (strtotime((string) $post['updated_at']) > strtotime((string) $post['published_at']) + 86400): ?>
          <span>Updated <time datetime="<?= e(iso_date((string) $post['updated_at'])) ?>"><?= e(format_date((string) $post['updated_at'])) ?></time></span>
          <?php endif; ?>
          <span><?= reading_time($content) ?> min read</span>
        </p>
      </div>
      <?php if ($post['image'] !== ''): ?>
      <figure class="post-figure">
        <?= picture_tag((string) $post['image'], (int) $post['image_width'], (int) $post['image_height'], (string) ($post['image_alt'] ?: $post['title']), '(min-width: 1240px) 1200px, 100vw', false) ?>
      </figure>
      <?php endif; ?>
    </div>
  </header>

  <div class="wrap section-tight">
    <div class="prose">
      <?= $content ?>
    </div>
    <aside class="post-cta tile tile--blue" aria-labelledby="post-cta-heading">
      <h2 id="post-cta-heading" class="h3">Want us to check your website?</h2>
      <p>Get a free SEO audit with a clear list of fixes for your business.</p>
      <p><a class="btn btn-primary" href="<?= e(url('/free-seo-audit')) ?>">Request a free SEO audit</a></p>
    </aside>
  </div>
</article>

<?php if ($related): ?>
<section class="section-tight related" aria-labelledby="related-heading">
  <div class="wrap">
    <h2 id="related-heading">Related articles</h2>
    <div class="cards">
      <?php foreach ($related as $item): ?>
        <?= View::partial('partials/post-card', ['post' => $item]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?= View::partial('partials/cta') ?>
