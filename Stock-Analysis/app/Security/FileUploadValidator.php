<?php

namespace App\Security;

/**
 * File Upload Validator
 * 
 * Provides secure file upload validation to prevent malicious files.
 */
class FileUploadValidator
{
    // Maximum file sizes (in bytes)
    private const MAX_SIZES = [
        'csv' => 5 * 1024 * 1024,      // 5MB
        'image' => 2 * 1024 * 1024,    // 2MB
        'document' => 10 * 1024 * 1024, // 10MB
        'default' => 1 * 1024 * 1024   // 1MB
    ];
    
    // Allowed MIME types by category
    private const ALLOWED_TYPES = [
        'csv' => [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel'
        ],
        'image' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]
    ];
    
    /**
     * Validate uploaded file
     * 
     * @param array $file The $_FILES array element
     * @param string $type File type category (csv, image, document)
     * @return array ['valid' => bool, 'error' => ?string, 'sanitized_name' => ?string]
     */
    public static function validate(array $file, string $type = 'csv'): array
    {
        // Check if file array has required keys
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return [
                'valid' => false,
                'error' => 'No file uploaded or file not uploaded via HTTP POST',
                'sanitized_name' => null
            ];
        }
        
        // Check for upload errors
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => self::getUploadErrorMessage($file['error'] ?? -1),
                'sanitized_name' => null
            ];
        }
        
        // Validate unknown file type category BEFORE size checks
        if (!isset(self::MAX_SIZES[$type]) || !isset(self::ALLOWED_TYPES[$type])) {
            return [
                'valid' => false,
                'error' => 'Unknown file type category',
                'sanitized_name' => null
            ];
        }
        
        // Validate empty file BEFORE size checks
        if (!isset($file['size']) || $file['size'] === 0) {
            return [
                'valid' => false,
                'error' => 'File is empty',
                'sanitized_name' => null
            ];
        }
        
        // Validate file size
        $maxSize = self::MAX_SIZES[$type];
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 1);
            return [
                'valid' => false,
                'error' => "File is too large. Maximum size: {$maxSizeMB}MB",
                'sanitized_name' => null
            ];
        }
        
        // Additional security checks BEFORE MIME type validation
        // This catches malicious files before MIME type mismatch
        $securityCheck = self::performSecurityChecks($file['tmp_name'], $type);
        if (!$securityCheck['valid']) {
            return $securityCheck;
        }
        
        // Validate MIME type
        $allowedTypes = self::ALLOWED_TYPES[$type];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes, true)) {
            return [
                'valid' => false,
                'error' => "Invalid file type. Allowed: " . implode(', ', $allowedTypes),
                'sanitized_name' => null
            ];
        }
        
        // Sanitize filename - use more aggressive approach
        $sanitizedName = self::sanitizeFilename($file['name']);
        
        return [
            'valid' => true,
            'error' => null,
            'sanitized_name' => $sanitizedName,
            'mime_type' => $mimeType,
            'size' => $file['size']
        ];
    }
    
    /**
     * Perform additional security checks on file content
     */
    private static function performSecurityChecks(string $tmpPath, string $type): array
    {
        // For CSV files, check for malicious content
        if ($type === 'csv') {
            $content = file_get_contents($tmpPath, false, null, 0, 1024); // First 1KB
            
            // Check for potential code execution
            $dangerousPatterns = [
                '/<\?php/i',
                '/<script/i',
                '/eval\s*\(/i',
                '/exec\s*\(/i',
                '/system\s*\(/i',
                '/passthru\s*\(/i'
            ];
            
            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    return [
                        'valid' => false,
                        'error' => 'File contains potentially malicious content',
                        'sanitized_name' => null
                    ];
                }
            }
        }
        
        // For images, validate image format
        if ($type === 'image') {
            $imageInfo = @getimagesize($tmpPath);
            if ($imageInfo === false) {
                return [
                    'valid' => false,
                    'error' => 'Invalid image file',
                    'sanitized_name' => null
                ];
            }
        }
        
        return ['valid' => true];
    }
    
    /**
     * Get human-readable upload error message
     */
    private static function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive in HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by PHP extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Move uploaded file to destination with security checks
     * 
     * @param string $tmpPath Temporary file path
     * @param string $destination Destination path
     * @return array ['success' => bool, 'error' => ?string, 'path' => ?string]
     */
    public static function moveUploadedFile(string $tmpPath, string $destination): array
    {
        // Validate temp file still exists
        if (!file_exists($tmpPath)) {
            return [
                'success' => false,
                'error' => 'Temporary file not found',
                'path' => null
            ];
        }
        
        // Validate destination directory exists
        $destDir = dirname($destination);
        if (!is_dir($destDir)) {
            if (!mkdir($destDir, 0755, true)) {
                return [
                    'success' => false,
                    'error' => 'Failed to create destination directory',
                    'path' => null
                ];
            }
        }
        
        // Validate destination is writable
        if (!is_writable($destDir)) {
            return [
                'success' => false,
                'error' => 'Destination directory is not writable',
                'path' => null
            ];
        }
        
        // Prevent overwriting existing files
        if (file_exists($destination)) {
            $destination = self::generateUniqueFilename($destination);
        }
        
        // Move file
        if (!move_uploaded_file($tmpPath, $destination)) {
            return [
                'success' => false,
                'error' => 'Failed to move uploaded file',
                'path' => null
            ];
        }
        
        // Set secure permissions
        chmod($destination, 0644);
        
        return [
            'success' => true,
            'error' => null,
            'path' => $destination
        ];
    }
    
    /**
     * Generate unique filename if file already exists
     */
    private static function generateUniqueFilename(string $path): string
    {
        $pathInfo = pathinfo($path);
        $dir = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? '';
        
        $counter = 1;
        while (file_exists($path)) {
            $newFilename = $filename . '_' . $counter;
            if ($extension) {
                $newFilename .= '.' . $extension;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $newFilename;
            $counter++;
        }
        
        return $path;
    }
    
    /**
     * Sanitize filename for secure storage
     * 
     * Removes dangerous characters and keywords that could be used in attacks.
     * More aggressive than InputSanitizer::sanitizeFilename().
     * 
     * @param string $filename Original filename
     * @return string Sanitized filename
     */
    private static function sanitizeFilename(string $filename): string
    {
        // Remove HTML tags FIRST (removes <script>, <img>, etc. INCLUDING content between tags)
        $filename = preg_replace('/<[^>]*>/', '', $filename);
        
        // Remove path separators and directory traversal
        $filename = str_replace(['/', '\\', '..', chr(0)], '', $filename);
        
        // Remove all characters except alphanumeric, dot, dash, underscore
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Remove dangerous keywords from filename (case-insensitive)
        // This catches keywords not in tags
        $dangerousKeywords = [
            'script', 'javascript', 'eval', 'exec', 'system',
            'passthru', 'shell', 'cmd', 'powershell', 'bash'
        ];
        
        foreach ($dangerousKeywords as $keyword) {
            $filename = preg_replace('/' . preg_quote($keyword, '/') . '/i', '', $filename);
        }
        
        // Remove consecutive underscores, dots, dashes
        $filename = preg_replace('/[_.-]+/', '_', $filename);
        
        // Ensure filename is not empty
        if (empty($filename) || $filename === '_') {
            $filename = 'upload_' . time();
        }
        
        return $filename;
    }
    
    /**
     * Validate multiple file uploads
     * 
     * @param array $files $_FILES array
     * @param string $type File type category
     * @param int $maxFiles Maximum number of files allowed
     * @return array ['valid' => bool, 'errors' => array, 'files' => array]
     */
    public static function validateMultiple(array $files, string $type = 'csv', int $maxFiles = 10): array
    {
        $results = [
            'valid' => true,
            'errors' => [],
            'files' => []
        ];
        
        $fileCount = count($files['name']);
        
        if ($fileCount > $maxFiles) {
            $results['valid'] = false;
            $results['errors'][] = "Too many files (max {$maxFiles})";
            return $results;
        }
        
        for ($i = 0; $i < $fileCount; $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $validation = self::validate($file, $type);
            
            if (!$validation['valid']) {
                $results['valid'] = false;
                $results['errors'][] = "File {$file['name']}: {$validation['error']}";
            } else {
                $results['files'][] = $validation;
            }
        }
        
        return $results;
    }
}
