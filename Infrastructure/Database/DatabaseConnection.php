<?php

namespace BookStore\Infrastructure\Database;

use Exception;
use PDO;
use PDOException;

class DatabaseConnection
{
    private PDO $connection;
    private static ?DatabaseConnection $instance = null;

    /**
     * Constructs Database Connection instance
     *
     * @throws Exception
     */
    private function __construct()
    {
        $dbHost = getenv('DB_HOST') ?: $_ENV['APP_DB_HOST'];
        $dbName = getenv('DB_NAME') ?: $_ENV['APP_DB_NAME'];
        $dbUser = getenv('DB_USER') ?: $_ENV['APP_DB_USER'];
        $dbPassword = getenv('DB_PASSWORD') ?: $_ENV['APP_DB_PASSWORD'];
        $dsn = "mysql:host={$dbHost};dbname={$dbName}";

        try {
            $this->connection = new PDO(
                $dsn,
                $dbUser,
                $dbPassword,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Creates and returns DatabaseConnection instance
     *
     * @return DatabaseConnection
     */
    public static function getInstance(): DatabaseConnection
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Returns PDO instance
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
