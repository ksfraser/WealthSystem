<?php
/**
 * Quick Actions Link Checker
 * Checks which QuickActions links are working
 */

require_once 'QuickActions.php';

// Get the actions from QuickActions class
$reflection = new ReflectionClass('QuickActions');
$actionsProperty = $reflection->getProperty('actions');
$actionsProperty->setAccessible(true);
$actions = $actionsProperty->getValue();

echo "<h2>QuickActions Link Status Check</h2>\n";
echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>Label</th><th>File</th><th>Status</th><th>Action</th></tr>\n";

foreach ($actions as $action) {
    $filePath = __DIR__ . '/' . $action['href'];
    $exists = file_exists($filePath);
    $status = $exists ? '✅ Exists' : '❌ Missing';
    $actionNeeded = $exists ? 'None' : 'Create file';
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($action['label']) . "</td>";
    echo "<td>" . htmlspecialchars($action['href']) . "</td>";
    echo "<td>$status</td>";
    echo "<td>$actionNeeded</td>";
    echo "</tr>\n";
}

echo "</table>\n";

echo "<h3>Summary</h3>\n";
$totalActions = count($actions);
$existingFiles = 0;
$missingFiles = [];

foreach ($actions as $action) {
    $filePath = __DIR__ . '/' . $action['href'];
    if (file_exists($filePath)) {
        $existingFiles++;
    } else {
        $missingFiles[] = $action;
    }
}

echo "<p>Total QuickActions: $totalActions</p>\n";
echo "<p>Existing files: $existingFiles</p>\n";
echo "<p>Missing files: " . count($missingFiles) . "</p>\n";

if (!empty($missingFiles)) {
    echo "<h3>Missing Files:</h3>\n";
    echo "<ul>\n";
    foreach ($missingFiles as $missing) {
        echo "<li>{$missing['label']} - {$missing['href']}</li>\n";
    }
    echo "</ul>\n";
}
?>
