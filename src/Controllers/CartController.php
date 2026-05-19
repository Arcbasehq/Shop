<?php
declare(strict_types=1);

namespace Golders\Controllers;

use Golders\Cart;
use Golders\View;

final class CartController
{
    public function show(): void
    {
        [$items, $subtotal] = Cart::hydrate();
        View::render('cart', ['items' => $items, 'subtotal' => $subtotal]);
    }

    public function add(): void
    {
        $id = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        if ($id > 0) Cart::add($id, $qty);
        header('Location: /cart');
    }

    public function update(): void
    {
        $id = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 0);
        if ($id > 0) Cart::set($id, $qty);
        header('Location: /cart');
    }

    public function remove(): void
    {
        $id = (int)($_POST['product_id'] ?? 0);
        if ($id > 0) Cart::remove($id);
        header('Location: /cart');
    }
}
