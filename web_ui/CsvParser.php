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
            $header = fgetcsv($handle);
            if (!$header || empty($header)) {
                $this->logger->warning('CSV file has no header or empty header', ['file' => $filePath]);
                fclose($handle);
                return [];
            }

            $lineNumber = 1;
            while (($data = fgetcsv($handle)) !== false) {
                $lineNumber++;
                if (count($data) !== count($header)) {
                    $this->logger->warning('CSV line has different column count than header', [
                        'file' => $filePath,
                        'line' => $lineNumber,
                        'expected' => count($header),
                        'actual' => count($data)
                    ]);
                    continue;
                }
                $rows[] = array_combine($header, $data);
            }
            fclose($handle);
        } catch (\Throwable $e) {
            $this->logger->error('Exception while parsing CSV', ['file' => $filePath, 'error' => $e->getMessage()]);
            fclose($handle);
            return [];
        }
        return $rows;
    }

    public function write($filePath, array $data)
    {
        if (empty($data)) return false;
        $handle = fopen($filePath, 'w');
        if ($handle === false) return false;
        fputcsv($handle, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
        return true;
    }
}
