<?php
/**
 * Contact / free SEO audit form. @var string $type 'contact'|'audit' @var string $action
 */
use App\Models\Lead;

$errors = errors();
$isAudit = $type === 'audit';
$labels = [
    'name' => 'Your name', 'phone' => 'Phone number', 'email' => 'Email address', 'website' => 'Website URL',
    'business_type' => 'Type of business', 'message' => $isAudit ? 'Anything we should know?' : 'How can we help?',
];
$fieldAttrs = static function (string $field) use ($errors): string {
    return isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . e($field) . '-error"' : '';
};
$fieldError = static function (string $field) use ($errors): string {
    return isset($errors[$field]) ? '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field]) . '</p>' : '';
};
$defaultMessage = '';
$plan = App\Core\Request::query('plan');
if ($isAudit && in_array($plan, ['starter', 'growth', 'pro'], true)) {
    $defaultMessage = 'I am interested in the ' . ucfirst($plan) . ' plan.';
}
?>
<form class="form" method="post" action="<?= e($action) ?>#lead-form" id="lead-form" novalidate data-lead-form>
  <?php if ($errors): ?>
  <div class="error-summary" role="alert" tabindex="-1" data-error-summary>
    <h2>Please check the form</h2>
    <ul>
      <?php foreach ($errors as $field => $message): ?>
        <?php if (isset($labels[$field])): ?>
        <li><a href="#lead-<?= e($field) ?>"><?= e($message) ?></a></li>
        <?php else: ?>
        <li><?= e($message) ?></li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>
  <?= csrf_field() ?>
  <?= spam_fields() ?>
  <p class="form-note">Fields marked <span class="req" aria-hidden="true">*</span><span class="sr-only">with an asterisk</span> are required.</p>
  <div class="form-grid">
    <div class="field">
      <label for="lead-name"><?= e($labels['name']) ?> <span class="req" aria-hidden="true">*</span></label>
      <input id="lead-name" name="name" type="text" autocomplete="name" maxlength="100" required value="<?= e(old('name')) ?>"<?= $fieldAttrs('name') ?>>
      <?= $fieldError('name') ?>
    </div>
    <div class="field">
      <label for="lead-phone"><?= e($labels['phone']) ?> <span class="req" aria-hidden="true">*</span></label>
      <input id="lead-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" maxlength="22" required value="<?= e(old('phone')) ?>"<?= $fieldAttrs('phone') ?>>
      <?= $fieldError('phone') ?>
    </div>
    <div class="field">
      <label for="lead-email"><?= e($labels['email']) ?> <span class="req" aria-hidden="true">*</span></label>
      <input id="lead-email" name="email" type="email" autocomplete="email" maxlength="190" required value="<?= e(old('email')) ?>"<?= $fieldAttrs('email') ?>>
      <?= $fieldError('email') ?>
    </div>
    <div class="field">
      <label for="lead-website"><?= e($labels['website']) ?><?= $isAudit ? ' <span class="req" aria-hidden="true">*</span>' : '' ?></label>
      <input id="lead-website" name="website" type="url" autocomplete="url" inputmode="url" maxlength="255" placeholder="example.com"<?= $isAudit ? ' required' : '' ?> value="<?= e(old('website')) ?>"<?= $fieldAttrs('website') ?>>
      <?= $fieldError('website') ?>
    </div>
    <div class="field full">
      <label for="lead-business_type"><?= e($labels['business_type']) ?> <span class="req" aria-hidden="true">*</span></label>
      <select id="lead-business_type" name="business_type" required<?= $fieldAttrs('business_type') ?>>
        <option value="">Choose one</option>
        <?php foreach (Lead::BUSINESS_TYPES as $option): ?>
        <option value="<?= e($option) ?>"<?= old('business_type') === $option ? ' selected' : '' ?>><?= e($option) ?></option>
        <?php endforeach; ?>
      </select>
      <?= $fieldError('business_type') ?>
    </div>
    <div class="field full">
      <label for="lead-message"><?= e($labels['message']) ?><?= $isAudit ? '' : ' <span class="req" aria-hidden="true">*</span>' ?></label>
      <textarea id="lead-message" name="message" rows="5" maxlength="2000"<?= $isAudit ? '' : ' required' ?><?= $fieldAttrs('message') ?>><?= e(old('message', $defaultMessage)) ?></textarea>
      <?= $fieldError('message') ?>
    </div>
  </div>
  <div class="btn-row">
    <button class="btn btn-primary" type="submit" data-submit><?= $isAudit ? 'Request my free audit' : 'Send message' ?></button>
  </div>
  <p class="form-note">We use your details only to reply to your enquiry. See our <a href="<?= e(url('/privacy-policy')) ?>">privacy policy</a>.</p>
</form>
