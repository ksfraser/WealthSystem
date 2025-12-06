<?php

declare(strict_types=1);

namespace Tests\Crypto;

use App\Crypto\CryptoDataService;
use PHPUnit\Framework\TestCase;

/**
 * CryptoDataService Test Suite
 * 
 * Tests cryptocurrency data fetching including:
 * - Real-time crypto prices (Bitcoin, Ethereum)
 * - 24/7 price availability
 * - Multiple exchange aggregation
 * - ETF NAV tracking
 * - Data caching
 * 
 * @package Tests\Crypto
 */
class CryptoDataServiceTest extends TestCase
{
    private CryptoDataService $service;
    
    protected function setUp(): void
    {
        $this->service = new CryptoDataService([
            'api_key' => 'test_key',
            'cache_ttl' => 60,
            'timeout' => 5
        ]);
    }
    
    public function testItFetchesBitcoinPrice(): void
    {
        $price = $this->service->getCryptoPrice('BTC');
        
        $this->assertIsArray($price);
        $this->assertArrayHasKey('symbol', $price);
        $this->assertArrayHasKey('price', $price);
        $this->assertArrayHasKey('timestamp', $price);
        $this->assertEquals('BTC', $price['symbol']);
        $this->assertGreaterThan(0, $price['price']);
    }
    
    public function testItFetchesEthereumPrice(): void
    {
        $price = $this->service->getCryptoPrice('ETH');
        
        $this->assertIsArray($price);
        $this->assertEquals('ETH', $price['symbol']);
        $this->assertGreaterThan(0, $price['price']);
    }
    
    public function testItFetchesMultipleCryptoPrices(): void
    {
        $prices = $this->service->getMultiplePrices(['BTC', 'ETH']);
        
        $this->assertIsArray($prices);
        $this->assertCount(2, $prices);
        $this->assertArrayHasKey('BTC', $prices);
        $this->assertArrayHasKey('ETH', $prices);
    }
    
    public function testItReturnsHistoricalData(): void
    {
        $history = $this->service->getHistoricalPrices('BTC', 7);
        
        $this->assertIsArray($history);
        $this->assertGreaterThan(0, count($history));
        
        foreach ($history as $data) {
            $this->assertArrayHasKey('date', $data);
            $this->assertArrayHasKey('price', $data);
            $this->assertArrayHasKey('volume', $data);
        }
    }
    
    public function testItCalculates24HourChange(): void
    {
        $change = $this->service->get24HourChange('BTC');
        
        $this->assertIsArray($change);
        $this->assertArrayHasKey('change_percent', $change);
        $this->assertArrayHasKey('change_amount', $change);
        $this->assertArrayHasKey('high_24h', $change);
        $this->assertArrayHasKey('low_24h', $change);
    }
    
    public function testItTracksCryptoVolatility(): void
    {
        $volatility = $this->service->calculateVolatility('BTC', 30);
        
        $this->assertIsFloat($volatility);
        $this->assertGreaterThanOrEqual(0, $volatility);
    }
    
    public function testItFetchesETFNav(): void
    {
        $nav = $this->service->getETFNav('BTCC.TO');
        
        $this->assertIsArray($nav);
        $this->assertArrayHasKey('nav', $nav);
        $this->assertArrayHasKey('nav_date', $nav);
        $this->assertArrayHasKey('symbol', $nav);
        $this->assertGreaterThan(0, $nav['nav']);
    }
    
    public function testItCalculatesIntraDayNav(): void
    {
        $iNav = $this->service->calculateIntraDayNav('BTCC.TO', 'BTC');
        
        $this->assertIsArray($iNav);
        $this->assertArrayHasKey('inav', $iNav);
        $this->assertArrayHasKey('underlying_price', $iNav);
        $this->assertArrayHasKey('calculation_time', $iNav);
        $this->assertGreaterThan(0, $iNav['inav']);
    }
    
    public function testItDetectsMarketStatus(): void
    {
        $status = $this->service->getMarketStatus();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('is_market_open', $status);
        $this->assertArrayHasKey('next_open', $status);
        $this->assertArrayHasKey('next_close', $status);
        $this->assertIsBool($status['is_market_open']);
    }
    
    public function testItTracksOvernightMoves(): void
    {
        $overnight = $this->service->getOvernightMove('BTC');
        
        $this->assertIsArray($overnight);
        $this->assertArrayHasKey('change_since_close', $overnight);
        $this->assertArrayHasKey('last_close_time', $overnight);
        $this->assertArrayHasKey('current_price', $overnight);
    }
    
    public function testItFetchesMultipleExchangePrices(): void
    {
        $prices = $this->service->getExchangePrices('BTC', ['binance', 'coinbase', 'kraken']);
        
        $this->assertIsArray($prices);
        $this->assertGreaterThan(0, count($prices));
        
        foreach ($prices as $exchange => $data) {
            $this->assertArrayHasKey('price', $data);
            $this->assertArrayHasKey('volume', $data);
        }
    }
    
    public function testItCalculatesAveragePrice(): void
    {
        $avg = $this->service->getVolumeWeightedAveragePrice('BTC');
        
        $this->assertIsFloat($avg);
        $this->assertGreaterThan(0, $avg);
    }
    
    public function testItCachesCryptoPrices(): void
    {
        // First call
        $start = microtime(true);
        $price1 = $this->service->getCryptoPrice('BTC');
        $time1 = microtime(true) - $start;
        
        // Second call (should be cached)
        $start = microtime(true);
        $price2 = $this->service->getCryptoPrice('BTC');
        $time2 = microtime(true) - $start;
        
        $this->assertEquals($price1['price'], $price2['price']);
        $this->assertLessThan($time1, $time2); // Cached call should be faster
    }
    
    public function testItHandlesInvalidSymbol(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getCryptoPrice('INVALID');
    }
    
    public function testItHandlesAPITimeout(): void
    {
        $service = new CryptoDataService(['timeout' => 0.001]);
        
        $this->expectException(\RuntimeException::class);
        $service->getCryptoPrice('BTC');
    }
    
    public function testItValidatesCryptoSymbol(): void
    {
        $this->assertTrue($this->service->isValidSymbol('BTC'));
        $this->assertTrue($this->service->isValidSymbol('ETH'));
        $this->assertFalse($this->service->isValidSymbol('INVALID'));
    }
    
    public function testItFormatsPrice(): void
    {
        $formatted = $this->service->formatPrice('BTC', 45123.456789);
        
        $this->assertIsString($formatted);
        $this->assertStringContainsString('45,123.46', $formatted); // PHP rounds to .46
    }
    
    public function testItConvertsToUSD(): void
    {
        $usd = $this->service->convertToUSD('BTC', 1.5);
        
        $this->assertIsArray($usd);
        $this->assertArrayHasKey('amount', $usd);
        $this->assertArrayHasKey('currency', $usd);
        $this->assertEquals('USD', $usd['currency']);
    }
    
    public function testItGetsCryptoMarketCap(): void
    {
        $marketCap = $this->service->getMarketCap('BTC');
        
        $this->assertIsArray($marketCap);
        $this->assertArrayHasKey('market_cap', $marketCap);
        $this->assertArrayHasKey('rank', $marketCap);
        $this->assertGreaterThan(0, $marketCap['market_cap']);
    }
    
    public function testItTracksSupplyData(): void
    {
        $supply = $this->service->getSupplyData('BTC');
        
        $this->assertIsArray($supply);
        $this->assertArrayHasKey('circulating_supply', $supply);
        $this->assertArrayHasKey('max_supply', $supply);
        $this->assertGreaterThan(0, $supply['circulating_supply']);
    }
}
