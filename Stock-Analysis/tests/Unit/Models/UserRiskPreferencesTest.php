<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\UserRiskPreferences;

/**
 * Test User Risk Preferences Model
 */
class UserRiskPreferencesTest extends TestCase
{
    /**
     * Test default initialization with balanced profile
     */
    public function testDefaultInitializationBalanced(): void
    {
        $prefs = new UserRiskPreferences(['user_id' => 1]);
        
        $this->assertEquals(1, $prefs->getUserId());
        $this->assertEquals('balanced', $prefs->getRiskProfile());
        $this->assertEquals(0.10, $prefs->getAttribute('default_position_size'));
        $this->assertEquals(5, $prefs->getAttribute('max_positions'));
        $this->assertTrue($prefs->getAttribute('enable_trailing_stop'));
        $this->assertEquals(0.05, $prefs->getAttribute('trailing_stop_activation'));
        $this->assertEquals(0.10, $prefs->getAttribute('trailing_stop_distance'));
    }
    
    /**
     * Test setting risk profile to conservative
     */
    public function testSetConservativeProfile(): void
    {
        $prefs = new UserRiskPreferences(['user_id' => 1]);
        $prefs->setRiskProfile('conservative');
        
        $this->assertEquals('conservative', $prefs->getRiskProfile());
        $this->assertEquals(0.05, $prefs->getAttribute('default_position_size')); // 5% per trade
        $this->assertEquals(3, $prefs->getAttribute('max_positions'));
        $this->assertEquals(0.08, $prefs->getAttribute('default_stop_loss')); // 8% stop
        $this->assertEquals(0.03, $prefs->getAttribute('trailing_stop_activation')); // Activates at 3%
    }
    
    /**
     * Test setting risk profile to aggressive
     */
    public function testSetAggressiveProfile(): void
    {
        $prefs = new UserRiskPreferences(['user_id' => 1]);
        $prefs->setRiskProfile('aggressive');
        
        $this->assertEquals('aggressive', $prefs->getRiskProfile());
        $this->assertEquals(0.15, $prefs->getAttribute('default_position_size')); // 15% per trade
        $this->assertEquals(7, $prefs->getAttribute('max_positions'));
        $this->assertEquals(0.15, $prefs->getAttribute('default_stop_loss')); // 15% stop
        $this->assertEquals(0.10, $prefs->getAttribute('trailing_stop_activation')); // Activates at 10%
    }
    
    /**
     * Test getBacktestOptions returns correct format
     */
    public function testGetBacktestOptions(): void
    {
        $prefs = new UserRiskPreferences(['user_id' => 1]);
        $options = $prefs->getBacktestOptions();
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('position_size', $options);
        $this->assertArrayHasKey('stop_loss', $options);
        $this->assertArrayHasKey('trailing_stop', $options);
        $this->assertArrayHasKey('trailing_stop_activation', $options);
        $this->assertArrayHasKey('trailing_stop_distance', $options);
        $this->assertArrayHasKey('partial_profit_taking', $options);
        $this->assertArrayHasKey('profit_levels', $options);
        
        // Verify values
        $this->assertEquals(0.10, $options['position_size']);
        $this->assertEquals(0.10, $options['stop_loss']);
        $this->assertTrue($options['trailing_stop']);
        $this->assertEquals(0.05, $options['trailing_stop_activation']);
        $this->assertIsArray($options['profit_levels']);
    }
    
    /**
     * Test getBacktestOptions with overrides
     */
    public function testGetBacktestOptionsWithOverrides(): void
    {
        $prefs = new UserRiskPreferences(['user_id' => 1]);
        
        $overrides = [
            'position_size' => 0.20,
            'stop_loss' => 0.15,
            'custom_param' => 'test'
        ];
        
        $options = $prefs->getBacktestOptions($overrides);
        
        // Overrides should be applied
        $this->assertEquals(0.20, $options['position_size']);
        $this->assertEquals(0.15, $options['stop_loss']);
        $this->assertEquals('test', $options['custom_param']);
        
        // Other values should remain
        $this->assertEquals(0.05, $options['trailing_stop_activation']);
    }
    
    /**
     * Test strategy-specific overrides
     */
    public function testStrategyOverrides(): void
    {
        $prefs = new UserRiskPreferences(['user_id' => 1]);
        
        // Set override for momentum strategy
        $prefs->setStrategyOverride('MomentumStrategy', [
            'trailing_stop_distance' => 0.15,
            'position_size' => 0.12
        ]);
        
        $override = $prefs->getStrategyOverride('MomentumStrategy');
        
        $this->assertIsArray($override);
        $this->assertEquals(0.15, $override['trailing_stop_distance']);
        $this->assertEquals(0.12, $override['position_size']);
        
        // Non-existent strategy should return empty array
        $empty = $prefs->getStrategyOverride('NonExistentStrategy');
        $this->assertEmpty($empty);
    }
    
