<?php
/** @var string|null $heading @var string|null $text */
$heading = $heading ?? 'Ready to rank higher on Google?';
$text = $text ?? 'Get a free SEO audit of your website and a clear plan to win more customers from Noida and Delhi NCR.';
$wa = whatsapp_href(setting('whatsapp'), 'Hi ' . site_name() . ', I would like a free SEO audit.');
$tel = tel_href(setting('phone'));
?>
<section class="section-tight" aria-labelledby="cta-heading">
  <div class="wrap">
    <div class="cta-band">
      <div>
        <h2 id="cta-heading"><?= e($heading) ?></h2>
        <p><?= e($text) ?></p>
      </div>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= e(url('/free-seo-audit')) ?>">Get my free SEO audit</a>
        <?php if ($wa !== ''): ?>
        <a class="btn btn-light" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?>WhatsApp us<span class="sr-only"> (opens in a new tab)</span></a>
        <?php elseif ($tel !== ''): ?>
        <a class="btn btn-light" href="<?= e($tel) ?>"><?= icon('phone') ?>Call <?= e(setting('phone')) ?></a>
        <?php else: ?>
        <a class="btn btn-light" href="<?= e(url('/contact')) ?>">Contact us</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
