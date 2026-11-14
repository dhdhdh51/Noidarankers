<?php /** @var array<int, array{question:string, answer:string}> $faqs @var string|null $heading */ ?>
<?php if ($faqs): ?>
<section class="section-tight" aria-labelledby="faq-heading">
  <div class="narrow">
    <h2 id="faq-heading"><?= e($heading ?? 'Frequently asked questions') ?></h2>
    <div class="faq">
      <?php foreach ($faqs as $faq): ?>
      <details>
        <summary><?= e($faq['question']) ?></summary>
        <div class="faq-answer"><p><?= nl2br(e($faq['answer'])) ?></p></div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
