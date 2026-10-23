<?php
/**
 * @var array<int, array<string, mixed>> $services
 * @var array<int, array<string, mixed>> $plans
 * @var array<int, array<string, mixed>> $testimonials
 * @var array<int, array<string, mixed>> $posts
 * @var array<int, array{value:string,label:string}> $stats
 */
use App\Core\View;

$statColors = ['blue', 'amber', 'green', 'slate'];
?>
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <p class="eyebrow"><?= icon('map-pin') ?> SEO agency in Noida</p>
      <h1>Rank higher on Google. Get more customers from Noida.</h1>
      <p class="lead">We help local businesses show up when nearby customers search, with local SEO, technical fixes, helpful content and a Google Business Profile that brings in calls.</p>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= e(url('/free-seo-audit')) ?>">Get a free SEO audit <?= icon('arrow-right') ?></a>
        <a class="btn btn-secondary" href="<?= e(url('/pricing')) ?>">View SEO pricing</a>
      </div>
      <ul class="hero-points">
        <li><?= icon('check') ?> Honest, white-hat SEO</li>
        <li><?= icon('check') ?> Clear monthly reports</li>
        <li><?= icon('check') ?> Local team in Noida</li>
      </ul>
    </div>
    <div class="hero-mosaic" aria-hidden="true">
      <div class="tile tile--dark tile--wide">
        <span class="tile-icon"><?= icon('search') ?></span>
        <strong>"seo company near me"</strong>
        <p>Be the business people find first in Google Search and Maps.</p>
      </div>
      <div class="tile tile--blue">
        <span class="tile-icon"><?= icon('map-pin') ?></span>
        <strong>Map Pack</strong>
        <p>Local visibility</p>
      </div>
      <div class="tile tile--green">
        <span class="tile-icon"><?= icon('phone') ?></span>
        <strong>More calls</strong>
        <p>Real enquiries</p>
      </div>
    </div>
  </div>
</section>

<section class="section-tight" aria-labelledby="services-heading">
  <div class="wrap">
    <div class="section-head">
      <h2 id="services-heading">SEO services that bring in local customers</h2>
      <p>Everything your business needs to be found on Google, from your Google Business Profile to the technical health of your website.</p>
    </div>
    <?= View::partial('partials/service-tiles', ['services' => $services]) ?>
    <p class="center section-tight"><a class="btn btn-secondary" href="<?= e(url('/services')) ?>">See all SEO services</a></p>
  </div>
</section>

<section class="section-tight" aria-labelledby="results-heading">
  <div class="wrap">
    <div class="section-head">
      <h2 id="results-heading">Results we track for every client</h2>
      <p>SEO should pay for itself. We report on the numbers that matter to your business, not vanity metrics.</p>
    </div>
    <div class="stats">
      <?php foreach ($stats as $i => $stat): ?>
      <div class="tile stat tile--<?= e($statColors[$i % 4]) ?>">
        <p class="stat-value"><?= e($stat['value']) ?></p>
        <p class="stat-label"><?= e($stat['label']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="section-tight"><a class="btn btn-secondary" href="<?= e(url('/results')) ?>">Read our case studies <?= icon('arrow-right') ?></a></p>
  </div>
</section>

<section class="section-tight" aria-labelledby="how-heading">
  <div class="wrap">
    <div class="section-head">
      <h2 id="how-heading">How it works</h2>
      <p>A simple, three-step process, explained in plain language at every stage.</p>
    </div>
    <ol class="steps">
      <li class="tile tile--slate">
        <span class="step-number">01</span>
        <h3>Audit</h3>
        <p>We review your website, Google Business Profile and competitors to find what is holding you back.</p>
      </li>
      <li class="tile tile--blue">
        <span class="step-number">02</span>
        <h3>Optimise</h3>
        <p>We fix technical issues, improve your pages and profile, and create content that answers customer questions.</p>
      </li>
      <li class="tile tile--dark">
        <span class="step-number">03</span>
        <h3>Rank</h3>
        <p>We build authority with reviews and links, track your progress and keep improving month after month.</p>
      </li>
    </ol>
  </div>
</section>

<?php if ($plans): ?>
<section class="section-tight" aria-labelledby="pricing-heading">
  <div class="wrap">
    <div class="section-head">
      <h2 id="pricing-heading">Simple monthly SEO plans</h2>
      <p>Choose the level of support that fits your goals. Every plan starts with a free audit.</p>
    </div>
    <?= View::partial('partials/plans', ['plans' => $plans]) ?>
    <p class="section-tight"><a class="btn btn-secondary" href="<?= e(url('/pricing')) ?>">Compare plans in detail</a></p>
  </div>
</section>
<?php endif; ?>

<?php if ($testimonials): ?>
<section class="section-tight" aria-labelledby="testimonials-heading">
  <div class="wrap">
    <div class="section-head">
      <h2 id="testimonials-heading">What our clients say</h2>
    </div>
    <div class="quotes">
      <?php foreach ($testimonials as $i => $t): ?>
      <figure class="tile quote tile--<?= e(['amber', 'slate', 'green'][$i % 3]) ?>">
        <blockquote><p>“<?= e($t['quote']) ?>”</p></blockquote>
        <figcaption><?= e($t['author_name']) ?><span><?= e(trim($t['author_role'] . ', ' . $t['company'], ', ')) ?></span></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($posts): ?>
<section class="section-tight" aria-labelledby="blog-heading">
  <div class="wrap">
    <div class="section-head">
      <h2 id="blog-heading">Latest SEO tips</h2>
      <p>Practical guides for business owners who want to understand what works on Google.</p>
    </div>
    <div class="cards">
      <?php foreach ($posts as $post): ?>
        <?= View::partial('partials/post-card', ['post' => $post]) ?>
      <?php endforeach; ?>
    </div>
    <p class="section-tight"><a class="btn btn-secondary" href="<?= e(url('/blog')) ?>">Visit the SEO blog</a></p>
  </div>
</section>
<?php endif; ?>

<?= View::partial('partials/cta') ?>
