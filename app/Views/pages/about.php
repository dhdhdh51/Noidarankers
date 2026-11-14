<?php
/** @var App\Core\Seo $seo */
use App\Core\View;
?>
<section class="page-hero">
  <div class="wrap">
    <div class="tile tile--blue">
      <?= View::partial('partials/breadcrumbs', ['items' => $seo->breadcrumbs]) ?>
      <h1>About NoidaRankers</h1>
      <p class="lead">We are an SEO agency based in Noida. We help local businesses get found on Google by the customers who are already searching for them.</p>
    </div>
  </div>
</section>

<section class="section-tight" aria-labelledby="approach-heading">
  <div class="wrap">
    <div class="section-head">
      <h2 id="approach-heading">Why we do SEO differently</h2>
      <p>Too many business owners have paid for SEO without knowing what was done or whether it worked. We built NoidaRankers to fix that.</p>
    </div>
    <div class="bento">
      <div class="tile tile--dark tile--wide">
        <span class="tile-icon"><?= icon('shield') ?></span>
        <h3>Honest and white-hat</h3>
        <p>No paid links, fake reviews or keyword-stuffed business names. We follow Google’s guidelines, because shortcuts can cost you your rankings overnight.</p>
      </div>
      <div class="tile tile--amber">
        <span class="tile-icon"><?= icon('chart') ?></span>
        <h3>Measured by results</h3>
        <p>We report on calls, enquiries and traffic, not just rankings.</p>
      </div>
      <div class="tile tile--green">
        <span class="tile-icon"><?= icon('map-pin') ?></span>
        <h3>Local focus</h3>
        <p>We know Noida’s sectors, markets and the way people here search.</p>
      </div>
      <div class="tile tile--slate tile--wide">
        <span class="tile-icon"><?= icon('megaphone') ?></span>
        <h3>Plain language</h3>
        <p>You will always know what we are working on and why. Every report explains what changed, what it means for your business and what comes next.</p>
      </div>
      <div class="tile tile--blue tile--wide">
        <span class="tile-icon"><?= icon('clipboard') ?></span>
        <h3>A plan before a contract</h3>
        <p>Every engagement starts with an audit, so you see the problems and priorities before you commit to anything.</p>
      </div>
    </div>
  </div>
</section>

<section class="section-tight" aria-labelledby="who-heading">
  <div class="narrow">
    <h2 id="who-heading">Who we work with</h2>
    <p>We work with businesses that serve customers in Noida, Greater Noida, Ghaziabad and across Delhi NCR: clinics and dentists, restaurants and cafés, coaching institutes, real estate agents, salons and gyms, home-service companies, manufacturers and professional firms.</p>
    <p>Whether you need a stronger <a href="<?= e(url('/services/google-business-profile')) ?>">Google Business Profile</a>, a <a href="<?= e(url('/services/technical-seo')) ?>">technically healthy website</a> or <a href="<?= e(url('/services/content-writing')) ?>">content that answers your customers’ questions</a>, we tailor the work to your goals and budget.</p>
  </div>
</section>

<?= View::partial('partials/cta', ['heading' => 'Let’s talk about your business']) ?>
