<?php
declare(strict_types=1);

/**
 * Interactive installer. Runs schema + seed and creates the first admin user.
 * Usage: php bin/install.php
 */

if (PHP_SAPI !== 'cli') {
    exit("Run from the CLI only.\n");
}

$root = dirname(__DIR__);
$configPath = $root . '/config/config.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "Copy config/config.example.php → config/config.php first.\n");
    exit(1);
}
$config = require $configPath;

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

// ---- Connect ---------------------------------------------------------------
try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['db']['host'], (int)$config['db']['port'], $config['db']['name'], $config['db']['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "DB connect failed: " . $e->getMessage() . "\n");
    exit(2);
}
echo "Connected to {$config['db']['name']} @ {$config['db']['host']}\n";

// ---- Schema ----------------------------------------------------------------
echo "Applying schema.sql ...\n";
$schema = file_get_contents($root . '/config/schema.sql');
$pdo->exec($schema);

// ---- Seed (only if products table is empty) --------------------------------
$has = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
if ($has === 0) {
    echo "Seeding products ...\n";
    $pdo->exec(file_get_contents($root . '/config/seed.sql'));
} else {
    echo "Products already exist, skipping seed.\n";
}

// ---- Admin user ------------------------------------------------------------
$exists = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_admin = 1')->fetchColumn();
if ($exists > 0) {
    echo "Admin already exists. Done.\n";
    exit(0);
}

$email = prompt("Admin email: ");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid email.\n"); exit(3);
}
$pw1 = prompt("Admin password (>=12 chars, hidden): ", true);
$pw2 = prompt("Confirm password (hidden): ", true);
if ($pw1 !== $pw2) { fwrite(STDERR, "Passwords don't match.\n"); exit(4); }
if (strlen($pw1) < 12) { fwrite(STDERR, "Too short.\n"); exit(5); }

$hash = password_hash($pw1, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1,
]);
$stmt = $pdo->prepare('INSERT INTO users (email, password_hash, is_admin) VALUES (?, ?, 1)');
$stmt->execute([$email, $hash]);
echo "Admin created: $email\n";
echo "Done.\n";
