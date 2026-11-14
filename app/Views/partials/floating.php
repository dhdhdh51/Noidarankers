<?php
$wa = whatsapp_href(setting('whatsapp'), 'Hi ' . site_name() . ', I would like to know more about your SEO services.');
$tel = tel_href(setting('phone'));
?>
<?php if ($tel !== ''): ?>
<a class="fab fab-call" href="<?= e($tel) ?>" aria-label="Call <?= e(site_name()) ?>"><?= icon('phone') ?></a>
<?php endif; ?>
<?php if ($wa !== ''): ?>
<a class="fab fab-whatsapp" href="<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="Chat with <?= e(site_name()) ?> on WhatsApp (opens in a new tab)"><?= icon('whatsapp') ?></a>
<?php endif; ?>
