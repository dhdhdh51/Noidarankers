<?php
/** @var array<string, array<string, array{0:string,1:string,2:string}>> $groups @var array<string,string> $values @var array<string,string> $errors */
?>
<?php if ($errors): ?>
<div class="error-summary" role="alert" tabindex="-1" data-error-summary>
  <h2>Please fix the following</h2>
  <ul><?php foreach ($errors as $key => $message): ?><li><a href="#s-<?= e($key) ?>"><?= e($message) ?></a></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>
<p class="muted">Values saved here override the defaults in config.php. Clear a field to go back to the config.php value. Text in [BRACKETS] is a placeholder: it is shown on the site but left out of links and structured data.</p>
<form class="form" method="post" action="<?= e(url('/admin/settings')) ?>" novalidate>
  <?= csrf_field() ?>
  <?php foreach ($groups as $group => $fields): ?>
  <fieldset class="panel">
    <legend><?= e($group) ?></legend>
    <div class="form-grid">
      <?php foreach ($fields as $key => [$label, $type, $help]):
          $id = 's-' . $key;
          $described = trim(($help !== '' ? $id . '-help' : '') . (isset($errors[$key]) ? ' ' . $id . '-error' : '')); ?>
      <div class="field">
        <label for="<?= e($id) ?>"><?= e($label) ?></label>
        <input id="<?= e($id) ?>" name="<?= e($key) ?>" type="<?= $type === 'date' ? 'date' : 'text' ?>" value="<?= e($values[$key] ?? '') ?>" maxlength="500"
          <?= $type === 'email' ? 'inputmode="email" autocomplete="off"' : '' ?><?= $type === 'tel' ? 'inputmode="tel"' : '' ?>
          <?= $described !== '' ? 'aria-describedby="' . e($described) . '"' : '' ?><?= isset($errors[$key]) ? ' aria-invalid="true"' : '' ?>>
        <?php if ($help !== ''): ?><p class="hint" id="<?= e($id) ?>-help"><?= e($help) ?></p><?php endif; ?>
        <?php if (isset($errors[$key])): ?><p class="field-error" id="<?= e($id) ?>-error"><?= e($errors[$key]) ?></p><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <?php endforeach; ?>
  <div class="btn-row"><button class="btn btn-primary" type="submit">Save settings</button></div>
</form>
