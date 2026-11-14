<?php
/** @var bool $configured @var bool $enabled @var bool $imageOn @var array<int,array{id:int,name:string}>|array<string,string> $categories @var array<string,string> $tones */
?>
<div class="btn-row" style="margin-bottom:1rem">
  <a class="btn btn-outline btn-sm" href="<?= e(url('/admin/ai/settings')) ?>"><?= icon('gear') ?> API &amp; model settings</a>
</div>
<?php if (!$configured): ?>
<div class="alert alert-error" role="alert">
  AI is not set up yet. <a href="<?= e(url('/admin/ai/settings')) ?>">Add your provider link, API key and model →</a>
</div>
<?php elseif (!$enabled): ?>
<div class="alert" role="status">
  AI is configured but turned off. You can still generate drafts here. <a href="<?= e(url('/admin/ai/settings')) ?>">Settings →</a>
</div>
<?php endif; ?>

<p class="muted">Give a topic, keywords, or a reference URL. The writer creates an original, SEO-optimised draft in HTML — with headings, internal links<?= $imageOn ? ' and a generated image' : '' ?> — saved as a <strong>draft</strong> for you to review before publishing.</p>

<form class="form" method="post" action="<?= e(url('/admin/ai/generate')) ?>" data-ai-generate novalidate>
  <?= csrf_field() ?>
  <fieldset class="panel">
    <legend>What should it write about?</legend>
    <div class="form-grid">
      <div class="field" style="grid-column:1/-1">
        <label for="f-topic">Topic / title idea</label>
        <input id="f-topic" name="topic" type="text" maxlength="300" placeholder="e.g. Local SEO checklist for Noida restaurants">
        <p class="hint">Leave empty if you give keywords or a URL below.</p>
      </div>
      <div class="field" style="grid-column:1/-1">
        <label for="f-keywords">Target keywords <span class="muted">(optional)</span></label>
        <input id="f-keywords" name="keywords" type="text" maxlength="300" placeholder="local seo noida, google business profile, map ranking">
      </div>
      <div class="field" style="grid-column:1/-1">
        <label for="f-url">Reference URL <span class="muted">(optional — researched &amp; rewritten, never copied)</span></label>
        <input id="f-url" name="url" type="url" maxlength="300" placeholder="https://example.com/article-to-learn-from" inputmode="url">
      </div>
    </div>
  </fieldset>

  <fieldset class="panel">
    <legend>Style</legend>
    <div class="form-grid">
      <div class="field">
        <label for="f-category">Category</label>
        <select id="f-category" name="category_id">
          <option value="">No category</option>
          <?php foreach ($categories as $cid => $cname): ?>
            <option value="<?= e((string) $cid) ?>"><?= e((string) $cname) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="f-tone">Tone</label>
        <select id="f-tone" name="tone">
          <?php foreach ($tones as $tval => $tlabel): ?>
            <option value="<?= e($tval) ?>"><?= e($tlabel) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="f-length">Length</label>
        <select id="f-length" name="length">
          <option value="auto" selected>Auto — let the AI decide (recommended)</option>
          <option value="short">Short (~600 words)</option>
          <option value="medium">Medium (~1000 words)</option>
          <option value="long">Long (~1600 words)</option>
        </select>
        <p class="hint">Auto picks the best depth for your topic.</p>
      </div>
      <?php if ($imageOn): ?>
      <div class="field" style="grid-column:1/-1">
        <label class="check"><input type="checkbox" name="make_image" value="1" checked> Generate a featured image too</label>
        <p class="hint">Uses your image model. Adds a few seconds.</p>
      </div>
      <?php endif; ?>
    </div>
  </fieldset>

  <div class="btn-row">
    <button class="btn btn-primary" type="submit" data-ai-submit>Generate draft</button>
    <span class="muted" data-ai-status hidden>Writing your article… this can take 30–60 seconds. Please wait.</span>
  </div>
</form>

<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  var form = document.querySelector('[data-ai-generate]');
  if (!form) return;
  form.addEventListener('submit', function () {
    var btn = form.querySelector('[data-ai-submit]');
    var status = form.querySelector('[data-ai-status]');
    if (btn) { btn.disabled = true; btn.textContent = 'Generating…'; }
    if (status) { status.hidden = false; }
  });
})();
</script>
