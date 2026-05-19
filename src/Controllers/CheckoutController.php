<?php
declare(strict_types=1);

namespace Golders\Controllers;

use Golders\Auth;
use Golders\BlockBeeClient;
use Golders\Cart;
use Golders\Database;
use Golders\Security;
use Golders\View;

final class CheckoutController
{
    private const SHIPPING_CENTS = 800; // flat $8.00

    public function __construct(
        private BlockBeeClient $blockbee,
        private array $cfg,
    ) {}

    public function show(): void
    {
        [$items, $subtotal] = Cart::hydrate();
        if (!$items) { header('Location: /cart'); return; }
        $user = Auth::user();
        View::render('checkout', [
            'items'    => $items,
            'subtotal' => $subtotal,
            'shipping' => self::SHIPPING_CENTS,
            'total'    => $subtotal + self::SHIPPING_CENTS,
            'user'     => $user,
        ]);
    }

    public function place(): void
    {
        [$items, $subtotal] = Cart::hydrate();
        if (!$items) { header('Location: /cart'); return; }

        $errors = [];
        $email    = trim((string)($_POST['email'] ?? ''));
        $name     = trim((string)($_POST['ship_name'] ?? ''));
        $address1 = trim((string)($_POST['ship_address1'] ?? ''));
        $address2 = trim((string)($_POST['ship_address2'] ?? ''));
        $city     = trim((string)($_POST['ship_city'] ?? ''));
        $state    = trim((string)($_POST['ship_state'] ?? ''));
        $postcode = trim((string)($_POST['ship_postcode'] ?? ''));
        $country  = strtoupper(trim((string)($_POST['ship_country'] ?? '')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        if ($name === '' || strlen($name) > 190)        $errors[] = 'Recipient name required.';
        if ($address1 === '')                            $errors[] = 'Address required.';
        if ($city === '')                                $errors[] = 'City required.';
        if ($postcode === '')                            $errors[] = 'Postcode required.';
        if (!preg_match('/^[A-Z]{2}$/', $country))       $errors[] = 'Country must be a 2-letter ISO code.';

        if ($errors) {
            View::render('checkout', [
                'items'    => $items,
                'subtotal' => $subtotal,
                'shipping' => self::SHIPPING_CENTS,
                'total'    => $subtotal + self::SHIPPING_CENTS,
                'user'     => Auth::user(),
                'errors'   => $errors,
                'form'     => $_POST,
            ]);
            return;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $ids = array_column($items, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $lock = $pdo->prepare("SELECT id, stock FROM products WHERE id IN ($placeholders) FOR UPDATE");
            $lock->execute($ids);
            $stockMap = [];
            foreach ($lock->fetchAll() as $r) $stockMap[(int)$r['id']] = (int)$r['stock'];

            foreach ($items as $it) {
                if (($stockMap[(int)$it['id']] ?? 0) < (int)$it['qty']) {
                    throw new \RuntimeException('Insufficient stock for ' . $it['name']);
                }
            }

            $orderNumber = Security::orderNumber();
            $total = $subtotal + self::SHIPPING_CENTS;
            $user = Auth::user();

            $ins = $pdo->prepare(
                'INSERT INTO orders
                 (user_id, order_number, status, subtotal_cents, shipping_cents, total_cents, currency,
                  email, ship_name, ship_address1, ship_address2, ship_city, ship_state, ship_postcode, ship_country)
                 VALUES (?, ?, "new", ?, ?, ?, "USD", ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $user['id'] ?? null,
                $orderNumber,
                $subtotal, self::SHIPPING_CENTS, $total,
                $email, $name, $address1, $address2 ?: null,
                $city, $state ?: null, $postcode, $country,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $itemIns = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, name_snapshot, price_cents, qty)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $decStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            foreach ($items as $it) {
                $itemIns->execute([$orderId, (int)$it['id'], $it['name'], (int)$it['price_cents'], (int)$it['qty']]);
                $decStock->execute([(int)$it['qty'], (int)$it['id']]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            View::render('checkout', [
                'items' => $items, 'subtotal' => $subtotal,
                'shipping' => self::SHIPPING_CENTS, 'total' => $subtotal + self::SHIPPING_CENTS,
                'user' => Auth::user(),
                'errors' => ['Could not place order: ' . $e->getMessage()],
                'form' => $_POST,
            ]);
            return;
        }

        Cart::clear();
        header('Location: /order/' . $orderNumber . '/pay');
    }

    /**
     * Payment page. If no coin has been chosen, show the coin picker. If a
     * coin has been chosen but no deposit address yet, generate one via
     * BlockBee. If we already have an address, show payment details.
     */
    public function pay(string $orderNumber): void
    {
        $order = $this->loadOrder($orderNumber);
        if (!$order) { http_response_code(404); View::render('errors/404'); return; }

        if (in_array($order['status'], ['paid','shipped'], true)) {
            header('Location: /order/' . $orderNumber);
            return;
        }

        $accepted = $this->cfg['blockbee']['accepted_coins'] ?? [];
        $qr = null;

        if (empty($order['coin']) || empty($order['deposit_address'])) {
            View::render('order_pay_select', [
                'order'    => $order,
                'accepted' => $accepted,
            ]);
            return;
        }

        $qr = $this->blockbee->qrUrl(
            $order['coin'],
            $order['deposit_address'],
            (string)$order['expected_coin_amount']
        );

        View::render('order_pay', [
            'order' => $order,
            'qr'    => $qr,
            'minConfirmations' => $this->cfg['blockbee']['min_confirmations'][$order['coin']] ?? 1,
        ]);
    }

    /**
     * POST handler: customer picked a coin. Generate a BlockBee deposit
     * address and persist it (along with a callback token) on the order.
     */
    public function selectCoin(string $orderNumber): void
    {
        $order = $this->loadOrder($orderNumber);
        if (!$order) { http_response_code(404); View::render('errors/404'); return; }
        if (in_array($order['status'], ['paid','shipped'], true)) {
            header('Location: /order/' . $orderNumber); return;
        }

        $coin = strtoupper(trim((string)($_POST['coin'] ?? '')));
        $accepted = $this->cfg['blockbee']['accepted_coins'] ?? [];
        if (!in_array($coin, $accepted, true)) {
            http_response_code(400);
            View::render('order_pay_select', [
                'order' => $order, 'accepted' => $accepted,
                'error' => 'Please choose a supported coin.',
            ]);
            return;
        }

        try {
            $coinAmount = $this->blockbee->fiatToCoin(
                $coin, $order['total_cents'] / 100, $order['currency']
            );
            $token = Security::randomToken(24);
            $callbackUrl = rtrim((string)($GLOBALS['_app_url'] ?? ''), '/')
                . '/webhook/blockbee?order=' . urlencode($order['order_number'])
                . '&token=' . urlencode($token);

            $created = $this->blockbee->createPayment($coin, $callbackUrl);
        } catch (\Throwable $e) {
            error_log('[blockbee] create failed: ' . $e->getMessage());
            View::render('order_pay_select', [
                'order' => $order, 'accepted' => $accepted,
                'error' => 'Payment provider error: ' . $e->getMessage(),
            ]);
            return;
        }

        $upd = Database::pdo()->prepare(
            'UPDATE orders
             SET coin = ?, deposit_address = ?, expected_coin_amount = ?,
                 callback_token = ?, status = "awaiting_payment"
             WHERE id = ? AND status IN ("new","awaiting_payment")'
        );
        $upd->execute([
            $coin,
            $created['address_in'],
            $coinAmount,
            $token,
            (int)$order['id'],
        ]);

        header('Location: /order/' . $orderNumber . '/pay');
    }

    public function view(string $orderNumber): void
    {
        $order = $this->loadOrder($orderNumber);
        if (!$order) { http_response_code(404); View::render('errors/404'); return; }

        $items = Database::pdo()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $items->execute([(int)$order['id']]);

        View::render('order_view', ['order' => $order, 'items' => $items->fetchAll()]);
    }

    private function loadOrder(string $orderNumber): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM orders WHERE order_number = ? LIMIT 1'
        );
        $stmt->execute([$orderNumber]);
        return $stmt->fetch() ?: null;
    }
}
