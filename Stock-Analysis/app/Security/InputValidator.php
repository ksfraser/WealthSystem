<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Input Validation Layer
 * 
 * Provides comprehensive input validation and sanitization for user data.
 * Prevents XSS, SQL injection, and other input-based attacks.
 * 
 * Usage:
 *   $validator = new InputValidator($_POST);
 *   $userId = $validator->required('user_id')->int()->min(1)->getValue();
 *   $email = $validator->required('email')->email()->getValue();
 */
class InputValidator
{
    private array $data;
    private array $errors = [];
    private ?string $currentField = null;
    private mixed $currentValue = null;
    private bool $isRequired = false;

    /**
     * @param array $data Input data to validate ($_GET, $_POST, etc.)
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Start validating a required field
     * 
     * @param string $field Field name
     * @return self For method chaining
     */
    public function required(string $field): self
    {
        $this->currentField = $field;
        $this->isRequired = true;
        $this->currentValue = $this->data[$field] ?? null;

        if ($this->currentValue === null || $this->currentValue === '') {
            $this->errors[$field][] = "Field '$field' is required";
        }

        return $this;
    }

    /**
     * Start validating an optional field
     * 
     * @param string $field Field name
     * @param mixed $default Default value if not present
     * @return self For method chaining
     */
    public function optional(string $field, mixed $default = null): self
    {
        $this->currentField = $field;
        $this->isRequired = false;
        $this->currentValue = $this->data[$field] ?? $default;

        return $this;
    }

    /**
     * Validate as integer
     * 
     * @return self For method chaining
     */
    public function int(): self
    {
        if ($this->shouldSkipValidation()) {
            return $this;
        }

        if (!is_numeric($this->currentValue) || (int)$this->currentValue != $this->currentValue) {
            $this->errors[$this->currentField][] = "Field '{$this->currentField}' must be an integer";
        } else {
            $this->currentValue = (int)$this->currentValue;
        }

        return $this;
    }

    /**
     * Validate as float
     * 
     * @return self For method chaining
     */
    public function float(): self
    {
        if ($this->shouldSkipValidation()) {
            return $this;
        }

        if (!is_numeric($this->currentValue)) {
            $this->errors[$this->currentField][] = "Field '{$this->currentField}' must be a number";
        } else {
            $this->currentValue = (float)$this->currentValue;
        }

        return $this;
    }

    /**
     * Validate as string
     * 
     * @return self For method chaining
     */
    public function string(): self
    {
        if ($this->shouldSkipValidation()) {
            return $this;
        }

        $this->currentValue = (string)$this->currentValue;
        return $this;
    }

    /**
     * Validate as email
     * 
     * @return self For method chaining
     */
    public function email(): self
    {
        if ($this->shouldSkipValidation()) {
            return $this;
        }

        if (!filter_var($this->currentValue, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$this->currentField][] = "Field '{$this->currentField}' must be a valid email";
        }

        return $this;
    }

    /**
     * Validate as URL
     * 
     * @return self For method chaining
     */
    public function url(): self
    {
        if ($this->shouldSkipValidation()) {
            return $this;
        }

        if (!filter_var($this->currentValue, FILTER_VALIDATE_URL)) {
            $this->errors[$this->currentField][] = "Field '{$this->currentField}' must be a valid URL";
        }

        return $this;
    }

