<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../web_ui/parsers/CibcParser.php';
require_once __DIR__ . '/../../web_ui/parsers/MidCapParser.php';
require_once __DIR__ . '/../../web_ui/parsers/CsvFileReader.php';

/**
 * Unit tests for Transaction Parsers
 *
 * Tests follow Test-Driven Development (TDD) principles.
 * Each parser is tested for validation, parsing correctness, and edge cases.
 */
class ParserTest extends TestCase {

    private CsvFileReader $csvReader;

    protected function setUp(): void {
        $this->csvReader = new CsvFileReader();
    }

    /**
     * Test CIBC Parser with valid transaction data
     *
     * @covers CibcParser::canParse
     * @covers CibcParser::parse
     * @covers CibcParser::getParserName
     */
    public function testCibcParser_ValidTransactions(): void {
        $parser = new CibcParser();
        
        // Create test CSV lines with CIBC format (including header rows)
        $csvLines = [
            ['59154498 RRSP', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['KEVIN FRASER', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['September 04, 2025 3:34 PM ET', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['From', 'July 22, 2025', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['To', 'September 04, 2025', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Transaction Date', 'Settlement Date', 'Currency of Sub-account Held In', 'Transaction Type', 'Symbol', 'Market', 'Description', 'Quantity', 'Currency of Price', 'Price', 'Commission', 'Exchange Rate', 'Currency of Amount', 'Amount', 'Settlement Instruction', 'Exchange Rate (Canadian Equivalent)', 'Canadian Equivalent'],
            [' September 2, 2025', ' September 2, 2025', 'CAD', 'Dividend', '', '', '1000THS SCOTIA MONEY MARKET FD SR F (557)', '10', '', '0.00', '', '', 'CAD', '0.00', '', ''],
            [' August 29, 2025', ' August 29, 2025', 'CAD', 'Dividend', 'CDZ', 'CDN', 'ISHARES S&P/TSX CANADIAN DIVIDEND ARISTOCRATS INDEX ETF', '0', '', '0.117', '', '', 'CAD', '11.70', '', ''],
            [' August 14, 2025', ' August 14, 2025', 'CAD', 'Merger', '', 'CDN', 'KEG ROYALTIES INCOME FUND TRUST UNITS CASH FOR MERGED SHRS', '750', '', '0.00', '', '', 'CAD', '13,950.00', '', '']
        ];

        // Test canParse
        $this->assertTrue($parser->canParse($csvLines));
        $this->assertEquals('CIBC Transaction Parser', $parser->getParserName());

        // Test parsing
        $transactions = $parser->parse($csvLines);
        $this->assertCount(3, $transactions);

        // Validate first transaction (Scotia Money Market)
        $this->assertEquals('2025-09-02', $transactions[0]['tran_date']);
        $this->assertEquals('', $transactions[0]['stock_symbol']); // No symbol in this case
        $this->assertEquals('Dividend', $transactions[0]['tran_type']);
        $this->assertEquals(10.0, $transactions[0]['quantity']);
        $this->assertEquals(0.00, $transactions[0]['amount']);

        // Validate second transaction (CDZ)
        $this->assertEquals('2025-08-29', $transactions[1]['tran_date']);
        $this->assertEquals('CDZ', $transactions[1]['stock_symbol']);
        $this->assertEquals('Dividend', $transactions[1]['tran_type']);
        $this->assertEquals(0.0, $transactions[1]['quantity']);
        $this->assertEquals(11.70, $transactions[1]['amount']);
        
        // Validate third transaction (KEG merger with symbol extraction)
        $this->assertEquals('2025-08-14', $transactions[2]['tran_date']);
        $this->assertEquals('KEG.UN', $transactions[2]['stock_symbol']); // Extracted from description
        $this->assertEquals('Merger', $transactions[2]['tran_type']);
        $this->assertEquals(750.0, $transactions[2]['quantity']);
        $this->assertEquals(13950.00, $transactions[2]['amount']);
    }

    /**
     * Test CIBC Parser with invalid data
     *
     * @covers CibcParser::canParse
     */
    public function testCibcParser_InvalidData(): void {
        $parser = new CibcParser();
        
        // Test with non-CIBC format
        $invalidLines = [
            ['symbol', 'shares', 'price', 'market_value'],
            ['AAPL', '100', '150.00', '15000.00']
        ];
        
        $this->assertFalse($parser->canParse($invalidLines));
        
        // Test with empty data
        $this->assertFalse($parser->canParse([]));
    }

    /**
     * Test MidCap Parser with valid holdings data
     *
     * @covers MidCapParser::canParse
     * @covers MidCapParser::parse
     * @covers MidCapParser::getParserName
     */
    public function testMidCapParser_ValidHoldings(): void {
        $parser = new MidCapParser();
        
        // Create test CSV lines with MidCap holdings format
        $csvLines = [
            ['symbol', 'shares', 'price', 'market_value'],
            ['AAPL', '100', '150.00', '15000.00'],
            ['GOOG', '50', '2800.00', '140000.00'],
            ['MSFT', '75', '300.00', '22500.00']
        ];

        // Test canParse
        $this->assertTrue($parser->canParse($csvLines));
        $this->assertEquals('MidCap Transaction/Holdings Parser', $parser->getParserName());

        // Test parsing
        $transactions = $parser->parse($csvLines);
        $this->assertCount(3, $transactions);

        // Validate first holding
        $this->assertEquals(date('Y-m-d'), $transactions[0]['tran_date']);
        $this->assertEquals('AAPL', $transactions[0]['stock_symbol']);
        $this->assertEquals('Holdings', $transactions[0]['tran_type']);
        $this->assertEquals(100.0, $transactions[0]['quantity']);
        $this->assertEquals(150.0, $transactions[0]['price']);
        $this->assertEquals(15000.0, $transactions[0]['amount']);
    }

    /**
     * Test MidCap Parser with transactions data
     *
     * @covers MidCapParser::canParse
     * @covers MidCapParser::parse
     */
    public function testMidCapParser_ValidTransactions(): void {
        $parser = new MidCapParser();
        
        // Create test CSV lines with MidCap transaction format
        $csvLines = [
            ['date', 'symbol', 'type', 'shares', 'price', 'amount'],
            ['2025-09-01', 'AAPL', 'BUY', '100', '150.00', '15000.00'],
            ['2025-09-02', 'GOOG', 'SELL', '25', '2800.00', '70000.00']
        ];

        $this->assertTrue($parser->canParse($csvLines));

        $transactions = $parser->parse($csvLines);
        $this->assertCount(2, $transactions);

        // Validate first transaction
        $this->assertEquals('2025-09-01', $transactions[0]['tran_date']);
        $this->assertEquals('AAPL', $transactions[0]['stock_symbol']);
        $this->assertEquals('BUY', $transactions[0]['tran_type']);
        $this->assertEquals(100.0, $transactions[0]['quantity']);
        $this->assertEquals(15000.0, $transactions[0]['amount']);
    }

    /**
     * Test MidCap Parser with invalid data
     *
     * @covers MidCapParser::canParse
     */
    public function testMidCapParser_InvalidData(): void {
        $parser = new MidCapParser();
        
        // Test with CIBC format (should fail)
        $cibcLines = [
            ['Transaction Date', 'Settlement Date', 'Transaction Type', 'Symbol'],
            [' September 2, 2025', ' September 2, 2025', 'Dividend', 'CDZ']
        ];
        
        $this->assertFalse($parser->canParse($cibcLines));
        
        // Test with empty data
        $this->assertFalse($parser->canParse([]));
        
        // Test with insufficient headers
        $invalidLines = [
            ['symbol', 'price'], // Missing required headers
            ['AAPL', '150.00']
        ];
        
        $this->assertFalse($parser->canParse($invalidLines));
    }

    /**
     * Test CSV File Reader functionality
     *
     * @covers CsvFileReader::readCsvFromString
     */
    public function testCsvFileReader_ParseString(): void {
        $csvContent = "symbol,shares,price,market_value\nAAPL,100,150.00,15000.00\nGOOG,50,2800.00,140000.00";
        
        $lines = $this->csvReader->readCsvFromString($csvContent);
        
        $this->assertCount(3, $lines);
        $this->assertEquals(['symbol', 'shares', 'price', 'market_value'], $lines[0]);
        $this->assertEquals(['AAPL', '100', '150.00', '15000.00'], $lines[1]);
        $this->assertEquals(['GOOG', '50', '2800.00', '140000.00'], $lines[2]);
    }

    /**
     * Test error handling for invalid parser input
     *
     * @covers CibcParser::parse
     * @covers MidCapParser::parse
     */
    public function testParsers_InvalidInput(): void {
        $cibcParser = new CibcParser();
        $midCapParser = new MidCapParser();
        
        // Test with wrong format
        $wrongFormat = [['wrong', 'format']];
        
        $this->expectException(InvalidArgumentException::class);
        $cibcParser->parse($wrongFormat);
        
        $this->expectException(InvalidArgumentException::class);
        $midCapParser->parse($wrongFormat);
    }
}
