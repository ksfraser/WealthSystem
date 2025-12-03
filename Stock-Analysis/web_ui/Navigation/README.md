# Navigation Architecture

## Overview

This is a **Single Responsibility Principle (SRP)** based architecture for managing navigation items across the application. It provides a single source of truth for all navigation elements (menu items, dashboard cards, etc.) with built-in access control.

## Key Features

- ✅ **Single Source of Truth**: Define each navigation item once, use everywhere
- ✅ **Access Control**: Role-based access (admin, advisor, user)
- ✅ **Configurable Display**: Hide or grey-out restricted items (FrontAccounting style)
- ✅ **Provider Pattern**: Separate providers for each feature area
- ✅ **Builder Pattern**: Build menus and cards from providers
- ✅ **Active State Detection**: Automatically highlights current page
- ✅ **Easy to Extend**: Add new features by creating new providers

## Directory Structure

```
Navigation/
├── Models/
│   ├── NavigationItem.php      # Base class for all navigation items
│   ├── DashboardCard.php        # Dashboard card implementation
│   └── MenuItem.php             # Menu item implementation (supports dropdowns)
├── Providers/
│   ├── NavigationItemProvider.php    # Provider interface
│   ├── PortfolioItemsProvider.php    # Portfolio feature items
│   ├── StockAnalysisItemsProvider.php
│   ├── DataManagementItemsProvider.php
│   ├── ReportsItemsProvider.php
│   ├── AdminItemsProvider.php
│   └── ProfileItemsProvider.php
├── Services/
│   ├── NavigationBuilder.php        # Builds navigation menus
│   └── DashboardCardBuilder.php     # Builds dashboard cards
├── NavigationFactory.php            # Factory for easy creation
└── USAGE_EXAMPLES.php               # Usage examples

config/
└── navigation.php                   # Configuration file
```

## Configuration

Edit `config/navigation.php`:

```php
return [
    // How to display restricted items
    'restricted_items_mode' => 'greyed_out', // or 'hidden'
    
    // Performance
    'cache_enabled' => false,
    'cache_duration' => 3600,
    
    // Display options
    'show_icons' => true,
    'show_restriction_tooltip' => true,
    'restriction_tooltip_text' => 'Requires {level} access',
    
    // Role definitions
    'admin_roles' => ['admin', 'administrator', 'super_admin'],
    'advisor_roles' => ['advisor', 'financial_advisor'],
];
```

## Quick Start

### 1. Render Navigation Menu

```php
require_once __DIR__ . '/Navigation/NavigationFactory.php';
require_once __DIR__ . '/UserAuthDAO.php';

$auth = new UserAuthDAO();
$user = $auth->getCurrentUser();
$currentPath = basename($_SERVER['PHP_SELF']);

$navBuilder = NavigationFactory::createNavigationBuilder($user, $currentPath);

echo '<ul class="navbar-nav">';
echo $navBuilder->renderMenu();
echo '</ul>';
```

### 2. Render Dashboard Cards

```php
$dashboardBuilder = NavigationFactory::createDashboardCardBuilder($user);

echo '<div class="row">';
echo $dashboardBuilder->renderCards();
echo '</div>';
```

## Access Control

Access control is based on user roles:

- **null** (no role required): Available to all authenticated users
- **'admin'**: Requires admin privileges
- **'advisor'**: Requires advisor privileges
- **Custom roles**: You can define custom roles

### Display Modes

1. **Hidden Mode** (`restricted_items_mode = 'hidden'`):
   - Restricted items are completely hidden from non-authorized users
   - Clean UI, users only see what they can access

2. **Greyed Out Mode** (`restricted_items_mode = 'greyed_out'`):
   - Restricted items are visible but disabled
   - Shows users what features exist but require higher access
   - Tooltip shows required access level
   - Similar to FrontAccounting style

## Architecture Details

### NavigationItem (Base Class)

All navigation items inherit from this:

```php
abstract class NavigationItem {
    protected $id;           // Unique identifier
    protected $title;        // Display title
    protected $description;  // Description/tooltip
    protected $icon;         // Icon (emoji or CSS class)
    protected $url;          // Target URL
    protected $requiredRole; // Required role (null = all users)
    protected $sortOrder;    // Display order
    
    public function hasAccess(?string $userRole, bool $isAdmin): bool;
    public function toArray(): array;
}
```

### DashboardCard

Extends `NavigationItem`, adds:
- Multiple action buttons
- Card type (default, success, warning, etc.)
- Custom color classes
- Render method for HTML output

### MenuItem

Extends `NavigationItem`, adds:
- Dropdown children support
- Active state detection
- Badge support
- Separate rendering for menu vs dropdown items

### Providers

Each provider implements `NavigationItemProvider`:

```php
interface NavigationItemProvider {
    public function getMenuItems(): array;      // Returns MenuItem[]
    public function getDashboardCards(): array; // Returns DashboardCard[]
}
```

Example provider:

