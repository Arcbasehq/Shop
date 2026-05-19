<?php /** @var array $orders */ ?>
<h1>Admin · Orders</h1>
<nav class="adminnav">
    <a href="/admin">Dashboard</a>
    <a href="/admin/orders">Orders</a>
    <a href="/admin/products">Products</a>
</nav>
<table class="cart-table">
    <thead><tr><th>#</th><th>Customer</th><th>Status</th><th>Total</th><th>Coin</th><th>Tx</th><th>Date</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
            <td><a href="/order/<?= $e($o['order_number']) ?>"><?= $e($o['order_number']) ?></a></td>
            <td><?= $e($o['ship_name']) ?><br><small><?= $e($o['email']) ?></small></td>
            <td><span class="badge <?= $e($o['status']) ?>"><?= $e(str_replace('_',' ',$o['status'])) ?></span></td>
            <td><?= Golders\View::money((int)$o['total_cents']) ?></td>
            <td><?= $e($o['coin'] ?? '—') ?><?php if ((int)($o['confirmations'] ?? 0) > 0): ?> <small>(<?= (int)$o['confirmations'] ?>)</small><?php endif; ?></td>
            <td><small><?= $e(substr((string)($o['paid_tx'] ?? ''), 0, 16)) ?></small></td>
            <td><?= $e($o['created_at']) ?></td>
            <td>
                <?php if ($o['status'] === 'paid'): ?>
                    <form method="post" action="/admin/orders/ship" class="inline">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                        <button class="linkbtn" type="submit">mark shipped</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
