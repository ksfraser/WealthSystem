# Account Types Management System

## Overview
The Account Types Management system provides a comprehensive interface for managing different types of investment accounts in the trading system. It integrates with the existing centralized database architecture and includes table creation, data prepopulation, and full CRUD operations.

## Features

### 🏗️ **Database Setup**
- **Table Creation**: Automatically creates the `account_types` table with enhanced schema
- **Prepopulation**: Loads standard Canadian account types (CAD and USD versions)
- **Status Monitoring**: Shows table existence, record count, and prepopulation status

### ✏️ **CRUD Operations**
- **Create**: Add new account types with validation
- **Read**: View all account types with filtering and sorting
- **Update**: Edit existing account types (name, description, currency, status)
- **Delete**: Remove account types with confirmation

### 🛡️ **Data Validation**
- **Name uniqueness**: Prevents duplicate account type names
- **Required fields**: Validates mandatory information
- **Currency validation**: Ensures valid currency codes (CAD/USD)
- **Input sanitization**: Protects against malicious input

### 🎨 **Enhanced UI**
- **Visual status indicators**: Color-coded connection and status displays
- **Responsive design**: Works on different screen sizes
- **Intuitive forms**: Clear, organized input forms
- **Action confirmation**: Prevents accidental deletions

## Database Schema

### Enhanced Table Structure
```sql
CREATE TABLE IF NOT EXISTS account_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) UNIQUE NOT NULL,
    description TEXT NULL,
    currency VARCHAR(3) DEFAULT 'CAD',
    is_registered BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Schema Improvements
The enhanced schema includes several improvements over the original:

1. **`description`** - Detailed description of the account type
2. **`currency`** - Currency designation (CAD/USD)
3. **`is_registered`** - Tax-advantaged account flag
4. **`is_active`** - Account type status
5. **`created_at/updated_at`** - Audit timestamps

## Prepopulated Account Types

### Canadian Accounts (CAD)
- **Cash** - Regular cash account
- **Margin** - Margin trading account
- **TFSA** - Tax-Free Savings Account (Registered)
- **RRSP** - Registered Retirement Savings Plan (Registered)
- **RRIF** - Registered Retirement Income Fund (Registered)
- **RESP** - Registered Education Savings Plan (Registered)
- **LIRA** - Locked-in Retirement Account (Registered)
- **LIF** - Life Income Fund (Registered)
- **Corporate** - Corporate investment account
- **Joint** - Joint investment account
- **Trust** - Trust account

### US Dollar Accounts (USD)
- All the above types in USD denomination
- Prefixed with "USD" for clarity

### Special Account Types
- **Other** - Catch-all for non-standard account types

## Usage Workflow

### 1. Initial Setup
1. **Check Connection**: Verify database connectivity
2. **Create Table**: Click "Create Table" if table doesn't exist
3. **Prepopulate**: Click "Prepopulate Standard Account Types" to load defaults

### 2. Managing Account Types
1. **Add New**: Use the "Add New Account Type" form
2. **Edit Existing**: Click "Edit" button next to any account type
3. **Delete**: Click "Delete" with confirmation for unwanted types

### 3. Monitoring
- **Connection Status**: Real-time database connection monitoring
- **Table Status**: Shows table existence and record count
- **Operation Results**: Clear success/error messages

## Integration Points

### With Existing System
- **Extends `EnhancedCommonDAO`**: Uses centralized database architecture
- **Uses `DbConfigClasses`**: Leverages existing configuration system
- **Integrates with `QuickActions`**: Accessible from main navigation
- **Follows UI patterns**: Consistent with other admin pages

### Error Handling
- **Graceful degradation**: Functions when database is unavailable
- **Clear error messages**: Helpful troubleshooting information
- **Connection diagnostics**: Links to database management tools
- **Transaction safety**: Proper rollback handling

## Security Features

### Input Validation
- **SQL injection prevention**: Parameterized queries only
- **XSS protection**: HTML entity encoding
- **Length validation**: Enforced field length limits
- **Type validation**: Currency and boolean field validation

### Access Control
- **Database dependency**: Requires valid database connection
- **Form token protection**: CSRF protection ready
- **Error information limiting**: No sensitive data in error messages

## Code Architecture

### DAO Pattern
```php
class AccountTypesDAO extends EnhancedCommonDAO
{
    // Extends existing centralized system
    // Adds account-type-specific functionality
    // Maintains backward compatibility
}
```

### Key Methods
- **`ensureTableExists()`** - Table creation with error handling
- **`prepopulateAccountTypes()`** - Bulk data insertion
- **`getAllAccountTypes()`** - Retrieve with sorting
- **`addAccountType()`** - Create with validation
- **`updateAccountType()`** - Update with conflict resolution
- **`deleteAccountType()`** - Safe deletion
- **`getTableStatus()`** - Status monitoring

### Error Handling Pattern
```php
try {
    // Database operation
    $result = $dao->someOperation();
    $message = "Success message";
    $messageType = 'success';
} catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
    $messageType = 'error';
}
```

## File Structure

### Core Files
- **`admin_account_types.php`** - Main interface
- **`EnhancedCommonDAO.php`** - Enhanced base DAO
- **`SimpleValidators.php`** - Validation classes
- **`schema/003_create_account_types.sql`** - Original table creation
- **`schema/010_seed_account_types.sql`** - Original seed data

### Supporting Files
- **`UiStyles.php`** - UI styling
- **`QuickActions.php`** - Navigation integration
- **`database.php`** - Database management portal

## Usage Examples

### Adding a Custom Account Type
1. Navigate to Admin Account Types
2. Fill in the "Add New Account Type" form:
   - **Name**: "Crypto TFSA"
   - **Description**: "Cryptocurrency holdings in TFSA"
   - **Currency**: CAD
   - **Registered**: ✓ Checked
3. Click "Add Account Type"

### Editing an Existing Type
1. Find the account type in the list
2. Click "Edit" button
3. Modify fields as needed
4. Click "Update Account Type"

### Bulk Setup
1. Ensure database connection is working
2. Click "Create Table" (if needed)
3. Click "Prepopulate Standard Account Types"
4. Review the 23 standard account types loaded

## Troubleshooting

### Common Issues

#### Database Connection Failed
- **Symptom**: "Not Connected" status, no functionality available
- **Solution**: Check database configuration, ensure PDO MySQL is enabled
- **Reference**: Use Database Management page for diagnosis

#### Table Creation Failed
- **Symptom**: Error message when clicking "Create Table"
- **Solution**: Verify database permissions, check error logs
- **Fallback**: Run SQL manually from schema files

#### Duplicate Account Type Error
- **Symptom**: "Account type already exists" message
- **Solution**: Use different name or edit existing account type
- **Alternative**: Delete existing type first (if safe to do so)

### Logging
All operations are logged to `logs/account_types.log` including:
- Connection attempts
- Table operations
- CRUD operations
- Error details

## Future Enhancements

### Planned Features
- **Account type categories**: Grouping related account types
- **Tax rules integration**: Link to tax calculation rules
- **Reporting integration**: Account type-based reporting
- **Import/Export**: Bulk account type management
- **API endpoints**: REST API for external integrations

### Migration Path
The system is designed to work alongside existing account type functionality:
- Existing code continues to work unchanged
- New features are additive
- Gradual migration to enhanced functionality
- Backward compatibility maintained

## Conclusion

The Account Types Management system provides a robust, user-friendly interface for managing investment account types while integrating seamlessly with the existing centralized database architecture. It includes comprehensive setup, management, and monitoring capabilities with proper error handling and security measures.
