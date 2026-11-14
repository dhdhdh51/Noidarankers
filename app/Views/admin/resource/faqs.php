<?php
/** @var array<int, array{question:string, answer:string}> $faqs @var array<string,string> $errors */
$rows = array_merge($faqs, array_fill(0, 2, ['question' => '', 'answer' => '']));
?>
<fieldset class="faq-editor" id="f-faqs">
  <legend>Frequently asked questions</legend>
  <p class="hint">Shown on the service page and added to the FAQPage structured data. Leave a row empty to remove it.</p>
  <?php if (isset($errors['faqs'])): ?><p class="field-error"><?= e($errors['faqs']) ?></p><?php endif; ?>
  <div data-faq-rows>
    <?php foreach ($rows as $i => $faq): ?>
    <div class="faq-row">
      <div class="field">
        <label for="faq-q-<?= $i ?>">Question <?= $i + 1 ?></label>
        <input id="faq-q-<?= $i ?>" type="text" name="faq_question[]" maxlength="255" value="<?= e($faq['question']) ?>">
      </div>
      <div class="field">
        <label for="faq-a-<?= $i ?>">Answer <?= $i + 1 ?></label>
        <textarea id="faq-a-<?= $i ?>" name="faq_answer[]" rows="3" maxlength="2000"><?= e($faq['answer']) ?></textarea>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <button class="btn btn-secondary btn-sm" type="button" data-add-faq hidden>Add another question</button>
</fieldset>
