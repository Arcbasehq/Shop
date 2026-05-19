<?php /** @var array $user */ /** @var array $orders */ ?>
<h1>Your account</h1>
<p>Signed in as <strong><?= $e($user['email']) ?></strong>.</p>

<h2>Recent orders</h2>
<?php if (!$orders): ?>
    <p>No orders yet. <a href="/">Start shopping →</a></p>
<?php else: ?>
<table class="cart-table">
    <thead><tr><th>Order</th><th>Status</th><th>Total</th><th>Date</th></tr></thead>
    <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
            <td><a href="/order/<?= $e($o['order_number']) ?>"><?= $e($o['order_number']) ?></a></td>
            <td><span class="badge <?= $e($o['status']) ?>"><?= $e(str_replace('_',' ',$o['status'])) ?></span></td>
            <td><?= Golders\View::money((int)$o['total_cents']) ?></td>
            <td><?= $e($o['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
