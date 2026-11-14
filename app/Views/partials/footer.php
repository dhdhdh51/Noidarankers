<?php
use App\Core\Request;
use App\Core\View;
use App\Models\Service;

$services = Service::published();
$phone = setting('phone');
$email = setting('email');
$address = array_filter([setting('address_street'), setting('address_locality'), setting('address_city'),
    trim(setting('address_region') . ' ' . setting('address_postal'))]);
$socials = [];
foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'x' => 'X (Twitter)', 'youtube' => 'YouTube'] as $key => $label) {
    $link = setting('social_' . $key);
    if (preg_match('#^https://#', $link)) {
        $socials[] = [$key, $label, $link];
    }
}
$newsError = flash_message('newsletter_error');
$newsOk = flash_message('newsletter_success');
?>
<footer class="site-footer">
  <div class="wrap">
    <div class="footer-grid">
      <div>
        <a class="logo" href="<?= e(url('/')) ?>" aria-label="<?= e(site_name()) ?> home"><?= View::partial('partials/logo') ?></a>
        <p>SEO agency in Noida helping local businesses rank higher on Google and win more customers.</p>
        <address class="nap">
          <p><?= e(implode(', ', $address)) ?></p>
          <p><?php if (tel_href($phone) !== ''): ?><a href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a><?php else: ?><?= e($phone) ?><?php endif; ?></p>
          <p><?php if (mailto_href($email) !== ''): ?><a href="<?= e(mailto_href($email)) ?>"><?= e($email) ?></a><?php else: ?><?= e($email) ?><?php endif; ?></p>
        </address>
        <?php if ($socials): ?>
        <ul class="social">
          <?php foreach ($socials as [$key, $label, $link]): ?>
          <li><a href="<?= e($link) ?>" rel="noopener" target="_blank" aria-label="<?= e(site_name() . ' on ' . $label) ?> (opens in a new tab)"><?= icon($key) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
      <nav aria-label="Services">
        <h2 class="footer-title">Services</h2>
        <ul class="footer-links">
          <?php foreach ($services as $service): ?>
          <li><a href="<?= e(url('/services/' . $service['slug'])) ?>"><?= e($service['nav_title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <nav aria-label="Company">
        <h2 class="footer-title">Company</h2>
        <ul class="footer-links">
          <li><a href="<?= e(url('/about')) ?>">About us</a></li>
          <li><a href="<?= e(url('/results')) ?>">Results</a></li>
          <li><a href="<?= e(url('/pricing')) ?>">Pricing</a></li>
          <li><a href="<?= e(url('/blog')) ?>">SEO blog</a></li>
          <li><a href="<?= e(url('/contact')) ?>">Contact</a></li>
          <li><a href="<?= e(url('/free-seo-audit')) ?>">Free SEO audit</a></li>
        </ul>
      </nav>
      <div class="newsletter" id="newsletter">
        <h2 class="footer-title">Monthly SEO tips</h2>
        <p>Practical advice for growing your business on Google. One email a month, no spam.</p>
        <?php if ($newsOk !== ''): ?><p class="alert alert-success" role="status"><?= e($newsOk) ?></p><?php endif; ?>
        <form class="newsletter-form" method="post" action="<?= e(url('/subscribe')) ?>" novalidate>
          <?= csrf_field() ?>
          <?= spam_fields() ?>
          <input type="hidden" name="return" value="<?= e(Request::path()) ?>">
          <label class="sr-only" for="newsletter-email">Email address</label>
          <input id="newsletter-email" type="email" name="email" autocomplete="email" placeholder="you@business.com" required
            <?= $newsError !== '' ? 'aria-invalid="true" aria-describedby="newsletter-error"' : '' ?> value="<?= e(old('newsletter_email')) ?>">
          <button class="btn btn-primary" type="submit">Subscribe</button>
        </form>
        <?php if ($newsError !== ''): ?><p class="field-error" id="newsletter-error" role="alert"><?= e($newsError) ?></p><?php endif; ?>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> <?= e(site_name()) ?>. All rights reserved.</p>
      <ul>
        <li><a href="<?= e(url('/privacy-policy')) ?>">Privacy policy</a></li>
        <li><a href="<?= e(url('/terms')) ?>">Terms</a></li>
      </ul>
    </div>
  </div>
</footer>
