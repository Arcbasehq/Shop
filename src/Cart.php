<?php
declare(strict_types=1);

namespace Golders;

/**
 * Session-backed shopping cart. Stores product_id => qty. Prices are always
 * re-read from the DB at checkout time (never trust session-stored prices).
 */
final class Cart
{
    public static function items(): array
    {
        return $_SESSION['cart'] ?? [];
    }

    public static function add(int $productId, int $qty = 1): void
    {
        if ($qty < 1) return;
        $cart = self::items();
        $cart[$productId] = ($cart[$productId] ?? 0) + $qty;
        if ($cart[$productId] > 99) $cart[$productId] = 99;
        $_SESSION['cart'] = $cart;
    }

    public static function set(int $productId, int $qty): void
    {
        $cart = self::items();
        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = min(99, $qty);
        }
        $_SESSION['cart'] = $cart;
    }

    public static function remove(int $productId): void
    {
        $cart = self::items();
        unset($cart[$productId]);
        $_SESSION['cart'] = $cart;
    }

    public static function clear(): void { $_SESSION['cart'] = []; }

    /** Hydrates cart with current product rows. Returns [items, subtotalCents]. */
    public static function hydrate(): array
    {
        $cart = self::items();
        if (!$cart) return [[], 0];

        $ids = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT id, slug, name, price_cents, stock, image_path
             FROM products WHERE active = 1 AND id IN ($placeholders)"
        );
        $stmt->execute($ids);

        $items = [];
        $subtotal = 0;
        foreach ($stmt->fetchAll() as $row) {
            $qty = min((int)$cart[$row['id']], (int)$row['stock']);
            if ($qty < 1) continue;
            $row['qty'] = $qty;
            $row['line_cents'] = (int)$row['price_cents'] * $qty;
            $items[] = $row;
            $subtotal += $row['line_cents'];
        }
        return [$items, $subtotal];
    }

    public static function count(): int
    {
        $n = 0;
        foreach (self::items() as $q) $n += (int)$q;
        return $n;
    }
}
