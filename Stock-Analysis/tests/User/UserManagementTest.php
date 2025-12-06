<?php

declare(strict_types=1);

namespace Tests\User;

use PHPUnit\Framework\TestCase;
use App\User\User;
use App\User\UserRepository;
use App\User\AuthService;
use App\User\AuthorizationService;
use App\Exceptions\DataException;

class UserManagementTest extends TestCase
{
    public function testUserCreation(): void
    {
        $hash = password_hash('secret', PASSWORD_BCRYPT);
        $user = new User(1, 'john_doe', 'john@example.com', $hash);
        
        $this->assertSame(1, $user->getId());
        $this->assertSame('john_doe', $user->getUsername());
        $this->assertSame('john@example.com', $user->getEmail());
        $this->assertTrue($user->isActive());
        $this->assertTrue($user->hasRole('trader'));
    }
    
    public function testUserPasswordVerification(): void
    {
        $hash = password_hash('mypassword', PASSWORD_BCRYPT);
        $user = new User(1, 'test', 'test@example.com', $hash);
        
        $this->assertTrue($user->verifyPassword('mypassword'));
        $this->assertFalse($user->verifyPassword('wrongpassword'));
    }
    
    public function testUserRoles(): void
    {
        $hash = password_hash('pass', PASSWORD_BCRYPT);
        $user = new User(1, 'admin', 'admin@example.com', $hash, ['admin', 'trader']);
        
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('trader'));
        $this->assertFalse($user->hasRole('viewer'));
    }
    
    public function testUserToArray(): void
    {
        $hash = password_hash('pass', PASSWORD_BCRYPT);
        $user = new User(1, 'test', 'test@example.com', $hash);
        
        $array = $user->toArray();
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('username', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('roles', $array);
        $this->assertArrayNotHasKey('password_hash', $array);
    }
    
    public function testUserRepositoryCreate(): void
    {
        $repo = new UserRepository();
        
        $user = $repo->create('alice', 'alice@example.com', 'password123');
        
        $this->assertSame(1, $user->getId());
        $this->assertSame('alice', $user->getUsername());
        $this->assertTrue($user->verifyPassword('password123'));
    }
    
    public function testUserRepositoryDuplicateUsername(): void
    {
        $this->expectException(DataException::class);
        $this->expectExceptionMessage("Username 'bob' already exists");
        
        $repo = new UserRepository();
        $repo->create('bob', 'bob1@example.com', 'pass');
        $repo->create('bob', 'bob2@example.com', 'pass');
    }
    
    public function testUserRepositoryDuplicateEmail(): void
    {
        $this->expectException(DataException::class);
        $this->expectExceptionMessage("Email 'test@example.com' already exists");
        
        $repo = new UserRepository();
        $repo->create('user1', 'test@example.com', 'pass');
        $repo->create('user2', 'test@example.com', 'pass');
    }
    
    public function testUserRepositoryFindById(): void
    {
        $repo = new UserRepository();
        $user = $repo->create('charlie', 'charlie@example.com', 'pass');
        
        $found = $repo->findById($user->getId());
        
        $this->assertSame($user, $found);
    }
    
    public function testUserRepositoryFindByUsername(): void
    {
        $repo = new UserRepository();
        $user = $repo->create('dave', 'dave@example.com', 'pass');
        
        $found = $repo->findByUsername('dave');
        
        $this->assertSame($user, $found);
    }
    
    public function testUserRepositoryFindByEmail(): void
    {
        $repo = new UserRepository();
        $user = $repo->create('eve', 'eve@example.com', 'pass');
        
        $found = $repo->findByEmail('eve@example.com');
        
        $this->assertSame($user, $found);
    }
    
    public function testAuthServiceLogin(): void
    {
        $repo = new UserRepository();
        $repo->create('frank', 'frank@example.com', 'secure123');
        
        $auth = new AuthService($repo);
        $token = $auth->login('frank', 'secure123');
        
        $this->assertNotEmpty($token);
        $this->assertTrue($auth->isValidToken($token));
    }
    
    public function testAuthServiceInvalidPassword(): void
    {
        $this->expectException(DataException::class);
        $this->expectExceptionMessage('Invalid username or password');
        
        $repo = new UserRepository();
        $repo->create('grace', 'grace@example.com', 'password');
        
        $auth = new AuthService($repo);
        $auth->login('grace', 'wrongpassword');
    }
    
    public function testAuthServiceInvalidUsername(): void
    {
        $this->expectException(DataException::class);
        $this->expectExceptionMessage('Invalid username or password');
        
        $repo = new UserRepository();
        $auth = new AuthService($repo);
        $auth->login('nonexistent', 'password');
    }
    
    public function testAuthServiceLogout(): void
    {
        $repo = new UserRepository();
        $repo->create('helen', 'helen@example.com', 'pass');
        
        $auth = new AuthService($repo);
        $token = $auth->login('helen', 'pass');
        
        $this->assertTrue($auth->isValidToken($token));
        
        $auth->logout($token);
        
        $this->assertFalse($auth->isValidToken($token));
    }
    
    public function testAuthServiceGetUserByToken(): void
    {
        $repo = new UserRepository();
        $user = $repo->create('ivan', 'ivan@example.com', 'pass');
        
        $auth = new AuthService($repo);
        $token = $auth->login('ivan', 'pass');
        
        $retrieved = $auth->getUserByToken($token);
        
        $this->assertSame($user, $retrieved);
    }
    
    public function testAuthorizationHasRole(): void
    {
        $hash = password_hash('pass', PASSWORD_BCRYPT);
        $user = new User(1, 'test', 'test@example.com', $hash, ['admin']);
        
        $authz = new AuthorizationService();
        
        $this->assertTrue($authz->hasRole($user, 'admin'));
        $this->assertFalse($authz->hasRole($user, 'trader'));
    }
    
    public function testAuthorizationHasAnyRole(): void
    {
        $hash = password_hash('pass', PASSWORD_BCRYPT);
        $user = new User(1, 'test', 'test@example.com', $hash, ['trader']);
        
        $authz = new AuthorizationService();
        
        $this->assertTrue($authz->hasAnyRole($user, ['admin', 'trader']));
        $this->assertFalse($authz->hasAnyRole($user, ['admin', 'viewer']));
    }
    
    public function testAuthorizationHasAllRoles(): void
    {
        $hash = password_hash('pass', PASSWORD_BCRYPT);
        $user = new User(1, 'test', 'test@example.com', $hash, ['admin', 'trader']);
        
        $authz = new AuthorizationService();
        
        $this->assertTrue($authz->hasAllRoles($user, ['admin', 'trader']));
        $this->assertFalse($authz->hasAllRoles($user, ['admin', 'trader', 'viewer']));
    }
    
    public function testAuthorizationCanTrade(): void
    {
        $hash = password_hash('pass', PASSWORD_BCRYPT);
        $trader = new User(1, 'trader', 'trader@example.com', $hash, ['trader']);
        $admin = new User(2, 'admin', 'admin@example.com', $hash, ['admin']);
        $viewer = new User(3, 'viewer', 'viewer@example.com', $hash, ['viewer']);
        
        $authz = new AuthorizationService();
        
        $this->assertTrue($authz->canTrade($trader));
        $this->assertTrue($authz->canTrade($admin));
        $this->assertFalse($authz->canTrade($viewer));
    }
    
    public function testAuthorizationCanViewAllPortfolios(): void
    {
        $hash = password_hash('pass', PASSWORD_BCRYPT);
        $admin = new User(1, 'admin', 'admin@example.com', $hash, ['admin']);
        $trader = new User(2, 'trader', 'trader@example.com', $hash, ['trader']);
        
        $authz = new AuthorizationService();
        
        $this->assertTrue($authz->canViewAllPortfolios($admin));
        $this->assertFalse($authz->canViewAllPortfolios($trader));
    }
}
