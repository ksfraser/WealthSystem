<?php

namespace App\Models;

use App\Core\Interfaces\ModelInterface;

/**
 * User Model
 * 
 * Represents user entity compatible with existing database structure.
 * Works with UserAuthDAO and existing user management system.
 */
class User extends BaseModel implements ModelInterface
{
    protected array $validationRules = [
        'username' => ['required', 'min:3', 'max:64'],
        'email' => ['required', 'email', 'max:128'],
        'password' => ['required', 'min:8']
    ];
    
    /**
     * Initialize user with default values
     */
    public function __construct(array $data = [])
    {
        // Set default attributes for user
        $this->attributes = [
            'username' => '',
            'email' => '', 
            'password_hash' => '',
            'is_admin' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => null
        ];
        
        parent::__construct($data);
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return (bool) $this->attributes['is_admin'];
    }
    
    /**
     * Set admin status
     */
    public function setAdmin(bool $isAdmin): void
    {
        $this->attributes['is_admin'] = $isAdmin ? 1 : 0;
    }
    
    /**
     * Set password (hashed)
     */
    public function setPassword(string $password): void
    {
        $this->attributes['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->attributes['password_hash'] ?? '');
    }
    
    /**
     * Get username
     */
    public function getUsername(): string
    {
        return $this->attributes['username'] ?? '';
    }
    
    /**
     * Get email
     */
    public function getEmail(): string
    {
        return $this->attributes['email'] ?? '';
    }
    
    /**
     * Get last login date
     */
    public function getLastLogin(): ?string
    {
        return $this->attributes['last_login'] ?? null;
    }
    
    /**
     * Set last login to now
     */
    public function updateLastLogin(): void
    {
        $this->attributes['last_login'] = date('Y-m-d H:i:s');
    }
    
    /**
     * Override toArray to exclude sensitive data
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        
        // Remove password hash from array representation
        unset($data['password_hash']);
        
        return $data;
    }
    
    /**
     * Get safe user data for sessions/API
     */
    public function toSafeArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->getUsername(),
            'email' => $this->getEmail(),
            'is_admin' => $this->isAdmin(),
            'last_login' => $this->getLastLogin()
        ];
    }
    
    /**
     * Convert to stdClass object (for backward compatibility)
     */
    public function toStdClass(): \stdClass
    {
        $obj = new \stdClass();
        
        foreach ($this->toSafeArray() as $key => $value) {
            $obj->{$key} = $value;
        }
        
        return $obj;
    }
    
    /**
     * Custom validation for user-specific rules
     */
    protected function validateField(string $field, $value, array $rules): void
    {
        parent::validateField($field, $value, $rules);
        
        // Additional user-specific validations
        switch ($field) {
            case 'username':
                if (!empty($value) && !preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
                    $this->errors[$field][] = 'Username can only contain letters, numbers, and underscores';
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = 'Please enter a valid email address';
                }
                break;
                
            case 'password':
                if (!empty($value)) {
                    if (strlen($value) < 8) {
                        $this->errors[$field][] = 'Password must be at least 8 characters long';
                    }
                    if (!preg_match('/[A-Z]/', $value)) {
                        $this->errors[$field][] = 'Password must contain at least one uppercase letter';
                    }
                    if (!preg_match('/[0-9]/', $value)) {
                        $this->errors[$field][] = 'Password must contain at least one number';
                    }
                }
                break;
        }
    }
    
    /**
     * Get display name for user
     */
    public function getDisplayName(): string
    {
        return $this->getUsername() ?: $this->getEmail() ?: 'Unknown User';
    }
    
    /**
     * Get user role as string
     */
    public function getRole(): string
    {
        return $this->isAdmin() ? 'admin' : 'user';
    }
    
    /**
     * Check if user has been active recently
     */
    public function isActive(int $days = 30): bool
    {
        $lastLogin = $this->getLastLogin();
        
        if (!$lastLogin) {
            return false;
        }
        
        $lastLoginTime = strtotime($lastLogin);
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        
        return $lastLoginTime > $cutoffTime;
    }
}