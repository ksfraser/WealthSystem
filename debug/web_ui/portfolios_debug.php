<?php
// Diagnostic version of portfolios.php - bypasses auth
// Mock authentication
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test_user';
$_SESSION['is_admin'] = false;

// Set current user data
$currentUser = ['user_id' => 1, 'username' => 'test_user'];
$user = $currentUser;
$isAdmin = false;

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<title>Portfolio Debug - Filter Test</title>\n";
echo "<meta charset='UTF-8'>\n";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>\n";
echo "<style>\n";
echo ".debug { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; margin: 10px 0; }\n";
echo "</style>\n";
echo "</head>\n<body>\n";

echo "<div class='container mt-4'>\n";
echo "<div class='debug'><strong>🔍 PORTFOLIO DEBUG MODE</strong><br>";
echo "Current User: " . htmlspecialchars($user['username']) . "<br>";
echo "Is Admin: " . ($isAdmin ? 'Yes' : 'No') . "<br>";
echo "Session Active: " . (session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No') . "</div>\n";

// Create the exact same filter controls as working test_filter.html
echo "<div class='card mb-4'>\n";
echo "<div class='card-header'><h5>🚀 Micro-Cap Portfolio with Filters</h5></div>\n";
echo "<div class='card-body'>\n";

// Filter controls (exactly like test_filter.html)
echo "<div style='margin: 15px 0; padding: 10px; background-color: #f8f9fa;'>\n";
echo "<label><input type='checkbox' id='filter-recent' checked> Show Recent Only</label>\n";
echo "<label><input type='checkbox' id='filter-profitable'> Profitable Only</label>\n";
echo "<label><input type='checkbox' id='filter-large-positions'> Large Positions (>$1000)</label>\n";
echo "<select id='sort-by' style='padding: 5px; margin-left: 10px;'>\n";
echo "<option value='none'>No Sorting</option>\n";
echo "<option value='date'>Sort by Date (Recent First)</option>\n";
echo "<option value='value'>Sort by Value (Highest First)</option>\n";
echo "<option value='symbol'>Sort by Symbol (A-Z)</option>\n";
echo "</select>\n";
echo "<button onclick='applyFilters()' class='btn btn-primary btn-sm'>Apply Filters</button>\n";
echo "<button onclick='resetFilters()' class='btn btn-secondary btn-sm'>Reset</button>\n";
echo "</div>\n";

echo "<div id='filter-status' style='margin: 5px 0; color: #666;'></div>\n";

// Create test table with data (exactly like test_filter.html structure)
echo "<table class='table table-striped' id='micro-cap-table'>\n";
echo "<thead>\n<tr>\n";
echo "<th>Date</th><th>Ticker</th><th>Shares</th><th>Current Price</th><th>Total Value</th><th>PnL</th><th>Action</th>\n";
echo "</tr>\n</thead>\n";
echo "<tbody>\n";

// Sample data (some profitable, some not, different values and dates)
$testData = [
    ['2023-12-15', 'AAPL', '100.0', '150.00', '15000.00', '500.00', 'HOLD'],
    ['2023-12-14', 'MSFT', '50.0', '280.00', '14000.00', '-250.00', 'SELL'], 
    ['2023-12-16', 'GOOGL', '10.0', '2500.00', '25000.00', '200.00', 'HOLD'],
    ['2023-12-10', 'TSLA', '25.0', '240.00', '6000.00', '500.00', 'BUY'],
    ['2023-12-01', 'NVDA', '5.0', '450.00', '2250.00', '-100.00', 'HOLD'],
    ['2023-12-17', 'META', '20.0', '350.00', '7000.00', '300.00', 'HOLD'],
];

foreach ($testData as $row) {
    echo "<tr>\n";
    foreach ($row as $cell) {
        echo "<td>{$cell}</td>";
    }
    echo "\n</tr>\n";
}

echo "</tbody>\n</table>\n";
echo "</div>\n</div>\n";

// Debug section
echo "<div class='debug'>\n";
echo "<strong>🔍 Debug Information:</strong><br>\n";
echo "<div id='debug-output'></div>\n";
echo "</div>\n";

// JavaScript (exactly from test_filter.html)
echo "<script>\n";
echo "console.log('🔍 DEBUG: Page loaded, initializing filters...');\n";

// The exact applyFilters function from test_filter.html
echo "
function applyFilters() {
    console.log('🔍 DEBUG: applyFilters() called');
    const table = document.getElementById('micro-cap-table');
    if (!table) {
        console.log('❌ Table not found: micro-cap-table');
        document.getElementById('debug-output').innerHTML += 'ERROR: Table not found<br>';
        return;
    }
    console.log('✅ Table found:', table);
    
    const showRecent = document.getElementById('filter-recent').checked;
    const showProfitable = document.getElementById('filter-profitable').checked;
    const showLarge = document.getElementById('filter-large-positions').checked;
    
    console.log('🔍 Filter states:', {recent: showRecent, profitable: showProfitable, large: showLarge});
    
    const tbody = table.querySelector('tbody');
    if (!tbody) {
        console.log('❌ Table body not found');
        return;
    }
    
    const rows = Array.from(tbody.querySelectorAll('tr'));
    console.log('🔍 Found ' + rows.length + ' rows to filter');
    
    let visibleCount = 0;
    
    // Filter rows
    rows.forEach((row, rowIndex) => {
        let show = true;
        const cells = Array.from(row.querySelectorAll('td'));
        
        console.log('🔍 Processing row ' + rowIndex + ':', cells.map(c => c.textContent));
        
        // Skip TOTAL rows from filtering
        const isTotal = cells.some(cell => cell.textContent.toLowerCase().includes('total'));
        if (isTotal) {
            row.style.display = ''; // Always show TOTAL rows
            visibleCount++;
            return;
        }
        
        if (showProfitable && cells.length > 0) {
            // Look for positive PnL values specifically
            let isProfitable = false;
            cells.forEach((cell, index) => {
                const headerCells = table.querySelectorAll('thead th');
                if (headerCells[index]) {
                    const headerText = headerCells[index].textContent.toLowerCase();
                    const cellText = cell.textContent.trim();
                    
                    // Check PnL column specifically - this is the main profit indicator
                    if (headerText.includes('pnl') || headerText.includes('profit') || headerText.includes('p&l')) {
                        const value = parseFloat(cellText.replace(/[^0-9.-]/g, ''));
                        console.log('🔍 PnL check - Header: \"' + headerText + '\", Cell: \"' + cellText + '\", Value: ' + value);
                        if (!isNaN(value) && value > 0) {
                            isProfitable = true;
                        }
                    }
                }
            });
            console.log('🔍 Row ' + rowIndex + ' profitable: ' + isProfitable);
            if (!isProfitable) show = false;
        }
        
        if (showLarge && cells.length > 0) {
            // Look for large monetary values in Total Value or Cost Basis columns
            let hasLargePosition = false;
            cells.forEach((cell, index) => {
                const headerCells = table.querySelectorAll('thead th');
                if (headerCells[index]) {
                    const headerText = headerCells[index].textContent.toLowerCase();
                    const cellText = cell.textContent.trim();
                    
                    if ((headerText.includes('value') || headerText.includes('cost') || headerText.includes('basis')) && 
                        cellText && cellText !== '') {
                        const value = parseFloat(cellText.replace(/[^0-9.-]/g, ''));
                        console.log('🔍 Value check - Header: \"' + headerText + '\", Cell: \"' + cellText + '\", Value: ' + value);
                        if (!isNaN(value) && Math.abs(value) > 1000) {
                            hasLargePosition = true;
                        }
                    }
                }
            });
            console.log('🔍 Row ' + rowIndex + ' large position: ' + hasLargePosition);
            if (!hasLargePosition) show = false;
        }
        
        console.log('🔍 Row ' + rowIndex + ' final show: ' + show);
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });
    
    // Apply sorting after filtering
    const sortBy = document.getElementById('sort-by').value;
    if (sortBy && sortBy !== 'none') {
        sortTable(table, sortBy);
    }
    
    console.log('🔍 Showing ' + visibleCount + ' of ' + rows.length + ' rows');
    
    // Update status
    const status = document.getElementById('filter-status');
    if (status) {
        status.textContent = 'Showing ' + visibleCount + ' of ' + rows.length + ' entries';
    }
    
    // Update debug output
    document.getElementById('debug-output').innerHTML = 
        'Filters applied - Showing ' + visibleCount + ' of ' + rows.length + ' rows<br>' +
        'Recent: ' + showRecent + ', Profitable: ' + showProfitable + ', Large: ' + showLarge + '<br>';
}

function resetFilters() {
    console.log('🔍 DEBUG: resetFilters() called');
    // Reset checkboxes
    document.getElementById('filter-recent').checked = true;
    document.getElementById('filter-profitable').checked = false;
    document.getElementById('filter-large-positions').checked = false;
    document.getElementById('sort-by').value = 'none';
    
    // Show all rows
    const table = document.getElementById('micro-cap-table');
    if (table) {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.style.display = '';
        });
        
        // Update status
        const status = document.getElementById('filter-status');
        if (status) {
            status.textContent = 'Showing all ' + rows.length + ' entries';
        }
        
        document.getElementById('debug-output').innerHTML = 'Filters reset - showing all rows<br>';
    }
}

function sortTable(table, sortBy) {
    console.log('🔍 DEBUG: sortTable() called with:', sortBy);
    // Implementation would go here - simplified for debugging
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 DEBUG: DOM loaded, setting up event listeners...');
    
    // Test that all elements exist
    const elements = {
        table: document.getElementById('micro-cap-table'),
        filterRecent: document.getElementById('filter-recent'),
        filterProfitable: document.getElementById('filter-profitable'),
        filterLarge: document.getElementById('filter-large-positions'),
        sortSelect: document.getElementById('sort-by')
    };
    
    console.log('🔍 Elements found:', elements);
    
    let debugInfo = 'Page initialization:<br>';
    for (const [name, element] of Object.entries(elements)) {
        debugInfo += name + ': ' + (element ? '✅ Found' : '❌ Missing') + '<br>';
    }
    document.getElementById('debug-output').innerHTML = debugInfo;
    
    // Auto-apply filters on page load to test
    setTimeout(() => {
        console.log('🔍 Auto-applying filters for initial test...');
        applyFilters();
    }, 500);
});
";

echo "</script>\n";
echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>\n";
echo "</body>\n</html>\n";
?>
