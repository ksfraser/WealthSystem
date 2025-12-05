<?php

namespace App\Security;

/**
 * Input Validator
 * 
 * Provides secure input validation and sanitization for all user input.
 * Prevents XSS, SQL injection, and other injection attacks.
 */
class InputValidator
{
    /**
     * Sanitize string input - removes tags and special characters
     */
    public static function sanitizeString(string $input): string
    {
        // Remove any null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Strip all tags
        $input = strip_tags($input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Convert special characters to HTML entities
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Validate and sanitize integer
     */
    public static function validateInt($input): ?int
    {
        $filtered = filter_var($input, FILTER_VALIDATE_INT);
        return $filtered !== false ? $filtered : null;
    }
    
    /**
     * Validate and sanitize float
     */
    public static function validateFloat($input): ?float
    {
        $filtered = filter_var($input, FILTER_VALIDATE_FLOAT);
        return $filtered !== false ? $filtered : null;
    }
    
    /**
     * Validate email address
     */
    public static function validateEmail(string $input): ?string
    {
        $filtered = filter_var($input, FILTER_VALIDATE_EMAIL);
        return $filtered !== false ? $filtered : null;
    }
    
    /**
     * Validate URL
     */
    public static function validateUrl(string $input): ?string
    {
        $filtered = filter_var($input, FILTER_VALIDATE_URL);
        return $filtered !== false ? $filtered : null;
    }
    
    /**
     * Validate boolean
     */
    public static function validateBoolean($input): bool
    {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }
    
    /**
     * Validate date in Y-m-d format
     */
    public static function validateDate(string $input): ?string
    {
        $date = \DateTime::createFromFormat('Y-m-d', $input);
        if ($date && $date->format('Y-m-d') === $input) {
            return $input;
        }
        return null;
    }
    
    /**
     * Validate datetime in Y-m-d H:i:s format
     */
    public static function validateDateTime(string $input): ?string
    {
        $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $input);
        if ($datetime && $datetime->format('Y-m-d H:i:s') === $input) {
            return $input;
        }
        return null;
    }
    
    /**
     * Validate and sanitize alphanumeric string
     */
    public static function validateAlphanumeric(string $input): ?string
    {
        if (preg_match('/^[a-zA-Z0-9]+$/', $input)) {
            return $input;
        }
        return null;
    }
    
    /**
     * Validate stock symbol (1-5 uppercase letters)
     */
    public static function validateStockSymbol(string $input): ?string
    {
        $input = strtoupper(trim($input));
        if (preg_match('/^[A-Z]{1,5}$/', $input)) {
            return $input;
        }
        return null;
    }
    
    /**
     * Sanitize filename - removes dangerous characters
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove path separators
        $filename = str_replace(['/', '\\', '..'], '', $filename);
        
        // Remove null bytes
        $filename = str_replace(chr(0), '', $filename);
        
        // Keep only safe characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        return $filename;
    }
    
    /**
     * Validate array of values
     */
    public static function validateArray(array $input, string $type): array
    {
        $validated = [];
        
        foreach ($input as $key => $value) {
            switch ($type) {
                case 'int':
                    $validated[$key] = self::validateInt($value);
                    break;
                case 'string':
                    $validated[$key] = self::sanitizeString($value);
                    break;
                case 'email':
                    $validated[$key] = self::validateEmail($value);
                    break;
                default:
                    $validated[$key] = $value;
            }
        }
        
        return array_filter($validated, fn($v) => $v !== null);
    }
    
    /**
     * Validate money amount (positive float with 2 decimals)
     */
    public static function validateMoney($input): ?float
    {
        $amount = self::validateFloat($input);
        
        if ($amount !== null && $amount >= 0) {
            return round($amount, 2);
        }
        
        return null;
    }
    
    /**
     * Validate phone number (basic validation)
     */
    public static function validatePhone(string $input): ?string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $input);
        
        // Check if it's 10 digits (North American format)
        if (strlen($phone) === 10) {
            return $phone;
        }
        
        return null;
    }
    
    /**
     * Validate against whitelist
     */
    public static function validateWhitelist($input, array $whitelist): ?string
    {
        if (in_array($input, $whitelist, true)) {
            return $input;
        }
        return null;
    }
    
    /**
     * Sanitize HTML content (allows safe tags)
     */
    public static function sanitizeHtml(string $input): string
    {
        // Allow only safe HTML tags
        $allowedTags = '<p><br><strong><em><u><ul><ol><li><a>';
        
        // Strip dangerous tags
        $sanitized = strip_tags($input, $allowedTags);
        
        // Remove any JavaScript
        $sanitized = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $sanitized);
        $sanitized = preg_replace('/on\w+="[^"]*"/i', '', $sanitized);
        
        return $sanitized;
    }
}
