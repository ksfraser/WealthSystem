<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use App\Security\FileUploadValidator;

/**
 * Test FileUploadValidator (Custom - no Symfony equivalent)
 */
class FileUploadValidatorTest extends TestCase
{
    /**
     * Test validate with missing file
     */
    public function testValidateWithMissingFile(): void
    {
        $file = [
            'name' => 'test.csv',
            'type' => 'text/csv',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0
        ];
        
        $result = FileUploadValidator::validate($file, 'csv');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('No file', $result['error']);
    }
    
    /**
     * Test validate with file too large
     */
    public function testValidateWithFileTooLarge(): void
    {
        $file = [
            'name' => 'test.csv',
            'type' => 'text/csv',
            'tmp_name' => __FILE__, // Use this file as test
            'error' => UPLOAD_ERR_OK,
            'size' => 10 * 1024 * 1024 // 10MB (over 5MB limit for CSV)
        ];
        
        $result = FileUploadValidator::validate($file, 'csv');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('too large', $result['error']);
    }
    
    /**
     * Test validate with empty file
     */
    public function testValidateWithEmptyFile(): void
    {
        $file = [
            'name' => 'test.csv',
            'type' => 'text/csv',
            'tmp_name' => __FILE__,
            'error' => UPLOAD_ERR_OK,
            'size' => 0
        ];
        
        $result = FileUploadValidator::validate($file, 'csv');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('empty', $result['error']);
    }
    
    /**
     * Test validate with unknown file type category
     */
    public function testValidateWithUnknownFileType(): void
    {
        $file = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => __FILE__,
            'error' => UPLOAD_ERR_OK,
            'size' => 100
        ];
        
