<?php
require_once __DIR__ . '/TransactionParserInterface.php';

class MidCapParser implements TransactionParserInterface {

    private const EXPECTED_HOLDINGS_HEADERS = ['symbol', 'shares', 'price', 'market_value'];
    private const EXPECTED_TRANSACTIONS_HEADERS = ['date', 'symbol', 'type', 'shares', 'price', 'amount'];

    public function validate(array $headers, array $sampleRow): bool {
        $firstHeader = array_map('strtolower', array_map('trim', $headers[0]));
        
        // Check if it's a holdings file
        $isHoldings = count(array_intersect(self::EXPECTED_HOLDINGS_HEADERS, $firstHeader)) >= count(self::EXPECTED_HOLDINGS_HEADERS);
        
        // Check if it's a transactions file
        $isTransactions = count(array_intersect(self::EXPECTED_TRANSACTIONS_HEADERS, $firstHeader)) >= count(self::EXPECTED_TRANSACTIONS_HEADERS);

        return $isHoldings || $isTransactions;
    }

    public function parse(string $filePath): array {
        $transactions = [];
        $file = fopen($filePath, 'r');
        if (!$file) {
            throw new Exception("Could not open file: {$filePath}");
        }

        $header = fgetcsv($file);
        $header = array_map('strtolower', array_map('trim', $header));

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) !== count($header) || empty(trim(implode('', $row)))) {
                continue; // Skip empty or malformed rows
            }
            
            $transactionData = array_combine($header, $row);
            $transactions[] = $this->transformToStandardFormat($transactionData);
        }

        fclose($file);
        return $transactions;
    }

    public function getHeaderRowSkipCount(string $filePath): int {
        // MidCap files have headers on the first row
        return 0;
    }

    private function transformToStandardFormat(array $data): array {
        $symbol = $this->getAliasedValue($data, ['symbol', 'ticker', 'stock_symbol']);
        $date = $this->getAliasedValue($data, ['date', 'transaction_date', 'txn_date'], date('Y-m-d'));
        $qty = $this->getAliasedValue($data, ['shares', 'quantity']);
        $price = $this->getAliasedValue($data, ['price', 'current_price']);
        $amount = $this->getAliasedValue($data, ['amount', 'total', 'market_value']);
        
        // If amount is missing, calculate it
        if (empty($amount) && !empty($qty) && !empty($price)) {
            $amount = (float)$qty * (float)$price;
        }

        return [
            'tran_date' => date('Y-m-d', strtotime($date)),
            'stock_symbol' => $symbol,
            'tran_type' => $this->getAliasedValue($data, ['type', 'transaction_type', 'txn_type'], 'Unknown'),
            'quantity' => (float) $qty,
            'price' => (float) $price,
            'amount' => (float) $amount,
            'description' => $this->getAliasedValue($data, ['description'], 'Imported transaction'),
        ];
    }

    private function getAliasedValue(array $row, array $aliases, $default = null) {
        foreach ($aliases as $alias) {
            if (isset($row[$alias])) {
                return $row[$alias];
            }
        }
        return $default;
    }
}
