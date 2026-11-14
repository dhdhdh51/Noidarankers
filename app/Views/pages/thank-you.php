<?php
/** @var App\Core\Seo $seo @var string $type @var string $name */
$wa = whatsapp_href(setting('whatsapp'), 'Hi ' . site_name() . ', I just sent a request on your website.');
?>
<section class="page-hero">
  <div class="wrap">
    <div class="tile tile--green">
      <h1>Thank you<?= $name !== '' ? ', ' . e($name) : '' ?>!</h1>
      <p class="lead">
        <?php if ($type === 'audit'): ?>
        We have received your free SEO audit request. We will review your website and get back to you with your audit and next steps.
        <?php else: ?>
        We have received your message and will get back to you as soon as possible.
        <?php endif; ?>
      </p>
      <div class="btn-row">
        <?php if ($wa !== ''): ?>
        <a class="btn btn-primary" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?>Message us on WhatsApp<span class="sr-only"> (opens in a new tab)</span></a>
        <?php endif; ?>
        <a class="btn btn-secondary" href="<?= e(url('/')) ?>">Back to the home page</a>
      </div>
    </div>
  </div>
</section>

<section class="section-tight" aria-labelledby="next-heading">
  <div class="wrap">
    <h2 id="next-heading">While you wait</h2>
    <div class="bento">
      <div class="tile tile--slate tile--wide tile--link">
        <h3><a href="<?= e(url('/blog')) ?>">Read our SEO tips</a></h3>
        <p>Practical guides on local SEO, Google Business Profile and technical SEO.</p>
      </div>
      <div class="tile tile--blue tile--wide tile--link">
        <h3><a href="<?= e(url('/results')) ?>">See our case studies</a></h3>
        <p>How we approach SEO projects for local businesses.</p>
      </div>
    </div>
  </div>
</section>
