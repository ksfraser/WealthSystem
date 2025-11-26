<?php
namespace Ksfraser\Finance\MarketFactors\Entities;

/**
 * Forex Rate Entity
 * 
 * Tracks foreign exchange rates and their impact on international investments
 */
class ForexRate extends MarketFactor
{
    private string $baseCurrency;
    private string $quoteCurrency;
    private float $bid;
    private float $ask;
    private float $spread;
    private string $pair;

    public function __construct(
        string $baseCurrency,
        string $quoteCurrency,
        float $rate,
        float $change = 0.0,
        float $changePercent = 0.0,
        float $bid = 0.0,
        float $ask = 0.0,
        ?\DateTime $timestamp = null
    ) {
        $this->baseCurrency = strtoupper($baseCurrency);
        $this->quoteCurrency = strtoupper($quoteCurrency);
        $this->pair = $this->baseCurrency . $this->quoteCurrency;
        $this->bid = $bid > 0 ? $bid : $rate;
        $this->ask = $ask > 0 ? $ask : $rate;
        $this->spread = $this->ask - $this->bid;
        
        parent::__construct(
            $this->pair,
            "{$this->baseCurrency}/{$this->quoteCurrency}",
            'forex',
            $rate,
            $change,
            $changePercent,
            $timestamp,
            [
                'base_currency' => $this->baseCurrency,
                'quote_currency' => $this->quoteCurrency,
                'bid' => $this->bid,
                'ask' => $this->ask,
                'spread' => $this->spread
            ]
        );
    }

    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    public function getQuoteCurrency(): string
    {
        return $this->quoteCurrency;
    }

    public function getPair(): string
    {
        return $this->pair;
    }

    public function getBid(): float
    {
        return $this->bid;
    }

    public function getAsk(): float
    {
        return $this->ask;
    }

    public function getSpread(): float
    {
        return $this->spread;
    }

    public function getSpreadBps(): float
    {
        return ($this->spread / $this->getValue()) * 10000; // Basis points
    }

    /**
     * Convert amount from base to quote currency
     */
    public function convertToQuote(float $baseAmount): float
    {
        return $baseAmount * $this->getValue();
    }

    /**
     * Convert amount from quote to base currency
     */
    public function convertToBase(float $quoteAmount): float
    {
        return $quoteAmount / $this->getValue();
    }

    /**
     * Get inverse rate (quote/base)
     */
    public function getInverseRate(): float
    {
        return 1 / $this->getValue();
    }

    /**
     * Check if currency is strengthening
     */
    public function isStrengthening(): bool
    {
        return $this->getChangePercent() > 0;
    }

    /**
     * Check if currency is weakening
     */
    public function isWeakening(): bool
    {
        return $this->getChangePercent() < 0;
    }

    /**
     * Get major currency pairs
     */
    public static function getMajorPairs(): array
    {
        return [
            'EURUSD' => ['base' => 'EUR', 'quote' => 'USD', 'name' => 'Euro/US Dollar'],
            'GBPUSD' => ['base' => 'GBP', 'quote' => 'USD', 'name' => 'British Pound/US Dollar'],
            'USDJPY' => ['base' => 'USD', 'quote' => 'JPY', 'name' => 'US Dollar/Japanese Yen'],
            'USDCHF' => ['base' => 'USD', 'quote' => 'CHF', 'name' => 'US Dollar/Swiss Franc'],
            'USDCAD' => ['base' => 'USD', 'quote' => 'CAD', 'name' => 'US Dollar/Canadian Dollar'],
            'AUDUSD' => ['base' => 'AUD', 'quote' => 'USD', 'name' => 'Australian Dollar/US Dollar'],
            'NZDUSD' => ['base' => 'NZD', 'quote' => 'USD', 'name' => 'New Zealand Dollar/US Dollar']
        ];
    }

    /**
     * Get commodity currency pairs
     */
    public static function getCommodityPairs(): array
    {
        return [
            'USDCAD' => ['commodity' => 'Oil', 'correlation' => 'negative'],
            'AUDUSD' => ['commodity' => 'Gold', 'correlation' => 'positive'],
            'NZDUSD' => ['commodity' => 'Dairy', 'correlation' => 'positive'],
            'USDNOK' => ['commodity' => 'Oil', 'correlation' => 'negative'],
            'USDRUB' => ['commodity' => 'Oil', 'correlation' => 'negative']
        ];
    }

    /**
     * Calculate cross rate between two non-USD pairs
     */
    public static function calculateCrossRate(ForexRate $rate1, ForexRate $rate2): ?float
    {
        // Both rates must have USD as base or quote
        if ($rate1->getBaseCurrency() === 'USD' && $rate2->getBaseCurrency() === 'USD') {
            return $rate1->getValue() / $rate2->getValue();
        } elseif ($rate1->getQuoteCurrency() === 'USD' && $rate2->getQuoteCurrency() === 'USD') {
            return $rate2->getValue() / $rate1->getValue();
        } elseif ($rate1->getBaseCurrency() === 'USD' && $rate2->getQuoteCurrency() === 'USD') {
            return $rate1->getValue() * $rate2->getValue();
        } elseif ($rate1->getQuoteCurrency() === 'USD' && $rate2->getBaseCurrency() === 'USD') {
            return 1 / ($rate1->getValue() * $rate2->getValue());
        }
        
        return null; // Cannot calculate cross rate
    }

    /**
     * Get volatility category based on recent movement
     */
    public function getVolatilityCategory(): string
    {
        $absChange = abs($this->getChangePercent());
        
        if ($absChange >= 2.0) return 'High';
        if ($absChange >= 1.0) return 'Moderate';
        if ($absChange >= 0.5) return 'Low';
        
        return 'Very Low';
    }
}
