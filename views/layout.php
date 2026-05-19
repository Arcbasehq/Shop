<?php /** @var string $content */ ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title><?= Golders\Security::e($title ?? 'Golders') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container nav">
        <a class="brand" href="/">GOLDERS</a>
        <nav class="primary">
            <a href="/">Shop</a>
            <a href="/cart">Cart<?php if ($cartCount > 0): ?> <span class="badge"><?= (int)$cartCount ?></span><?php endif; ?></a>
            <?php if ($user): ?>
                <a href="/account">Account</a>
                <?php if (!empty($user['is_admin'])): ?><a href="/admin">Admin</a><?php endif; ?>
                <form method="post" action="/logout" class="inline">
                    <input type="hidden" name="_csrf" value="<?= Golders\Security::e($csrf) ?>">
                    <button class="linkbtn" type="submit">Sign out</button>
                </form>
            <?php else: ?>
                <a href="/login">Sign in</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container main">
    <?= $content ?>
</main>
<footer class="site-footer">
    <div class="container">
        <small>© <?= date('Y') ?> Golders · Payments in XMR &amp; ETH via BTCPay Server</small>
    </div>
</footer>
</body>
</html>
