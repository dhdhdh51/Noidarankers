<?php
/** @var array<string,string> $input @var array<string,string> $errors @var array<int, array{0:string,1:bool}> $checks */
$err = static fn (string $f): string => isset($errors[$f]) ? '<p class="field-error" id="' . e($f) . '-error">' . e($errors[$f]) . '</p>' : '';
$aria = static fn (string $f): string => isset($errors[$f]) ? ' aria-invalid="true" aria-describedby="' . e($f) . '-error"' : '';
?>
<h1>Create your admin account</h1>
<p class="muted">This page only works once. After the first account is created it is switched off.</p>

<h2 class="h3">Server check</h2>
<ul class="checks">
  <?php foreach ($checks as [$label, $ok]): ?>
  <li class="<?= $ok ? 'ok' : 'warn' ?>"><?= icon($ok ? 'check' : 'close') ?><span><?= e($label) ?><?= $ok ? '' : ' – please fix' ?></span></li>
  <?php endforeach; ?>
</ul>

<form class="form" method="post" action="<?= e(url('/admin/setup')) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="field">
    <label for="setup_key">Setup key</label>
    <input id="setup_key" name="setup_key" type="password" autocomplete="off" required<?= $aria('setup_key') ?>>
    <p class="hint">The value of <code>setup_key</code> in config.php.</p>
    <?= $err('setup_key') ?>
  </div>
  <div class="field">
    <label for="name">Your name</label>
    <input id="name" name="name" type="text" autocomplete="name" required value="<?= e($input['name'] ?? '') ?>"<?= $aria('name') ?>>
    <?= $err('name') ?>
  </div>
  <div class="field">
    <label for="email">Email address</label>
    <input id="email" name="email" type="email" autocomplete="username" required value="<?= e($input['email'] ?? '') ?>"<?= $aria('email') ?>>
    <?= $err('email') ?>
  </div>
  <div class="field">
    <label for="password">Password</label>
    <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required<?= $aria('password') ?>>
    <p class="hint">At least 12 characters. A short sentence is easy to remember and hard to guess.</p>
    <?= $err('password') ?>
  </div>
  <div class="field">
    <label for="password_confirm">Repeat password</label>
    <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required<?= $aria('password_confirm') ?>>
    <?= $err('password_confirm') ?>
  </div>
  <button class="btn btn-primary" type="submit">Create account</button>
</form>
