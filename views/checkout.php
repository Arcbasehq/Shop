<?php
/** @var array $items */ /** @var int $subtotal */ /** @var int $shipping */
/** @var int $total */ /** @var array|null $user */
$errors = $errors ?? [];
$form = $form ?? [];
$val = fn(string $k, string $d = '') => $e((string)($form[$k] ?? $d));
?>
<h1>Checkout</h1>

<?php if ($errors): ?>
    <div class="alert error">
        <ul><?php foreach ($errors as $msg): ?><li><?= $e($msg) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="checkout-grid">
    <form method="post" action="/checkout" class="checkout-form" autocomplete="on" novalidate>
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

        <fieldset>
            <legend>Contact</legend>
            <label>Email
                <input type="email" name="email" required autocomplete="email"
                       value="<?= $val('email', (string)($user['email'] ?? '')) ?>">
            </label>
        </fieldset>

        <fieldset>
            <legend>Shipping address</legend>
            <label>Full name
                <input name="ship_name" required maxlength="190" value="<?= $val('ship_name') ?>">
            </label>
            <label>Address line 1
                <input name="ship_address1" required maxlength="255" value="<?= $val('ship_address1') ?>">
            </label>
            <label>Address line 2 (optional)
                <input name="ship_address2" maxlength="255" value="<?= $val('ship_address2') ?>">
            </label>
            <div class="row">
                <label>City
                    <input name="ship_city" required maxlength="120" value="<?= $val('ship_city') ?>">
                </label>
                <label>State / region
                    <input name="ship_state" maxlength="120" value="<?= $val('ship_state') ?>">
                </label>
            </div>
            <div class="row">
                <label>Postcode
                    <input name="ship_postcode" required maxlength="40" value="<?= $val('ship_postcode') ?>">
                </label>
                <label>Country (ISO-2)
                    <input name="ship_country" required maxlength="2" pattern="[A-Za-z]{2}" value="<?= $val('ship_country', 'US') ?>">
                </label>
            </div>
        </fieldset>

        <button type="submit" class="btn primary big">Place order &amp; pay with crypto →</button>
        <p class="note">You'll be shown a Monero / Ethereum payment screen on the next step. Your order ships once the on-chain payment confirms.</p>
    </form>

    <aside class="order-summary">
        <h2>Order</h2>
        <ul>
            <?php foreach ($items as $it): ?>
                <li>
                    <span class="qty"><?= (int)$it['qty'] ?>×</span>
                    <span class="name"><?= $e($it['name']) ?></span>
                    <span class="line"><?= Golders\View::money((int)$it['line_cents']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <dl>
            <dt>Subtotal</dt><dd><?= Golders\View::money((int)$subtotal) ?></dd>
            <dt>Shipping</dt><dd><?= Golders\View::money((int)$shipping) ?></dd>
            <dt class="total">Total</dt><dd class="total"><?= Golders\View::money((int)$total) ?></dd>
        </dl>
    </aside>
</div>
