<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/TableTypeRegistry.php';

/**
 * @covers TableTypeRegistry
 */
class TableTypeRegistryTest extends TestCase
{
    public function testIsValidSymbolValid()
    {
        $this->assertTrue(TableTypeRegistry::isValidSymbol('IBM'));
        $this->assertTrue(TableTypeRegistry::isValidSymbol('AAPL'));
        $this->assertTrue(TableTypeRegistry::isValidSymbol('GOOGL'));
        $this->assertTrue(TableTypeRegistry::isValidSymbol('TSLA1'));
    }

    public function testIsValidSymbolInvalid()
    {
        $this->assertFalse(TableTypeRegistry::isValidSymbol('ibm'));
        $this->assertFalse(TableTypeRegistry::isValidSymbol('AAPL!'));
        $this->assertFalse(TableTypeRegistry::isValidSymbol(''));
        $this->assertFalse(TableTypeRegistry::isValidSymbol('TOOLONGSYMBOL123'));
        $this->assertFalse(TableTypeRegistry::isValidSymbol('IBM-'));
        $this->assertFalse(TableTypeRegistry::isValidSymbol('IBM.'));
        $this->assertFalse(TableTypeRegistry::isValidSymbol('IBM '));
        $this->assertFalse(TableTypeRegistry::isValidSymbol(' IBM'));
        $this->assertFalse(TableTypeRegistry::isValidSymbol('IBM_'));
        $this->assertFalse(TableTypeRegistry::isValidSymbol('12345678901'));
    }

    public function testIsValidSymbolBoundaryConditions()
    {
        // Test minimum length (1 character)
        $this->assertTrue(TableTypeRegistry::isValidSymbol('A'));
        $this->assertTrue(TableTypeRegistry::isValidSymbol('1'));
        
        // Test maximum length (10 characters)
        $this->assertTrue(TableTypeRegistry::isValidSymbol('ABCDEFGH12'));
        $this->assertTrue(TableTypeRegistry::isValidSymbol('1234567890'));
        
        // Test just over maximum length (11 characters)
        $this->assertFalse(TableTypeRegistry::isValidSymbol('ABCDEFGH123'));
    }

    public function testIsValidSymbolWithNullAndNonString()
    {
        // Test behavior with invalid input types
        // The method should return false for non-string inputs
        $this->assertFalse(TableTypeRegistry::isValidSymbol(null));
        $this->assertFalse(TableTypeRegistry::isValidSymbol(123));
        $this->assertFalse(TableTypeRegistry::isValidSymbol(true));
        $this->assertFalse(TableTypeRegistry::isValidSymbol(false));
        $this->assertFalse(TableTypeRegistry::isValidSymbol([]));
        $this->assertFalse(TableTypeRegistry::isValidSymbol(new stdClass()));
    }

    public function testTableTypesConstant()
    {
        $expectedTypes = [
            'prices',
            'indicators',
            'patterns',
            'support_resistance',
            'signals',
            'earnings',
            'dividends',
        ];
        
        $this->assertEquals($expectedTypes, TableTypeRegistry::TABLE_TYPES);
        $this->assertCount(7, TableTypeRegistry::TABLE_TYPES);
        
        // Ensure all table types are strings
        foreach (TableTypeRegistry::TABLE_TYPES as $tableType) {
            $this->assertIsString($tableType);
            $this->assertNotEmpty($tableType);
        }
    }

    public function testTableTypesUniqueness()
    {
        $uniqueTypes = array_unique(TableTypeRegistry::TABLE_TYPES);
        $this->assertEquals(count(TableTypeRegistry::TABLE_TYPES), count($uniqueTypes), 'All table types should be unique');
    }
}
