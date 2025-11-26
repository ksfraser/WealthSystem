<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use App\Security\InputSanitizer;

/**
 * Test InputSanitizer (Symfony Validator-based)
 */
class InputSanitizerTest extends TestCase
{
    private InputSanitizer $sanitizer;
    
    protected function setUp(): void
    {
        $this->sanitizer = new InputSanitizer();
    }
    
    /**
     * Test string sanitization removes tags
     */
    public function testSanitizeStringRemovesTags(): void
    {
        $input = '<script>alert("xss")</script>Hello';
        $result = $this->sanitizer->sanitizeString($input);
        
        $this->assertEquals('Hello', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }
    
    /**
     * Test string sanitization escapes special characters
     */
    public function testSanitizeStringEscapesSpecialChars(): void
    {
        $input = '<>"\'&';
        $result = $this->sanitizer->sanitizeString($input);
        
        $this->assertEquals('&lt;&gt;&quot;&#039;&amp;', $result);
    }
    
    /**
     * Test string sanitization trims whitespace
     */
    public function testSanitizeStringTrimsWhitespace(): void
    {
        $input = '  Hello World  ';
        $result = $this->sanitizer->sanitizeString($input);
        
        $this->assertEquals('Hello World', $result);
    }
    
    /**
     * Test validate integer with valid input
     */
    public function testValidateIntWithValidInteger(): void
    {
        $result = $this->sanitizer->validateInt(123);
        
        $this->assertSame(123, $result);
    }
    
    /**
     * Test validate integer with string integer
     */
    public function testValidateIntWithStringInteger(): void
    {
        $result = $this->sanitizer->validateInt('456');
        
        $this->assertSame(456, $result);
    }
    
    /**
     * Test validate integer with invalid input
     */
    public function testValidateIntWithInvalidInput(): void
    {
        $this->assertNull($this->sanitizer->validateInt('abc'));
        $this->assertNull($this->sanitizer->validateInt('12.34'));
        $this->assertNull($this->sanitizer->validateInt(''));
    }
    
    /**
     * Test validate integer with zero
     */
    public function testValidateIntWithZero(): void
    {
        $result = $this->sanitizer->validateInt(0);
        
        $this->assertSame(0, $result);
    }
    
    /**
     * Test validate integer with negative numbers
     */
    public function testValidateIntWithNegativeNumber(): void
    {
        $result = $this->sanitizer->validateInt(-100);
        
        $this->assertSame(-100, $result);
    }
    
    /**
     * Test validate email with valid email
     */
    public function testValidateEmailWithValidEmail(): void
    {
        $result = $this->sanitizer->validateEmail('user@example.com');
        
        $this->assertEquals('user@example.com', $result);
    }
    
    /**
     * Test validate email with invalid emails
     */
    public function testValidateEmailWithInvalidEmails(): void
    {
        $this->assertNull($this->sanitizer->validateEmail('not-an-email'));
        $this->assertNull($this->sanitizer->validateEmail('missing@domain'));
        $this->assertNull($this->sanitizer->validateEmail('@example.com'));
        $this->assertNull($this->sanitizer->validateEmail('user@'));
    }
    
    /**
     * Test validate URL with valid URLs
     */
    public function testValidateUrlWithValidUrls(): void
    {
        $this->assertEquals('http://example.com', $this->sanitizer->validateUrl('http://example.com'));
        $this->assertEquals('https://example.com', $this->sanitizer->validateUrl('https://example.com'));
        $this->assertEquals('https://example.com/path', $this->sanitizer->validateUrl('https://example.com/path'));
    }
    
    /**
     * Test validate URL with invalid URLs
     */
    public function testValidateUrlWithInvalidUrls(): void
    {
        $this->assertNull($this->sanitizer->validateUrl('not a url'));
        $this->assertNull($this->sanitizer->validateUrl('javascript:alert(1)'));
        $this->assertNull($this->sanitizer->validateUrl('://example.com'));
    }
    
    /**
     * Test validate stock symbol with valid symbols
     */
    public function testValidateStockSymbolWithValidSymbols(): void
    {
        $this->assertEquals('AAPL', $this->sanitizer->validateStockSymbol('AAPL'));
        $this->assertEquals('MSFT', $this->sanitizer->validateStockSymbol('msft')); // Should uppercase
        $this->assertEquals('A', $this->sanitizer->validateStockSymbol('a'));
        $this->assertEquals('GOOGL', $this->sanitizer->validateStockSymbol('GOOGL'));
    }
    
    /**
     * Test validate stock symbol with invalid symbols
     */
    public function testValidateStockSymbolWithInvalidSymbols(): void
    {
        $this->assertNull($this->sanitizer->validateStockSymbol('TOOLONG')); // >5 chars
        $this->assertNull($this->sanitizer->validateStockSymbol('123')); // Numbers
        $this->assertNull($this->sanitizer->validateStockSymbol('AA-PL')); // Hyphen
        $this->assertNull($this->sanitizer->validateStockSymbol('AA PL')); // Space
        $this->assertNull($this->sanitizer->validateStockSymbol('')); // Empty
    }
    
    /**
     * Test validate stock symbol uppercases input
     */
    public function testValidateStockSymbolUppercasesInput(): void
    {
        $result = $this->sanitizer->validateStockSymbol('aapl');
        
        $this->assertEquals('AAPL', $result);
    }
    
    /**
     * Test validate date with valid dates
     */
    public function testValidateDateWithValidDates(): void
    {
        $this->assertEquals('2024-01-15', $this->sanitizer->validateDate('2024-01-15'));
        $this->assertEquals('2024-12-31', $this->sanitizer->validateDate('2024-12-31'));
    }
    
    /**
     * Test validate date with invalid dates
     */
    public function testValidateDateWithInvalidDates(): void
    {
        $this->assertNull($this->sanitizer->validateDate('2024-13-01')); // Invalid month
        $this->assertNull($this->sanitizer->validateDate('2024-01-32')); // Invalid day
        $this->assertNull($this->sanitizer->validateDate('not-a-date'));
        $this->assertNull($this->sanitizer->validateDate('01/15/2024')); // Wrong format
    }
    
    /**
     * Test sanitize filename removes dangerous characters
     */
    public function testSanitizeFilenameRemovesDangerousChars(): void
    {
        $result = $this->sanitizer->sanitizeFilename('../../../etc/passwd');
        
        $this->assertStringNotContainsString('..', $result);
        $this->assertStringNotContainsString('/', $result);
    }
    
    /**
     * Test sanitize filename removes path separators
     */
    public function testSanitizeFilenameRemovesPathSeparators(): void
    {
        $result = $this->sanitizer->sanitizeFilename('path/to/file.txt');
        
        $this->assertEquals('pathtofile.txt', $result);
        $this->assertStringNotContainsString('/', $result);
        $this->assertStringNotContainsString('\\', $result);
    }
    
    /**
     * Test sanitize filename keeps safe characters
     */
    public function testSanitizeFilenameKeepsSafeChars(): void
    {
        $result = $this->sanitizer->sanitizeFilename('my_file-123.txt');
        
        $this->assertEquals('my_file-123.txt', $result);
    }
    
    /**
     * Test sanitize filename replaces unsafe characters
     */
    public function testSanitizeFilenameReplacesUnsafeChars(): void
    {
        $result = $this->sanitizer->sanitizeFilename('my file!@#$.txt');
        
        $this->assertStringNotContainsString(' ', $result);
        $this->assertStringNotContainsString('!', $result);
        $this->assertStringNotContainsString('@', $result);
        $this->assertStringNotContainsString('#', $result);
    }
    
    /**
     * Test sanitize filename removes null bytes
     */
    public function testSanitizeFilenameRemovesNullBytes(): void
    {
        $result = $this->sanitizer->sanitizeFilename("file\0name.txt");
        
        $this->assertEquals('filename.txt', $result);
    }
    
    /**
     * Test XSS prevention in string sanitization
     */
    public function testXssPreventionInSanitization(): void
    {
        $xssAttempts = [
            '<img src=x onerror=alert(1)>',
            '<svg/onload=alert(1)>',
            'javascript:alert(1)',
            '<iframe src="javascript:alert(1)">',
            '<body onload=alert(1)>'
        ];
        
        foreach ($xssAttempts as $xss) {
            $result = $this->sanitizer->sanitizeString($xss);
            
            $this->assertStringNotContainsString('<script', strtolower($result));
            $this->assertStringNotContainsString('<img', strtolower($result));
            $this->assertStringNotContainsString('<svg', strtolower($result));
            $this->assertStringNotContainsString('<iframe', strtolower($result));
            $this->assertStringNotContainsString('javascript:', strtolower($result));
        }
    }
    
    /**
     * Test SQL injection patterns are handled
     */
    public function testSqlInjectionPatternsAreHandled(): void
    {
        $sqlInjection = "'; DROP TABLE users; --";
        $result = $this->sanitizer->sanitizeString($sqlInjection);
        
        // Should escape quotes
        $this->assertStringNotContainsString("'", $result);
    }
    
    /**
     * Test Unicode characters are preserved
     */
    public function testUnicodeCharactersArePreserved(): void
    {
        $unicode = 'Héllo Wörld 你好';
        $result = $this->sanitizer->sanitizeString($unicode);
        
        $this->assertEquals('Héllo Wörld 你好', $result);
    }
    
    /**
     * Test empty string handling
     */
    public function testEmptyStringHandling(): void
    {
        $result = $this->sanitizer->sanitizeString('');
        
        $this->assertEquals('', $result);
    }
    
    /**
     * Test very long strings
     */
    public function testVeryLongStrings(): void
    {
        $longString = str_repeat('a', 10000);
        $result = $this->sanitizer->sanitizeString($longString);
        
        $this->assertEquals($longString, $result);
        $this->assertEquals(10000, strlen($result));
    }
}
