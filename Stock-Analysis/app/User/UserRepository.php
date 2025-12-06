<?php

declare(strict_types=1);

namespace App\User;

use App\Exceptions\DataException;

/**
 * Manages user authentication and authorization
 */
class UserRepository
{
    /** @var array<int, User> */
    private array $users = [];
    
    private int $nextId = 1;
    
    /**
     * Create a new user
     *
     * @param string $username
     * @param string $email
     * @param string $password Plain text password (will be hashed)
     * @param array $roles
     * @return User
     * @throws DataException
     */
    public function create(string $username, string $email, string $password, array $roles = ['trader']): User
    {
        if ($this->findByUsername($username) !== null) {
            throw new DataException("Username '{$username}' already exists");
        }
        
        if ($this->findByEmail($email) !== null) {
            throw new DataException("Email '{$email}' already exists");
        }
        
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        $user = new User($this->nextId++, $username, $email, $passwordHash, $roles);
        $this->users[$user->getId()] = $user;
        
        return $user;
    }
    
    /**
     * Find user by ID
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }
    
    /**
     * Find user by username
     *
     * @param string $username
     * @return User|null
     */
    public function findByUsername(string $username): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getUsername() === $username) {
                return $user;
            }
        }
        return null;
    }
    
    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getEmail() === $email) {
                return $user;
            }
        }
        return null;
    }
    
    /**
     * Get all users
     *
     * @return array<User>
     */
    public function getAll(): array
    {
        return array_values($this->users);
    }
    
    /**
     * Count total users
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->users);
    }
}
