<?php
/** @var array<string, mixed> $post @var string|null $headingTag */
$tag = ($headingTag ?? 'h3') === 'h2' ? 'h2' : 'h3';
$colors = ['blue', 'amber', 'green', 'slate'];
$color = $colors[((int) ($post['category_id'] ?? 0)) % 4];
?>
<article class="card">
  <?php if (!empty($post['image'])): ?>
    <?= picture_tag((string) $post['image'], (int) $post['image_width'], (int) $post['image_height'], (string) ($post['image_alt'] ?: $post['title']), '(min-width: 1024px) 380px, (min-width: 640px) 50vw, 100vw') ?>
  <?php else: ?>
    <div class="card-art tile--<?= e($color) ?>" aria-hidden="true"><?= icon('trend') ?></div>
  <?php endif; ?>
  <div class="card-body">
    <?php if (!empty($post['category_name'])): ?><span class="chip"><?= e($post['category_name']) ?></span><?php endif; ?>
    <<?= $tag ?>><a href="<?= e(url('/blog/' . $post['slug'])) ?>"><?= e($post['title']) ?></a></<?= $tag ?>>
    <p><?= e(excerpt((string) $post['excerpt'], 140)) ?></p>
    <p class="card-meta"><time datetime="<?= e(iso_date((string) $post['published_at'])) ?>"><?= e(format_date((string) $post['published_at'])) ?></time></p>
  </div>
</article>
