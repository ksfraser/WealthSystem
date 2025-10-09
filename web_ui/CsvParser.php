<?php
namespace App;
require_once __DIR__ . '/CoreInterfaces.php';

use App\CsvParserInterface;
use App\LoggerInterface;

/**
 * Robust CSV parser with error handling and validation
 */
class CsvParser implements CsvParserInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function parse($filePath)
    {
        if (!file_exists($filePath)) {
            $this->logger->error('CSV file not found', ['file' => $filePath]);
            return [];
        }

        if (!is_readable($filePath)) {
            $this->logger->error('CSV file not readable', ['file' => $filePath]);
            return [];
        }

        $rows = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->logger->error('Failed to open CSV file', ['file' => $filePath]);
            return [];
        }

        try {
            $header = fgetcsv($handle, 0, ',', '"', '\\');
            if (!$header || empty($header)) {
                $this->logger->warning('CSV file has no header or empty header', ['file' => $filePath]);
                fclose($handle);
                return [];
            }

            // Normalize header
            $normalizedHeader = array_map([$this, 'normalizeHeader'], $header);

            $lineNumber = 1;
            while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $lineNumber++;
                if (count($data) !== count($normalizedHeader)) {
                    $this->logger->warning('CSV line has different column count than header', [
                        'file' => $filePath,
                        'line' => $lineNumber,
                        'expected' => count($normalizedHeader),
                        'actual' => count($data)
                    ]);
                    continue;
                }
                $rows[] = array_combine($normalizedHeader, $data);
            }
            fclose($handle);
        } catch (\Throwable $e) {
            $this->logger->error('Exception while parsing CSV', ['file' => $filePath, 'error' => $e->getMessage()]);
            fclose($handle);
            return [];
        }
        return $rows;
    }

    /**
     * Normalizes a header string to a consistent format.
     *
     * @param string $header The raw header string.
     * @return string The normalized header string.
     */
    private function normalizeHeader($header) {
        // Trim whitespace from the beginning and end of the string
        $header = trim($header);
        // Convert the entire string to lowercase
        $header = strtolower($header);
        // Replace any sequence of non-alphanumeric characters (except underscore) with a single underscore
        $header = preg_replace('/[^a-z0-9_]+/', '_', $header);
        // Remove any leading or trailing underscores that might result from the replacement
        $header = trim($header, '_');
        return $header;
    }

    public function write($filePath, array $data)
    {
        if (empty($data)) return false;
        $handle = fopen($filePath, 'w');
        if ($handle === false) return false;
        fputcsv($handle, array_keys($data[0]), ',', '"', '\\');
        foreach ($data as $row) {
            fputcsv($handle, $row, ',', '"', '\\');
        }
        fclose($handle);
        return true;
    }
}
