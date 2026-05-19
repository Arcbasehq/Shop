<?php /** @var array $products */ ?>
<h1>Admin · Products</h1>
<nav class="adminnav">
    <a href="/admin">Dashboard</a>
    <a href="/admin/orders">Orders</a>
    <a href="/admin/products">Products</a>
</nav>
<table class="cart-table">
    <thead><tr><th>Slug</th><th>Name</th><th>Price</th><th>Stock</th><th>Active</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
            <form method="post" action="/admin/products">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <td><?= $e($p['slug']) ?></td>
                <td><?= $e($p['name']) ?></td>
                <td><input type="number" step="0.01" min="0" name="price" value="<?= number_format($p['price_cents']/100, 2, '.', '') ?>"></td>
                <td><input type="number" min="0" name="stock" value="<?= (int)$p['stock'] ?>"></td>
                <td><label><input type="checkbox" name="active" value="1" <?= $p['active'] ? 'checked' : '' ?>></label></td>
                <td><button class="btn small" type="submit">save</button></td>
            </form>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
