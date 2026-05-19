<?php /** @var array $product */ ?>
<article class="product">
    <div class="product-image">
        <?php if ($product['image_path']): ?>
            <img src="<?= $e($product['image_path']) ?>" alt="<?= $e($product['name']) ?>">
        <?php else: ?>
            <div class="image-placeholder large"></div>
        <?php endif; ?>
    </div>
    <div class="product-detail">
        <h1><?= $e($product['name']) ?></h1>
        <p class="price big"><?= Golders\View::money((int)$product['price_cents']) ?></p>
        <p class="desc"><?= $e($product['description']) ?></p>
        <?php if ((int)$product['stock'] > 0): ?>
            <form method="post" action="/cart/add" class="add-to-cart">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <label>Qty
                    <input type="number" name="qty" min="1" max="<?= (int)$product['stock'] ?>" value="1">
                </label>
                <button type="submit" class="btn primary">Add to cart</button>
            </form>
            <p class="stock-note"><?= (int)$product['stock'] ?> in stock</p>
        <?php else: ?>
            <p class="oos">Currently sold out.</p>
        <?php endif; ?>
    </div>
</article>
