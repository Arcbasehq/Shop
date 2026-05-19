<?php
declare(strict_types=1);

namespace Golders\Controllers;

use Golders\Database;
use Golders\View;

final class ShopController
{
    public function index(): void
    {
        $stmt = Database::pdo()->query(
            'SELECT id, slug, name, description, price_cents, stock, image_path
             FROM products WHERE active = 1 ORDER BY id ASC'
        );
        View::render('home', ['products' => $stmt->fetchAll()]);
    }

    public function show(string $slug): void
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, slug, name, description, price_cents, stock, image_path
             FROM products WHERE slug = ? AND active = 1 LIMIT 1'
        );
        $stmt->execute([$slug]);
        $product = $stmt->fetch();
        if (!$product) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        View::render('product', ['product' => $product]);
    }
}
