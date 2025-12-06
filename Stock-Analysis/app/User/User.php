<?php

declare(strict_types=1);

namespace App\User;

/**
 * Represents a user in the trading system
 */
class User
{
    private int $id;
    private string $username;
    private string $email;
    private string $passwordHash;
    private array $roles;
    private bool $active;
    private int $createdAt;
    
    public function __construct(
        int $id,
        string $username,
        string $email,
        string $passwordHash,
        array $roles = ['trader'],
        bool $active = true
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->roles = $roles;
        $this->active = $active;
        $this->createdAt = time();
    }
    
    public function getId(): int
    {
        return $this->id;
    }
    
    public function getUsername(): string
    {
        return $this->username;
    }
    
    public function getEmail(): string
    {
        return $this->email;
    }
    
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }
    
    public function getRoles(): array
    {
        return $this->roles;
    }
    
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }
    
    public function isActive(): bool
    {
        return $this->active;
    }
    
    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }
    
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'roles' => $this->roles,
            'active' => $this->active,
            'created_at' => $this->createdAt,
        ];
    }
}
