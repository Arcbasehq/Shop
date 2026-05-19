<?php
/** @var array $order */
/** @var string $qr */
/** @var int $minConfirmations */
?>
<h1>Send <?= $e($order['coin']) ?> to complete order</h1>

<div class="pay-grid">
    <div class="pay-panel">
        <p>Order: <code><?= $e($order['order_number']) ?></code></p>
        <p>Status: <span class="badge <?= $e($order['status']) ?>"><?= $e(str_replace('_',' ',$order['status'])) ?></span></p>

        <div class="pay-amount">
            <span class="lbl">Send exactly</span>
            <span class="amt"><?= $e($order['expected_coin_amount']) ?> <?= $e($order['coin']) ?></span>
            <span class="fiat">≈ <?= Golders\View::money((int)$order['total_cents'], $order['currency']) ?></span>
        </div>

        <div class="pay-address">
            <span class="lbl">To this address</span>
            <code class="address" id="depositAddr"><?= $e($order['deposit_address']) ?></code>
        </div>

        <div class="pay-qr">
            <img src="<?= $e($qr) ?>" alt="Payment QR code" width="256" height="256">
        </div>

        <p class="note">
            Order auto-confirms after <strong><?= (int)$minConfirmations ?></strong> on-chain confirmations.
            You can safely close this tab and return — the status updates automatically.
        </p>
    </div>

    <aside class="pay-meta">
        <h3>Confirmations</h3>
        <p>So far: <strong><?= (int)$order['confirmations'] ?></strong> / <?= (int)$minConfirmations ?></p>
        <?php if (!empty($order['paid_tx'])): ?>
            <h3>Transaction</h3>
            <code><?= $e($order['paid_tx']) ?></code>
        <?php endif; ?>
        <h3>Why no card?</h3>
        <p>This shop never asks for an identity. Payments are routed through a non-custodial gateway straight to the merchant's own <?= $e($order['coin']) ?> wallet.</p>
    </aside>
</div>

<p><a href="/order/<?= $e($order['order_number']) ?>">View order details</a></p>

<script src="/assets/js/pay.js" defer></script>