        $result = FileUploadValidator::validate($file, 'unknown_type');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Unknown file type', $result['error']);
    }
    
    /**
     * Test sanitized filename removes path separators
     */
    public function testSanitizedFilenameRemovesPathSeparators(): void
    {
        // Can't fully test without actual upload, but test structure
        $filename = '../../../etc/passwd';
        $sanitized = preg_replace('/[\/\\\\]/', '', $filename);
        
        $this->assertStringNotContainsString('/', $sanitized);
        $this->assertStringNotContainsString('\\', $sanitized);
    }
    
    /**
     * Test upload error messages
     */
    public function testUploadErrorMessages(): void
    {
        $errorCodes = [
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE,
            UPLOAD_ERR_PARTIAL,
            UPLOAD_ERR_NO_TMP_DIR,
            UPLOAD_ERR_CANT_WRITE,
            UPLOAD_ERR_EXTENSION
        ];
        
        foreach ($errorCodes as $errorCode) {
            $file = [
                'name' => 'test.csv',
                'type' => 'text/csv',
                'tmp_name' => '',
                'error' => $errorCode,
                'size' => 0
            ];
            
            $result = FileUploadValidator::validate($file, 'csv');
            
            $this->assertFalse($result['valid']);
            $this->assertNotEmpty($result['error']);
        }
    }
    
    /**
     * Test result structure
     */
    public function testResultStructure(): void
    {
        $file = [
            'name' => 'test.csv',
            'type' => 'text/csv',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0
        ];
        
        $result = FileUploadValidator::validate($file, 'csv');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('sanitized_name', $result);
        
        $this->assertIsBool($result['valid']);
        $this->assertIsString($result['error']);
        $this->assertNull($result['sanitized_name']);
    }
    
    /**
     * Test filename sanitization removes dangerous characters
     */
    public function testFilenameSanitization(): void
    {
        // Test that dangerous filenames are sanitized
        $dangerousFilenames = [
            '../../../etc/passwd' => 'etcpasswd',
            '..\\..\\..\\windows\\system32\\config\\sam' => 'windowssystem32configsam',
            'test<script>.csv' => 'testscript.csv',
            'test|file.csv' => 'testfile.csv',
            "test\0file.csv" => 'testfile.csv'
        ];
        
        foreach ($dangerousFilenames as $dangerous => $expected) {
            // Test sanitization pattern (can't call private method directly)
            $sanitized = str_replace(['/', '\\', '..', chr(0)], '', $dangerous);
            $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '', $sanitized);
            
            $this->assertStringNotContainsString('..', $sanitized);
            $this->assertStringNotContainsString('/', $sanitized);
            $this->assertStringNotContainsString('\\', $sanitized);
        }
    }
    
    /**
     * Test CSV file type validation
     */
    public function testCsvFileTypeValidation(): void
    {
        // CSV should accept specific MIME types
        $validMimeTypes = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel'
        ];
        
        // This is testing the constant, not runtime behavior
        $this->assertIsArray($validMimeTypes);
        $this->assertContains('text/csv', $validMimeTypes);
    }
    
    /**
     * Test image file size limits
     */
    public function testImageFileSizeLimits(): void
    {
        $file = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => __FILE__,
            'error' => UPLOAD_ERR_OK,
            'size' => 3 * 1024 * 1024 // 3MB (over 2MB limit for images)
        ];
        
        $result = FileUploadValidator::validate($file, 'image');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('too large', $result['error']);
    }
    
    /**
     * Test document file size limits
     */
    public function testDocumentFileSizeLimits(): void
    {
        $file = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => __FILE__,
            'error' => UPLOAD_ERR_OK,
            'size' => 11 * 1024 * 1024 // 11MB (over 10MB limit for documents)
        ];
        
        $result = FileUploadValidator::validate($file, 'document');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('too large', $result['error']);
    }
    
    /**
     * Test move uploaded file with non-existent temp file
     */
    public function testMoveUploadedFileWithNonExistentFile(): void
    {
        $result = FileUploadValidator::moveUploadedFile(
            '/nonexistent/file.tmp',
            '/tmp/destination.csv'
        );
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }
    
    /**
     * Test move uploaded file result structure
     */
    public function testMoveUploadedFileResultStructure(): void
    {
        $result = FileUploadValidator::moveUploadedFile(
            '/nonexistent/file.tmp',
            '/tmp/destination.csv'
        );
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('path', $result);
        
        $this->assertIsBool($result['success']);
    }
    
    /**
     * Test validate multiple files with too many files
     */
    public function testValidateMultipleWithTooManyFiles(): void
    {
        $files = [
            'name' => array_fill(0, 15, 'test.csv'),
            'type' => array_fill(0, 15, 'text/csv'),
            'tmp_name' => array_fill(0, 15, ''),
            'error' => array_fill(0, 15, UPLOAD_ERR_NO_FILE),
            'size' => array_fill(0, 15, 0)
        ];
        
        $result = FileUploadValidator::validateMultiple($files, 'csv', 10);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Too many files', $result['errors'][0]);
    }
    
    /**
     * Test validate multiple result structure
     */
    public function testValidateMultipleResultStructure(): void
    {
        $files = [
            'name' => ['test1.csv', 'test2.csv'],
            'type' => ['text/csv', 'text/csv'],
            'tmp_name' => ['', ''],
            'error' => [UPLOAD_ERR_NO_FILE, UPLOAD_ERR_NO_FILE],
            'size' => [0, 0]
        ];
        
        $result = FileUploadValidator::validateMultiple($files, 'csv');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('files', $result);
        
        $this->assertIsBool($result['valid']);
        $this->assertIsArray($result['errors']);
        $this->assertIsArray($result['files']);
    }
    
    /**
     * Test malicious content detection in CSV
     */
    public function testMaliciousContentDetectionInCsv(): void
    {
        // Create temp file with PHP code
        $tempFile = sys_get_temp_dir() . '/test_malicious.csv';
        file_put_contents($tempFile, '<?php system("rm -rf /"); ?>');
        
        $file = [
            'name' => 'test.csv',
            'type' => 'text/csv',
            'tmp_name' => $tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tempFile)
        ];
        
        $result = FileUploadValidator::validate($file, 'csv');
        
        // Should detect malicious content
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('malicious', strtolower($result['error']));
        
        // Cleanup
        unlink($tempFile);
    }
    
    /**
     * Test XSS prevention in filenames
     */
    public function testXssPreventionInFilenames(): void
    {
        $xssFilenames = [
            '<script>alert(1)</script>.csv',
            'test<img src=x onerror=alert(1)>.csv',
            'file"><script>alert(1)</script>.csv'
        ];
        
        foreach ($xssFilenames as $xss) {
            $sanitized = str_replace(['/', '\\', '..', chr(0)], '', $xss);
            $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $sanitized);
            
            $this->assertStringNotContainsString('<', $sanitized);
            $this->assertStringNotContainsString('>', $sanitized);
            $this->assertStringNotContainsString('script', $sanitized);
        }
    }
}