```php
class PortfolioItemsProvider implements NavigationItemProvider {
    public function getMenuItems(): array {
        $menu = new MenuItem(
            'portfolio',
            'Portfolio',
            'Portfolio management',
            '💼',
            '#',
            null, // Available to all users
            1     // Sort order
        );
        
        $menu->addChild(new MenuItem(
            'portfolio.my',
            'My Portfolio',
            'View your portfolio',
            '🏠',
            'MyPortfolio.php',
            null,
            1
        ));
        
        return [$menu];
    }
    
    public function getDashboardCards(): array {
        $card = new DashboardCard(
            'card.portfolio.my',
            '🏠 My Portfolio',
            'View and manage your investment portfolio.',
            '🏠',
            'MyPortfolio.php',
            null,
            1
        );
        
        return [$card];
    }
}
```

### Builders

**NavigationBuilder**: Builds navigation menus
- Registers providers
- Filters items by access
- Handles active state
- Renders HTML

**DashboardCardBuilder**: Builds dashboard cards
- Registers providers
- Filters cards by access
- Renders card HTML

## Adding New Features

### 1. Create a Provider

```php
class MyNewFeatureProvider implements NavigationItemProvider {
    public function getMenuItems(): array {
        return [
            new MenuItem(
                'my_feature',
                'My Feature',
                'Description',
                '🎯',
                'my_feature.php',
                null, // or 'admin' for restricted
                10    // sort order
            )
        ];
    }
    
    public function getDashboardCards(): array {
        return [
            new DashboardCard(
                'card.my_feature',
                '🎯 My Feature',
                'Feature description',
                '🎯',
                'my_feature.php',
                null,
                10
            )
        ];
    }
}
```

### 2. Register in NavigationFactory

Edit `NavigationFactory.php`:

```php
$builder->addProvider(new MyNewFeatureProvider());
```

That's it! Your feature now appears in all menus and dashboards with proper access control.

## Migration Guide

### Migrating from Hardcoded Arrays

**Before** (dashboard.php):
```php
$features = [
    [
        'title' => '🏠 My Portfolio',
        'description' => 'View your portfolio',
        'actions' => [
            ['url' => 'MyPortfolio.php', 'label' => 'View']
        ]
    ],
    // ... more hardcoded arrays
];
```

**After**:
```php
$dashboardBuilder = NavigationFactory::createDashboardCardBuilder($user);
echo $dashboardBuilder->renderCards();
```

### Migrating NavigationService

**Before**:
```php
public function renderNavigationHeader(): string {
    $html = '<ul>';
    $html .= '<li><a href="MyPortfolio.php">Portfolio</a></li>';
    // ... hardcoded HTML
    return $html;
}
```

**After**:
```php
public function renderNavigationHeader(?array $user = null, string $currentPath = ''): string {
    $navBuilder = NavigationFactory::createNavigationBuilder($user, $currentPath);
    
    $html = '<ul class="navbar-nav">';
    $html .= $navBuilder->renderMenu();
    $html .= '</ul>';
    
    return $html;
}
```

## Benefits

1. **DRY (Don't Repeat Yourself)**
   - Define each navigation item once
   - Use in menus, cards, breadcrumbs, etc.

2. **Maintainability**
   - Change item details in one place
   - Add/remove features easily
   - Consistent icons, titles, URLs

3. **Security**
   - Access control built-in
   - No manual if/else checks needed
   - Configurable display modes

4. **Testability**
   - Each component has single responsibility
   - Easy to unit test
   - Mock providers for testing

5. **Extensibility**
   - Add new providers without touching existing code
   - Custom renderers possible
   - Easy to add new item types

## Best Practices

1. **ID Naming Convention**
   - Use dot notation: `'feature.subfeature'`
   - Example: `'portfolio.my'`, `'admin.bank_accounts'`

2. **Sort Order**
   - Use increments of 10 for easy insertion
   - Example: 10, 20, 30 (can add 25 later)

3. **Icons**
   - Use emoji for consistency: 🏠, 📈, 📋, etc.
   - Or use icon classes: `'fas fa-home'`

4. **Descriptions**
   - Keep concise (one sentence)
   - Focus on user benefit
   - Will be shown in tooltips

5. **Required Roles**
   - `null`: All authenticated users
   - `'admin'`: Administrators only
   - `'advisor'`: Advisors and admins
   - Custom roles as needed

## Troubleshooting

**Problem**: Items not showing up
- Check access control (requiredRole)
- Check sort order
- Verify provider is registered in NavigationFactory

**Problem**: Wrong items showing for user
- Verify user role in database
- Check `is_admin` flag
- Review configuration `restricted_items_mode`

**Problem**: Icons not displaying
- Ensure emoji support or icon font loaded
- Check `show_icons` config setting
- Verify icon string is correct

## Future Enhancements

Possible improvements:

- [ ] Caching implementation for performance
- [ ] Database-driven navigation items
- [ ] Permission-based access (not just roles)
- [ ] Breadcrumb generation
- [ ] Sitemap generation
- [ ] Analytics tracking integration
- [ ] A/B testing support

## Support

For questions or issues, refer to:
- `USAGE_EXAMPLES.php` for code examples
- Provider classes for implementation patterns
- Configuration file for display options
