<?php
declare(strict_types=1);

namespace Golders;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connect(array $cfg): PDO
    {
        if (self::$pdo !== null) return self::$pdo;

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'], (int)$cfg['port'], $cfg['name'], $cfg['charset'] ?? 'utf8mb4'
        );
        self::$pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ]);
        return self::$pdo;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('Database not initialized.');
        }
        return self::$pdo;
    }
}
