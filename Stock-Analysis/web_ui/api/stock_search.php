<?php
/**
 * AJAX Stock Search API
 * Provides autocomplete functionality for stock symbols
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../UserAuthDAO.php';

try {
    // Check authentication
    $auth = new UserAuthDAO();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    // Get search query
    $query = trim($_GET['q'] ?? $_GET['query'] ?? '');
    $limit = min(intval($_GET['limit'] ?? 20), 50); // Max 50 results
    
    if (strlen($query) < 1) {
        echo json_encode(['stocks' => []]);
        exit;
    }
    
    $pdo = $auth->getPdo();
    
    // Search stocks by symbol or name
    $searchQuery = '%' . $query . '%';
    
    $sql = "
        SELECT 
            symbol,
            name,
            sector,
            industry,
            is_active,
            (CASE 
                WHEN symbol LIKE ? THEN 1 
                WHEN name LIKE ? THEN 2 
                ELSE 3 
            END) as relevance_score
        FROM stocks 
        WHERE 
            (symbol LIKE ? OR name LIKE ?) 
            AND is_active = 1
        ORDER BY 
            relevance_score ASC, 
            symbol ASC
        LIMIT ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $query . '%',  // Exact symbol prefix match gets highest priority
        $query . '%',  // Exact name prefix match gets second priority
        $searchQuery,  // Symbol contains query
        $searchQuery,  // Name contains query
        $limit
    ]);
    
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format results for autocomplete
    $results = array_map(function($stock) {
        return [
            'value' => $stock['symbol'],
            'label' => $stock['symbol'] . ' - ' . $stock['name'],
            'symbol' => $stock['symbol'],
            'name' => $stock['name'],
            'sector' => $stock['sector'],
            'industry' => $stock['industry']
        ];
    }, $stocks);
    
    echo json_encode([
        'stocks' => $results,
        'total' => count($results),
        'query' => $query
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
}