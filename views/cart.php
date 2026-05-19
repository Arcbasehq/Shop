<?php /** @var array $items */ /** @var int $subtotal */ ?>
<h1>Cart</h1>

<?php if (!$items): ?>
    <p>Your cart is empty. <a href="/">Continue shopping →</a></p>
<?php else: ?>
<table class="cart-table">
    <thead>
        <tr><th>Item</th><th>Price</th><th>Qty</th><th>Line</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($items as $it): ?>
        <tr>
            <td><a href="/product/<?= $e($it['slug']) ?>"><?= $e($it['name']) ?></a></td>
            <td><?= Golders\View::money((int)$it['price_cents']) ?></td>
            <td>
                <form method="post" action="/cart/update" class="inline">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
                    <input type="number" name="qty" min="0" max="<?= (int)$it['stock'] ?>" value="<?= (int)$it['qty'] ?>">
                    <button class="linkbtn" type="submit">update</button>
                </form>
            </td>
            <td><?= Golders\View::money((int)$it['line_cents']) ?></td>
            <td>
                <form method="post" action="/cart/remove" class="inline">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
                    <button class="linkbtn danger" type="submit">remove</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><th colspan="3" class="r">Subtotal</th><th><?= Golders\View::money((int)$subtotal) ?></th><th></th></tr>
    </tfoot>
</table>
<p class="cart-actions">
    <a href="/" class="btn">Keep shopping</a>
    <a href="/checkout" class="btn primary">Checkout →</a>
</p>
<?php endif; ?>
