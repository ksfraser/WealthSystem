<?php

namespace App\Security;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input Sanitization Service using Symfony Validator
 * 
 * Provides comprehensive input validation and sanitization using Symfony's
 * Validator component. Prevents XSS, SQL injection, path traversal, and
 * other common web vulnerabilities.
 * 
 * @package App\Security
 */
class InputSanitizer
{
    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    private $validator;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->validator = Validation::createValidator();
    }
    
    /**
     * Sanitize string input
     * 
     * Removes HTML tags AND their content, escapes special characters, and trims whitespace.
     * Preserves Unicode characters.
     * 
     * @param string $input The input string to sanitize
     * @return string The sanitized string
     * 
     * @example
     * ```php
     * $sanitizer = new InputSanitizer();
     * $clean = $sanitizer->sanitizeString($_POST['comment']);
     * ```
     */
    public function sanitizeString(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Remove script tags AND their content (security critical)
        $input = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $input);
        
        // Remove other dangerous tags and their content
        $input = preg_replace('/<(iframe|object|embed|applet|meta|link|style)\b[^>]*>(.*?)<\/\1>/is', '', $input);
        
        // Remove javascript: protocol
        $input = preg_replace('/javascript:/i', '', $input);
        
        // Escape special HTML characters FIRST (before strip_tags removes lone < >)
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        
        // Now strip remaining HTML tags (they're now escaped)
        // Only remove escaped tags that look like real tags (with alphanumeric content)
        // This preserves standalone &lt;&gt; but removes &lt;b&gt;, &lt;div&gt;, etc.
        $input = preg_replace('/&lt;([a-z][a-z0-9]*)\b[^&]*&gt;/i', '', $input);
        $input = preg_replace('/&lt;\/([a-z][a-z0-9]*)&gt;/i', '', $input);
        
        return $input;
    }
    
    /**
     * Validate and sanitize integer input
     * 
     * Converts string integers to int, validates the value.
     * 
     * @param mixed $input The input to validate as integer
     * @return int|null The validated integer or null if invalid
     * 
     * @example
     * ```php
     * $userId = $sanitizer->validateInt($_GET['id']);
     * if ($userId === null) {
     *     throw new \InvalidArgumentException('Invalid user ID');
     * }
     * ```
     */
    public function validateInt($input): ?int
    {
        // Handle actual integers
        if (is_int($input)) {
            return $input;
        }
        
        // Handle string integers
        if (is_string($input) && $input !== '') {
            // Check if it's a valid integer string (including negatives)
            if (preg_match('/^-?\d+$/', $input)) {
                return (int) $input;
            }
        }
        
        return null;
    }
    
    /**
     * Validate email address
     * 
     * Uses Symfony Validator to check email format.
     * 
     * @param string $input The email to validate
     * @return string|null The validated email or null if invalid
     * 
     * @example
     * ```php
     * $email = $sanitizer->validateEmail($_POST['email']);
     * if ($email === null) {
     *     $errors[] = 'Invalid email address';
     * }
     * ```
     */
    public function validateEmail(string $input): ?string
    {
        $violations = $this->validator->validate($input, [
            new Assert\Email(),
            new Assert\NotBlank(),
        ]);
        
        return count($violations) === 0 ? $input : null;
    }
    
    /**
     * Validate URL
     * 
     * Validates URL format and protocol. Only allows http and https.
     * 
     * @param string $input The URL to validate
     * @return string|null The validated URL or null if invalid
     * 
     * @example
     * ```php
     * $url = $sanitizer->validateUrl($_POST['website']);
     * if ($url === null) {
     *     $errors[] = 'Invalid URL';
     * }
     * ```
     */
    public function validateUrl(string $input): ?string
    {
        $violations = $this->validator->validate($input, [
            new Assert\Url(protocols: ['http', 'https']),
            new Assert\NotBlank(),
        ]);
        
        return count($violations) === 0 ? $input : null;
    }
    
    /**
     * Validate stock symbol
     * 
     * Validates stock ticker symbols (1-5 uppercase letters only).
     * Automatically uppercases the input.
     * 
     * @param string $input The stock symbol to validate
     * @return string|null The validated symbol (uppercased) or null if invalid
     * 
     * @example
     * ```php
     * $symbol = $sanitizer->validateStockSymbol($_POST['ticker']);
     * if ($symbol === null) {
     *     $errors[] = 'Invalid stock symbol';
     * }
     * // $symbol will be 'AAPL' even if input was 'aapl'
     * ```
     */
    public function validateStockSymbol(string $input): ?string
    {
        // Uppercase the input first
        $input = strtoupper(trim($input));
        
        // Validate: 1-5 uppercase letters only
        $violations = $this->validator->validate($input, [
            new Assert\NotBlank(),
            new Assert\Length(min: 1, max: 5),
            new Assert\Regex(pattern: '/^[A-Z]+$/'),
        ]);
        
        return count($violations) === 0 ? $input : null;
    }
    
    /**
     * Validate date in YYYY-MM-DD format
     * 
     * Validates date format and checks if it's a real date.
     * 
     * @param string $input The date to validate
     * @return string|null The validated date or null if invalid
     * 
     * @example
     * ```php
     * $date = $sanitizer->validateDate($_POST['start_date']);
     * if ($date === null) {
     *     $errors[] = 'Invalid date format. Use YYYY-MM-DD';
     * }
     * ```
     */
    public function validateDate(string $input): ?string
    {
        // Check format first
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
            return null;
        }
        
        // Parse and validate the date
        $parts = explode('-', $input);
        $year = (int) $parts[0];
        $month = (int) $parts[1];
        $day = (int) $parts[2];
        
        // checkdate validates the actual date (e.g., no Feb 31)
        if (!checkdate($month, $day, $year)) {
            return null;
        }
        
        return $input;
    }
    
    /**
     * Sanitize filename
     * 
     * Removes path traversal attempts, directory separators, and unsafe characters.
     * Prevents directory traversal attacks.
     * 
     * @param string $input The filename to sanitize
     * @return string The sanitized filename
     * 
     * @example
     * ```php
     * $filename = $sanitizer->sanitizeFilename($_FILES['upload']['name']);
     * // '../../../etc/passwd' becomes 'etcpasswd'
     * ```
     */
    public function sanitizeFilename(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Remove path traversal attempts
        $input = str_replace(['../', '..\\', '..'], '', $input);
        
        // Remove directory separators
        $input = str_replace(['/', '\\'], '', $input);
        
        // Remove dangerous characters but keep alphanumeric, dash, underscore, dot
        $input = preg_replace('/[^a-zA-Z0-9._-]/', '', $input);
        
        return $input;
    }
    
    /**
     * Validate and sanitize array of values
     * 
     * Applies sanitization to each element in an array.
     * 
     * @param array<mixed> $input The array to sanitize
     * @param callable $sanitizerCallback The sanitizer function to apply
     * @return array<mixed> The sanitized array
     * 
     * @example
     * ```php
     * $cleanTags = $sanitizer->sanitizeArray($_POST['tags'], [$sanitizer, 'sanitizeString']);
     * ```
     */
    public function sanitizeArray(array $input, callable $sanitizerCallback): array
    {
        return array_map($sanitizerCallback, $input);
    }
    
    /**
     * Validate float/decimal number
     * 
     * @param mixed $input The input to validate as float
     * @return float|null The validated float or null if invalid
     * 
     * @example
     * ```php
     * $price = $sanitizer->validateFloat($_POST['price']);
     * ```
     */
    public function validateFloat($input): ?float
    {
        if (is_float($input)) {
            return $input;
        }
        
        if (is_int($input)) {
            return (float) $input;
        }
        
        if (is_string($input) && is_numeric($input)) {
            return (float) $input;
        }
        
        return null;
    }
    
    /**
     * Validate boolean value
     * 
     * Accepts: true, false, 1, 0, "1", "0", "true", "false", "yes", "no"
     * 
     * @param mixed $input The input to validate as boolean
     * @return bool|null The validated boolean or null if invalid
     * 
     * @example
     * ```php
     * $isActive = $sanitizer->validateBool($_POST['active']);
     * ```
     */
    public function validateBool($input): ?bool
    {
        if (is_bool($input)) {
            return $input;
        }
        
        if ($input === 1 || $input === '1' || strtolower((string)$input) === 'true' || strtolower((string)$input) === 'yes') {
            return true;
        }
        
        if ($input === 0 || $input === '0' || strtolower((string)$input) === 'false' || strtolower((string)$input) === 'no') {
            return false;
        }
        
        return null;
    }
    
    /**
     * Sanitize for SQL LIKE pattern
     * 
     * Escapes SQL LIKE wildcards (%, _) for safe use in LIKE queries.
     * Note: Still use prepared statements!
     * 
     * @param string $input The search term
     * @return string The escaped search term
     * 
     * @example
     * ```php
     * $search = $sanitizer->sanitizeLikePattern($_GET['search']);
     * $stmt = $pdo->prepare("SELECT * FROM stocks WHERE name LIKE :search");
     * $stmt->execute(['search' => "%{$search}%"]);
     * ```
     */
    public function sanitizeLikePattern(string $input): string
    {
        // Escape SQL LIKE special characters
        $input = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $input);
        
        return $this->sanitizeString($input);
    }
}
