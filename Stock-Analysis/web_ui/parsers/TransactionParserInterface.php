<?php
/**
 * Transaction Parser Interface
 *
 * Defines the contract for all bank-specific transaction parsers.
 * Follows Single Responsibility Principle (SRP) - parsers only parse content,
 * they don't handle file I/O operations.
 *
 * @startuml TransactionParserInterface
 * interface TransactionParserInterface {
 *   +canParse(lines: array): bool
 *   +parse(lines: array): StandardTransaction[]
 *   +getParserName(): string
 * }
 * 
 * class StandardTransaction {
 *   +tran_date: string
 *   +stock_symbol: string
 *   +tran_type: string
 *   +quantity: float
 *   +price: float
 *   +amount: float
 *   +description: string
 * }
 * 
 * TransactionParserInterface ..> StandardTransaction : creates
 * @enduml
 */
interface TransactionParserInterface {
    /**
     * Validates if the CSV content matches the expected format for this parser.
     *
     * @param array $lines Array of CSV lines (each line is an array of columns)
     * @return bool True if this parser can handle the format, false otherwise
     */
    public function canParse(array $lines): bool;

    /**
     * Parses the given CSV content and returns standardized transaction objects.
     *
     * @param array $lines Array of CSV lines (each line is an array of columns)
     * @return array Array of standardized transaction arrays
     * @throws InvalidArgumentException If the format is not supported by this parser
     * 
     * @example
     * [
     *     [
     *         'tran_date' => '2025-09-02',
     *         'stock_symbol' => 'BNS',
     *         'tran_type' => 'Dividend',
     *         'quantity' => 10.0,
     *         'price' => 10.00,
     *         'amount' => 100.00,
     *         'description' => 'Dividend payment'
     *     ]
     * ]
     */
    public function parse(array $lines): array;

    /**
     * Returns a human-readable name for this parser.
     *
     * @return string Parser identification name
     */
    public function getParserName(): string;
}
