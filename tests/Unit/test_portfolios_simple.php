<?php
/**
 * Simple portfolios test - no auth required
 */

// Mock authentication
$currentUser = ['username' => 'test', 'user_id' => 1];
$user = $currentUser;
$isAdmin = false;

// Include dependencies
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';

// Use the namespaced UI Factory
use Ksfraser\UIRenderer\Factories\UiFactory;

echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "<title>Portfolio Test</title>\n";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>\n";
echo "</head>\n";
echo "<body>\n";
echo "<div class='container mt-4'>\n";

// Test basic card creation
try {
    $testCard = UiFactory::createInfoCard(
        'Test Card', 
        '<p>This is a test of the UI Factory system.</p>'
    );
    echo $testCard->toHtml();
    echo "<br><br>\n";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Card Error: " . htmlspecialchars($e->getMessage()) . "</div>\n";
}

// Test table creation
try {
    $headers = ['Symbol', 'Price', 'Change'];
    $rows = [
        ['AAPL', '$150.00', '+2.5%'],
        ['MSFT', '$280.00', '-1.2%'],
        ['GOOGL', '$2500.00', '+0.8%']
    ];
    
    $table = UiFactory::createTable($rows, $headers, ['striped' => true, 'id' => 'testTable']);
    echo "<h4>Test Table:</h4>\n";
    echo $table->toHtml();
    echo "<br><br>\n";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Table Error: " . htmlspecialchars($e->getMessage()) . "</div>\n";
}

// Test with filter/sort JavaScript
echo "
<div class='card'>
    <div class='card-header'>
        <h5>🚀 Micro-Cap Portfolio with Filters</h5>
    </div>
    <div class='card-body'>
        <div class='row mb-3'>
            <div class='col-md-8'>
                <div class='btn-group' role='group' aria-label='Portfolio filters'>
                    <input type='radio' class='btn-check' name='microCapFilter' id='micro_all' value='all' checked>
                    <label class='btn btn-outline-primary btn-sm' for='micro_all'>All</label>
                    
                    <input type='radio' class='btn-check' name='microCapFilter' id='micro_recent' value='recent'>
                    <label class='btn btn-outline-success btn-sm' for='micro_recent'>Recent</label>
                    
                    <input type='radio' class='btn-check' name='microCapFilter' id='micro_profitable' value='profitable'>
                    <label class='btn btn-outline-warning btn-sm' for='micro_profitable'>Profitable</label>
                    
                    <input type='radio' class='btn-check' name='microCapFilter' id='micro_large' value='large'>
                    <label class='btn btn-outline-info btn-sm' for='micro_large'>Large Positions</label>
                </div>
            </div>
            <div class='col-md-4'>
                <select class='form-select form-select-sm' id='microCapSort'>
                    <option value='symbol'>Sort by Symbol</option>
                    <option value='current_value'>Sort by Value</option>
                    <option value='profit_loss'>Sort by P&L</option>
                    <option value='percentage_gain_loss'>Sort by % Change</option>
                    <option value='last_updated'>Sort by Date</option>
                </select>
            </div>
        </div>
        
        <table class='table table-striped' id='microCapTable'>
            <thead>
                <tr>
                    <th>Symbol</th>
                    <th>Shares</th>
                    <th>Avg Cost</th>
                    <th>Current Price</th>
                    <th>Current Value</th>
                    <th>P&L</th>
                    <th>% Change</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>AAPL</strong></td>
                    <td>100.00</td>
                    <td>$145.00</td>
                    <td>$150.00</td>
                    <td>$15,000.00</td>
                    <td><span class='text-success'>📈 $500.00</span></td>
                    <td><span class='text-success'>3.45%</span></td>
                    <td>Dec 15, 2023</td>
                </tr>
                <tr>
                    <td><strong>MSFT</strong></td>
                    <td>50.00</td>
                    <td>$285.00</td>
                    <td>$280.00</td>
                    <td>$14,000.00</td>
                    <td><span class='text-danger'>📉 $-250.00</span></td>
                    <td><span class='text-danger'>-1.75%</span></td>
                    <td>Dec 14, 2023</td>
                </tr>
                <tr>
                    <td><strong>GOOGL</strong></td>
                    <td>10.00</td>
                    <td>$2480.00</td>
                    <td>$2500.00</td>
                    <td>$25,000.00</td>
                    <td><span class='text-success'>📈 $200.00</span></td>
                    <td><span class='text-success'>0.81%</span></td>
                    <td>Dec 16, 2023</td>
                </tr>
                <tr>
                    <td><strong>TSLA</strong></td>
                    <td>25.00</td>
                    <td>$220.00</td>
                    <td>$240.00</td>
                    <td>$6,000.00</td>
                    <td><span class='text-success'>📈 $500.00</span></td>
                    <td><span class='text-success'>9.09%</span></td>
                    <td>Dec 10, 2023</td>
                </tr>
            </tbody>
        </table>
        
        <div id='filterResults' class='mt-3'></div>
    </div>
