<?php

declare(strict_types=1);

namespace App\User;

use App\Exceptions\DataException;

/**
 * Handles user authentication
 */
class AuthService
{
    private UserRepository $repository;
    
    /** @var array<string, User> Session storage - token => User */
    private array $sessions = [];
    
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * Authenticate user with username and password
     *
     * @param string $username
     * @param string $password
     * @return string Session token
     * @throws DataException
     */
    public function login(string $username, string $password): string
    {
        $user = $this->repository->findByUsername($username);
        
        if ($user === null) {
            throw new DataException('Invalid username or password');
        }
        
        if (!$user->isActive()) {
            throw new DataException('User account is inactive');
        }
        
        if (!$user->verifyPassword($password)) {
            throw new DataException('Invalid username or password');
        }
        
        $token = $this->generateToken();
        $this->sessions[$token] = $user;
        
        return $token;
    }
    
    /**
     * Logout user by token
     *
     * @param string $token
     * @return void
     */
    public function logout(string $token): void
    {
        unset($this->sessions[$token]);
    }
    
    /**
     * Get user by session token
     *
     * @param string $token
     * @return User|null
     */
    public function getUserByToken(string $token): ?User
    {
        return $this->sessions[$token] ?? null;
    }
    
    /**
     * Check if token is valid
     *
     * @param string $token
     * @return bool
     */
    public function isValidToken(string $token): bool
    {
        return isset($this->sessions[$token]);
    }
    
    /**
     * Get active session count
     *
     * @return int
     */
    public function getActiveSessionCount(): int
    {
        return count($this->sessions);
    }
    
    /**
     * Generate a random session token
     *
     * @return string
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
