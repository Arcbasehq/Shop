<?php
/** @var array $order */
/** @var array $accepted */
$error = $error ?? null;
?>
<h1>Pay for order <?= $e($order['order_number']) ?></h1>
<p>Total due: <strong><?= Golders\View::money((int)$order['total_cents'], $order['currency']) ?></strong></p>

<?php if ($error): ?><div class="alert error"><?= $e($error) ?></div><?php endif; ?>

<form method="post" action="/order/<?= $e($order['order_number']) ?>/pay" class="coin-picker">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <p>Choose a cryptocurrency. A fresh deposit address will be generated for this order.</p>
    <div class="coin-grid">
        <?php foreach ($accepted as $coin): ?>
            <label class="coin-option">
                <input type="radio" name="coin" value="<?= $e($coin) ?>" required>
                <span class="coin-name"><?= $e($coin) ?></span>
                <span class="coin-desc">
                    <?php if ($coin === 'XMR'): ?>Monero — private by default<?php endif; ?>
                    <?php if ($coin === 'ETH'): ?>Ethereum — fast confirmations<?php endif; ?>
                    <?php if ($coin === 'BTC'): ?>Bitcoin<?php endif; ?>
                </span>
            </label>
        <?php endforeach; ?>
    </div>
    <button type="submit" class="btn primary big">Continue →</button>
</form>

<p class="note">Payments are routed through <a href="https://blockbee.io" rel="noopener noreferrer">BlockBee</a>, a non-custodial gateway. Funds forward directly to the merchant's own wallet on the first confirmation — no exchange or processor holds them.</p>
