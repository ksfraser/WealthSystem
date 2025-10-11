# Web UI Architecture Analysis & Refactoring Plan

## Current Structure Problems

### MVC Violations
1. **Mixed Concerns**: Controllers, views, and business logic all in root directory
2. **No Routing**: Direct PHP file access instead of proper routing
3. **Procedural Code**: Many files are procedural scripts, not object-oriented
4. **No Front Controller**: Multiple entry points instead of single entry point

### SOLID Principle Violations

#### Single Responsibility Principle (SRP)
- `dashboard.php` - Handles auth, data fetching, AND rendering
- `bank_import.php` - File upload, validation, parsing, AND UI rendering
- `admin_*.php` - Authentication, business logic, AND HTML output
- Many DAOs have multiple responsibilities

#### Open/Closed Principle (OCP)
- Adding new features requires modifying existing files
- No plugin/extension architecture
- Hard-coded dependencies throughout

#### Interface Segregation Principle (ISP)
- Large, monolithic interfaces where they exist
- Classes forced to implement unused methods

#### Dependency Inversion Principle (DIP)
- High-level modules directly depend on low-level modules
- No dependency injection container
- Hard-coded database connections

### File Organization Issues
```
Current (Problematic):
web_ui/
├── dashboard.php           # Controller + View mixed
├── bank_import.php         # Controller + View + Business Logic
├── admin_users.php         # Admin Controller + View
├── BankAccountsDAO.php     # Data Access (good placement)
├── NavigationService.php   # Service (decent placement)
├── css/                    # Assets (should be in public/)
├── parsers/               # Business Logic (good grouping)
└── ... 100+ mixed files
```

## Proposed MVC Architecture

### Directory Structure
```
app/
├── Controllers/
│   ├── BaseController.php
│   ├── Web/
│   │   ├── DashboardController.php
│   │   ├── BankImportController.php
│   │   ├── UserController.php
│   │   └── Admin/
│   │       ├── UserController.php
│   │       ├── BrokerageController.php
│   │       └── BankAccountController.php
│   └── Api/
│       ├── PortfolioController.php
│       └── TransactionController.php
├── Models/
│   ├── BaseModel.php
│   ├── User.php
│   ├── BankAccount.php
│   ├── Transaction.php
│   ├── Portfolio.php
│   └── Stock.php
├── Services/
│   ├── AuthenticationService.php
│   ├── BankImportService.php
│   ├── NavigationService.php
│   ├── NotificationService.php
│   └── Parser/
│       ├── ParserFactory.php
│       └── Parsers/
├── Repositories/
│   ├── Interfaces/
│   │   ├── UserRepositoryInterface.php
│   │   ├── BankAccountRepositoryInterface.php
│   │   └── TransactionRepositoryInterface.php
│   └── Implementations/
│       ├── DatabaseUserRepository.php
│       ├── DatabaseBankAccountRepository.php
│       └── DatabaseTransactionRepository.php
├── Views/
│   ├── layouts/
│   │   ├── app.php
│   │   └── admin.php
│   ├── components/
│   │   ├── navigation.php
│   │   └── forms/
│   ├── dashboard/
│   │   └── index.php
│   ├── bank-import/
│   │   └── upload.php
│   └── admin/
│       ├── users/
│       └── brokerages/
├── Middleware/
│   ├── AuthMiddleware.php
│   ├── AdminMiddleware.php
│   └── CsrfMiddleware.php
└── Core/
    ├── Application.php
    ├── Router.php
    ├── Request.php
    ├── Response.php
    └── Container.php

config/
├── app.php
├── database.php
└── routes.php

public/
├── index.php              # Front controller
├── css/
├── js/
└── assets/

routes/
├── web.php
└── api.php

tests/
├── Unit/
├── Integration/
└── Feature/
```

## Core Interfaces Needed

### 1. Controller Interface
```php
interface ControllerInterface {
    public function handle(Request $request): Response;
}
```

### 2. Service Interface  
```php
interface ServiceInterface {
    // Marker interface for services
}
```

### 3. Repository Interface
```php
interface RepositoryInterface {
    public function find(int $id): ?Model;
    public function findAll(): array;
    public function create(array $data): Model;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
```

### 4. Model Interface
```php
interface ModelInterface {
    public function toArray(): array;
    public function validate(): bool;
    public function save(): bool;
}
```

## Migration Strategy

### Phase 1: Foundation
1. Create MVC directory structure
2. Implement core interfaces and base classes
3. Create router and front controller
4. Implement dependency injection container

### Phase 2: Controllers
1. Extract page logic into controllers
2. Implement middleware stack
3. Create proper routing configuration

### Phase 3: Models & Repositories
1. Convert DAOs to repositories
2. Create model classes with validation
3. Implement proper data mapping

### Phase 4: Views & Services
1. Extract HTML into template system
2. Move business logic to services
3. Create reusable components

### Phase 5: Testing & Documentation
1. Write comprehensive unit tests
2. Create integration tests
3. Update documentation

## Benefits of Refactoring

### Development Benefits
- **Maintainability**: Clear separation of concerns
- **Testability**: Proper dependency injection and interfaces
- **Extensibility**: Plugin architecture and proper abstractions
- **Code Reuse**: Shared components and services
- **Team Development**: Clear boundaries for different developers

### Performance Benefits
- **Caching**: Proper layered caching strategies
- **Lazy Loading**: Components loaded only when needed
- **Database Optimization**: Repository pattern with query optimization
- **Asset Management**: Proper asset bundling and minification

### Security Benefits
- **Input Validation**: Centralized validation in models
- **Authentication**: Middleware-based auth with proper session management
- **CSRF Protection**: Built into form handling
- **SQL Injection**: Repository pattern with prepared statements
- **XSS Protection**: Template escaping by default

## Implementation Priorities

1. **Critical**: Router, Controllers, Models (basic MVC)
2. **High**: Authentication, Services, Repositories  
3. **Medium**: Views refactoring, Middleware
4. **Low**: Advanced features, optimization

This refactoring will transform the current monolithic structure into a modern, maintainable, and extensible MVC application following all SOLID principles and best practices.