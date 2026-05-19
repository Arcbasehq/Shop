<?php $error = $error ?? null; $email = $email ?? ''; ?>
<h1>Create account</h1>
<?php if ($error): ?><div class="alert error"><?= $e($error) ?></div><?php endif; ?>
<form method="post" action="/register" class="auth-form" autocomplete="on">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <label>Email <input type="email" name="email" required value="<?= $e($email) ?>"></label>
    <label>Password (min 12 chars) <input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
    <label>Confirm password <input type="password" name="password_confirm" required minlength="12" autocomplete="new-password"></label>
    <button class="btn primary" type="submit">Create account</button>
</form>
