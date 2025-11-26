<?php

namespace App\Middleware;

use App\Security\InputValidator;

/**
 * Input Validation Middleware
 * 
 * Sanitizes and validates input from GET/POST/REQUEST superglobals.
 */
class ValidateInput
{
    /**
     * Sanitize all GET parameters
     */
    public static function sanitizeGet(): array
    {
        $sanitized = [];
        foreach ($_GET as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $sanitized[$key] = InputValidator::sanitizeString($value);
            }
        }
        return $sanitized;
    }
    
    /**
     * Sanitize all POST parameters
     */
    public static function sanitizePost(): array
    {
        $sanitized = [];
        foreach ($_POST as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $sanitized[$key] = InputValidator::sanitizeString($value);
            }
        }
        return $sanitized;
    }
    
    /**
     * Sanitize array recursively
     */
    private static function sanitizeArray(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $sanitized[$key] = InputValidator::sanitizeString($value);
            }
        }
        return $sanitized;
    }
    
    /**
     * Validate integer from GET/POST
     */
    public static function getInt(string $key, ?int $default = null): ?int
    {
        $value = $_GET[$key] ?? $_POST[$key] ?? $default;
        return InputValidator::validateInt($value);
    }
    
    /**
     * Validate float from GET/POST
     */
    public static function getFloat(string $key, ?float $default = null): ?float
    {
        $value = $_GET[$key] ?? $_POST[$key] ?? $default;
        return InputValidator::validateFloat($value);
    }
    
    /**
     * Validate string from GET/POST
     */
    public static function getString(string $key, ?string $default = null): ?string
    {
        $value = $_GET[$key] ?? $_POST[$key] ?? $default;
        return $value ? InputValidator::sanitizeString($value) : $default;
    }
    
    /**
     * Validate email from GET/POST
     */
    public static function getEmail(string $key, ?string $default = null): ?string
    {
        $value = $_GET[$key] ?? $_POST[$key] ?? $default;
        return $value ? InputValidator::validateEmail($value) : $default;
    }
    
    /**
     * Validate stock symbol from GET/POST
     */
    public static function getStockSymbol(string $key, ?string $default = null): ?string
    {
        $value = $_GET[$key] ?? $_POST[$key] ?? $default;
        return $value ? InputValidator::validateStockSymbol($value) : $default;
    }
    
    /**
     * Validate date from GET/POST
     */
    public static function getDate(string $key, ?string $default = null): ?string
    {
        $value = $_GET[$key] ?? $_POST[$key] ?? $default;
        return $value ? InputValidator::validateDate($value) : $default;
    }
    
    /**
     * Validate value against whitelist
     */
    public static function getWhitelisted(string $key, array $whitelist, ?string $default = null): ?string
    {
        $value = $_GET[$key] ?? $_POST[$key] ?? $default;
        return $value ? InputValidator::validateWhitelist($value, $whitelist) : $default;
    }
}
