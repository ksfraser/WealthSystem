<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\Finance\MarketFactors\Entities\EconomicIndicator;

class EconomicIndicatorTest extends TestCase
{
    /**
     * Test creation with all parameters
     */
    public function testCreationWithAllParameters(): void
    {
        $releaseDate = new \DateTime('2024-01-15 08:30:00');
        $indicator = new EconomicIndicator(
            'US_CPI',
            'Consumer Price Index',
            'US',
            2.8,
            2.5,
            2.7,
            'monthly',
            '%',
            'high',
            'BLS',
            $releaseDate
        );
        
        $this->assertEquals('US_CPI', $indicator->getSymbol());
        $this->assertEquals('Consumer Price Index', $indicator->getName());
        $this->assertEquals('US', $indicator->getCountry());
        $this->assertEquals(2.8, $indicator->getValue());
        $this->assertEquals(2.5, $indicator->getPreviousValue());
        $this->assertEquals(2.7, $indicator->getForecast());
        $this->assertEquals('monthly', $indicator->getFrequency());
        $this->assertEquals('%', $indicator->getUnit());
        $this->assertEquals('high', $indicator->getImportance());
        $this->assertEquals('BLS', $indicator->getSource());
        $this->assertEquals($releaseDate, $indicator->getReleaseDate());
        $this->assertEquals('economic', $indicator->getType());
    }

    /**
     * Test creation with minimal parameters
     */
    public function testCreationWithMinimalParameters(): void
    {
        $indicator = new EconomicIndicator('US_GDP', 'GDP Growth', 'US', 3.2);
        
        $this->assertEquals('US_GDP', $indicator->getSymbol());
        $this->assertEquals('GDP Growth', $indicator->getName());
        $this->assertEquals('US', $indicator->getCountry());
        $this->assertEquals(3.2, $indicator->getValue());
        $this->assertEquals(0.0, $indicator->getPreviousValue());
        $this->assertEquals(0.0, $indicator->getForecast());
        $this->assertEquals('monthly', $indicator->getFrequency());
        $this->assertEquals('', $indicator->getUnit());
        $this->assertEquals('medium', $indicator->getImportance());
        $this->assertEquals('', $indicator->getSource());
        $this->assertInstanceOf(\DateTime::class, $indicator->getReleaseDate());
    }

    /**
     * Test forecast vs actual analysis
     */
    public function testForecastVsActual(): void
    {
        // Beat forecast
        $beatForecast = new EconomicIndicator('US_JOBS', 'Non-Farm Payrolls', 'US', 180000, 0, 150000);
        $this->assertTrue($beatForecast->beatForecast());
        $this->assertFalse($beatForecast->missedForecast());
        
        // Missed forecast
        $missedForecast = new EconomicIndicator('US_RETAIL', 'Retail Sales', 'US', 1.2, 0, 1.8);
        $this->assertFalse($missedForecast->beatForecast());
        $this->assertTrue($missedForecast->missedForecast());
        
        // Met forecast exactly
        $metForecast = new EconomicIndicator('US_INFLATION', 'CPI', 'US', 2.5, 0, 2.5);
        $this->assertFalse($metForecast->beatForecast());
        $this->assertFalse($metForecast->missedForecast());
    }

    /**
     * Test surprise factor calculation
     */
    public function testSurpriseFactor(): void
    {
        // Positive surprise
        $positiveSurprise = new EconomicIndicator('US_JOBS', 'NFP', 'US', 200000, 0, 150000);
        $this->assertEqualsWithDelta(33.33, $positiveSurprise->getSurpriseFactor(), 0.01);
        
        // Negative surprise
        $negativeSurprise = new EconomicIndicator('US_RETAIL', 'Retail Sales', 'US', 1.0, 0, 2.0);
        $this->assertEquals(-50.0, $negativeSurprise->getSurpriseFactor());
        
        // No forecast
        $noForecast = new EconomicIndicator('TEST', 'Test', 'US', 5.0);
        $this->assertEquals(0.0, $noForecast->getSurpriseFactor());
    }

    /**
     * Test key economic indicators
     */
    public function testKeyEconomicIndicators(): void
    {
        $keyIndicators = EconomicIndicator::getKeyIndicators();
        
        $this->assertIsArray($keyIndicators);
        $this->assertArrayHasKey('US_GDP', $keyIndicators);
        $this->assertArrayHasKey('US_CPI', $keyIndicators);
        $this->assertArrayHasKey('US_UNEMPLOYMENT', $keyIndicators);
        $this->assertArrayHasKey('US_NFP', $keyIndicators);
        $this->assertArrayHasKey('CA_GDP', $keyIndicators);
        $this->assertArrayHasKey('VIX', $keyIndicators);
        
        // Check structure
        $this->assertArrayHasKey('name', $keyIndicators['US_GDP']);
        $this->assertArrayHasKey('country', $keyIndicators['US_GDP']);
        $this->assertArrayHasKey('importance', $keyIndicators['US_GDP']);
        
        $this->assertEquals('GDP Growth Rate', $keyIndicators['US_GDP']['name']);
        $this->assertEquals('US', $keyIndicators['US_GDP']['country']);
        $this->assertEquals('high', $keyIndicators['US_GDP']['importance']);
    }

