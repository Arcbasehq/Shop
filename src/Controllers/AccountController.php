<?php
declare(strict_types=1);

namespace Golders\Controllers;

use Golders\Auth;
use Golders\Database;
use Golders\View;

final class AccountController
{
    public function loginForm(): void
    {
        View::render('login', ['next' => $_GET['next'] ?? '/account']);
    }

    public function login(): void
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $pwd   = (string)($_POST['password'] ?? '');
        $next  = (string)($_POST['next'] ?? '/account');

        [$ok, $err] = Auth::attemptLogin($email, $pwd);
        if (!$ok) {
            View::render('login', ['error' => $err, 'email' => $email, 'next' => $next]);
            return;
        }
        // Only redirect to local paths.
        if (!preg_match('#^/[A-Za-z0-9_\-/?=&%.]*$#', $next)) $next = '/account';
        header('Location: ' . $next);
    }

    public function registerForm(): void
    {
        View::render('register');
    }

    public function register(): void
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $pwd   = (string)($_POST['password'] ?? '');
        $pwd2  = (string)($_POST['password_confirm'] ?? '');
        if ($pwd !== $pwd2) {
            View::render('register', ['error' => 'Passwords do not match.', 'email' => $email]);
            return;
        }
        [$ok, $err] = Auth::register($email, $pwd);
        if (!$ok) {
            View::render('register', ['error' => $err, 'email' => $email]);
            return;
        }
        header('Location: /account');
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /');
    }

    public function dashboard(): void
    {
        Auth::requireLogin();
        $u = Auth::user();
        $stmt = Database::pdo()->prepare(
            'SELECT order_number, status, total_cents, currency, created_at
             FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 50'
        );
        $stmt->execute([(int)$u['id']]);
        View::render('account', ['user' => $u, 'orders' => $stmt->fetchAll()]);
    }
}
