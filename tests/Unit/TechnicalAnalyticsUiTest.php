<?php

require_once __DIR__ . '/../../src/UI/TechnicalAnalyticsController.php';

use PHPUnit\Framework\TestCase;

class TechnicalAnalyticsUiTest extends TestCase
{
    /**
     * @covers UI\TechnicalAnalyticsController::getRealtimeTechnicalGraph
     * LLM/TDD: UI must display real-time technical indicator graphs (RSI, MACD, SMA, EMA, etc.) per symbol.
     */
    public function testRealtimeTechnicalGraphRendersIndicators()
    {
        $mockData = [
            'ohlcv' => [
                ['Date' => '2025-10-01', 'Open' => 100, 'High' => 110, 'Low' => 95, 'Close' => 105, 'Volume' => 10000],
                ['Date' => '2025-10-02', 'Open' => 106, 'High' => 112, 'Low' => 104, 'Close' => 110, 'Volume' => 12000],
                ['Date' => '2025-10-03', 'Open' => 111, 'High' => 115, 'Low' => 109, 'Close' => 113, 'Volume' => 13000],
            ],
            'indicators' => [
                'rsi' => ['2025-10-01' => 50, '2025-10-02' => 60, '2025-10-03' => 70],
                'macd' => ['2025-10-01' => 1.2, '2025-10-02' => 1.5, '2025-10-03' => 1.7],
                'sma' => ['2025-10-01' => 102, '2025-10-02' => 108, '2025-10-03' => 111],
                'ema' => ['2025-10-01' => 101, '2025-10-02' => 107, '2025-10-03' => 110],
            ]
        ];
        $mockStockDataService = $this->getMockBuilder(\Ksfraser\Finance\Services\StockDataService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStockDataWithIndicators'])
            ->getMock();
        $mockStockDataService->method('getStockDataWithIndicators')->willReturn($mockData);
        $controller = new \UI\TechnicalAnalyticsController($mockStockDataService);
        $result = $controller->getRealtimeTechnicalGraph('AAPL', '1y', ['rsi','macd','sma','ema']);
        $this->assertArrayHasKey('ohlcv', $result);
        $this->assertArrayHasKey('indicators', $result);
        $this->assertArrayHasKey('rsi', $result['indicators']);
        $this->assertArrayHasKey('macd', $result['indicators']);
        $this->assertArrayHasKey('sma', $result['indicators']);
        $this->assertArrayHasKey('ema', $result['indicators']);
    }

    /**
     * @covers UI\TechnicalAnalyticsController::getCandlestickPatternAnalytics
     * LLM/TDD: UI must display candlestick pattern occurrences and prediction accuracy.
     */
    public function testCandlestickPatternAnalytics()
    {
        $mockPatterns = [
            'hammer' => [
                'occurrences' => 5,
                'accuracy' => 0.8,
                'history' => [
                    ['date' => '2025-10-01', 'result' => 'win'],
                    ['date' => '2025-10-02', 'result' => 'loss'],
                ]
            ],
            'doji' => [
                'occurrences' => 3,
                'accuracy' => 0.67,
                'history' => [
                    ['date' => '2025-10-03', 'result' => 'win'],
                ]
            ]
        ];
        $mockStockDataService2 = $this->getMockBuilder(\Ksfraser\Finance\Services\StockDataService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $controller = new \UI\TechnicalAnalyticsController($mockStockDataService2, function($symbol, $period) use ($mockPatterns) {
            return $mockPatterns;
        });
        $result = $controller->getCandlestickPatternAnalytics('AAPL', '1y');
        $this->assertArrayHasKey('hammer', $result);
        $this->assertArrayHasKey('doji', $result);
        $this->assertEquals(5, $result['hammer']['occurrences']);
        $this->assertEquals(0.8, $result['hammer']['accuracy']);
        $this->assertEquals('win', $result['hammer']['history'][0]['result']);
    }

    /**
     * @covers UI\TechnicalAnalyticsController::getIndicatorPriceMismatchAnalytics
     * LLM/TDD: UI must display indicator/price mismatches and trigger LLM news search.
     */
    public function testIndicatorPriceMismatchAnalyticsAndLLMNewsSearch()
    {
        $mockMismatches = [
            [
                'date' => '2025-10-02',
                'indicator' => 'rsi',
                'type' => 'divergence',
                'llm_news_summary' => 'News event: earnings surprise.'
            ]
        ];
        $mockStockDataService3 = $this->getMockBuilder(\Ksfraser\Finance\Services\StockDataService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $controller = new \UI\TechnicalAnalyticsController($mockStockDataService3, null, function($symbol, $period) use ($mockMismatches) {
            return $mockMismatches;
        });
        $result = $controller->getIndicatorPriceMismatchAnalytics('AAPL', '1y');
        $this->assertIsArray($result);
        $this->assertEquals('rsi', $result[0]['indicator']);
        $this->assertEquals('divergence', $result[0]['type']);
        $this->assertStringContainsString('earnings', $result[0]['llm_news_summary']);
    }
}
