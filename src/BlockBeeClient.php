<?php
declare(strict_types=1);

namespace Golders;

/**
 * BlockBee (formerly CryptAPI) client.
 *
 * Non-custodial payment gateway: BlockBee generates a unique deposit address
 * per order and forwards received coins straight to your own wallet. No
 * account, no API key, no KYC. Docs: https://docs.blockbee.io/
 *
 * Flow:
 *  1. createPayment(coin, fiatAmount, ...) -> returns the buyer's deposit
 *     address + the exact coin amount to send.
 *  2. BlockBee POSTs to your callback URL once the tx is detected; we verify
 *     a HMAC token embedded in the URL AND re-call /info to confirm.
 */
final class BlockBeeClient
{
    private const BASE = 'https://api.blockbee.io';

    public function __construct(private array $cfg) {}

    /** Map our coin codes to BlockBee tickers. */
    private function ticker(string $coin): string
    {
        $map = ['ETH' => 'eth', 'XMR' => 'xmr', 'BTC' => 'btc'];
        $c = strtoupper($coin);
        if (!isset($map[$c])) {
            throw new \InvalidArgumentException("Unsupported coin: $coin");
        }
        return $map[$c];
    }

    /** The merchant wallet address that BlockBee forwards funds to. */
    private function merchantAddress(string $coin): string
    {
        $c = strtoupper($coin);
        $addr = $this->cfg['wallets'][$c] ?? '';
        if ($addr === '') {
            throw new \RuntimeException("No merchant wallet configured for $coin");
        }
        return $addr;
    }

    /**
     * Convert a fiat amount to the equivalent coin amount via BlockBee's
     * conversion endpoint. Returns the coin amount as a string (preserve
     * precision; never use floats for crypto values).
     */
    public function fiatToCoin(string $coin, float $fiatAmount, string $fiatCurrency = 'USD'): string
    {
        $t = $this->ticker($coin);
        $res = $this->get("/$t/convert/", [
            'value' => number_format($fiatAmount, 8, '.', ''),
            'from'  => strtolower($fiatCurrency),
        ]);
        if (($res['status'] ?? '') !== 'success') {
            throw new \RuntimeException('BlockBee conversion failed: ' . json_encode($res));
        }
        return (string)$res['value_coin'];
    }

    /**
     * Create a deposit address for an order. Returns:
     *   ['address_in' => '...', 'address_out' => '...', 'callback_url' => '...']
     */
    public function createPayment(string $coin, string $callbackUrl): array
    {
        $t = $this->ticker($coin);
        $merchant = $this->merchantAddress($coin);
        $res = $this->get("/$t/create/", [
            'callback' => $callbackUrl,
            'address'  => $merchant,
            'pending'  => 0,    // only callback on confirmed payments
            'post'     => 1,    // BlockBee POSTs the callback
            'json'     => 1,    // callback body is JSON
            'priority' => 'default',
        ]);
        if (($res['status'] ?? '') !== 'success') {
            throw new \RuntimeException('BlockBee create failed: ' . json_encode($res));
        }
        return [
            'address_in'   => (string)$res['address_in'],
            'address_out'  => (string)$res['address_out'],
            'callback_url' => (string)$res['callback_url'],
        ];
    }

    /**
     * Re-fetch payment info from BlockBee. Used in the callback handler to
     * authoritatively confirm that money actually arrived before flipping
     * order status (defense in depth on top of the URL token).
     */
    public function paymentInfo(string $coin, string $callbackUrl): array
    {
        $t = $this->ticker($coin);
        return $this->get("/$t/logs/", ['callback' => $callbackUrl]);
    }

    /** URL of a QR code SVG for a deposit address + amount. */
    public function qrUrl(string $coin, string $address, string $coinAmount, int $size = 256): string
    {
        $t = $this->ticker($coin);
        $qs = http_build_query([
            'address' => $address,
            'value'   => $coinAmount,
            'size'    => $size,
        ]);
        return self::BASE . "/$t/qrcode/?$qs";
    }

    private function get(string $path, array $params): array
    {
        $url = self::BASE . $path . '?' . http_build_query($params);
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('BlockBee request failed: ' . $err);
        }
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode($resp, true);
        if ($code >= 400 || !is_array($decoded)) {
            throw new \RuntimeException("BlockBee HTTP $code: $resp");
        }
        return $decoded;
    }
}
