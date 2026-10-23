<?php
/** @var App\Core\Seo $seo */
use App\Core\View;
?>
<section class="page-hero">
  <div class="wrap">
    <div class="tile tile--dark">
      <?= View::partial('partials/breadcrumbs', ['items' => $seo->breadcrumbs]) ?>
      <h1>Get a free SEO audit</h1>
      <p class="lead">Find out what is stopping your website from ranking, and what to fix first. No cost, no obligation.</p>
    </div>
  </div>
</section>

<section class="section-tight">
  <div class="wrap split">
    <div>
      <h2>Request your audit</h2>
      <?= View::partial('partials/lead-form', ['type' => 'audit', 'action' => url('/free-seo-audit')]) ?>
    </div>
    <aside class="split-sticky" aria-labelledby="audit-includes">
      <div class="tile tile--blue">
        <h2 id="audit-includes" class="h3">What your audit includes</h2>
        <ul class="check-list">
          <li><?= icon('check') ?><span>Technical health: indexing, speed and mobile experience</span></li>
          <li><?= icon('check') ?><span>On-page SEO for your homepage and key service pages</span></li>
          <li><?= icon('check') ?><span>Google Business Profile and local presence review</span></li>
          <li><?= icon('check') ?><span>Keyword and competitor snapshot</span></li>
          <li><?= icon('check') ?><span>A prioritised list of fixes, explained in plain language</span></li>
        </ul>
      </div>
    </aside>
  </div>
</section>

<?= View::partial('partials/faq', ['faqs' => [
    ['question' => 'Is the audit really free?', 'answer' => 'Yes. There is no cost and no obligation. If you would like help with the fixes afterwards, we will send you a proposal and you can decide.'],
    ['question' => 'What do I need to provide?', 'answer' => 'Just your website address and a few details about your business. If you can share view access to Google Search Console and Analytics later, the audit will be even more accurate.'],
    ['question' => 'How will I receive the audit?', 'answer' => 'We email you the report and offer a short call to walk you through the findings and answer your questions.'],
], 'heading' => 'Free audit questions']) ?>
