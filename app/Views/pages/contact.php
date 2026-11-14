<?php
/** @var App\Core\Seo $seo */
use App\Core\View;

$phone = setting('phone');
$email = setting('email');
$wa = whatsapp_href(setting('whatsapp'), 'Hi ' . site_name() . ', I have a question about SEO.');
$address = array_filter([setting('address_street'), setting('address_locality'), setting('address_city'),
    trim(setting('address_region') . ' ' . setting('address_postal'))]);
$map = setting('google_maps_url');
?>
<section class="page-hero">
  <div class="wrap">
    <div class="tile tile--blue">
      <?= View::partial('partials/breadcrumbs', ['items' => $seo->breadcrumbs]) ?>
      <h1>Contact NoidaRankers</h1>
      <p class="lead">Tell us about your business and what you would like to achieve on Google. Call, WhatsApp or send a message, whichever suits you.</p>
    </div>
  </div>
</section>

<section class="section-tight">
  <div class="wrap split">
    <div>
      <h2>Send us a message</h2>
      <?= View::partial('partials/lead-form', ['type' => 'contact', 'action' => url('/contact')]) ?>
    </div>
    <aside class="contact-tiles split-sticky" aria-label="Contact details">
      <div class="tile tile--slate">
        <span class="tile-icon"><?= icon('phone') ?></span>
        <h2 class="h3">Call us</h2>
        <p><?php if (tel_href($phone) !== ''): ?><a href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a><?php else: ?><?= e($phone) ?><?php endif; ?></p>
      </div>
      <div class="tile tile--green">
        <span class="tile-icon"><?= icon('whatsapp') ?></span>
        <h2 class="h3">WhatsApp</h2>
        <p><?php if ($wa !== ''): ?><a href="<?= e($wa) ?>" target="_blank" rel="noopener">Chat with us on WhatsApp<span class="sr-only"> (opens in a new tab)</span></a><?php else: ?><?= e(setting('whatsapp')) ?><?php endif; ?></p>
      </div>
      <div class="tile tile--amber">
        <span class="tile-icon"><?= icon('mail') ?></span>
        <h2 class="h3">Email</h2>
        <p><?php if (mailto_href($email) !== ''): ?><a href="<?= e(mailto_href($email)) ?>"><?= e($email) ?></a><?php else: ?><?= e($email) ?><?php endif; ?></p>
      </div>
      <div class="tile tile--slate">
        <span class="tile-icon"><?= icon('map-pin') ?></span>
        <h2 class="h3">Office</h2>
        <address class="nap"><?= e(implode(', ', $address)) ?></address>
        <p><?= icon('clock', 'icon sr-only') ?><?= e(setting('opening_hours')) ?></p>
        <?php if (preg_match('#^https://#', $map)): ?>
        <p><a href="<?= e($map) ?>" target="_blank" rel="noopener">Get directions on Google Maps<span class="sr-only"> (opens in a new tab)</span></a></p>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</section>
