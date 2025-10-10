<?php
require_once __DIR__ . '/TransactionParserInterface.php';
require_once __DIR__ . '/CibcParser.php';
require_once __DIR__ . '/MidCapParser.php';

/**
 * Parser Factory
 *
 * Factory class for creating and managing transaction parsers.
 * Follows Factory Pattern and provides auto-detection capabilities.
 *
 * @startuml ParserFactory
 * class ParserFactory {
 *   -parsers: TransactionParserInterface[]
 *   +__construct()
 *   +getAvailableParsers(): array
 *   +getParserByName(name: string): TransactionParserInterface
 *   +detectParser(csvLines: array): TransactionParserInterface|null
 *   +validateFile(csvLines: array, parserName: string): bool
 * }
 * 
 * ParserFactory --> TransactionParserInterface : manages
 * ParserFactory --> CibcParser : creates
 * ParserFactory --> MidCapParser : creates
 * @enduml
 */
class ParserFactory {
    
    /**
     * Available parsers
     * @var TransactionParserInterface[]
     */
    private array $parsers = [];

    /**
     * Constructor - Initialize available parsers
     */
    public function __construct() {
        $this->parsers = [
            'cibc' => new CibcParser(),
            'midcap' => new MidCapParser()
        ];
    }

    /**
     * Get all available parsers with their display names
     *
     * @return array Array of parser info [key => ['name' => 'Display Name', 'description' => '...']]
     */
    public function getAvailableParsers(): array {
        return [
            'cibc' => [
                'name' => 'CIBC Transaction History',
                'description' => 'CIBC bank CSV transaction files with multiple header rows',
                'parser' => $this->parsers['cibc']
            ],
            'midcap' => [
                'name' => 'MidCap Holdings/Transactions',
                'description' => 'Standard CSV format for holdings and transaction data',
                'parser' => $this->parsers['midcap']
            ]
        ];
    }

    /**
     * Get parser by name/key
     *
     * @param string $parserKey Parser key (e.g., 'cibc', 'midcap')
     * @return TransactionParserInterface|null Parser instance or null if not found
     */
    public function getParserByKey(string $parserKey): ?TransactionParserInterface {
        return $this->parsers[$parserKey] ?? null;
    }

    /**
     * Auto-detect parser for given CSV content
     *
     * @param array $csvLines Array of CSV lines
     * @return string|null Parser key that can handle the content, or null if none found
     */
    public function detectParser(array $csvLines): ?string {
        foreach ($this->parsers as $key => $parser) {
            if ($parser->canParse($csvLines)) {
                return $key;
            }
        }
        return null;
    }

    /**
     * Validate uploaded file
     *
     * @param array $uploadedFile $_FILES array entry for uploaded file
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateUploadedFile(array $uploadedFile): array {
        // Check for upload errors
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File is too large (exceeds upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE => 'File is too large (exceeds MAX_FILE_SIZE)',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            ];
            
            $message = $errors[$uploadedFile['error']] ?? 'Unknown upload error';
            return ['valid' => false, 'message' => $message];
        }
        
        // Check file size
        if ($uploadedFile['size'] === 0) {
            return ['valid' => false, 'message' => 'Uploaded file is empty'];
        }
        
        if ($uploadedFile['size'] > 10 * 1024 * 1024) { // 10MB limit
            return ['valid' => false, 'message' => 'File is too large (maximum 10MB)'];
        }
        
        // Check file extension
        $filename = $uploadedFile['name'];
        if (!preg_match('/\.csv$/i', $filename)) {
            return ['valid' => false, 'message' => 'Only CSV files are supported'];
        }
        
        return ['valid' => true, 'message' => 'File validation passed'];
    }

    /**
     * Validate CSV content against specific parser
     *
     * @param array $csvLines CSV content as array of lines
     * @param string $parserKey Parser key to validate against
     * @return bool True if parser can handle the content
     */
    public function validateFile(array $csvLines, string $parserKey): bool {
        $parser = $this->getParserByKey($parserKey);
        if (!$parser) {
            return false;
        }
        
        return $parser->canParse($csvLines);
    }

    /**
     * Parse CSV content using specified parser
     *
     * @param array $csvLines CSV content as array of lines
     * @param string $parserKey Parser key to use
     * @return array Parsed transactions in standardized format
     * @throws InvalidArgumentException If parser not found or cannot handle content
     */
    public function parseWithParser(array $csvLines, string $parserKey): array {
        $parser = $this->getParserByKey($parserKey);
        if (!$parser) {
            throw new InvalidArgumentException("Parser '{$parserKey}' not found");
        }
        
        if (!$parser->canParse($csvLines)) {
            throw new InvalidArgumentException("Parser '{$parserKey}' cannot handle this file format");
        }
        
        return $parser->parse($csvLines);
    }
}