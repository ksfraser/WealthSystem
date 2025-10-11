<?php

namespace App\Models;

use App\Core\Interfaces\ModelInterface;

/**
 * BankAccount Model
 * 
 * Represents bank account entity compatible with existing bank account management.
 * Works with BankAccountsDAO and existing admin system.
 */
class BankAccount extends BaseModel implements ModelInterface
{
    protected array $validationRules = [
        'bank_name' => ['required', 'max:100'],
        'account_number' => ['required', 'max:50'],
        'account_type' => ['required', 'max:50']
    ];
    
    /**
     * Initialize bank account with default values
     */
    public function __construct(array $data = [])
    {
        // Set default attributes for bank account
        $this->attributes = [
            'bank_name' => '',
            'account_number' => '',
            'account_type' => 'Checking',
            'routing_number' => '',
            'branch_code' => '',
            'currency' => 'CAD',
            'is_active' => true,
            'notes' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        parent::__construct($data);
    }
    
    /**
     * Get bank name
     */
    public function getBankName(): string
    {
        return $this->attributes['bank_name'] ?? '';
    }
    
    /**
     * Get account number
     */
    public function getAccountNumber(): string
    {
        return $this->attributes['account_number'] ?? '';
    }
    
    /**
     * Get account type
     */
    public function getAccountType(): string
    {
        return $this->attributes['account_type'] ?? 'Checking';
    }
    
    /**
     * Get routing number
     */
    public function getRoutingNumber(): string
    {
        return $this->attributes['routing_number'] ?? '';
    }
    
    /**
     * Get branch code
     */
    public function getBranchCode(): string
    {
        return $this->attributes['branch_code'] ?? '';
    }
    
    /**
     * Get currency
     */
    public function getCurrency(): string
    {
        return $this->attributes['currency'] ?? 'CAD';
    }
    
    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return (bool) ($this->attributes['is_active'] ?? true);
    }
    
    /**
     * Get notes
     */
    public function getNotes(): string
    {
        return $this->attributes['notes'] ?? '';
    }
    
    /**
     * Set bank name
     */
    public function setBankName(string $bankName): void
    {
        $this->attributes['bank_name'] = $bankName;
    }
    
    /**
     * Set account number
     */
    public function setAccountNumber(string $accountNumber): void
    {
        $this->attributes['account_number'] = $accountNumber;
    }
    
    /**
     * Set account type
     */
    public function setAccountType(string $accountType): void
    {
        $this->attributes['account_type'] = $accountType;
    }
    
    /**
     * Set active status
     */
    public function setActive(bool $isActive): void
    {
        $this->attributes['is_active'] = $isActive;
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
    }
    
    /**
     * Set notes
     */
    public function setNotes(string $notes): void
    {
        $this->attributes['notes'] = $notes;
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
    }
    
    /**
     * Get display name for the account
     */
    public function getDisplayName(): string
    {
        $bankName = $this->getBankName();
        $accountNumber = $this->getAccountNumber();
        
        // Mask account number for security
        $maskedNumber = $this->getMaskedAccountNumber();
        
        return "{$bankName} - {$maskedNumber}";
    }
    
    /**
     * Get masked account number for security
     */
    public function getMaskedAccountNumber(): string
    {
        $accountNumber = $this->getAccountNumber();
        
        if (strlen($accountNumber) <= 4) {
            return $accountNumber;
        }
        
        $lastFour = substr($accountNumber, -4);
        $masked = str_repeat('*', strlen($accountNumber) - 4) . $lastFour;
        
        return $masked;
    }
    
    /**
     * Check if this matches bank import data
     */
    public function matchesImportData(string $bankName, string $accountNumber): bool
    {
        // Normalize bank names for comparison
        $thisBankName = strtolower(trim($this->getBankName()));
        $importBankName = strtolower(trim($bankName));
        
        // Check for exact match
        if ($thisBankName === $importBankName && $this->getAccountNumber() === $accountNumber) {
            return true;
        }
        
        // Check for partial bank name match (e.g., "TD" matches "TD Bank")
        if (strpos($thisBankName, $importBankName) !== false || 
            strpos($importBankName, $thisBankName) !== false) {
            if ($this->getAccountNumber() === $accountNumber) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate account identifier for import mapping
     */
    public function getImportIdentifier(): string
    {
        return md5($this->getBankName() . ':' . $this->getAccountNumber());
    }
    
    /**
     * Custom validation for bank account specific rules
     */
    protected function validateField(string $field, $value, array $rules): void
    {
        parent::validateField($field, $value, $rules);
        
        // Additional bank account specific validations
        switch ($field) {
            case 'account_number':
                if (!empty($value) && !preg_match('/^[0-9\-\s]+$/', $value)) {
                    $this->errors[$field][] = 'Account number can only contain numbers, hyphens, and spaces';
                }
                break;
                
            case 'routing_number':
                if (!empty($value) && !preg_match('/^[0-9]{8,9}$/', str_replace(['-', ' '], '', $value))) {
                    $this->errors[$field][] = 'Routing number must be 8-9 digits';
                }
                break;
                
            case 'currency':
                $validCurrencies = ['CAD', 'USD', 'EUR', 'GBP'];
                if (!empty($value) && !in_array(strtoupper($value), $validCurrencies)) {
                    $this->errors[$field][] = 'Currency must be one of: ' . implode(', ', $validCurrencies);
                }
                break;
        }
    }
    
    /**
     * Get account summary for display
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->getId(),
            'display_name' => $this->getDisplayName(),
            'bank_name' => $this->getBankName(),
            'account_type' => $this->getAccountType(),
            'currency' => $this->getCurrency(),
            'is_active' => $this->isActive(),
            'masked_account' => $this->getMaskedAccountNumber()
        ];
    }
    
    /**
     * Convert to array for admin display (with sensitive data)
     */
    public function toAdminArray(): array
    {
        $data = $this->toArray();
        
        // Admin can see full account number
        return $data;
    }
    
    /**
     * Convert to array for user display (masked sensitive data)
     */
    public function toUserArray(): array
    {
        $data = $this->toArray();
        
        // Mask account number for regular users
        $data['account_number'] = $this->getMaskedAccountNumber();
        
        return $data;
    }
}