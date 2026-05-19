<?php $error = $error ?? null; $email = $email ?? ''; $next = $next ?? '/account'; ?>
<h1>Sign in</h1>
<?php if ($error): ?><div class="alert error"><?= $e($error) ?></div><?php endif; ?>
<form method="post" action="/login" class="auth-form" autocomplete="on">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <input type="hidden" name="next" value="<?= $e($next) ?>">
    <label>Email <input type="email" name="email" required value="<?= $e($email) ?>"></label>
    <label>Password <input type="password" name="password" required minlength="8" autocomplete="current-password"></label>
    <button class="btn primary" type="submit">Sign in</button>
    <p>No account? <a href="/register">Create one</a>.</p>
</form>
