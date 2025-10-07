# Ksfraser\UIRenderer

A clean, modern PHP UI rendering library with component-based architecture, designed for building maintainable web interfaces.

## Features

- **Component-Based Architecture**: Reusable UI components following SOLID principles
- **Clean Separation of Concerns**: Separate DTOs, Components, Renderers, and Providers
- **Theme Support**: Built-in themes (default, dark, minimal) with easy customization
- **Responsive Design**: Mobile-first CSS with responsive components
- **PSR-4 Autoloading**: Compatible with Composer and modern PHP standards
- **PHP 7.0+ Compatible**: Works with older PHP versions for maximum compatibility
- **Factory Pattern**: Easy component creation with UiFactory
- **Backward Compatibility**: Drop-in replacement for existing systems

## Installation

### Via Composer (when published)
```bash
composer require ksfraser/ui-renderer
```

### Manual Installation
1. Copy the `src/Ksfraser/UIRenderer` directory to your project
2. Include the autoloader: `require_once 'path/to/UIRenderer/autoload.php'`

## Quick Start

```php
<?php
require_once 'path/to/UIRenderer/autoload.php';

use Ksfraser\UIRenderer\Factories\UiFactory;

// Create a simple page
$navigation = UiFactory::createNavigation(
    'My Application',
    'home',
    ['username' => 'John Doe'],
    false,
    [
        ['label' => 'Home', 'url' => '/', 'page' => 'home'],
        ['label' => 'About', 'url' => '/about', 'page' => 'about']
    ],
    true
);

$welcomeCard = UiFactory::createSuccessCard(
    'Welcome!',
    'Welcome to our application. Everything is working perfectly.'
);

$page = UiFactory::createPage(
    'Home - My Application',
    $navigation,
    [$welcomeCard]
);

echo $page->render();
```

## Components

### Navigation Component
```php
$navigation = UiFactory::createNavigation(
    $title = 'App Title',
    $currentPage = 'home',
    $user = ['username' => 'John'],
    $isAdmin = false,
    $menuItems = [
        ['label' => 'Home', 'url' => '/', 'page' => 'home']
    ],
    $isAuthenticated = true
);
```

### Card Components
```php
// Basic card
$card = UiFactory::createCard('Title', 'Content', 'info', '📝');

// Specialized cards
$success = UiFactory::createSuccessCard('Success!', 'Operation completed');
$info = UiFactory::createInfoCard('Information', 'Here is some info');
$warning = UiFactory::createWarningCard('Warning!', 'Please be careful');
$error = UiFactory::createErrorCard('Error!', 'Something went wrong');
```

### Table Component
```php
$data = [
    ['Name' => 'John', 'Age' => 30, 'City' => 'New York'],
    ['Name' => 'Jane', 'Age' => 25, 'City' => 'Boston']
];

$table = UiFactory::createTable($data, ['Name', 'Age', 'City'], [
    'striped' => true,
    'hover' => true,
    'responsive' => true
]);

$dataCard = UiFactory::createCard('User Data', $table->toHtml());
```

### Data Card (Table + Card Combined)
```php
$dataCard = UiFactory::createDataCard('Users', $data, ['Name', 'Age', 'City']);
```

## Themes

### Using Different Themes
```php
$page = UiFactory::createPage(
    'My App',
    $navigation,
    $components,
    ['theme' => 'dark'] // or 'minimal'
);
```

### Available Themes
- **default**: Modern blue theme with gradients
- **dark**: Dark theme with good contrast
- **minimal**: Clean, minimal design with subtle shadows

## Architecture

```
src/Ksfraser/UIRenderer/
├── Contracts/           # Interfaces
│   └── RendererInterface.php
├── DTOs/               # Data Transfer Objects
│   └── DataTransferObjects.php
├── Components/         # UI Components
│   ├── NavigationComponent.php
│   ├── CardComponent.php
│   └── TableComponent.php
├── Renderers/          # Page Renderers
│   └── PageRenderer.php
├── Providers/          # CSS and other providers
│   └── CssProvider.php
├── Factories/          # Factory classes
│   └── UiFactory.php
└── autoload.php       # PSR-4 autoloader
```

## Advanced Usage

### Custom Components
```php
use Ksfraser\UIRenderer\Contracts\ComponentInterface;

class CustomComponent implements ComponentInterface {
    public function toHtml() {
        return '<div class="custom">Custom content</div>';
    }
}
```

### Custom CSS
```php
$page = UiFactory::createPage(
    'My App',
    $navigation,
    $components,
    [
        'additionalCss' => '.custom { background: red; }',
        'additionalJs' => 'console.log("Page loaded");'
    ]
);
```

### Meta Tags
```php
$page = UiFactory::createPage(
    'My App',
    $navigation,
    $components,
    [
        'meta' => [
            'description' => 'My application description',
            'keywords' => 'app, php, ui',
            'author' => 'Your Name'
        ]
    ]
);
```

## Backward Compatibility

For existing applications, a compatibility layer is provided that maintains the old API:

```php
// Old way (still works)
$card = UiFactory::createCardComponent('Title', 'Content');
$nav = UiFactory::createNavigationComponent('App', 'home');
$page = UiFactory::createPageRenderer('Title', $nav, [$card]);
```

## CSS Classes Reference

### Layout
- `.container` - Main content container (max-width: 1200px)
- `.grid` - CSS Grid layout
- `.flex` - Flexbox layout
- `.gap-1`, `.gap-2`, `.gap-3` - Spacing utilities

### Cards
- `.card` - Basic card style
- `.success` - Green left border
- `.info` - Blue left border  
- `.warning` - Yellow left border
- `.error` - Red left border

### Buttons
- `.btn` - Basic button
- `.btn-success` - Green button
- `.btn-warning` - Yellow button
- `.btn-danger` - Red button
- `.btn-secondary` - Gray button
- `.btn-sm`, `.btn-lg` - Size variants

### Navigation
- `.nav-header` - Navigation container
- `.nav-header.admin` - Admin styling (red)
- `.nav-header.dark` - Dark theme navigation

## Contributing

1. Follow PSR-4 autoloading standards
2. Implement appropriate interfaces
3. Add PHPDoc comments
4. Maintain PHP 7.0+ compatibility
5. Write tests for new components

## License

MIT License - see LICENSE file for details.

## Roadmap

- [ ] Add form components
- [ ] Add modal components  
- [ ] Add notification/toast components
- [ ] Add chart/visualization components
- [ ] Add pagination component
- [ ] Add breadcrumb component
- [x] Improve accessibility (ARIA labels)
- [ ] Add JavaScript interaction helpers
- [ ] Create more themes
- [x] Add RTL language support
