<?php
declare(strict_types=1);

namespace Golders;

use PDO;

/**
 * Session-based auth with rate-limited login.
 */
final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SEC   = 900; // 15 min

    public static function user(): ?array
    {
        if (empty($_SESSION['uid'])) return null;
        $stmt = Database::pdo()->prepare(
            'SELECT id, email, is_admin, created_at FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int)$_SESSION['uid']]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function isLoggedIn(): bool { return !empty($_SESSION['uid']); }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && (int)$u['is_admin'] === 1;
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /login?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            exit('Forbidden.');
        }
    }

    /**
     * Attempts to log a user in. Returns [success, errorMessage].
     */
    public static function attemptLogin(string $email, string $password): array
    {
        if (self::isThrottled($email)) {
            return [false, 'Too many attempts. Try again later.'];
        }

        $stmt = Database::pdo()->prepare(
            'SELECT id, password_hash, is_admin FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        // Always verify against *something* to keep timing roughly constant
        // for unknown vs known emails.
        $hash = $row['password_hash']
            ?? '$argon2id$v=19$m=65536,t=4,p=1$ZmFrZWZha2VmYWtl$AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
        $ok = Security::verifyPassword($password, $hash) && $row !== false;

        self::recordAttempt($email, $ok);

        if (!$ok) return [false, 'Invalid credentials.'];

        // Rotate session ID on privilege change (fixation defense).
        session_regenerate_id(true);
        Security::rotateCsrf();
        $_SESSION['uid'] = (int)$row['id'];

        // Rehash if params drifted.
        if (password_needs_rehash($row['password_hash'], PASSWORD_ARGON2ID)) {
            $newHash = Security::hashPassword($password);
            $upd = Database::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $upd->execute([$newHash, (int)$row['id']]);
        }

        return [true, ''];
    }

    public static function register(string $email, string $password): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return [false, 'Invalid email.'];
        if (strlen($password) < 12) return [false, 'Password must be at least 12 characters.'];
        if (strlen($password) > 256) return [false, 'Password too long.'];

        $pdo = Database::pdo();
        $exists = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $exists->execute([$email]);
        if ($exists->fetchColumn()) return [false, 'An account with that email already exists.'];

        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password_hash, is_admin) VALUES (?, ?, 0)'
        );
        $stmt->execute([$email, Security::hashPassword($password)]);

        session_regenerate_id(true);
        Security::rotateCsrf();
        $_SESSION['uid'] = (int)$pdo->lastInsertId();
        return [true, ''];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ---- Rate limiting -----------------------------------------------------

    private static function isThrottled(string $email): bool
    {
        $pdo = Database::pdo();
        $ip = Security::clientIpBin();
        $since = (new \DateTimeImmutable('-' . self::WINDOW_SEC . ' seconds'))->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE success = 0 AND created_at > ?
             AND (ip = ? OR email = ?)'
        );
        $stmt->execute([$since, $ip, $email]);
        return ((int)$stmt->fetchColumn()) >= self::MAX_ATTEMPTS;
    }

    private static function recordAttempt(string $email, bool $success): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO login_attempts (ip, email, success) VALUES (?, ?, ?)'
        );
        $stmt->execute([Security::clientIpBin(), $email, $success ? 1 : 0]);
    }
}
