<?php

declare(strict_types=1);

namespace Tests\Indicators;

use PHPUnit\Framework\TestCase;
use App\Indicators\BollingerBands;
use App\Indicators\RSI;
use App\Indicators\MACD;
use App\Indicators\Stochastic;
use App\Indicators\ATR;

class IndicatorsTest extends TestCase
{
    public function testBollingerBands(): void
    {
        $bb = new BollingerBands();
        $prices = [100, 102, 101, 103, 105, 104, 106, 108, 107, 109, 
                   111, 110, 112, 114, 113, 115, 117, 116, 118, 120];
        
        $bands = $bb->calculate($prices, 20, 2.0);
        
        $this->assertArrayHasKey('middle', $bands);
        $this->assertArrayHasKey('upper', $bands);
        $this->assertArrayHasKey('lower', $bands);
        $this->assertArrayHasKey('bandwidth', $bands);
        
        $this->assertGreaterThan($bands['middle'], $bands['upper']);
        $this->assertLessThan($bands['middle'], $bands['lower']);
    }
    
    public function testBollingerBandsInsufficientData(): void
    {
        $bb = new BollingerBands();
        $prices = [100, 102, 101];
        
        $bands = $bb->calculate($prices, 20, 2.0);
        
        $this->assertSame(0.0, $bands['middle']);
    }
    
    public function testBollingerBandsPercentB(): void
    {
        $bb = new BollingerBands();
        $prices = array_fill(0, 20, 100.0);
        
        $percentB = $bb->getPercentB(100.0, $prices);
        
        $this->assertEqualsWithDelta(0.5, $percentB, 0.1);
    }
    
    public function testRSICalculation(): void
    {
        $rsi = new RSI();
        $prices = [44, 44.34, 44.09, 43.61, 44.33, 44.83, 45.10, 45.42,
                   45.84, 46.08, 45.89, 46.03, 45.61, 46.28, 46.28, 46.00];
        
        $value = $rsi->calculate($prices, 14);
        
        $this->assertGreaterThan(0, $value);
        $this->assertLessThan(100, $value);
    }
    
    public function testRSIOversold(): void
    {
        $rsi = new RSI();
        
        $this->assertTrue($rsi->isOversold(25.0));
        $this->assertFalse($rsi->isOversold(35.0));
    }
    
    public function testRSIOverbought(): void
    {
        $rsi = new RSI();
        
        $this->assertTrue($rsi->isOverbought(75.0));
        $this->assertFalse($rsi->isOverbought(65.0));
    }
    
    public function testMACDCalculation(): void
    {
        $macd = new MACD();
        $prices = array_merge(
            array_fill(0, 15, 100.0),
            array_fill(0, 15, 105.0)
        );
        
        $result = $macd->calculate($prices, 12, 26, 9);
        
        $this->assertArrayHasKey('macd', $result);
        $this->assertArrayHasKey('signal', $result);
        $this->assertArrayHasKey('histogram', $result);
    }
    
    public function testMACDInsufficientData(): void
    {
        $macd = new MACD();
        $prices = [100, 102, 101];
        
        $result = $macd->calculate($prices, 12, 26, 9);
        
        $this->assertSame(0.0, $result['macd']);
    }
    
    public function testMACDBullishCrossover(): void
    {
        $macd = new MACD();
        
        $previous = ['macd' => -1.0, 'signal' => 0.5, 'histogram' => -1.5];
        $current = ['macd' => 1.0, 'signal' => 0.5, 'histogram' => 0.5];
        
        $this->assertTrue($macd->isBullishCrossover($current, $previous));
    }
    
    public function testMACDBearishCrossover(): void
    {
        $macd = new MACD();
        
        $previous = ['macd' => 1.0, 'signal' => 0.5, 'histogram' => 0.5];
        $current = ['macd' => -1.0, 'signal' => 0.5, 'histogram' => -1.5];
        
        $this->assertTrue($macd->isBearishCrossover($current, $previous));
    }
    
    public function testStochasticCalculation(): void
    {
        $stoch = new Stochastic();
        
        $highs = [110, 112, 111, 113, 115, 114, 116, 118, 117, 119,
                  121, 120, 122, 124, 123];
        $lows = [100, 102, 101, 103, 105, 104, 106, 108, 107, 109,
                 111, 110, 112, 114, 113];
        $closes = [105, 107, 106, 108, 110, 109, 111, 113, 112, 114,
                   116, 115, 117, 119, 118];
        
        $result = $stoch->calculate($highs, $lows, $closes, 14);
        
        $this->assertArrayHasKey('k', $result);
        $this->assertArrayHasKey('d', $result);
        $this->assertGreaterThanOrEqual(0, $result['k']);
        $this->assertLessThanOrEqual(100, $result['k']);
    }
    
    public function testStochasticOversold(): void
    {
        $stoch = new Stochastic();
        
        $this->assertTrue($stoch->isOversold(['k' => 15, 'd' => 15]));
        $this->assertFalse($stoch->isOversold(['k' => 25, 'd' => 25]));
    }
    
    public function testStochasticOverbought(): void
    {
        $stoch = new Stochastic();
        
        $this->assertTrue($stoch->isOverbought(['k' => 85, 'd' => 85]));
        $this->assertFalse($stoch->isOverbought(['k' => 75, 'd' => 75]));
    }
    
    public function testATRCalculation(): void
    {
        $atr = new ATR();
        
        $highs = array_fill(0, 20, 110.0);
        $lows = array_fill(0, 20, 100.0);
        $closes = array_fill(0, 20, 105.0);
        
        $value = $atr->calculate($highs, $lows, $closes, 14);
        
        $this->assertGreaterThan(0, $value);
    }
    
    public function testATRInsufficientData(): void
    {
        $atr = new ATR();
        
        $result = $atr->calculate([110], [100], [105], 14);
        
        $this->assertSame(0.0, $result);
    }
    
    public function testATRVolatilityPercent(): void
    {
        $atr = new ATR();
        
        $volatility = $atr->getVolatilityPercent(5.0, 100.0);
        
        $this->assertSame(5.0, $volatility);
    }
    
    public function testATRVolatilityPercentZeroPrice(): void
    {
        $atr = new ATR();
        
        $volatility = $atr->getVolatilityPercent(5.0, 0.0);
        
        $this->assertSame(0.0, $volatility);
    }
}
