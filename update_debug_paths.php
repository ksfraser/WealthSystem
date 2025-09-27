<?php
/**
 * Debug File Path Updater
 * Updates require_once paths in moved debug files to work from new locations
 */

echo "=== UPDATING DEBUG FILE PATHS ===\n";

$debugDir = __DIR__ . '/debug/web_ui';
$projectRoot = __DIR__;

function updateRequirePaths($filePath, $projectRoot) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Update common require paths
    $replacements = [
        // Direct web_ui references
        "require_once __DIR__ . '/UserAuthDAO.php';" => "require_once '{$projectRoot}/web_ui/UserAuthDAO.php';",
        "require_once __DIR__ . '/PortfolioDAO.php';" => "require_once '{$projectRoot}/web_ui/PortfolioDAO.php';",
        "require_once __DIR__ . '/TradeLogDAO.php';" => "require_once '{$projectRoot}/web_ui/TradeLogDAO.php';",
        "require_once __DIR__ . '/DatabaseConnection.php';" => "require_once '{$projectRoot}/web_ui/DatabaseConnection.php';",
        "require_once __DIR__ . '/StockDAO.php';" => "require_once '{$projectRoot}/web_ui/StockDAO.php';",
        
        // Relative paths that need updating  
        "require_once '../UserAuthDAO.php';" => "require_once '{$projectRoot}/web_ui/UserAuthDAO.php';",
        "require_once './UserAuthDAO.php';" => "require_once '{$projectRoot}/web_ui/UserAuthDAO.php';",
        
        // Update __DIR__ references to work from new location
        "__DIR__ . '/" => "'{$projectRoot}/web_ui/'",
        "__DIR__.'/" => "'{$projectRoot}/web_ui/'",
    ];
    
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    // Update any remaining __DIR__ references in require statements
    $content = preg_replace(
        '/require_once\s+__DIR__\s*\.\s*[\'"]([^"\']+)[\'"]/',
        "require_once '{$projectRoot}/web_ui$1'",
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        return true;
    }
    
    return false;
}

// Get all PHP files in debug directory
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($debugDir)
);

$updatedFiles = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filePath = $file->getPathname();
        if (updateRequirePaths($filePath, $projectRoot)) {
            $updatedFiles[] = $filePath;
            echo "✓ Updated: " . str_replace($projectRoot . '/', '', $filePath) . "\n";
        }
    }
}

if (empty($updatedFiles)) {
    echo "ℹ️  No files needed path updates\n";
} else {
    echo "\n✅ Updated " . count($updatedFiles) . " debug files with correct paths\n";
}

echo "\n=== PATH UPDATE COMPLETE ===\n";
?>