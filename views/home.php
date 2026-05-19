<?php /** @var array $products */ ?>
<section class="hero">
    <h1>Built to last. Paid in crypto.</h1>
    <p class="lede">Small-batch goods. Settled on-chain in Monero or Ethereum, never with cards.</p>
</section>

<section class="product-grid">
    <?php foreach ($products as $p): ?>
        <article class="card">
            <a class="card-image" href="/product/<?= $e($p['slug']) ?>">
                <?php if ($p['image_path']): ?>
                    <img src="<?= $e($p['image_path']) ?>" alt="<?= $e($p['name']) ?>">
                <?php else: ?>
                    <div class="image-placeholder"></div>
                <?php endif; ?>
            </a>
            <div class="card-body">
                <h3><a href="/product/<?= $e($p['slug']) ?>"><?= $e($p['name']) ?></a></h3>
                <p class="price"><?= Golders\View::money((int)$p['price_cents']) ?></p>
                <?php if ((int)$p['stock'] <= 0): ?>
                    <p class="oos">Sold out</p>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
