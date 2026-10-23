<?php
/** @var int $status @var string $title @var string $message */
$texts = [
    403 => 'You do not have permission to view this page.',
    405 => 'This page cannot be opened this way.',
    419 => 'For your security, the form expired. Please go back, refresh the page and try again.',
    429 => 'You have made too many requests in a short time. Please wait a few minutes and try again.',
    500 => 'Something went wrong on our side. Please try again in a moment.',
    503 => 'The website is temporarily unavailable. Please try again in a few minutes.',
];
?>
<section class="page-hero">
  <div class="wrap">
    <div class="tile tile--amber">
      <p class="eyebrow">Error <?= (int) $status ?></p>
      <h1><?= e($title) ?></h1>
      <p class="lead"><?= e($message !== '' ? $message : ($texts[$status] ?? 'Please try again.')) ?></p>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= e(url('/')) ?>">Go to the home page</a>
      </div>
    </div>
  </div>
</section>
