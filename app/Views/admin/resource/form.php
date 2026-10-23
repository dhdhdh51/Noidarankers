<?php
/**
 * @var App\Controllers\Admin\ResourceController $resource @var array<string, array<string,mixed>> $fields
 * @var array<string,mixed> $values @var array<string,string> $errors @var array<string,mixed>|null $existing
 * @var string $extra @var string $action
 */
$hasFile = in_array('image', array_column($fields, 'type'), true);
$isSample = !empty($existing['is_sample']);
?>
<?php if ($errors): ?>
<div class="error-summary" role="alert" tabindex="-1" data-error-summary>
  <h2>Please fix the following</h2>
  <ul>
    <?php foreach ($errors as $field => $message): ?><li><a href="#f-<?= e($field) ?>"><?= e($message) ?></a></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<?php if ($isSample): ?>
<p class="alert alert-sample">This is SAMPLE content. Saving it removes the SAMPLE label, so it will be kept when you delete sample content.</p>
<?php endif; ?>

<form class="panel form resource-form" method="post" action="<?= e($action) ?>"<?= $hasFile ? ' enctype="multipart/form-data"' : '' ?> novalidate>
  <?= csrf_field() ?>
  <div class="form-grid">
  <?php foreach ($fields as $name => $field):
      $type = $field['type'] ?? 'text';
      $label = (string) ($field['label'] ?? $name);
      $value = $values[$name] ?? '';
      $id = 'f-' . $name;
      $describedBy = [];
      if (!empty($field['help'])) { $describedBy[] = $id . '-help'; }
      if (isset($errors[$name])) { $describedBy[] = $id . '-error'; }
      $attrs = ' id="' . e($id) . '" name="' . e($name) . '"'
          . (!empty($field['required']) ? ' required' : '')
          . (isset($field['max']) ? ' maxlength="' . (int) $field['max'] . '"' : '')
          . ($describedBy ? ' aria-describedby="' . e(implode(' ', $describedBy)) . '"' : '')
          . (isset($errors[$name]) ? ' aria-invalid="true"' : '')
          . (isset($field['counter']) ? ' data-counter="' . (int) $field['counter'] . '"' : '');
      $full = !empty($field['full']) || in_array($type, ['editor'], true);
  ?>
    <?php if (!empty($field['section'])): ?><h2 class="form-section full"><?= e($field['section']) ?></h2><?php endif; ?>
    <div class="field<?= $full ? ' full' : '' ?>">
      <?php if ($type === 'checkbox'): ?>
        <label class="check"><input type="checkbox" value="1"<?= $attrs ?><?= (int) $value === 1 ? ' checked' : '' ?>> <?= e($label) ?></label>
      <?php else: ?>
        <label for="<?= e($id) ?>"><?= e($label) ?><?= !empty($field['required']) ? ' <span class="req" aria-hidden="true">*</span>' : '' ?></label>
        <?php if ($type === 'textarea'): ?>
          <textarea rows="<?= (int) ($field['rows'] ?? 4) ?>"<?= $attrs ?>><?= e($value) ?></textarea>
        <?php elseif ($type === 'editor'): ?>
          <textarea rows="16" data-editor<?= $attrs ?>><?= e($value) ?></textarea>
        <?php elseif ($type === 'select'): ?>
          <select<?= $attrs ?>>
            <?php foreach ((array) ($field['options'] ?? []) as $optValue => $optLabel): ?>
            <option value="<?= e($optValue) ?>"<?= (string) $value === (string) $optValue ? ' selected' : '' ?>><?= e($optLabel) ?></option>
            <?php endforeach; ?>
          </select>
        <?php elseif ($type === 'image'): ?>
          <?php if (!empty($existing[$name])): ?>
          <div class="image-preview">
            <img src="<?= e(upload_url((string) $existing[$name])) ?>" alt="Current image" width="240" height="<?= (int) round(240 * max(1, (int) ($existing[$name . '_height'] ?? 1)) / max(1, (int) ($existing[$name . '_width'] ?? 1))) ?>">
            <label class="check"><input type="checkbox" name="remove_<?= e($name) ?>" value="1"> Remove this image</label>
          </div>
          <?php endif; ?>
          <input type="file" accept="image/jpeg,image/png,image/webp,image/gif"<?= $attrs ?>>
        <?php elseif ($type === 'datetime'): ?>
          <input type="datetime-local" value="<?= e($value !== '' && $value !== null ? date('Y-m-d\TH:i', (int) strtotime((string) $value)) : '') ?>"<?= $attrs ?>>
        <?php elseif ($type === 'number'): ?>
          <input type="number" inputmode="numeric" value="<?= e($value) ?>"<?= $attrs ?>>
        <?php else: ?>
          <input type="<?= $type === 'url' ? 'url' : ($type === 'email' ? 'email' : 'text') ?>" value="<?= e($value) ?>"<?= $attrs ?><?= $type === 'slug' ? ' data-slug-from="f-' . e((string) ($field['from'] ?? 'title')) . '"' : '' ?>>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (!empty($field['help'])): ?><p class="hint" id="<?= e($id) ?>-help"><?= e($field['help']) ?></p><?php endif; ?>
      <?php if (isset($errors[$name])): ?><p class="field-error" id="<?= e($id) ?>-error"><?= e($errors[$name]) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
  <?= $extra ?>
  <div class="btn-row form-actions">
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn btn-secondary" href="<?= e(url($resource->basePath())) ?>">Back to list</a>
    <?php if ($existing && ($view = $resource->viewUrl($existing)) !== null): ?>
    <a class="btn btn-outline" href="<?= e($view) ?>" target="_blank" rel="noopener">View on site<span class="sr-only"> (opens in a new tab)</span></a>
    <?php endif; ?>
  </div>
</form>
