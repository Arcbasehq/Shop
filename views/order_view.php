<?php /** @var array $order */ /** @var array $items */ ?>
<h1>Order <?= $e($order['order_number']) ?></h1>
<p>Status: <span class="badge <?= $e($order['status']) ?>"><?= $e(str_replace('_',' ',$order['status'])) ?></span></p>

<table class="cart-table">
    <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Line</th></tr></thead>
    <tbody>
        <?php foreach ($items as $it): ?>
        <tr>
            <td><?= $e($it['name_snapshot']) ?></td>
            <td><?= (int)$it['qty'] ?></td>
            <td><?= Golders\View::money((int)$it['price_cents']) ?></td>
            <td><?= Golders\View::money((int)$it['price_cents'] * (int)$it['qty']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><th colspan="3" class="r">Total</th><th><?= Golders\View::money((int)$order['total_cents']) ?></th></tr>
    </tfoot>
</table>

<h3>Ship to</h3>
<address>
    <?= $e($order['ship_name']) ?><br>
    <?= $e($order['ship_address1']) ?><br>
    <?php if ($order['ship_address2']): ?><?= $e($order['ship_address2']) ?><br><?php endif; ?>
    <?= $e($order['ship_city']) ?><?= $order['ship_state'] ? ', ' . $e($order['ship_state']) : '' ?> <?= $e($order['ship_postcode']) ?><br>
    <?= $e($order['ship_country']) ?>
</address>

<?php if (in_array($order['status'], ['new','awaiting_payment'], true)): ?>
    <p><a class="btn primary" href="/order/<?= $e($order['order_number']) ?>/pay">Pay now</a></p>
<?php endif; ?>
