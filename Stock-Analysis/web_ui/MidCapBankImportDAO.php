<?php

require_once __DIR__ . '/CommonDAO.php';
require_once __DIR__ . '/SchemaMigrator.php';
require_once __DIR__ . '/InvestGLDAO.php';
require_once __DIR__ . '/StockSymbolDAO.php';

/**
 * MidCap Bank Import Data Access Object
 *
 * Streamlined DAO focused solely on data insertion and persistence.
 * All parsing responsibilities have been moved to dedicated parser classes.
 * Follows Single Responsibility Principle (SRP) - only handles database operations.
 *
 * @startuml MidCapBankImportDAO
 * class MidCapBankImportDAO {
 *   -investGLDAO: InvestGLDAO
 *   -stockSymbolDAO: StockSymbolDAO
 *   +__construct(PDO $pdo = null)
 *   +saveStagingCSV(array $rows, string $type): string
 *   +importToMidCap(array $rows, string $type, int $userId, int $bankAccountId): bool
 *   -insertTransaction(string $table, array $row): void
 *   -getAliasedValue(array $row, array $aliases, mixed $default = null): mixed
 * }
 * 
 * MidCapBankImportDAO --> InvestGLDAO : uses
 * MidCapBankImportDAO --> StockSymbolDAO : uses
 * MidCapBankImportDAO --> CommonDAO : extends
 * @enduml
 */
class MidCapBankImportDAO extends CommonDAO {
    
    /** @var InvestGLDAO */
    private $investGLDAO;
    
    /** @var StockSymbolDAO */
    private $stockSymbolDAO;
    
    /**
     * Constructor - Initialize DAO with database dependencies
     * 
     * @param PDO|null $pdo Optional PDO injection for testability/DI
     */
    public function __construct($pdo = null) {
        parent::__construct('LegacyDatabaseConfig');
        $this->pdo = $pdo ?: $this->pdo;
        $this->investGLDAO = new InvestGLDAO($this->pdo);
        $this->stockSymbolDAO = new StockSymbolDAO($this->pdo);
        
        // Run schema migrations if PDO is available
        if ($this->pdo) {
            $schemaDir = __DIR__ . '/schema';
            $migrator = new SchemaMigrator($this->pdo, $schemaDir);
            $migrator->migrate();
        }
    }

    /**
     * Save parsed transaction data to staging CSV file
     *
     * Creates a temporary CSV file for audit trail and debugging purposes.
     * Files are stored in bank_imports directory (excluded from git).
     *
     * @param array $rows Array of transaction data rows
     * @param string $type Type identifier (e.g., 'transactions', 'holdings')
     * @return string Path to the created staging file
     * @throws RuntimeException If directory creation or file writing fails
     */
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

    /**
     * Import parsed transaction data into MidCap tables and General Ledger
     *
     * Processes standardized transaction data and inserts into appropriate tables.
     * Creates double-entry accounting records in the investment GL.
     * Ensures stock symbols exist before processing transactions.
     *
     * @param array $rows Standardized transaction data from parsers
     * @param string $type Import type: 'holdings' for positions, 'transactions' for trades
     * @param int $userId The user ID performing the import
     * @param int $bankAccountId Target bank account ID for the transactions
     * @return bool True on successful import
     * @throws PDOException On database operation failures
     * @throws InvalidArgumentException On invalid transaction data
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
     * Get value from row using flexible key matching
     *
     * Supports multiple field name aliases to handle different CSV formats.
     * Useful for backwards compatibility and format variations.
     *
     * @param array $row The transaction data row
     * @param array $aliases Array of possible field names to check
     * @param mixed $default Default value if no matching key found
     * @return mixed The found value or default
     */
    private function getAliasedValue(array $row, array $aliases, $default = null) {
        foreach ($aliases as $alias) {
            if (isset($row[$alias])) {
                return $row[$alias];
            }
        }
        return $default;
    }

    /**
     * Insert individual transaction record into database
     *
     * Handles the low-level database insertion with proper error handling.
     * Maps transaction fields to database columns using flexible key matching.
     *
     * @param string $table Target database table name
     * @param array $row Transaction data row with standardized fields
     * @throws PDOException On database operation failure
     * @throws Exception On data validation failure
     */
    private function insertTransaction($table, $row) {
        try {
            // Validate transaction data before insertion
            $this->validateTransactionData($row);
            
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

    /**
     * Validate transaction data before insertion
     *
     * Performs basic validation on transaction records to ensure data quality.
     * Can be extended to add more sophisticated validation rules.
     *
     * @param array $row Transaction data row
     * @return bool True if validation passes
     * @throws InvalidArgumentException On validation failure
     */
    private function validateTransactionData(array $row): bool {
        // Basic validation - ensure required fields are present
        $requiredFields = ['bank_name', 'account_number'];
        
        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }
        
        return true;
    }
}