</div>

<script>
// Portfolio filtering and sorting functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Portfolio page loaded - initializing filters');
    
    // Filter functionality
    const filterRadios = document.querySelectorAll(\"input[name='microCapFilter']\");
    const sortSelect = document.getElementById('microCapSort');
    const table = document.getElementById('microCapTable');
    const resultsDiv = document.getElementById('filterResults');
    
    if (filterRadios.length > 0) {
        filterRadios.forEach(radio => {
            radio.addEventListener('change', applyFilters);
        });
        console.log('Filter radios attached:', filterRadios.length);
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', applySorting);
        console.log('Sort select attached');
    }
    
    function applyFilters() {
        const selectedFilter = document.querySelector(\"input[name='microCapFilter']:checked\").value;
        console.log('Applying filter:', selectedFilter);
        
        if (!table) {
            console.log('Table not found');
            return;
        }
        
        const rows = table.querySelectorAll('tbody tr');
        console.log('Found rows:', rows.length);
        
        let visibleCount = 0;
        
        rows.forEach(row => {
            let show = true;
            
            if (selectedFilter === 'recent') {
                // Show positions updated in last 7 days
                const dateCell = row.cells[7]; // Last Updated column
                if (dateCell) {
                    const dateText = dateCell.textContent;
                    const rowDate = new Date(dateText);
                    const weekAgo = new Date();
                    weekAgo.setDate(weekAgo.getDate() - 7);
                    show = rowDate > weekAgo;
                }
            } else if (selectedFilter === 'profitable') {
                // Show positions with positive P&L
                const plCell = row.cells[5]; // P&L column
                if (plCell) {
                    const plText = plCell.textContent;
                    const plValue = parseFloat(plText.replace(/[^-\\d.]/g, ''));
                    show = plValue > 0;
                }
            } else if (selectedFilter === 'large') {
                // Show positions worth more than $10000
                const valueCell = row.cells[4]; // Current Value column
                if (valueCell) {
                    const valueText = valueCell.textContent;
                    const value = parseFloat(valueText.replace(/[^-\\d.]/g, ''));
                    show = value > 10000;
                }
            }
            
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        
        resultsDiv.innerHTML = '<div class=\"alert alert-info\">Filter \"' + selectedFilter + '\" applied. Showing ' + visibleCount + ' of ' + rows.length + ' positions.</div>';
        console.log('Filter applied:', selectedFilter, 'Visible:', visibleCount);
    }
    
    function applySorting() {
        const sortBy = sortSelect.value;
        console.log('Sorting by:', sortBy);
        sortTable(table, sortBy);
    }
    
    function sortTable(table, sortBy) {
        if (!table) return;
        
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            let aVal, bVal;
            
            switch(sortBy) {
                case 'symbol':
                    aVal = a.cells[0].textContent.trim();
                    bVal = b.cells[0].textContent.trim();
                    return aVal.localeCompare(bVal);
                    
                case 'current_value':
                    aVal = parseFloat(a.cells[4].textContent.replace(/[^-\\d.]/g, ''));
                    bVal = parseFloat(b.cells[4].textContent.replace(/[^-\\d.]/g, ''));
                    return bVal - aVal; // Descending
                    
                case 'profit_loss':
                    aVal = parseFloat(a.cells[5].textContent.replace(/[^-\\d.]/g, ''));
                    bVal = parseFloat(b.cells[5].textContent.replace(/[^-\\d.]/g, ''));
                    return bVal - aVal; // Descending
                    
                case 'percentage_gain_loss':
                    aVal = parseFloat(a.cells[6].textContent.replace(/[^-\\d.]/g, ''));
                    bVal = parseFloat(b.cells[6].textContent.replace(/[^-\\d.]/g, ''));
                    return bVal - aVal; // Descending
                    
                case 'last_updated':
                    aVal = new Date(a.cells[7].textContent);
                    bVal = new Date(b.cells[7].textContent);
                    return bVal - aVal; // Most recent first
                    
                default:
                    return 0;
            }
        });
        
        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
        console.log('Table sorted by:', sortBy);
    }
});
</script>

";

echo "</div>\n";
echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>\n";
echo "</body>\n";
echo "</html>\n";
?>
