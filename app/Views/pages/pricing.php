<?php
/** @var App\Core\Seo $seo @var array<int, array<string, mixed>> $plans @var array<int, array<string,string>> $faqs */
use App\Core\View;
?>
<section class="page-hero">
  <div class="wrap">
    <div class="tile tile--blue">
      <?= View::partial('partials/breadcrumbs', ['items' => $seo->breadcrumbs]) ?>
      <h1>SEO pricing plans</h1>
      <p class="lead">Clear monthly plans for local businesses in Noida and Delhi NCR. Every plan starts with a free SEO audit so we can agree the right priorities.</p>
    </div>
  </div>
</section>

<section class="section-tight" aria-labelledby="plans-heading">
  <div class="wrap">
    <h2 id="plans-heading" class="sr-only">Plans</h2>
    <?php if ($plans): ?>
      <?= View::partial('partials/plans', ['plans' => $plans]) ?>
    <?php else: ?>
      <p class="empty-state">Our plans are being updated. <a href="<?= e(url('/contact')) ?>">Contact us</a> for a quote.</p>
    <?php endif; ?>
  </div>
</section>

<section class="section-tight" aria-labelledby="included-heading">
  <div class="wrap">
    <div class="section-head">
      <h2 id="included-heading">Included in every plan</h2>
    </div>
    <div class="bento">
      <div class="tile tile--slate"><span class="tile-icon"><?= icon('clipboard') ?></span><h3>Free starting audit</h3><p>A full review of your website and Google Business Profile before we begin.</p></div>
      <div class="tile tile--green"><span class="tile-icon"><?= icon('chart') ?></span><h3>Monthly reports</h3><p>Rankings, traffic, calls and enquiries explained in plain language.</p></div>
      <div class="tile tile--amber"><span class="tile-icon"><?= icon('shield') ?></span><h3>White-hat methods</h3><p>No paid links, fake reviews or shortcuts that put your business at risk.</p></div>
      <div class="tile tile--blue"><span class="tile-icon"><?= icon('phone') ?></span><h3>A real person to talk to</h3><p>Questions answered by people who know your account.</p></div>
    </div>
  </div>
</section>

<?= View::partial('partials/faq', ['faqs' => $faqs, 'heading' => 'Pricing questions']) ?>
<?= View::partial('partials/cta', ['heading' => 'Not sure which plan you need?', 'text' => 'Start with a free SEO audit. We will recommend the plan that fits your goals and budget.']) ?>
