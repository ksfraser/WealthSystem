<?php
/**
 * Create stocks table and sample data
 */

require_once __DIR__ . '/UserAuthDAO.php';

try {
    $auth = new UserAuthDAO();
    $pdo = $auth->getPDO();
    
    // Create stocks table
    $sql = "CREATE TABLE IF NOT EXISTS stocks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        symbol VARCHAR(10) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        sector VARCHAR(100),
        industry VARCHAR(100),
        market_cap BIGINT,
        exchange VARCHAR(20) DEFAULT 'NASDAQ',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_symbol (symbol),
        INDEX idx_sector (sector),
        INDEX idx_industry (industry)
    )";
    
    $pdo->exec($sql);
    echo "✓ Stocks table created successfully\n";
    
    // Insert some sample stocks
    $sampleStocks = [
        ['AAPL', 'Apple Inc.', 'Technology', 'Consumer Electronics'],
        ['MSFT', 'Microsoft Corporation', 'Technology', 'Software'],
        ['GOOGL', 'Alphabet Inc.', 'Technology', 'Internet Services'],
        ['TSLA', 'Tesla Inc.', 'Consumer Cyclical', 'Auto Manufacturers'],
        ['AMZN', 'Amazon.com Inc.', 'Consumer Cyclical', 'Internet Retail'],
        ['NVDA', 'NVIDIA Corporation', 'Technology', 'Semiconductors'],
        ['META', 'Meta Platforms Inc.', 'Technology', 'Social Media'],
        ['NFLX', 'Netflix Inc.', 'Communication Services', 'Entertainment'],
        ['JPM', 'JPMorgan Chase & Co.', 'Financial Services', 'Banks'],
        ['V', 'Visa Inc.', 'Financial Services', 'Credit Services'],
        ['JNJ', 'Johnson & Johnson', 'Healthcare', 'Drug Manufacturers'],
        ['PG', 'Procter & Gamble Co.', 'Consumer Defensive', 'Household Products'],
        ['HD', 'The Home Depot Inc.', 'Consumer Cyclical', 'Home Improvement Retail'],
        ['BAC', 'Bank of America Corp.', 'Financial Services', 'Banks'],
        ['XOM', 'Exxon Mobil Corporation', 'Energy', 'Oil & Gas Integrated']
    ];
    
    $insertSql = "INSERT IGNORE INTO stocks (symbol, name, sector, industry) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($insertSql);
    
    $inserted = 0;
    foreach ($sampleStocks as $stock) {
        if ($stmt->execute($stock)) {
            $inserted++;
        }
    }
    
    echo "✓ {$inserted} sample stocks inserted successfully\n";
    echo "✓ Database setup complete!\n";
    
    // Verify the table
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM stocks");
    $total = $countStmt->fetchColumn();
    echo "✓ Total stocks in database: {$total}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>