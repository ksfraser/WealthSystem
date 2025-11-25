<?php

use RuntimeException;

/**
 * CSV File Reader
 *
 * Handles file I/O operations for CSV files.
 * Follows Single Responsibility Principle (SRP) - only responsible for reading files.
 * Supports Dependency Injection by allowing custom file handlers.
 *
 * @startuml CsvFileReader
 * class CsvFileReader {
 *   -fileHandler: FileHandlerInterface
 *   +__construct(fileHandler?: FileHandlerInterface)
 *   +readCsvLines(filePath: string): array
 *   +readCsvFromString(content: string): array
 * }
 * 
 * interface FileHandlerInterface {
 *   +readFile(path: string): string
 *   +fileExists(path: string): bool
 * }
 * 
 * class DefaultFileHandler implements FileHandlerInterface {
 *   +readFile(path: string): string
 *   +fileExists(path: string): bool
 * }
 * 
 * CsvFileReader --> FileHandlerInterface : uses
 * DefaultFileHandler ..|> FileHandlerInterface
 * @enduml
 */

/**
 * File Handler Interface for Dependency Injection
 */
interface FileHandlerInterface {
    /**
     * Read file contents
     * 
     * @param string $path File path
     * @return string File contents
     * @throws RuntimeException If file cannot be read
     */
    public function readFile(string $path): string;

    /**
     * Check if file exists
     * 
     * @param string $path File path
     * @return bool True if file exists
     */
    public function fileExists(string $path): bool;
}

/**
 * Default File Handler Implementation
 */
class DefaultFileHandler implements FileHandlerInterface {
    /**
     * {@inheritdoc}
     */
    public function readFile(string $path): string {
        if (!$this->fileExists($path)) {
            throw new RuntimeException("File not found: {$path}");
        }
        
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Could not read file: {$path}");
        }
        
        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function fileExists(string $path): bool {
        return file_exists($path) && is_readable($path);
    }
}

/**
 * CSV File Reader
 * 
 * Responsible for reading CSV files and converting them to arrays of lines.
 * Each line is parsed as an array of columns.
 */
class CsvFileReader {
    private FileHandlerInterface $fileHandler;

    /**
     * Constructor with Dependency Injection
     * 
     * @param FileHandlerInterface|null $fileHandler Optional custom file handler
     */
    public function __construct(?FileHandlerInterface $fileHandler = null) {
        $this->fileHandler = $fileHandler ?? new DefaultFileHandler();
    }

    /**
     * Read CSV file and return array of parsed lines
     * 
     * @param string $filePath Path to CSV file
     * @return array Array where each element is an array of CSV columns
     * @throws RuntimeException If file cannot be read or parsed
     */
    public function readCsvLines(string $filePath): array {
        $content = $this->fileHandler->readFile($filePath);
        return $this->readCsvFromString($content);
    }

    /**
     * Parse CSV content from string
     * 
     * @param string $content CSV content as string
     * @return array Array where each element is an array of CSV columns
     */
    public function readCsvFromString(string $content): array {
        $lines = [];
        $rows = str_getcsv($content, "\n");
        
        foreach ($rows as $row) {
            if (trim($row) !== '') {
                $lines[] = str_getcsv($row);
            }
        }
        
        return $lines;
    }
}