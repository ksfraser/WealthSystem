<?php

require_once __DIR__ . '/CommonDAO.php';
require_once __DIR__ . '/SchemaMigrator.php';
require_once __DIR__ . '/CsvParser.php';
use App\CsvParser;
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/InvestGLDAO.php';
require_once __DIR__ . '/StockSymbolDAO.php';

class MidCapBankImportDAO extends CommonDAO {
    /** @var CsvParser */
    private $csvParser;
    /** @var InvestGLDAO */
    private $investGLDAO;
    /** @var StockSymbolDAO */
    private $stockSymbolDAO;
    /**
     * @param PDO|null $pdo Optionally inject PDO for testability/DI
     */
    public function __construct($pdo = null) {
        parent::__construct('LegacyDatabaseConfig');
        $logger = new FileLogger(__DIR__ . '/../logs/bank_import.log', 'info');
        $this->csvParser = new CsvParser($logger);
        $this->pdo = $pdo ?: $this->pdo;
        $this->investGLDAO = new InvestGLDAO($this->pdo);
        $this->stockSymbolDAO = new StockSymbolDAO($this->pdo);
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
            fputcsv($fp, array_keys($rows[0]), ',', '"', '\\');
            foreach ($rows as $row) {
                fputcsv($fp, $row, ',', '"', '\\');
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
     * @param int $userId The user ID performing the import
     * @return bool
     */
    public function importToMidCap($rows, $type, $userId, $bankAccountId) {
        if ($type === 'holdings') {
            // For each holding, create an opening balance GL entry
            foreach ($rows as $row) {
                $symbol = $this->getAliasedValue($row, ['symbol', 'ticker', 'stock_symbol']);
                if (!$symbol) {
                    // Skip rows that don't have a symbol, they might be cash balances or headers
                    continue;
                }
                $this->stockSymbolDAO->ensureSymbolExists($symbol);

                $glAccount = '1100'; // TODO: Map to correct GL account
                $date = $this->getAliasedValue($row, ['date'], date('Y-m-d'));
                $qty = $this->getAliasedValue($row, ['shares', 'quantity']);
                $price = $this->getAliasedValue($row, ['price', 'current_price']);
                $marketValue = $this->getAliasedValue($row, ['market_value', 'total_value'], $qty * $price);
                $desc = 'Opening balance from import';
                $this->investGLDAO->addOpeningBalance($userId, $glAccount, $symbol, $date, $qty, $price, $marketValue, $desc, $bankAccountId);
            }
        } else {
            $table = 'midcap_transactions';
            foreach ($rows as $row) {
                $symbol = $this->getAliasedValue($row, ['symbol', 'ticker', 'stock_symbol']);
                if (!$symbol) {
                    // Skip rows that don't have a symbol
                    continue;
                }
                $this->insertTransaction($table, $row);
                
                // Also add to gl_trans_invest
                $date = $this->getAliasedValue($row, ['date', 'transaction_date', 'txn_date'], date('Y-m-d'));
                $tranType = $this->getAliasedValue($row, ['type', 'transaction_type', 'txn_type'], 'UNKNOWN');
                $amount = $this->getAliasedValue($row, ['amount', 'total']);
                $qty = $this->getAliasedValue($row, ['shares', 'quantity']);
                $price = $this->getAliasedValue($row, ['price']);
                $fees = $this->getAliasedValue($row, ['commission', 'fee', 'fees'], 0);
                $costBasis = 0; // This might need more complex calculation
                $desc = $this->getAliasedValue($row, ['description'], 'Imported transaction');

                $this->investGLDAO->addTransaction($userId, '1100', $symbol, $date, $tranType, $amount, $qty, $price, $fees, $costBasis, $desc, $bankAccountId);
            }
        }
        return true;
    }

    /**
     * Gets a value from a row by checking multiple possible keys (aliases).
     *
     * @param array $row The data row.
     * @param array $aliases An array of possible keys.
     * @param mixed $default The default value if no key is found.
     * @return mixed The found value or the default.
     */
    private function getAliasedValue(array $row, array $aliases, $default = null) {
        foreach ($aliases as $alias) {
            if (isset($row[$alias])) {
                return $row[$alias];
            }
        }
        return $default;
    }

    private function insertTransaction($table, $row) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO $table (bank_name, account_number, symbol, txn_type, shares, price, amount, txn_date, settlement_date, currency_subaccount, market, description, currency_of_price, commission, exchange_rate, currency_of_amount, settlement_instruction, exchange_rate_cad, cad_equivalent, extra) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $symbol = $this->getAliasedValue($row, ['symbol', 'ticker']);
            $txnType = $this->getAliasedValue($row, ['type', 'transaction_type', 'txn_type']);
            $shares = $this->getAliasedValue($row, ['shares', 'quantity'], 0);
            $price = $this->getAliasedValue($row, ['price'], 0);
            $amount = $this->getAliasedValue($row, ['amount', 'total'], 0);
            $txnDate = $this->getAliasedValue($row, ['date', 'transaction_date', 'txn_date']);
            $settlementDate = $this->getAliasedValue($row, ['settlement_date']);
            $currencySubaccount = $this->getAliasedValue($row, ['currency_of_sub_account_held_in']);
            $market = $this->getAliasedValue($row, ['market']);
            $description = $this->getAliasedValue($row, ['description']);
            $currencyOfPrice = $this->getAliasedValue($row, ['currency_of_price']);
            $commission = $this->getAliasedValue($row, ['commission', 'fee', 'fees']);
            $exchangeRate = $this->getAliasedValue($row, ['exchange_rate']);
            $currencyOfAmount = $this->getAliasedValue($row, ['currency_of_amount']);
            $settlementInstruction = $this->getAliasedValue($row, ['settlement_instruction']);
            $exchangeRateCad = $this->getAliasedValue($row, ['exchange_rate_canadian_equivalent']);
            $cadEquivalent = $this->getAliasedValue($row, ['canadian_equivalent']);
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