    /**
     * Test improving indicator detection
     */
    public function testImprovingIndicator(): void
    {
        // GDP improvement (positive change is good)
        $gdpImproving = new EconomicIndicator('GDP', 'GDP', 'US', 3.5, 3.0, 0);
        $this->assertTrue($gdpImproving->isImproving());
        
        // Unemployment improvement (negative change is good)
        $unemploymentImproving = new EconomicIndicator('UNEMPLOYMENT', 'Unemployment', 'US', 4.0, 4.5, 0);
        $this->assertTrue($unemploymentImproving->isImproving());
        
        // GDP declining (negative change is bad)
        $gdpDeclining = new EconomicIndicator('GDP', 'GDP', 'US', 2.5, 3.0, 0);
        $this->assertFalse($gdpDeclining->isImproving());
        
        // Unemployment worsening (positive change is bad)
        $unemploymentWorsening = new EconomicIndicator('UNEMPLOYMENT', 'Unemployment', 'US', 5.0, 4.5, 0);
        $this->assertFalse($unemploymentWorsening->isImproving());
    }

    /**
     * Test market impact weight
     */
    public function testMarketImpactWeight(): void
    {
        $highImpact = new EconomicIndicator('US_GDP', 'GDP', 'US', 3.2, 0, 0, 'quarterly', '', 'high');
        $this->assertEquals(1.0, $highImpact->getMarketImpactWeight());
        
        $mediumImpact = new EconomicIndicator('US_RETAIL', 'Retail Sales', 'US', 1.5, 0, 0, 'monthly', '', 'medium');
        $this->assertEquals(0.6, $mediumImpact->getMarketImpactWeight());
        
        $lowImpact = new EconomicIndicator('US_HOUSING', 'Housing Starts', 'US', 1200000, 0, 0, 'monthly', '', 'low');
        $this->assertEquals(0.3, $lowImpact->getMarketImpactWeight());
        
        $unknownImpact = new EconomicIndicator('TEST', 'Test', 'US', 100, 0, 0, 'monthly', '', 'unknown');
        $this->assertEquals(0.5, $unknownImpact->getMarketImpactWeight());
    }

    /**
     * Test indicator type classification
     */
    public function testIndicatorType(): void
    {
        $leadingIndicator = new EconomicIndicator('PMI', 'Manufacturing PMI', 'US', 55.0);
        $this->assertEquals('Leading', $leadingIndicator->getIndicatorType());
        
        $laggingIndicator = new EconomicIndicator('GDP', 'GDP Growth', 'US', 3.2);
        $this->assertEquals('Lagging', $laggingIndicator->getIndicatorType());
        
        $coincidentIndicator = new EconomicIndicator('CUSTOM', 'Custom Indicator', 'US', 100.0);
        $this->assertEquals('Coincident', $coincidentIndicator->getIndicatorType());
    }

    /**
     * Test array conversion
     */
    public function testToArray(): void
    {
        $releaseDate = new \DateTime('2024-01-15 08:30:00');
        $indicator = new EconomicIndicator(
            'US_CPI',
            'Consumer Price Index',
            'US',
            2.8,
            2.6,
            2.7,
            'monthly',
            '%',
            'high',
            'BLS',
            $releaseDate
        );
        
        $array = $indicator->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('US_CPI', $array['symbol']);
        $this->assertEquals('Consumer Price Index', $array['name']);
        $this->assertEquals('economic', $array['type']);
        $this->assertEquals(2.8, $array['value']);
    $this->assertEqualsWithDelta(0.2, $array['change'], 0.00001);
        $this->assertEqualsWithDelta(7.69, $array['change_percent'], 0.01);
        $this->assertArrayHasKey('metadata', $array);
        $this->assertEquals('US', $array['metadata']['country']);
        $this->assertEquals('monthly', $array['metadata']['frequency']);
        $this->assertEquals('%', $array['metadata']['unit']);
        $this->assertEquals(2.6, $array['metadata']['previous_value']);
        $this->assertEquals(2.7, $array['metadata']['forecast']);
        $this->assertEquals('high', $array['metadata']['importance']);
        $this->assertEquals('BLS', $array['metadata']['source']);
    }

    /**
     * Test metadata operations
     */
    public function testMetadataOperations(): void
    {
        $indicator = new EconomicIndicator('US_GDP', 'GDP', 'US', 3.2);
        
        $metadata = $indicator->getMetadata();
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('country', $metadata);
        $this->assertArrayHasKey('frequency', $metadata);
        $this->assertArrayHasKey('importance', $metadata);
        
        $indicator->setMetadata(array_merge($metadata, ['source' => 'Federal Reserve']));
        $updatedMetadata = $indicator->getMetadata();
        $this->assertEquals('Federal Reserve', $updatedMetadata['source']);
    }

    /**
     * Test edge cases
     */
    public function testEdgeCases(): void
    {
        // Zero values
        $zeroIndicator = new EconomicIndicator('ZERO', 'Zero Test', 'US', 0.0, 0.0, 0.0);
        $this->assertEquals(0.0, $zeroIndicator->getValue());
        $this->assertEquals(0.0, $zeroIndicator->getChangePercent());
        $this->assertEquals(0.0, $zeroIndicator->getSurpriseFactor());
        
        // Large values
        $largeIndicator = new EconomicIndicator('LARGE', 'Large Test', 'US', 1000000.0);
        $this->assertEquals(1000000.0, $largeIndicator->getValue());
        
        // Negative values
        $negativeIndicator = new EconomicIndicator('NEGATIVE', 'Negative Test', 'US', -5.0, 0.0, 2.0);
        $this->assertEquals(-5.0, $negativeIndicator->getValue());
        $this->assertTrue($negativeIndicator->missedForecast());
    }
}
