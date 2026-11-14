<?php
/** @var App\Core\Seo $seo @var array<int, array<string, mixed>> $services */
use App\Core\View;
?>
<section class="page-hero">
  <div class="wrap">
    <div class="tile tile--blue">
      <?= View::partial('partials/breadcrumbs', ['items' => $seo->breadcrumbs]) ?>
      <h1>SEO services in Noida</h1>
      <p class="lead">Six focused services that work together to get your business found on Google, and turn searches into calls, visits and enquiries.</p>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= e(url('/free-seo-audit')) ?>">Not sure where to start? Get a free audit</a>
      </div>
    </div>
  </div>
</section>

<section class="section-tight" aria-labelledby="all-services">
  <div class="wrap">
    <h2 id="all-services" class="sr-only">All services</h2>
    <?= View::partial('partials/service-tiles', ['services' => $services]) ?>
  </div>
</section>

<section class="section-tight" aria-labelledby="why-heading">
  <div class="wrap">
    <div class="section-head">
      <h2 id="why-heading">How our services fit together</h2>
      <p>Most businesses need a mix. Here is how we usually combine them.</p>
    </div>
    <div class="bento">
      <div class="tile tile--slate tile--wide">
        <h3>Foundations first</h3>
        <p>An <a href="<?= e(url('/services/seo-audit')) ?>">SEO audit</a> and <a href="<?= e(url('/services/technical-seo')) ?>">technical SEO</a> fixes make sure Google can crawl, understand and trust your website.</p>
      </div>
      <div class="tile tile--green tile--wide">
        <h3>Win local searches</h3>
        <p><a href="<?= e(url('/services/local-seo')) ?>">Local SEO</a> and <a href="<?= e(url('/services/google-business-profile')) ?>">Google Business Profile</a> management put you in front of nearby customers.</p>
      </div>
      <div class="tile tile--amber tile--wide">
        <h3>Answer every question</h3>
        <p><a href="<?= e(url('/services/content-writing')) ?>">Content writing</a> creates service pages and articles that match what your customers search for.</p>
      </div>
      <div class="tile tile--blue tile--wide">
        <h3>Build authority</h3>
        <p><a href="<?= e(url('/services/link-building')) ?>">Link building</a> earns mentions from trusted websites, helping your pages outrank competitors.</p>
      </div>
    </div>
  </div>
</section>

<?= View::partial('partials/cta') ?>
