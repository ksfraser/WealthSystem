<?php
// Simple test to see what's happening
echo "Starting test...\n";

// Test 1: Basic PHP
echo "PHP is working\n";

// Test 2: Include auth check
echo "About to include auth_check...\n";
try {
    require_once 'auth_check.php';
    echo "auth_check included successfully\n";
} catch (Exception $e) {
    echo "Error with auth_check: " . $e->getMessage() . "\n";
}

// Test 3: Include UserAuthDAO
echo "About to include UserAuthDAO...\n";
try {
    require_once __DIR__ . '/UserAuthDAO.php';
    echo "UserAuthDAO included successfully\n";
} catch (Exception $e) {
    echo "Error with UserAuthDAO: " . $e->getMessage() . "\n";
}

// Test 4: Create UserAuthDAO instance
echo "About to create UserAuthDAO instance...\n";
try {
    $userAuth = new UserAuthDAO();
    echo "UserAuthDAO created successfully\n";
} catch (Exception $e) {
    echo "Error creating UserAuthDAO: " . $e->getMessage() . "\n";
}

// Test 5: Get current user
echo "About to get current user...\n";
try {
    $currentUser = $userAuth->getCurrentUser();
    echo "Current user result: " . ($currentUser ? "User found" : "No user") . "\n";
} catch (Exception $e) {
    echo "Error getting current user: " . $e->getMessage() . "\n";
}

echo "Test complete\n";
?>
