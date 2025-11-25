<?php

namespace App\Repositories;

use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Core\Interfaces\ModelInterface;
use App\Models\User;

// Include existing UserAuthDAO
require_once __DIR__ . '/../../web_ui/UserAuthDAO.php';

/**
 * User Repository Implementation
 * 
 * Bridges between new MVC architecture and existing UserAuthDAO.
 * Maintains compatibility while providing modern interface.
 */
class UserRepository implements UserRepositoryInterface
{
    private \UserAuthDAO $userAuthDAO;
    
    public function __construct()
    {
        $this->userAuthDAO = new \UserAuthDAO();
    }
    
    /**
     * Find record by ID
     */
    public function findById(int $id): ?ModelInterface
    {
        try {
            $pdo = $this->userAuthDAO->getPdo();
            if (!$pdo) {
                return null;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $userData ? new User($userData) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Find records by criteria
     */
    public function findBy(array $criteria): array
    {
        $users = [];
        
        try {
            $pdo = $this->userAuthDAO->getPdo();
            if (!$pdo) {
                return [];
            }
            
            $conditions = [];
            $params = [];
            
            foreach ($criteria as $field => $value) {
                $conditions[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
            $sql = "SELECT * FROM users {$whereClause}";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($results as $userData) {
                $users[] = new User($userData);
            }
        } catch (\Exception $e) {
            // Log error but return empty array
        }
        
        return $users;
    }
    
    /**
     * Find one record by criteria
     */
    public function findOneBy(array $criteria): ?ModelInterface
    {
        $results = $this->findBy($criteria);
        return $results[0] ?? null;
    }
    
    /**
     * Find all records
     */
    public function findAll(): array
    {
        $users = [];
        
        try {
            $allUsers = $this->userAuthDAO->getAllUsers();
            
            foreach ($allUsers as $userData) {
                $users[] = new User($userData);
            }
        } catch (\Exception $e) {
            // Log error but return empty array
        }
        
        return $users;
    }
    
    /**
     * Create new record
     */
    public function create(array $data): ModelInterface
    {
        // Use existing UserAuthDAO method for user creation
        $userId = $this->userAuthDAO->registerUser(
            $data['username'] ?? '',
            $data['email'] ?? '',
            $data['password'] ?? ''
        );
        
        if (!$userId) {
            throw new \Exception('Failed to create user');
        }
        
        $user = $this->findById($userId);
        if (!$user) {
            throw new \Exception('User created but not found');
        }
        
        return $user;
    }
    
    /**
     * Update record by ID
     */
    public function update(int $id, array $data): bool
    {
        try {
            $pdo = $this->userAuthDAO->getPdo();
            if (!$pdo) {
                return false;
            }
            
            $fields = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if ($field !== 'id') { // Don't update ID
                    $fields[] = "{$field} = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $params[] = $id;
            $sql = "UPDATE users SET " . implode(',', $fields) . " WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Delete record by ID
     */
    public function delete(int $id): bool
    {
        try {
            $pdo = $this->userAuthDAO->getPdo();
            if (!$pdo) {
                return false;
            }
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Count records
     */
    public function count(array $criteria = []): int
    {
        try {
            $pdo = $this->userAuthDAO->getPdo();
            if (!$pdo) {
                return 0;
            }
            
            $conditions = [];
            $params = [];
            
            foreach ($criteria as $field => $value) {
                $conditions[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
            $sql = "SELECT COUNT(*) FROM users {$whereClause}";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    // UserRepositoryInterface specific methods
    
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?object
    {
        $user = $this->findOneBy(['email' => $email]);
        return $user ? $user->toStdClass() : null;
    }
    
    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?object
    {
        $user = $this->findOneBy(['username' => $username]);
        return $user ? $user->toStdClass() : null;
    }
    
    /**
     * Verify user credentials
     */
    public function verifyCredentials(string $identifier, string $password): ?object
    {
        try {
            if ($this->userAuthDAO->login($identifier, $password)) {
                $currentUser = $this->userAuthDAO->getCurrentUser();
                return $currentUser ? (object) $currentUser : null;
            }
        } catch (\Exception $e) {
            // Login failed
        }
        
        return null;
    }
    
    /**
     * Update user's last login timestamp
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->update($userId, ['last_login' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * Check if user has role
     */
    public function hasRole(int $userId, string $role): bool
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            return false;
        }
        
        // Check admin role
        if ($role === 'admin') {
            return $user->is_admin == 1;
        }
        
        // Default role is 'user'
        return $role === 'user';
    }
}