<?php

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * Base Model Class
 * Handles database interaction and PDO prepared statements.
 * Never handles HTTP responses.
 */
abstract class Model
{
    /**
     * @var PDO
     */
    protected PDO $db;

    /**
     * Constructor initializes the PDO database connection
     */
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Execute a prepared SQL query and return the statement
     *
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all matching records as associative arrays
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single matching record as an associative array or null
     *
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE statement
     *
     * @param string $sql
     * @param array $params
     * @return bool
     */
    protected function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get the ID of the last inserted row
     *
     * @return string
     */
    protected function lastInsertId(): string
    {
        return $this->db->lastInsertId();
    }
}
