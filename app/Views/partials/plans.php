<?php /** @var array<int, array<string, mixed>> $plans */ ?>
<div class="plans">
  <?php foreach ($plans as $plan): $featured = (int) $plan['is_featured'] === 1; ?>
  <article class="tile plan <?= $featured ? 'tile--dark' : 'tile--white' ?>">
    <?php if ($featured): ?><span class="plan-badge">Most popular</span><?php endif; ?>
    <h3><?= e($plan['name']) ?></h3>
    <p class="<?= $featured ? '' : 'muted' ?>"><?= e($plan['description']) ?></p>
    <p class="plan-price"><?= e($plan['price_label']) ?> <span class="plan-period"><?= e($plan['billing_period']) ?></span></p>
    <ul class="check-list">
      <?php foreach (lines((string) $plan['features']) as $feature): ?>
      <li><?= icon('check') ?><span><?= e($feature) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <a class="btn <?= $featured ? 'btn-primary' : 'btn-secondary' ?>" href="<?= e(url('/free-seo-audit')) ?>?plan=<?= e(rawurlencode(strtolower((string) $plan['name']))) ?>"><?= e($plan['cta_label']) ?><span class="sr-only"> plan</span></a>
  </article>
  <?php endforeach; ?>
</div>
