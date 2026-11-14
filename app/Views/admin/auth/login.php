<?php /** @var string $email @var string $error */ ?>
<h1>Sign in</h1>
<?php if ($error !== ''): ?><p class="alert alert-error" role="alert" id="login-error"><?= e($error) ?></p><?php endif; ?>
<form class="form" method="post" action="<?= e(url('/admin/login')) ?>">
  <?= csrf_field() ?>
  <div class="field">
    <label for="email">Email address</label>
    <input id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>"<?= $error !== '' ? ' aria-describedby="login-error"' : '' ?>>
  </div>
  <div class="field">
    <label for="password">Password</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
  </div>
  <button class="btn btn-primary" type="submit">Sign in</button>
</form>
