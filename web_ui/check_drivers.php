<?php
echo "<h2>PHP PDO Driver Check</h2>";

echo "<h3>Available PDO Drivers:</h3>";
$drivers = PDO::getAvailableDrivers();
if (empty($drivers)) {
    echo "<p style='color: red;'>No PDO drivers found!</p>";
} else {
    echo "<ul>";
    foreach ($drivers as $driver) {
        echo "<li>$driver</li>";
    }
    echo "</ul>";
}

echo "<h3>Required Extensions Check:</h3>";
$required = [
    'pdo' => 'PDO (PHP Data Objects)',
    'pdo_mysql' => 'PDO MySQL Driver',
    'mysql' => 'MySQL (legacy)',
    'mysqli' => 'MySQL Improved'
];

foreach ($required as $ext => $description) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? '✓' : '✗';
    $color = $loaded ? 'green' : 'red';
    echo "<p style='color: $color;'>$status $description ($ext)</p>";
}

echo "<h3>PHP Configuration:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>PHP API: " . php_sapi_name() . "</p>";

// Show loaded extensions
echo "<h3>All Loaded Extensions:</h3>";
$extensions = get_loaded_extensions();
sort($extensions);
echo "<ul>";
foreach ($extensions as $ext) {
    echo "<li>$ext</li>";
}
echo "</ul>";
?>
