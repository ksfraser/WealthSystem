<?php
/**
 * Add SmallCapCatalyst parameters to database
 */

$dbPath = __DIR__ . '/../storage/database/stock_analysis.db';
$pdo = new PDO('sqlite:' . $dbPath);

// Check if SmallCapCatalyst parameters exist
$stmt = $pdo->query('SELECT COUNT(*) FROM strategy_parameters WHERE strategy_name = "SmallCapCatalyst"');
$count = $stmt->fetchColumn();

echo "Current SmallCapCatalyst parameters: $count\n";

if ($count == 0) {
    echo "Adding SmallCapCatalyst parameters...\n";
    
    // Read the INSERT statements from the migration file
    $migrationFile = __DIR__ . '/../database/migrations/create_strategy_parameters_table.sql';
    $sql = file_get_contents($migrationFile);
    
    // Extract only the SmallCapCatalyst INSERT statements
    preg_match("/-- Insert default parameters for Small-Cap Catalyst Strategy.*?(?=-- Insert default parameters|$)/s", $sql, $matches);
    
    if (!empty($matches[0])) {
        try {
            $pdo->exec($matches[0]);
            
            // Verify
            $stmt = $pdo->query('SELECT COUNT(*) FROM strategy_parameters WHERE strategy_name = "SmallCapCatalyst"');
            $newCount = $stmt->fetchColumn();
            
            echo "✅ Added $newCount SmallCapCatalyst parameters\n";
        } catch (PDOException $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Could not find SmallCapCatalyst parameters in migration file\n";
    }
} else {
    echo "✅ SmallCapCatalyst parameters already exist\n";
}

// List all strategies
echo "\nAll strategies in database:\n";
$stmt = $pdo->query('SELECT strategy_name, COUNT(*) as count FROM strategy_parameters GROUP BY strategy_name');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  - {$row['strategy_name']}: {$row['count']} parameters\n";
}