    /**
     * Test partial profit levels
     */
    public function testPartialProfitLevels(): void
    {
        $prefs = new UserRiskPreferences(['user_id' => 1]);
        $levels = $prefs->getPartialProfitLevels();
        
        $this->assertIsArray($levels);
        $this->assertCount(3, $levels); // Default has 3 levels
        
        // Verify structure
        $this->assertArrayHasKey('profit', $levels[0]);
        $this->assertArrayHasKey('sell_pct', $levels[0]);
        
        // Verify values (balanced profile)
        $this->assertEquals(0.10, $levels[0]['profit']); // 10% gain
        $this->assertEquals(0.25, $levels[0]['sell_pct']); // Sell 25%
    }
    
    /**
     * Test trailing stop configuration
     */
    public function testTrailingStopConfig(): void
    {
        $prefs = new UserRiskPreferences(['user_id' => 1]);
        $config = $prefs->getTrailingStopConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('activation', $config);
        $this->assertArrayHasKey('distance', $config);
        
        $this->assertTrue($config['enabled']);
        $this->assertEquals(0.05, $config['activation']);
        $this->assertEquals(0.10, $config['distance']);
    }
    
    /**
     * Test toArray for database storage
     */
    public function testToArrayForDatabase(): void
    {
        $prefs = new UserRiskPreferences(['user_id' => 1]);
        $array = $prefs->toArray();
        
        // Booleans should be converted to integers
        $this->assertIsInt($array['enable_trailing_stop']);
        $this->assertIsInt($array['enable_partial_profits']);
        $this->assertEquals(1, $array['enable_trailing_stop']);
        
        // Arrays should be JSON encoded
        $this->assertIsString($array['partial_profit_levels']);
        $decoded = json_decode($array['partial_profit_levels'], true);
        $this->assertIsArray($decoded);
    }
    
    /**
     * Test fromArray for loading from database
     */
    public function testFromArrayFromDatabase(): void
    {
        $dbData = [
            'user_id' => 1,
            'risk_profile' => 'balanced',
            'enable_trailing_stop' => 1,
            'enable_partial_profits' => 1,
            'partial_profit_levels' => '[{"profit": 0.10, "sell_pct": 0.25}]',
            'strategy_overrides' => '{"TestStrategy": {"stop_loss": 0.12}}'
        ];
        
        $prefs = new UserRiskPreferences();
        $prefs->fromArray($dbData);
        
        // Integers should be converted to booleans
        $this->assertTrue($prefs->getAttribute('enable_trailing_stop'));
        $this->assertTrue($prefs->getAttribute('enable_partial_profits'));
        
        // JSON should be decoded to arrays
        $levels = $prefs->getPartialProfitLevels();
        $this->assertIsArray($levels);
        $this->assertCount(1, $levels);
        
        $overrides = $prefs->getStrategyOverride('TestStrategy');
        $this->assertIsArray($overrides);
        $this->assertEquals(0.12, $overrides['stop_loss']);
    }
    
    /**
     * Test different risk profiles have correct settings
     */
    public function testRiskProfileDifferences(): void
    {
        $conservative = new UserRiskPreferences(['user_id' => 1]);
        $conservative->setRiskProfile('conservative');
        
        $balanced = new UserRiskPreferences(['user_id' => 2]);
        $balanced->setRiskProfile('balanced');
        
        $aggressive = new UserRiskPreferences(['user_id' => 3]);
        $aggressive->setRiskProfile('aggressive');
        
        // Position sizes should increase
        $this->assertLessThan(
            $balanced->getAttribute('default_position_size'),
            $conservative->getAttribute('default_position_size')
        );
        $this->assertLessThan(
            $aggressive->getAttribute('default_position_size'),
            $balanced->getAttribute('default_position_size')
        );
        
        // Stop losses should widen
        $this->assertLessThan(
            $balanced->getAttribute('default_stop_loss'),
            $conservative->getAttribute('default_stop_loss')
        );
        $this->assertLessThan(
            $aggressive->getAttribute('default_stop_loss'),
            $balanced->getAttribute('default_stop_loss')
        );
        
        // Trailing activation should require more gain for aggressive
        $this->assertLessThan(
            $balanced->getAttribute('trailing_stop_activation'),
            $conservative->getAttribute('trailing_stop_activation')
        );
        $this->assertLessThan(
            $aggressive->getAttribute('trailing_stop_activation'),
            $balanced->getAttribute('trailing_stop_activation')
        );
    }
}
