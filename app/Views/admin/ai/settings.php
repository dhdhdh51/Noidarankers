<?php
/** @var array<string,mixed> $c @var bool $hasKey @var bool $hasImageKey @var bool $defaultKey */
$sizes = ['1024x1024' => '1024×1024 (square)', '1536x1024' => '1536×1024 (landscape)', '1024x1536' => '1024×1536 (portrait)', '1792x1024' => '1792×1024 (wide)'];
?>
<p class="muted">Connect any AI provider that offers an OpenAI-compatible API (OpenAI, OpenRouter, Groq, Together, DeepSeek, a local server…) or Anthropic. Enter the base link, your API key and a model. The key is encrypted before it is stored. <strong>Save</strong> your changes, then use <strong>Test</strong>.</p>

<?php if ($defaultKey): ?>
<div class="alert" role="status">Tip: your <code>app.key</code> in config.php looks like a default. Set a long random <code>app.key</code> so stored API keys are strongly encrypted.</div>
<?php endif; ?>

<form class="form" method="post" action="<?= e(url('/admin/ai/settings')) ?>" novalidate>
  <?= csrf_field() ?>
  <fieldset class="panel">
    <legend>Text model (writes the article)</legend>
    <div class="form-grid">
      <div class="field" style="grid-column:1/-1">
        <label class="check"><input type="checkbox" name="ai_enabled" value="1" <?= $c['enabled'] ? 'checked' : '' ?>> Enable the AI writer</label>
      </div>
      <div class="field">
        <label for="f-url">API base link</label>
        <input id="f-url" name="ai_base_url" type="url" value="<?= e((string) $c['base_url']) ?>" placeholder="https://api.openai.com/v1" inputmode="url" autocomplete="off">
        <p class="hint">OpenAI: <code>https://api.openai.com/v1</code> · OpenRouter: <code>https://openrouter.ai/api/v1</code> · Anthropic: <code>https://api.anthropic.com</code><br>You can paste the base URL or the full endpoint — the path is sorted out for you.</p>
      </div>
      <div class="field">
        <label for="f-format">API format</label>
        <select id="f-format" name="ai_format">
          <option value="openai" <?= $c['format'] === 'openai' ? 'selected' : '' ?>>OpenAI-compatible (/chat/completions)</option>
          <option value="anthropic" <?= $c['format'] === 'anthropic' ? 'selected' : '' ?>>Anthropic (/v1/messages)</option>
        </select>
      </div>
      <div class="field">
        <label for="f-key">API key</label>
        <input id="f-key" name="ai_api_key" type="password" value="" autocomplete="new-password" placeholder="<?= $hasKey ? '•••••••• (saved — leave blank to keep)' : 'Paste your API key' ?>">
      </div>
      <div class="field">
        <label for="f-model">Model</label>
        <input id="f-model" name="ai_model" type="text" value="<?= e((string) $c['model']) ?>" list="model-list" placeholder="e.g. gpt-4o-mini / claude-3-5-sonnet / llama-3.1-70b" autocomplete="off">
        <datalist id="model-list"></datalist>
        <p class="hint"><button class="btn btn-secondary btn-sm" type="button" data-ai-models>Load models</button> <span class="muted" data-models-status></span></p>
      </div>
      <div class="field">
        <label for="f-temp">Creativity (temperature)</label>
        <input id="f-temp" name="ai_temperature" type="number" min="0" max="2" step="0.1" value="<?= e((string) $c['temperature']) ?>">
        <p class="hint">0 = precise, 1 = balanced, 2 = very creative.</p>
      </div>
    </div>
    <div class="btn-row">
      <button class="btn btn-secondary btn-sm" type="button" data-ai-test>Test connection</button>
      <span data-test-status role="status"></span>
    </div>
  </fieldset>

  <fieldset class="panel">
    <legend>Images (optional)</legend>
    <div class="form-grid">
      <div class="field" style="grid-column:1/-1">
        <label class="check"><input type="checkbox" name="ai_image_enabled" value="1" <?= $c['image_enabled'] ? 'checked' : '' ?>> Generate a featured image for posts</label>
      </div>
      <div class="field">
        <label for="f-img-url">Image API base link</label>
        <input id="f-img-url" name="ai_image_base_url" type="url" value="<?= e((string) setting('ai_image_base_url')) ?>" placeholder="blank = same as text model" inputmode="url" autocomplete="off">
      </div>
      <div class="field">
        <label for="f-img-key">Image API key</label>
        <input id="f-img-key" name="ai_image_key" type="password" value="" autocomplete="new-password" placeholder="<?= $hasImageKey ? '•••••••• (saved)' : 'blank = same as text key' ?>">
      </div>
      <div class="field">
        <label for="f-img-model">Image model</label>
        <input id="f-img-model" name="ai_image_model" type="text" value="<?= e((string) $c['image_model']) ?>" placeholder="gpt-image-1 / dall-e-3" autocomplete="off">
      </div>
      <div class="field">
        <label for="f-img-size">Image size</label>
        <select id="f-img-size" name="ai_image_size">
          <?php foreach ($sizes as $sv => $sl): ?>
            <option value="<?= e($sv) ?>" <?= (string) $c['image_size'] === $sv ? 'selected' : '' ?>><?= e($sl) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </fieldset>

  <div class="btn-row"><button class="btn btn-primary" type="submit">Save settings</button>
    <a class="btn btn-outline" href="<?= e(url('/admin/ai')) ?>">Go to writer</a></div>
</form>

<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  var token = document.querySelector('meta[name="csrf-token"]');
  token = token ? token.getAttribute('content') : '';
  function post(url) {
    return fetch(url, { method: 'POST', headers: { 'X-CSRF-Token': token, 'Accept': 'application/json' } }).then(function (r) { return r.json(); });
  }
  var testBtn = document.querySelector('[data-ai-test]');
  var testOut = document.querySelector('[data-test-status]');
  if (testBtn) testBtn.addEventListener('click', function () {
    testOut.textContent = 'Testing…'; testOut.className = 'muted';
    post('<?= e(url('/admin/ai/test')) ?>').then(function (d) {
      testOut.textContent = (d.ok ? '✓ ' : '✗ ') + (d.message || '');
      testOut.className = d.ok ? 'ok-text' : 'field-error';
    }).catch(function () { testOut.textContent = 'Could not run the test.'; testOut.className = 'field-error'; });
  });
  var modelsBtn = document.querySelector('[data-ai-models]');
  var modelsOut = document.querySelector('[data-models-status]');
  var list = document.getElementById('model-list');
  if (modelsBtn) modelsBtn.addEventListener('click', function () {
    modelsOut.textContent = 'Loading…';
    post('<?= e(url('/admin/ai/models')) ?>').then(function (d) {
      var models = (d && d.models) || [];
      list.innerHTML = '';
      models.forEach(function (m) { var o = document.createElement('option'); o.value = m; list.appendChild(o); });
      modelsOut.textContent = models.length ? (models.length + ' models loaded — click the model box.') : 'No models returned (save your key first, or type the model manually).';
    }).catch(function () { modelsOut.textContent = 'Could not load models.'; });
  });
})();
</script>
