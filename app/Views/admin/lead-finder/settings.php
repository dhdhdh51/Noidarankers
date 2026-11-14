<?php
/** @var bool $enabled @var bool $hasKey */
?>
<p class="muted">Lead Finder uses Google's official <strong>Places API (New)</strong> to look up businesses on Google Maps. You need a Google API key with the Places API enabled. The key is encrypted before it is stored.</p>

<form class="form" method="post" action="<?= e(url('/admin/lead-finder/settings')) ?>" data-lf-settings novalidate>
  <?= csrf_field() ?>
  <fieldset class="panel">
    <legend>Google Places API</legend>
    <div class="form-grid">
      <div class="field" style="grid-column:1/-1">
        <label class="check"><input type="checkbox" name="maps_enabled" value="1" <?= $enabled ? 'checked' : '' ?>> Enable Lead Finder</label>
      </div>
      <div class="field" style="grid-column:1/-1">
        <label for="f-key">Google API key</label>
        <input id="f-key" name="maps_api_key" type="password" value="" autocomplete="new-password" placeholder="<?= $hasKey ? '•••••••• (saved — leave blank to keep)' : 'AIza… your Google Maps API key' ?>">
      </div>
    </div>
    <div class="btn-row">
      <button class="btn btn-primary" type="submit">Save settings</button>
      <button class="btn btn-secondary btn-sm" type="button" data-lf-test>Test key</button>
      <span data-test-status role="status"></span>
    </div>
  </fieldset>
</form>

<div class="panel">
  <h2>How to get a Google API key</h2>
  <ol class="muted">
    <li>Go to <a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">console.cloud.google.com</a> and create (or pick) a project.</li>
    <li>Enable <strong>Places API (New)</strong> under “APIs &amp; Services → Library”.</li>
    <li>Make sure <strong>Billing</strong> is turned on for the project (Google gives a monthly free credit; low usage is usually free).</li>
    <li>Create an API key under “APIs &amp; Services → Credentials”, then paste it above.</li>
    <li>Recommended: restrict the key to the Places API, and to your server's IP, from the key's settings.</li>
  </ol>
  <p class="muted">Note: Google Maps does not provide email addresses — you get the business name, phone, website, address and rating.</p>
</div>

<p><a href="<?= e(url('/admin/lead-finder')) ?>">&larr; Back to Lead Finder</a></p>

<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  var form = document.querySelector('[data-lf-settings]');
  if (!form) { return; }
  var tokenEl = document.querySelector('meta[name="csrf-token"]');
  var token = tokenEl ? tokenEl.getAttribute('content') : '';
  var testBtn = document.querySelector('[data-lf-test]');
  var out = document.querySelector('[data-test-status]');
  if (!testBtn) { return; }
  testBtn.addEventListener('click', function () {
    out.textContent = 'Testing…'; out.className = 'muted';
    var keyEl = form.querySelector('[name="maps_api_key"]');
    var body = new URLSearchParams();
    body.set('maps_api_key', keyEl ? keyEl.value : '');
    fetch('<?= e(url('/admin/lead-finder/test')) ?>', {
      method: 'POST',
      headers: { 'X-CSRF-Token': token, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString()
    }).then(function (r) { return r.json(); }).then(function (d) {
      out.textContent = (d.ok ? '✓ ' : '✗ ') + (d.message || '');
      out.className = d.ok ? 'ok-text' : 'field-error';
    }).catch(function () { out.textContent = 'Could not run the test.'; out.className = 'field-error'; });
  });
})();
</script>
