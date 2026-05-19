<?php
declare(strict_types=1);

namespace Golders\Controllers;

use Golders\BlockBeeClient;
use Golders\Database;

/**
 * BlockBee callback receiver.
 *
 * BlockBee POSTs (JSON) when a tx is detected for a deposit address. We
 * authenticate the callback by matching a per-order token embedded in the
 * callback URL against the token we stored when the address was created.
 * As defense in depth, we then re-fetch the payment logs from BlockBee and
 * only flip the order to "paid" once the on-chain confirmations meet the
 * configured minimum.
 */
final class WebhookController
{
    public function __construct(
        private BlockBeeClient $blockbee,
        private array $cfg,
    ) {}

    public function blockbee(): void
    {
        $orderNumber = (string)($_GET['order'] ?? '');
        $token       = (string)($_GET['token'] ?? '');
        if ($orderNumber === '' || $token === '') {
            http_response_code(400); echo 'missing'; return;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT * FROM orders WHERE order_number = ? LIMIT 1'
        );
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch();
        if (!$order) { http_response_code(404); echo 'unknown'; return; }

        // Constant-time token comparison.
        $expected = (string)($order['callback_token'] ?? '');
        if ($expected === '' || !hash_equals($expected, $token)) {
            error_log('[blockbee] token mismatch for order ' . $orderNumber);
            http_response_code(401); echo 'unauthorized'; return;
        }

        // Parse the callback body for the txid + value (informational).
        $body = file_get_contents('php://input') ?: '';
        $payload = json_decode($body, true) ?: $_POST;
        $txid       = (string)($payload['txid_in']       ?? $payload['txid'] ?? '');
        $valueCoin  = (string)($payload['value_coin']    ?? '');
        $confirms   = (int)   ($payload['confirmations'] ?? 0);

        // Authoritative re-check: ask BlockBee directly using our callback URL.
        $callbackUrl = rtrim((string)($GLOBALS['_app_url'] ?? ''), '/')
            . '/webhook/blockbee?order=' . urlencode($orderNumber)
            . '&token=' . urlencode($token);

        try {
            $info = $this->blockbee->paymentInfo((string)$order['coin'], $callbackUrl);
        } catch (\Throwable $e) {
            error_log('[blockbee] logs fetch failed: ' . $e->getMessage());
            http_response_code(500); echo 'verify failed'; return;
        }

        $minConfirms = (int)($this->cfg['blockbee']['min_confirmations'][$order['coin']] ?? 1);
        $totalReceived = 0.0;
        $bestConfirms  = 0;
        $bestTxid      = '';
        foreach (($info['callbacks'] ?? []) as $cb) {
            $c = (int)($cb['confirmations'] ?? 0);
            if ($c >= $minConfirms) {
                $totalReceived += (float)($cb['value_coin'] ?? 0);
                if ($c > $bestConfirms) {
                    $bestConfirms = $c;
                    $bestTxid     = (string)($cb['txid_in'] ?? '');
                }
            }
        }

        $expectedAmount = (float)$order['expected_coin_amount'];
        // Persist whatever we just learned, even if not yet enough to settle.
        $upd = $pdo->prepare(
            'UPDATE orders
             SET confirmations = GREATEST(confirmations, ?),
                 paid_tx = COALESCE(NULLIF(?, ""), paid_tx),
                 paid_amount = ?
             WHERE id = ?'
        );
        $upd->execute([
            max($confirms, $bestConfirms),
            $bestTxid !== '' ? $bestTxid : $txid,
            (string)$totalReceived,
            (int)$order['id'],
        ]);

        // Only flip to paid when confirmations are enough AND the on-chain
        // value covers the expected amount (allow tiny rounding slack).
        if ($bestConfirms >= $minConfirms && $totalReceived + 1e-12 >= $expectedAmount) {
            $pdo->prepare(
                'UPDATE orders SET status = "paid"
                 WHERE id = ? AND status IN ("new","awaiting_payment")'
            )->execute([(int)$order['id']]);

            // BlockBee considers callback "handled" when our response body is
            // exactly "*ok*" and it stops retrying.
            echo '*ok*';
            return;
        }

        // Acknowledge but don't say *ok* yet - BlockBee will retry on next
        // confirmation tick until we hit the threshold.
        echo 'pending';
    }
}
