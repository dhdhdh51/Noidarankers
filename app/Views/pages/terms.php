<?php
/** @var App\Core\Seo $seo */
use App\Core\View;

$legal = setting('legal_name');
$email = setting('email');
?>
<section class="page-hero">
  <div class="wrap">
    <?= View::partial('partials/breadcrumbs', ['items' => $seo->breadcrumbs]) ?>
    <h1>Terms of service</h1>
    <p class="muted">Last updated: <?= e(format_date(setting('policies_updated'))) ?></p>
  </div>
</section>
<section class="section-tight">
  <div class="wrap prose">
    <p>These terms apply when you use this website or buy services from <?= e($legal) ?>. By using the website you agree to them.</p>

    <h2>Our services</h2>
    <p>We provide search engine optimisation and related services as described on this website and in the proposal or agreement we send you. The details of each project, including scope, fees and timelines, are set out in that proposal.</p>

    <h2>No guaranteed rankings</h2>
    <p>Search engines such as Google decide their own rankings and change their systems regularly. We work to improve your visibility using methods that follow search engine guidelines, but we cannot guarantee any specific ranking, traffic level or number of enquiries.</p>

    <h2>Fees and payment</h2>
    <p>Prices shown on this website are for guidance. The fees in your signed proposal apply. Payment terms, taxes and notice periods are set out in your proposal or invoice.</p>

    <h2>Your responsibilities</h2>
    <p>You agree to give us accurate information and the access we need to do the work, and you confirm that you have the right to share any content you give us.</p>

    <h2>Website content</h2>
    <p>The content on this website is for general information only. It is not professional advice for your specific situation. You may not copy or reuse our content without permission.</p>

    <h2>Liability</h2>
    <p>To the extent allowed by law, we are not liable for indirect or consequential losses, such as loss of profit or business, arising from the use of this website or our services.</p>

    <h2>Governing law</h2>
    <p>These terms are governed by the laws of India. Any disputes will be handled by the courts with jurisdiction over Gautam Buddh Nagar (Noida), Uttar Pradesh.</p>

    <h2>Contact</h2>
    <p>Questions about these terms? Email <?php if (mailto_href($email) !== ''): ?><a href="<?= e(mailto_href($email)) ?>"><?= e($email) ?></a><?php else: ?><?= e($email) ?><?php endif; ?>.</p>

    <p class="muted"><em>This is a general template. Please have it reviewed by a legal adviser before relying on it.</em></p>
  </div>
</section>
