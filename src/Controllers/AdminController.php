<?php
declare(strict_types=1);

namespace Golders\Controllers;

use Golders\Auth;
use Golders\Database;
use Golders\View;

final class AdminController
{
    public function dashboard(): void
    {
        Auth::requireAdmin();
        $pdo = Database::pdo();
        $stats = [
            'orders_total'  => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'orders_paid'   => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('paid','shipped')")->fetchColumn(),
            'orders_unpaid' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('new','awaiting_payment')")->fetchColumn(),
            'revenue_cents' => (int)$pdo->query("SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE status IN ('paid','shipped')")->fetchColumn(),
        ];
        View::render('admin/dashboard', ['stats' => $stats]);
    }

    public function orders(): void
    {
        Auth::requireAdmin();
        $rows = Database::pdo()->query(
            'SELECT id, order_number, status, total_cents, currency, email, ship_name, coin, paid_tx, confirmations, created_at
             FROM orders ORDER BY id DESC LIMIT 200'
        )->fetchAll();
        View::render('admin/orders', ['orders' => $rows]);
    }

    public function markShipped(): void
    {
        Auth::requireAdmin();
        $id = (int)($_POST['order_id'] ?? 0);
        $stmt = Database::pdo()->prepare(
            'UPDATE orders SET status = "shipped" WHERE id = ? AND status = "paid"'
        );
        $stmt->execute([$id]);
        header('Location: /admin/orders');
    }

    public function products(): void
    {
        Auth::requireAdmin();
        $rows = Database::pdo()->query(
            'SELECT id, slug, name, price_cents, currency, stock, active FROM products ORDER BY id ASC'
        )->fetchAll();
        View::render('admin/products', ['products' => $rows]);
    }

    public function saveProduct(): void
    {
        Auth::requireAdmin();
        $id     = (int)($_POST['id'] ?? 0);
        $stock  = max(0, (int)($_POST['stock'] ?? 0));
        $price  = max(0, (int)round(((float)($_POST['price'] ?? 0)) * 100));
        $active = isset($_POST['active']) ? 1 : 0;

        $stmt = Database::pdo()->prepare(
            'UPDATE products SET price_cents = ?, stock = ?, active = ? WHERE id = ?'
        );
        $stmt->execute([$price, $stock, $active, $id]);
        header('Location: /admin/products');
    }
}
