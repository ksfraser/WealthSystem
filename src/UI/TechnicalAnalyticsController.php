<?php
namespace UI;


use Ksfraser\Finance\Services\StockDataService;

class TechnicalAnalyticsController
{
    private $stockDataService;
    private $candlestickAnalyticsCallback;
    private $llmAnalyticsCallback;

    /**
     * @param StockDataService|null $stockDataService
     * @param callable|null $candlestickAnalyticsCallback function($symbol, $period)
     * @param callable|null $llmAnalyticsCallback function($symbol, $period)
     */
    public function __construct($stockDataService = null, $candlestickAnalyticsCallback = null, $llmAnalyticsCallback = null)
    {
        $this->stockDataService = $stockDataService ?: new StockDataService([], null);
        $this->candlestickAnalyticsCallback = $candlestickAnalyticsCallback;
        $this->llmAnalyticsCallback = $llmAnalyticsCallback;
    }

    // Returns technical indicator and OHLCV data for the UI
    public function getRealtimeTechnicalGraph($symbol, $period, $indicators)
    {
        return $this->stockDataService->getStockDataWithIndicators($symbol, $period, $indicators);
    }

    // Returns candlestick pattern analytics for the UI
    public function getCandlestickPatternAnalytics($symbol, $period)
    {
        if (is_callable($this->candlestickAnalyticsCallback)) {
            return call_user_func($this->candlestickAnalyticsCallback, $symbol, $period);
        }
        // Default stub
        return [
            'hammer' => [
                'occurrences' => 0,
                'accuracy' => 0.0,
                'history' => []
            ],
            'doji' => [
                'occurrences' => 0,
                'accuracy' => 0.0,
                'history' => []
            ]
        ];
    }

    // Returns indicator/price mismatches and LLM news search results
    public function getIndicatorPriceMismatchAnalytics($symbol, $period)
    {
        if (is_callable($this->llmAnalyticsCallback)) {
            return call_user_func($this->llmAnalyticsCallback, $symbol, $period);
        }
        // Default stub
        return [
            [
                'date' => date('Y-m-d'),
                'indicator' => 'rsi',
                'type' => 'divergence',
                'llm_news_summary' => 'No news found.'
            ]
        ];
    }
}
