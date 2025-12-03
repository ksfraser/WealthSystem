<?php
require_once __DIR__ . '/../Models/MenuItem.php';
require_once __DIR__ . '/../Models/DashboardCard.php';
require_once __DIR__ . '/NavigationItemProvider.php';

/**
 * Database Navigation Items Provider
 * Loads navigation items from database instead of hardcoded arrays
 */
class DatabaseNavigationProvider implements NavigationItemProvider {
    private $pdo;
    private $cache = [];
    
    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get menu items from database
     */
    public function getMenuItems(): array {
        if (isset($this->cache['menu'])) {
            return $this->cache['menu'];
        }
        
        // Get all menu items (parent items and children)
        $stmt = $this->pdo->prepare("
            SELECT * FROM navigation_items 
            WHERE item_type IN ('menu', 'both') 
            AND is_enabled = 1 
            ORDER BY sort_order ASC
        ");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build menu structure
        $menuItems = [];
        $childrenMap = [];
        
        // First pass: create all items and separate parents from children
        foreach ($items as $item) {
            $menuItem = $this->createMenuItem($item);
            
            if ($item['parent_id'] === null) {
                $menuItems[$item['item_id']] = $menuItem;
            } else {
                if (!isset($childrenMap[$item['parent_id']])) {
                    $childrenMap[$item['parent_id']] = [];
                }
                $childrenMap[$item['parent_id']][] = $menuItem;
            }
        }
        
        // Second pass: attach children to parents
        foreach ($childrenMap as $parentId => $children) {
            if (isset($menuItems[$parentId])) {
                $menuItems[$parentId]->setChildren($children);
            }
        }
        
        $result = array_values($menuItems);
        $this->cache['menu'] = $result;
        
        return $result;
    }
    
    /**
     * Get dashboard cards from database
     */
    public function getDashboardCards(): array {
        if (isset($this->cache['cards'])) {
            return $this->cache['cards'];
        }
        
        // Get all card items
        $stmt = $this->pdo->prepare("
            SELECT * FROM navigation_items 
            WHERE item_type IN ('card', 'both') 
            AND is_enabled = 1 
            AND parent_id IS NULL
            ORDER BY sort_order ASC
        ");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $cards = [];
        foreach ($items as $item) {
            $card = $this->createDashboardCard($item);
            
            // Load actions for this card
            $actions = $this->loadActions($item['item_id']);
            if (!empty($actions)) {
                $card->setActions($actions);
            }
            
            $cards[] = $card;
        }
        
        $this->cache['cards'] = $cards;
        
        return $cards;
    }
    
    /**
     * Create MenuItem from database row
     */
    private function createMenuItem(array $row): MenuItem {
        return new MenuItem(
            $row['item_id'],
            $row['title'],
            $row['description'] ?? '',
            $row['icon'] ?? '',
            $row['url'],
            $row['required_role'],
            (int)$row['sort_order']
        );
    }
    
    /**
     * Create DashboardCard from database row
     */
    private function createDashboardCard(array $row): DashboardCard {
        return new DashboardCard(
            $row['item_id'],
            $row['title'],
            $row['description'] ?? '',
            $row['icon'] ?? '',
            $row['url'],
            $row['required_role'],
            (int)$row['sort_order']
        );
    }
    
    /**
     * Load actions for a card from database
     */
    private function loadActions(string $itemId): array {
        $stmt = $this->pdo->prepare("
            SELECT url, label, sort_order 
            FROM navigation_item_actions 
            WHERE item_id = ? 
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$itemId]);
        
        $actions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $actions[] = [
                'url' => $row['url'],
                'label' => $row['label']
            ];
        }
        
        return $actions;
    }
    
    /**
     * Add a new navigation item to database
     */
    public function addItem(array $data): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO navigation_items 
            (item_id, item_type, title, description, icon, url, required_role, sort_order, parent_id, is_enabled)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['item_id'],
            $data['item_type'] ?? 'both',
            $data['title'],
            $data['description'] ?? '',
            $data['icon'] ?? '',
            $data['url'],
            $data['required_role'] ?? null,
            $data['sort_order'] ?? 0,
            $data['parent_id'] ?? null,
            $data['is_enabled'] ?? true
        ]);
        
        // Clear cache
        $this->cache = [];
        
        return $result;
    }
    
    /**
     * Update navigation item in database
     */
    public function updateItem(string $itemId, array $data): bool {
        $fields = [];
        $values = [];
        
        $allowedFields = ['item_type', 'title', 'description', 'icon', 'url', 'required_role', 'sort_order', 'parent_id', 'is_enabled'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $itemId;
        
        $sql = "UPDATE navigation_items SET " . implode(', ', $fields) . " WHERE item_id = ?";
        $stmt = $this->pdo->prepare($sql);
        
        $result = $stmt->execute($values);
        
        // Clear cache
        $this->cache = [];
        
        return $result;
    }
    
    /**
     * Delete navigation item from database
     */
    public function deleteItem(string $itemId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM navigation_items WHERE item_id = ?");
        $result = $stmt->execute([$itemId]);
        
        // Clear cache
        $this->cache = [];
        
        return $result;
    }
    
    /**
     * Add action to a card
     */
    public function addAction(string $itemId, string $url, string $label, int $sortOrder = 0): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO navigation_item_actions (item_id, url, label, sort_order)
            VALUES (?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([$itemId, $url, $label, $sortOrder]);
        
        // Clear cache
        $this->cache = [];
        
        return $result;
    }
    
    /**
     * Clear internal cache
     */
    public function clearCache(): void {
        $this->cache = [];
    }
}
