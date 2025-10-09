<?php
// This script is intended to be run from the command line.
// It downloads historical stock data from a provider (e.g., Yahoo Finance)
// and saves it to the corresponding database table.

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db/Database.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

if ($argc < 2) {
    die("Usage: php download_historical_data.php <SYMBOL>\n");
}

$symbol = $argv[1];
$pdo = Database::getInstance()->getConnection();
$tableName = 'hist_prices_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $symbol));

function downloadData($symbol) {
    $client = new Client();
    // Use a reliable, free API for historical data.
    // This example uses a generic endpoint format. Replace with a real one.
    // Note: Yahoo Finance API has become difficult to access directly.
    // Consider using a service like Alpha Vantage, IEX Cloud, or FinancialModelingPrep.
    $apiKey = 'YOUR_API_KEY'; // Replace with your actual API key
    $url = "https://www.alphavantage.co/query?function=TIME_SERIES_DAILY_ADJUSTED&symbol={$symbol}&outputsize=full&apikey={$apiKey}";

    try {
        $response = $client->request('GET', $url);
        $data = json_decode($response->getBody(), true);

        if (isset($data['Time Series (Daily)'])) {
            return $data['Time Series (Daily)'];
        } else {
            error_log("Could not find time series data for {$symbol}. Response: " . json_encode($data));
            return null;
        }
    } catch (RequestException $e) {
        error_log("Failed to download data for {$symbol}: " . $e->getMessage());
        return null;
    }
}

function saveData($pdo, $tableName, $data) {
    if (empty($data)) {
        return;
    }

    $sql = "INSERT INTO `{$tableName}` (date, open, high, low, close, adj_close, volume) VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE open=VALUES(open), high=VALUES(high), low=VALUES(low), close=VALUES(close), adj_close=VALUES(adj_close), volume=VALUES(volume)";
    
    $stmt = $pdo->prepare($sql);

    $pdo->beginTransaction();
    foreach ($data as $date => $row) {
        $stmt->execute([
            $date,
            $row['1. open'],
            $row['2. high'],
            $row['3. low'],
            $row['4. close'],
            $row['5. adjusted close'],
            $row['6. volume']
        ]);
    }
    $pdo->commit();
}

echo "Starting download for {$symbol}...\n";
$historicalData = downloadData($symbol);
if ($historicalData) {
    echo "Download complete. Saving data to database...\n";
    saveData($pdo, $tableName, $historicalData);
    echo "Data saved for {$symbol}.\n";
} else {
    echo "Failed to download data for {$symbol}.\n";
}
