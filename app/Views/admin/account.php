<?php
/** @var array<string,mixed> $input @var array<string,string> $errors */
$err = static fn (string $f): string => isset($errors[$f]) ? '<p class="field-error" id="' . e($f) . '-error">' . e($errors[$f]) . '</p>' : '';
$aria = static fn (string $f): string => isset($errors[$f]) ? ' aria-invalid="true" aria-describedby="' . e($f) . '-error"' : '';
?>
<form class="panel form narrow-form" method="post" action="<?= e(url('/admin/account')) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="field">
    <label for="name">Name</label>
    <input id="name" name="name" type="text" autocomplete="name" required value="<?= e($input['name'] ?? '') ?>"<?= $aria('name') ?>>
    <?= $err('name') ?>
  </div>
  <div class="field">
    <label for="email">Email address (used to sign in)</label>
    <input id="email" name="email" type="email" autocomplete="username" required value="<?= e($input['email'] ?? '') ?>"<?= $aria('email') ?>>
    <?= $err('email') ?>
  </div>
  <div class="field">
    <label for="new_password">New password</label>
    <input id="new_password" name="new_password" type="password" autocomplete="new-password"<?= $aria('new_password') ?>>
    <p class="hint">Leave empty to keep your current password. Minimum 12 characters.</p>
    <?= $err('new_password') ?>
  </div>
  <div class="field">
    <label for="new_password_confirm">Repeat new password</label>
    <input id="new_password_confirm" name="new_password_confirm" type="password" autocomplete="new-password"<?= $aria('new_password_confirm') ?>>
    <?= $err('new_password_confirm') ?>
  </div>
  <div class="field">
    <label for="current_password">Current password (required to save)</label>
    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required<?= $aria('current_password') ?>>
    <?= $err('current_password') ?>
  </div>
  <button class="btn btn-primary" type="submit">Save changes</button>
</form>
