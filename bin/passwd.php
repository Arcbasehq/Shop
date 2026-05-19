<?php
declare(strict_types=1);

/**
 * Reset a user's password. Usage: php bin/passwd.php <email>
 * Prompts for the new password twice; hash is Argon2id.
 */

if (PHP_SAPI !== 'cli') exit("Run from the CLI only.\n");

$root = dirname(__DIR__);
$configPath = $root . '/config/config.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "Missing config/config.php.\n"); exit(1);
}
$config = require $configPath;

$email = $argv[1] ?? '';
if (!$email) {
    fwrite(STDERR, "Usage: php bin/passwd.php <email>\n"); exit(1);
}

function prompt(string $msg, bool $hidden = false): string {
    fwrite(STDOUT, $msg);
    if ($hidden && stripos(PHP_OS, 'WIN') === false) {
        system('stty -echo');
        $v = trim((string)fgets(STDIN));
        system('stty echo');
        fwrite(STDOUT, "\n");
        return $v;
    }
    return trim((string)fgets(STDIN));
}

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['db']['host'], (int)$config['db']['port'], $config['db']['name'], $config['db']['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "DB connect failed: " . $e->getMessage() . "\n"); exit(2);
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    fwrite(STDERR, "No user with email: $email\n"); exit(3);
}

$pw1 = prompt("New password (>=12 chars, hidden): ", true);
$pw2 = prompt("Confirm password (hidden): ", true);
if ($pw1 !== $pw2)    { fwrite(STDERR, "Passwords don't match.\n"); exit(4); }
if (strlen($pw1) < 12){ fwrite(STDERR, "Too short.\n"); exit(5); }

$hash = password_hash($pw1, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1,
]);
$upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
$upd->execute([$hash, (int)$row['id']]);
echo "Password updated for $email\n";
