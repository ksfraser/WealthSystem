<?php

namespace App\Models;

use App\Core\Interfaces\ModelInterface;

/**
 * Base Model
 * 
 * Provides common functionality for all model classes.
 * Implements basic validation and data handling.
 */
abstract class BaseModel implements ModelInterface
{
    protected ?int $id = null;
    protected array $attributes = [];
    protected array $validationRules = [];
    protected array $errors = [];
    
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->fromArray($data);
        }
    }
    
    /**
     * Get model ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }
    
    /**
     * Set model ID
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    
    /**
     * Get model attributes as array
     */
    public function toArray(): array
    {
        return array_merge(['id' => $this->id], $this->attributes);
    }
    
    /**
     * Set attributes from array
     */
    public function fromArray(array $data): void
    {
        if (isset($data['id'])) {
            $this->id = (int) $data['id'];
            unset($data['id']);
        }
        
        $this->attributes = array_merge($this->attributes, $data);
    }
    
    /**
     * Validate model data
     */
    public function validate(): array
    {
        $this->errors = [];
        
        foreach ($this->validationRules as $field => $rules) {
            $value = $this->attributes[$field] ?? null;
            $this->validateField($field, $value, $rules);
        }
        
        return $this->errors;
    }
    
    /**
     * Check if model is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
    
    /**
     * Get attribute value
     */
    public function __get(string $name)
    {
        if ($name === 'id') {
            return $this->id;
        }
        
        return $this->attributes[$name] ?? null;
    }
    
    /**
     * Set attribute value
     */
    public function __set(string $name, $value): void
    {
        if ($name === 'id') {
            $this->id = (int) $value;
        } else {
            $this->attributes[$name] = $value;
        }
    }
    
    /**
     * Check if attribute exists
     */
    public function __isset(string $name): bool
    {
        if ($name === 'id') {
            return $this->id !== null;
        }
        
        return isset($this->attributes[$name]);
    }
    
    /**
     * Validate individual field
     */
    protected function validateField(string $field, $value, array $rules): void
    {
        foreach ($rules as $rule) {
            switch ($rule) {
                case 'required':
                    if (empty($value)) {
                        $this->errors[$field][] = "{$field} is required";
                    }
                    break;
                    
                case 'email':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->errors[$field][] = "{$field} must be a valid email address";
                    }
                    break;
                    
                case 'numeric':
                    if (!empty($value) && !is_numeric($value)) {
                        $this->errors[$field][] = "{$field} must be a number";
                    }
                    break;
                    
                default:
                    if (strpos($rule, 'min:') === 0) {
                        $min = (int) substr($rule, 4);
                        if (!empty($value) && strlen((string) $value) < $min) {
                            $this->errors[$field][] = "{$field} must be at least {$min} characters";
                        }
                    } elseif (strpos($rule, 'max:') === 0) {
                        $max = (int) substr($rule, 4);
                        if (!empty($value) && strlen((string) $value) > $max) {
                            $this->errors[$field][] = "{$field} must not exceed {$max} characters";
                        }
                    }
                    break;
            }
        }
    }
    
    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}