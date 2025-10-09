<?php
/**
 * Transaction Parser Interface
 *
 * Defines the contract for all bank-specific transaction parsers.
 * Ensures that each parser can validate a file and return transactions
 * in a standardized format.
 */
interface TransactionParserInterface {
    /**
     * Validates if the file content matches the expected format for this parser.
     *
     * @param array $headers The header row(s) from the CSV file.
     * @param array $sampleRow A sample data row from the CSV file.
     * @return bool True if the format is valid, false otherwise.
     */
    public function validate(array $headers, array $sampleRow): bool;

    /**
     * Parses the given file content and returns an array of standardized transaction objects.
     *
     * @param string $filePath The path to the CSV file.
     * @return array An array of associative arrays, where each represents a transaction.
     *               Example:
     *               [
     *                   [
     *                       'tran_date' => '2025-09-02',
     *                       'stock_symbol' => 'BNS',
     *                       'tran_type' => 'Dividend',
     *                       'quantity' => 10,
     *                       'price' => 10.00,
     *                       'amount' => 100.00,
     *                       'description' => 'Description text'
     *                   ],
     *                   ...
     *               ]
     */
    public function parse(string $filePath): array;

    /**
     * Returns the number of header rows to skip.
     *
     * @param string $filePath The path to the CSV file.
     * @return int The number of rows to skip.
     */
    public function getHeaderRowSkipCount(string $filePath): int;
}
