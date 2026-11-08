<?php

namespace App\Models;

use App\Core\Model;

/**
 * User Model
 * 
 * Handles all database operations for the `users` table.
 * Pure Model layer: contains zero HTTP or presentation logic.
 */
class User extends Model
{
    /**
     * Database table name
     * @var string
     */
    protected string $table = 'users';

    /**
     * Retrieve all users ordered by creation date descending
     *
     * @return array
     */
    public function all(): array
    {
        $sql = "SELECT id, name, email, avatar, created_at FROM {$this->table} ORDER BY id DESC";
        return $this->fetchAll($sql);
    }

    /**
     * Find a single user by primary ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT id, name, email, avatar, created_at FROM {$this->table} WHERE id = :id LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Find a user by email address (optional exclusion for update checks)
     *
     * @param string $email
     * @param int|null $excludeId
     * @return array|null
     */
    public function findByEmail(string $email, ?int $excludeId = null): ?array
    {
        if ($excludeId !== null) {
            $sql = "SELECT id, name, email, avatar FROM {$this->table} WHERE email = :email AND id != :excludeId LIMIT 1";
            return $this->fetchOne($sql, ['email' => $email, 'excludeId' => $excludeId]);
        }

        $sql = "SELECT id, name, email, avatar FROM {$this->table} WHERE email = :email LIMIT 1";
        return $this->fetchOne($sql, ['email' => $email]);
    }

    /**
     * Create a new user record
     *
     * @param array $data ['name' => ..., 'email' => ..., 'avatar' => ...]
     * @return int Inserted user ID
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (name, email, avatar) VALUES (:name, :email, :avatar)";
        $this->execute($sql, [
            'name'   => trim($data['name']),
            'email'  => strtolower(trim($data['email'])),
            'avatar' => $data['avatar'] ?? null,
        ]);

        return (int)$this->lastInsertId();
    }

    /**
     * Update an existing user record
     *
     * @param int $id
     * @param array $data ['name' => ..., 'email' => ..., 'avatar' => ...]
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        if (isset($data['name'])) {
            $fields[] = 'name = :name';
            $params['name'] = trim($data['name']);
        }

        if (isset($data['email'])) {
            $fields[] = 'email = :email';
            $params['email'] = strtolower(trim($data['email']));
        }

        if (array_key_exists('avatar', $data)) {
            $fields[] = 'avatar = :avatar';
            $params['avatar'] = $data['avatar'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->execute($sql, $params);
    }

    /**
     * Update user avatar image path
     *
     * @param int $id
     * @param string|null $avatarPath
     * @return bool
     */
    public function updateAvatar(int $id, ?string $avatarPath): bool
    {
        $sql = "UPDATE {$this->table} SET avatar = :avatar WHERE id = :id";
        return $this->execute($sql, [
            'id'     => $id,
            'avatar' => $avatarPath,
        ]);
    }

    /**
     * Delete a user by ID
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        return $this->execute($sql, ['id' => $id]);
    }
}
