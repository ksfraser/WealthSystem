# SRP Navigation Architecture Implementation

## Summary

Created a complete Single Responsibility Principle (SRP) architecture for managing navigation items across the application.

## What Was Created

### Configuration
- `config/navigation.php` - Configuration file with access control settings

### Models (Base Classes)
- `Navigation/Models/NavigationItem.php` - Abstract base class
- `Navigation/Models/DashboardCard.php` - Dashboard card implementation
- `Navigation/Models/MenuItem.php` - Menu item with dropdown support

### Providers (Feature Areas)
- `Navigation/Providers/NavigationItemProvider.php` - Provider interface
- `Navigation/Providers/PortfolioItemsProvider.php` - Portfolio features
- `Navigation/Providers/StockAnalysisItemsProvider.php` - Stock analysis
- `Navigation/Providers/DataManagementItemsProvider.php` - Data import/export
- `Navigation/Providers/ReportsItemsProvider.php` - Reports
- `Navigation/Providers/AdminItemsProvider.php` - Admin features
- `Navigation/Providers/ProfileItemsProvider.php` - User profile

### Services (Builders)
- `Navigation/Services/NavigationBuilder.php` - Builds navigation menus
- `Navigation/Services/DashboardCardBuilder.php` - Builds dashboard cards

### Factory & Documentation
- `Navigation/NavigationFactory.php` - Easy-to-use factory class
- `Navigation/USAGE_EXAMPLES.php` - Complete usage examples
- `Navigation/README.md` - Comprehensive documentation

## Key Features

1. **Single Source of Truth**
   - Each navigation item defined once
   - Used everywhere (menus, cards, etc.)

2. **Access Control**
   - Role-based access (admin, advisor, user)
   - Two display modes:
     * Hidden: Restricted items don't appear
     * Greyed Out: Restricted items visible but disabled (FrontAccounting style)

3. **Provider Pattern**
   - Separate provider for each feature area
   - Easy to add new features

4. **Builder Pattern**
   - NavigationBuilder: Builds menus from providers
   - DashboardCardBuilder: Builds cards from providers
   - Filters by user access automatically

5. **Active State Detection**
   - Automatically highlights current page

## Access Control Features

### Configuration Option
```php
'restricted_items_mode' => 'greyed_out' // or 'hidden'
```

### Role-Based Access
- `null`: All authenticated users
- `'admin'`: Administrators only
- `'advisor'`: Advisors only
- Custom roles supported

### Display Modes
1. **Hidden Mode**: Restricted items completely hidden
2. **Greyed Out Mode**: Restricted items shown but disabled with tooltip

## Usage

### Simple Usage (Recommended)
```php
// Navigation Menu
$navBuilder = NavigationFactory::createNavigationBuilder($user, $currentPath);
echo $navBuilder->renderMenu();

// Dashboard Cards
$dashboardBuilder = NavigationFactory::createDashboardCardBuilder($user);
echo $dashboardBuilder->renderCards();
```

### Manual Usage (Advanced)
```php
$config = require 'config/navigation.php';
$builder = new NavigationBuilder($config, $user, $currentPath);
$builder->addProvider(new PortfolioItemsProvider());
$builder->addProvider(new AdminItemsProvider());
echo $builder->renderMenu();
```

## Adding New Features

1. Create a provider class implementing `NavigationItemProvider`
2. Define menu items and dashboard cards
3. Register provider in `NavigationFactory`
4. Done! Feature appears everywhere automatically

## Migration Path

### Phase 1: Create Architecture (DONE)
- ✅ Models, Providers, Services created
- ✅ Configuration file created
- ✅ Documentation written

### Phase 2: Refactor Existing Code (TODO)
- [ ] Update dashboard.php to use DashboardCardBuilder
- [ ] Update NavigationService to use NavigationBuilder
- [ ] Update MyPortfolio.php to use DashboardCardBuilder
- [ ] Remove hardcoded arrays

### Phase 3: Testing (TODO)
- [ ] Test with admin user (all items visible)
- [ ] Test with normal user (admin items hidden/greyed)
- [ ] Test 'hidden' mode
- [ ] Test 'greyed_out' mode
- [ ] Test active state detection
- [ ] Test dropdown menus

## Benefits

1. **Maintainability**: Change item details in one place
2. **Security**: Access control built-in
3. **Consistency**: Same icons, titles, URLs everywhere
4. **Extensibility**: Easy to add new features
5. **Testability**: Each component has single responsibility

## Next Steps

1. Refactor dashboard.php to use new architecture
2. Refactor NavigationService
3. Test with different user roles
4. Remove old hardcoded arrays
5. Commit and push changes

## File Count

- 16 new files created
- 1 configuration file
- 3 model classes
- 7 provider classes
- 2 service classes
- 1 factory class
- 2 documentation files

## Lines of Code

- ~1,500 lines of well-documented, SRP-compliant code
- Comprehensive inline documentation
- Full README with examples
- Usage examples file
