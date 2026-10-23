<?php /** @var App\Core\Seo $seo */ ?>
<section class="page-hero">
  <div class="wrap">
    <div class="tile tile--amber">
      <p class="eyebrow">Error 404</p>
      <h1>We can’t find that page</h1>
      <p class="lead">The page may have moved, or the link may be wrong. Here are some useful places to go instead.</p>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= e(url('/')) ?>">Go to the home page</a>
        <a class="btn btn-secondary" href="<?= e(url('/contact')) ?>">Contact us</a>
      </div>
    </div>
  </div>
</section>
<section class="section-tight" aria-labelledby="popular-heading">
  <div class="wrap">
    <h2 id="popular-heading">Popular pages</h2>
    <div class="bento">
      <div class="tile tile--blue tile--link"><h3><a href="<?= e(url('/services')) ?>">SEO services</a></h3><p>Local SEO, technical SEO, content and more.</p></div>
      <div class="tile tile--green tile--link"><h3><a href="<?= e(url('/pricing')) ?>">Pricing</a></h3><p>Compare our monthly SEO plans.</p></div>
      <div class="tile tile--slate tile--link"><h3><a href="<?= e(url('/blog')) ?>">SEO blog</a></h3><p>Practical tips for local businesses.</p></div>
      <div class="tile tile--dark tile--link"><h3><a href="<?= e(url('/free-seo-audit')) ?>">Free SEO audit</a></h3><p>See what is holding your site back.</p></div>
    </div>
  </div>
</section>