    /**
     * Validate as boolean
     * 
     * @return self For method chaining
     */
    public function bool(): self
    {
        if ($this->shouldSkipValidation()) {
            return $this;
        }

        $this->currentValue = filter_var($this->currentValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        
        if ($this->currentValue === null) {
            $this->errors[$this->currentField][] = "Field '{$this->currentField}' must be a boolean";
        }

        return $this;
    }

    /**
     * Validate minimum value
     * 
     * @param int|float $min Minimum value
     * @return self For method chaining
     */
    public function min(int|float $min): self
    {
        if ($this->shouldSkipValidation()) {
            return $this;
        }

        if (is_numeric($this->currentValue) && $this->currentValue < $min) {
            $this->errors[$this->currentField][] = "Field '{$this->currentField}' must be at least $min";
        }

        return $this;
    }

    /**
     * Validate maximum value
     * 
     * @param int|float $max Maximum value
     * @return self For method chaining
     */
    public function max(int|float $max): self
    {
        if ($this->shouldSkipValidation()) {
            return $this;
        }

        if (is_numeric($this->currentValue) && $this->currentValue > $max) {
            $this->errors[$this->currentField][] = "Field '{$this->currentField}' must be at most $max";
        }

        return $this;
    }

    /**
     * Validate minimum length
     * 
     * @param int $length Minimum length
     * @return self For method chaining
     */
    public function minLength(int $length): self
    {
        if ($this->shouldSkipValidation()) {
            return $this;
        }

        if (strlen((string)$this->currentValue) < $length) {
            $this->errors[$this->currentField][] = "Field '{$this->currentField}' must be at least $length characters";
        }

        return $this;
    }

    /**
     * Validate maximum length
     * 
     * @param int $length Maximum length
     * @return self For method chaining
     */
    public function maxLength(int $length): self
    {
        if ($this->shouldSkipValidation()) {
            return $this;
        }

        if (strlen((string)$this->currentValue) > $length) {
            $this->errors[$this->currentField][] = "Field '{$this->currentField}' must be at most $length characters";
        }

        return $this;
    }

    /**
     * Validate against pattern
     * 
     * @param string $pattern Regular expression pattern
     * @param string $message Error message
     * @return self For method chaining
     */
    public function pattern(string $pattern, string $message = 'Invalid format'): self
    {
        if ($this->shouldSkipValidation()) {
            return $this;
        }

        if (!preg_match($pattern, (string)$this->currentValue)) {
            $this->errors[$this->currentField][] = "Field '{$this->currentField}': $message";
        }

        return $this;
    }

    /**
     * Validate against array of allowed values
     * 
     * @param array $values Allowed values
     * @return self For method chaining
     */
    public function in(array $values): self
    {
        if ($this->shouldSkipValidation()) {
            return $this;
        }

        if (!in_array($this->currentValue, $values, true)) {
            $allowed = implode(', ', $values);
            $this->errors[$this->currentField][] = "Field '{$this->currentField}' must be one of: $allowed";
        }

        return $this;
    }

    /**
     * Sanitize HTML to prevent XSS
     * 
     * @param bool $allowBasicTags Allow basic HTML tags (<b>, <i>, etc.)
     * @return self For method chaining
     */
    public function sanitizeHtml(bool $allowBasicTags = false): self
    {
        if ($this->shouldSkipValidation()) {
            return $this;
        }

        if ($allowBasicTags) {
            $this->currentValue = strip_tags(
                (string)$this->currentValue,
                '<b><i><u><strong><em><p><br><ul><ol><li>'
            );
        } else {
            $this->currentValue = strip_tags((string)$this->currentValue);
        }

        return $this;
    }

    /**
     * Get validated value
     * 
     * @return mixed The validated and sanitized value
     */
    public function getValue(): mixed
    {
        return $this->currentValue;
    }

    /**
     * Check if validation has errors
     * 
     * @return bool True if there are errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get all validation errors
     * 
     * @return array Array of errors by field name
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     * 
     * @return string|null First error message or null
     */
    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }

    /**
     * Throw exception if validation failed
     * 
     * @throws \InvalidArgumentException If validation failed
     */
    public function validate(): void
    {
        if ($this->hasErrors()) {
            throw new \InvalidArgumentException($this->getFirstError());
        }
    }

    /**
     * Check if validation should be skipped
     * 
     * @return bool True if should skip
     */
    private function shouldSkipValidation(): bool
    {
        // Skip if there's already an error for this field
        if (isset($this->errors[$this->currentField])) {
            return true;
        }

        // Skip if optional and value is null
        if (!$this->isRequired && $this->currentValue === null) {
            return true;
        }

        return false;
    }
}
