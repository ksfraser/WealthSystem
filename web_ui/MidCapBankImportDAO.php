require_once __DIR__ . '/InvestGLDAO.php';
    /** @var InvestGLDAO */
    private $investGLDAO;
<?php

require_once __DIR__ . '/CommonDAO.php';
require_once __DIR__ . '/SchemaMigrator.php';
require_once __DIR__ . '/CsvParser.php';
use App\CsvParser;
require_once __DIR__ . '/Logger.php';

class MidCapBankImportDAO extends CommonDAO {
    /** @var CsvParser */
    private $csvParser;
    /** @var InvestGLDAO */
    private $investGLDAO;
    /**
     * @param PDO|null $pdo Optionally inject PDO for testability/DI
     */
    public function __construct($pdo = null) {
        parent::__construct('LegacyDatabaseConfig');
        $logger = new FileLogger(__DIR__ . '/../logs/bank_import.log', 'info');
        $this->csvParser = new CsvParser($logger);
        $this->pdo = $pdo ?: $this->pdo;
        $this->investGLDAO = new InvestGLDAO($this->pdo);
        if ($this->pdo) {
            $schemaDir = __DIR__ . '/schema';
            $migrator = new SchemaMigrator($this->pdo, $schemaDir);
            $migrator->migrate();
        }
    }
    // Parse Account Holdings CSV
    public function parseAccountHoldingsCSV($filePath) {
        return $this->csvParser->parse($filePath);
    }

    // Parse Transaction History CSV
    public function parseTransactionHistoryCSV($filePath) {
        return $this->csvParser->parse($filePath);
    }

    // Staging: Save parsed data to a temp CSV (not tracked by git)
    public function saveStagingCSV($rows, $type) {
        $dir = __DIR__ . "/../bank_imports";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $file = $dir . "/staging_{$type}_" . date('Ymd_His') . ".csv";
        $fp = fopen($file, 'w');
        if (!empty($rows)) {
            fputcsv($fp, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($fp, $row);
            }
        }
        fclose($fp);
        return $file;
    }

    // Insert staged data into mid-cap tables
    /**
     * Import staged data into mid-cap tables and GL (double-entry)
     * @param array $rows
     * @param string $type 'holdings' or 'transactions'
     * @return bool
     */
    public function importToMidCap($rows, $type) {
        if ($type === 'holdings') {
            // For each holding, create an opening balance GL entry
            foreach ($rows as $row) {
                $userId = $row['user_id'] ?? 1; // TODO: Replace with real user context
                $glAccount = '1100'; // TODO: Map to correct GL account
                $symbol = $row['symbol'] ?? $row['Symbol'] ?? $row['Ticker'] ?? '';
                $date = $row['date'] ?? $row['Date'] ?? date('Y-m-d');
                $qty = $row['shares'] ?? $row['quantity'] ?? $row['Shares'] ?? 0;
                $price = $row['current_price'] ?? $row['price'] ?? $row['Price'] ?? 0;
                $marketValue = $row['market_value'] ?? $row['Market Value'] ?? $qty * $price;
                $desc = 'Opening balance from import';
                $this->investGLDAO->addOpeningBalance($userId, $glAccount, $symbol, $date, $qty, $price, $marketValue, $desc);
            }
        } else {
            $table = 'midcap_transactions';
            foreach ($rows as $row) {
                $this->insertTransaction($table, $row);
            }
        }
        return true;
    }

    private function insertTransaction($table, $row) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO $table (bank_name, account_number, symbol, txn_type, shares, price, amount, txn_date, settlement_date, currency_subaccount, market, description, currency_of_price, commission, exchange_rate, currency_of_amount, settlement_instruction, exchange_rate_cad, cad_equivalent, extra) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $symbol = $row['Ticker'] ?? $row['Symbol'] ?? $row['symbol'] ?? null;
            $txnType = $row['Type'] ?? $row['Transaction Type'] ?? $row['txn_type'] ?? null;
            $shares = $row['Shares'] ?? $row['Quantity'] ?? $row['shares'] ?? 0;
            $price = $row['Price'] ?? $row['price'] ?? 0;
            $amount = $row['Amount'] ?? $row['Total'] ?? $row['amount'] ?? 0;
            $txnDate = $row['Date'] ?? $row['Transaction Date'] ?? $row['txn_date'] ?? null;
            $settlementDate = $row['Settlement Date'] ?? null;
            $currencySubaccount = $row['Currency of Sub-account Held In'] ?? null;
            $market = $row['Market'] ?? null;
            $description = $row['Description'] ?? null;
            $currencyOfPrice = $row['Currency of Price'] ?? null;
            $commission = $row['Commission'] ?? null;
            $exchangeRate = $row['Exchange Rate'] ?? null;
            $currencyOfAmount = $row['Currency of Amount'] ?? null;
            $settlementInstruction = $row['Settlement Instruction'] ?? null;
            $exchangeRateCad = $row['Exchange Rate (Canadian Equivalent)'] ?? null;
            $cadEquivalent = $row['Canadian Equivalent'] ?? null;
            $extra = json_encode($row);
            $stmt->execute([
                $row['bank_name'] ?? '',
                $row['account_number'] ?? '',
                $symbol,
                $txnType,
                $shares,
                $price,
                $amount,
                $txnDate,
                $settlementDate,
                $currencySubaccount,
                $market,
                $description,
                $currencyOfPrice,
                $commission,
                $exchangeRate,
                $currencyOfAmount,
                $settlementInstruction,
                $exchangeRateCad,
                $cadEquivalent,
                $extra
            ]);
        } catch (Throwable $e) {
            echo '<h2 style="color:red;">DB Insert Error</h2>';
            echo '<pre>' . htmlspecialchars($e) . '</pre>';
            throw $e;
        }
    }

    // Try to identify bank/account from CSV header or data
    public function identifyBankAccount($rows) {
        // TODO: Implement logic to guess bank/account from data
        // Return null if not sure
        return null;
    }
}
