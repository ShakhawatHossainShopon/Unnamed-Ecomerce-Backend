<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Database Connection Manager (PDO)
 */
class Database
{
    /**
     * @var PDO|null
     */
    private static ?PDO $instance = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get or initialize the PDO connection instance
     *
     * @param array|null $customConfig
     * @return PDO
     * @throws RuntimeException
     */
    public static function getConnection(?array $customConfig = null): PDO
    {
        if (self::$instance === null) {
            $config = $customConfig ?? require dirname(__DIR__, 2) . '/config/database.php';

            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $config['driver'] ?? 'mysql',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? 3306,
                $config['database'] ?? '',
                $config['charset'] ?? 'utf8mb4'
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'] ?? '',
                    $config['password'] ?? '',
                    $config['options'] ?? [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                // Never expose database credentials in production error output
                error_log('Database Connection Error: ' . $e->getMessage());
                throw new RuntimeException('Database connection failed. Please check your database configuration.');
            }
        }

        return self::$instance;
    }

    /**
     * Close the connection
     */
    public static function close(): void
    {
        self::$instance = null;
    }
}
