<?php
/** @var App\Core\Seo $seo */
use App\Core\View;

$legal = setting('legal_name');
$email = setting('email');
$address = implode(', ', array_filter([setting('address_street'), setting('address_locality'), setting('address_city'),
    trim(setting('address_region') . ' ' . setting('address_postal'))]));
?>
<section class="page-hero">
  <div class="wrap">
    <?= View::partial('partials/breadcrumbs', ['items' => $seo->breadcrumbs]) ?>
    <h1>Privacy policy</h1>
    <p class="muted">Last updated: <?= e(format_date(setting('policies_updated'))) ?></p>
  </div>
</section>
<section class="section-tight">
  <div class="wrap prose">
    <p>This policy explains how <?= e($legal) ?> ("we", "us") collects and uses personal information when you visit noidarankers.in or contact us. We keep it short and in plain language.</p>

    <h2>Information we collect</h2>
    <ul>
      <li><strong>Details you send us</strong> through our contact, free SEO audit and newsletter forms: your name, phone number, email address, website, type of business and message.</li>
      <li><strong>Technical data</strong> such as your IP address and browser type, which we store with form submissions to prevent spam and abuse.</li>
      <li><strong>Cookies:</strong> we use one essential cookie to keep forms secure. If analytics is enabled, Google Analytics sets cookies to measure how visitors use the site.</li>
    </ul>

    <h2>How we use your information</h2>
    <ul>
      <li>To reply to your enquiry and prepare any audit or proposal you ask for.</li>
      <li>To send our newsletter, if you subscribed. You can ask us to unsubscribe you at any time.</li>
      <li>To keep our website secure and understand how it is used.</li>
    </ul>
    <p>We do not sell your personal information. We share it only with service providers who help us run the website and email, and only as far as needed.</p>

    <h2>How long we keep it</h2>
    <p>We keep enquiry details for as long as needed to respond and to manage any work we do for you, and then delete them unless the law requires us to keep them longer.</p>

    <h2>Your choices and rights</h2>
    <p>You can ask us to access, correct or delete the personal information we hold about you, or to stop sending you emails. Contact us using the details below and we will respond promptly.</p>

    <h2>Contact</h2>
    <p><?= e($legal) ?><br><?= e($address) ?><br>Email: <?php if (mailto_href($email) !== ''): ?><a href="<?= e(mailto_href($email)) ?>"><?= e($email) ?></a><?php else: ?><?= e($email) ?><?php endif; ?></p>

    <p class="muted"><em>This is a general template. Please have it reviewed by a legal adviser so it matches how your business handles data.</em></p>
  </div>
</section>
