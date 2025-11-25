<?php

require_once __DIR__ . '/CoreInterfaces.php';

/**
 * Secure file upload handler with validation
 */
class SecureFileUploadHandler
{
    private $allowedExtensions;
    private $maxFileSize;
    private $uploadDirectory;
    private $logger;

    public function __construct($uploadDirectory, LoggerInterface $logger, array $allowedExtensions = ['csv'], $maxFileSize = 5242880) // 5MB default
    {
        $this->uploadDirectory = rtrim($uploadDirectory, '/\\');
        $this->allowedExtensions = array_map('strtolower', $allowedExtensions);
        $this->maxFileSize = $maxFileSize;
        $this->logger = $logger;

        // Ensure upload directory exists
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }
    }

    public function handle(array $uploadedFile)
    {
        try {
            // Basic upload validation
            if (!isset($uploadedFile['tmp_name']) || !isset($uploadedFile['name'])) {
                return new UploadResult(false, null, 'Invalid file upload data');
            }

            if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
                return new UploadResult(false, null, 'File upload error: ' . $this->getUploadErrorMessage($uploadedFile['error']));
            }

            // Validate file size
            if ($uploadedFile['size'] > $this->maxFileSize) {
                return new UploadResult(false, null, 'File size exceeds maximum allowed size');
            }

            // Validate file extension
            $filename = $uploadedFile['name'];
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $this->allowedExtensions)) {
                return new UploadResult(false, null, 'File type not allowed. Allowed types: ' . implode(', ', $this->allowedExtensions));
            }

            // Additional security checks
            if (!$this->isValidFile($uploadedFile['tmp_name'], $extension)) {
                return new UploadResult(false, null, 'File failed security validation');
            }

            // Generate secure filename
            $secureFilename = $this->generateSecureFilename($filename);
            $targetPath = $this->uploadDirectory . DIRECTORY_SEPARATOR . $secureFilename;

            // Move uploaded file
            if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
                return new UploadResult(false, null, 'Failed to move uploaded file');
            }

            $this->logger->info('File uploaded successfully', [
                'original_name' => $filename,
                'secure_name' => $secureFilename,
                'size' => $uploadedFile['size']
            ]);

            return new UploadResult(true, $targetPath);

        } catch (Exception $e) {
            $this->logger->error('File upload exception', ['error' => $e->getMessage()]);
            return new UploadResult(false, null, 'Upload failed due to server error');
        }
    }

    private function generateSecureFilename($originalFilename)
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $basename = pathinfo($originalFilename, PATHINFO_FILENAME);
        
        // Sanitize basename
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        $basename = substr($basename, 0, 50); // Limit length
        
        // Add timestamp for uniqueness
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        
        return sprintf('%s_%s_%s.%s', $basename, $timestamp, $random, $extension);
    }

    private function isValidFile($filePath, $expectedExtension)
    {
        // Check if file exists and is readable
        if (!is_file($filePath) || !is_readable($filePath)) {
            return false;
        }

        // For CSV files, do basic validation
        if ($expectedExtension === 'csv') {
            return $this->isValidCsv($filePath);
        }

        return true;
    }

    private function isValidCsv($filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return false;
        }

        // Check first few lines for CSV format
        $lineCount = 0;
        $isValid = true;
        
        while (($line = fgetcsv($handle)) !== false && $lineCount < 3) {
            if ($lineCount === 0 && empty($line)) {
                $isValid = false;
                break;
            }
            $lineCount++;
        }

        fclose($handle);
        return $isValid;
    }

    private function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
}
