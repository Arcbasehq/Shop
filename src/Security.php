<?php
declare(strict_types=1);

namespace Golders;

/**
 * Cross-cutting security helpers: response headers, CSRF tokens,
 * constant-time comparison, escaping, password hashing.
 */
final class Security
{
    /** Send hardened response headers. Called once from bootstrap. */
    public static function sendHeaders(): void
    {
        // Defense-in-depth headers.
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
        // No external scripts; inline styles are not used.
        header("Content-Security-Policy: default-src 'self'; "
             . "script-src 'self'; "
             . "style-src 'self'; "
             // BlockBee serves the payment-QR image.
             . "img-src 'self' data: https://api.blockbee.io; "
             . "connect-src 'self'; "
             . "form-action 'self'; "
             . "frame-ancestors 'none'; "
             . "base-uri 'self'; "
             . "object-src 'none'");
        // HSTS is only honored over HTTPS; safe to always emit.
        header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
        // Don't expose PHP version.
        header_remove('X-Powered-By');
    }

    /** Get (and lazily create) the current session CSRF token. */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /** Verify a submitted CSRF token in constant time. Aborts on failure. */
    public static function requireCsrf(): void
    {
        $sent = (string)($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $expected = $_SESSION['_csrf'] ?? '';
        if ($expected === '' || !hash_equals($expected, $sent)) {
            http_response_code(419);
            exit('CSRF token mismatch.');
        }
    }

    /** Rotate CSRF token. Call after privileged state transitions (login). */
    public static function rotateCsrf(): void
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    /** HTML-escape for output. */
    public static function e(?string $v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Argon2id with sane params. PHP picks safe defaults if constants exist. */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MiB
            'time_cost'   => 4,
            'threads'     => 1,
        ]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /** Generate a printable, URL-safe random token. */
    public static function randomToken(int $bytes = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    /** ULID-ish order number: 26-char Crockford base32, time-prefixed, sortable. */
    public static function orderNumber(): string
    {
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $ms = (int)(microtime(true) * 1000);
        $time = '';
        for ($i = 0; $i < 10; $i++) {
            $time = $alphabet[$ms & 31] . $time;
            $ms >>= 5;
        }
        $rand = '';
        $bytes = random_bytes(10);
        for ($i = 0; $i < 16; $i++) {
            $rand .= $alphabet[ord($bytes[$i >> 1]) >> (($i & 1) ? 0 : 3) & 31];
        }
        return $time . $rand;
    }

    /** Return the client IP packed as 4 or 16 bytes for storage. */
    public static function clientIpBin(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $packed = @inet_pton($ip);
        return $packed === false ? str_repeat("\0", 4) : $packed;
    }
}
