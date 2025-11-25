<?php
/**
 * Investigate fin_statement table and check for symbol data
 */

require_once __DIR__ . '/UserAuthDAO.php';

try {
    $auth = new UserAuthDAO();
    $pdo = $auth->getPDO();
    
    echo "=== FINANCIAL STATEMENT DATA ANALYSIS ===\n\n";
    
    // Check fin_statement structure
    echo "fin_statement table structure:\n";
    $columns = $pdo->query("DESCRIBE fin_statement")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    // Get some sample data with non-empty symbols
    echo "\n=== SAMPLE DATA ===\n";
    $samples = $pdo->query("
        SELECT * FROM fin_statement 
        WHERE symbol != '' AND symbol IS NOT NULL 
        ORDER BY lasteval DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($samples)) {
        // If no symbols, check what we have
        $samples = $pdo->query("
            SELECT * FROM fin_statement 
            ORDER BY lasteval DESC 
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    foreach ($samples as $row) {
        echo "Record: " . json_encode($row) . "\n";
    }
    
    // Look for other tables that might have price data
    echo "\n=== LOOKING FOR PRICE DATA IN OTHER TABLES ===\n";
    
    // Check if there are any CSV files in the directory that might contain price data
    $csvFiles = glob(__DIR__ . '/../*.csv') ?: [];
    $csvFiles = array_merge($csvFiles, glob(__DIR__ . '/../Scripts and CSV Files/*.csv') ?: []);
    
    if (!empty($csvFiles)) {
        echo "Found CSV files:\n";
        foreach ($csvFiles as $file) {
            echo "  - " . basename($file) . "\n";
            
            // Check if it looks like price data
            if (preg_match('/price|stock|trade/i', basename($file))) {
                echo "    (Potential price data file)\n";
                
                // Check first few lines
                if (file_exists($file) && is_readable($file)) {
                    $handle = fopen($file, 'r');
                    $header = fgetcsv($handle);
                    $firstRow = fgetcsv($handle);
                    fclose($handle);
                    
                    if ($header) {
                        echo "    Headers: " . implode(', ', $header) . "\n";
                    }
                    if ($firstRow) {
                        echo "    Sample: " . implode(', ', array_slice($firstRow, 0, 5)) . "...\n";
                    }
                }
            }
        }
    }
    
    // Check trading_script.py and other files for any stored data
    echo "\n=== CHECKING PROJECT FILES ===\n";
    
    $pyFiles = glob(__DIR__ . '/../*.py') ?: [];
    foreach ($pyFiles as $file) {
        if (preg_match('/trading|stock|price/i', basename($file))) {
            echo "Found: " . basename($file) . "\n";
        }
    }
    
    // Check if there are any temporary or backup files
    $dataFiles = array_merge(
        glob(__DIR__ . '/../*.json') ?: [],
        glob(__DIR__ . '/../*.sqlite') ?: [],
        glob(__DIR__ . '/../*.db') ?: []
    );
    
    if (!empty($dataFiles)) {
        echo "Other data files:\n";
        foreach ($dataFiles as $file) {
            echo "  - " . basename($file) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>