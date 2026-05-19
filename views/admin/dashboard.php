<?php /** @var array $stats */ ?>
<h1>Admin</h1>
<nav class="adminnav">
    <a href="/admin">Dashboard</a>
    <a href="/admin/orders">Orders</a>
    <a href="/admin/products">Products</a>
</nav>
<div class="stat-grid">
    <div class="stat"><div class="num"><?= (int)$stats['orders_total'] ?></div><div class="lbl">Orders</div></div>
    <div class="stat"><div class="num"><?= (int)$stats['orders_paid'] ?></div><div class="lbl">Paid / shipped</div></div>
    <div class="stat"><div class="num"><?= (int)$stats['orders_unpaid'] ?></div><div class="lbl">Awaiting payment</div></div>
    <div class="stat"><div class="num"><?= Golders\View::money((int)$stats['revenue_cents']) ?></div><div class="lbl">Revenue</div></div>
</div>
