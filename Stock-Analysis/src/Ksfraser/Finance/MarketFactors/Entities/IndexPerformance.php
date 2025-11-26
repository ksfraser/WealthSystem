<?php
namespace Ksfraser\Finance\MarketFactors\Entities;

/**
 * Index Performance Entity
 * 
 * Tracks performance of major market indices (S&P 500, NASDAQ, etc.)
 */
class IndexPerformance extends MarketFactor
{
    private string $indexName;
    private string $region;
    private string $assetClass;
    private int $constituents;
    private float $marketCap;
    private string $currency;

    public function __construct(
        string $indexSymbol,
        string $indexName,
        string $region,
        float $value,
        float $change = 0.0,
        float $changePercent = 0.0,
        string $assetClass = 'equity',
        int $constituents = 0,
        float $marketCap = 0.0,
        string $currency = 'USD',
        ?\DateTime $timestamp = null
    ) {
        parent::__construct(
            $indexSymbol,
            $indexName,
            'index',
            $value,
            $change,
            $changePercent,
            $timestamp,
            [
                'region' => $region,
                'asset_class' => $assetClass,
                'constituents' => $constituents,
                'market_cap' => $marketCap,
                'currency' => $currency
            ]
        );
        
        $this->indexName = $indexName;
        $this->region = $region;
        $this->assetClass = $assetClass;
        $this->constituents = $constituents;
        $this->marketCap = $marketCap;
        $this->currency = $currency;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getAssetClass(): string
    {
        return $this->assetClass;
    }

    public function getConstituents(): int
    {
        return $this->constituents;
    }

    public function getMarketCap(): float
    {
        return $this->marketCap;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Get performance relative to another index
     */
    public function getRelativePerformance(IndexPerformance $benchmark): float
    {
        return $this->getChangePercent() - $benchmark->getChangePercent();
    }

    /**
     * Check if index is outperforming benchmark
     */
    public function isOutperforming(IndexPerformance $benchmark): bool
    {
        return $this->getRelativePerformance($benchmark) > 0;
    }

    /**
     * Get major global indices
     */
    public static function getMajorIndices(): array
    {
        return [
            // US Indices
            'SPY' => ['name' => 'S&P 500', 'region' => 'US', 'type' => 'Large Cap'],
            'QQQ' => ['name' => 'NASDAQ 100', 'region' => 'US', 'type' => 'Technology'],
            'IWM' => ['name' => 'Russell 2000', 'region' => 'US', 'type' => 'Small Cap'],
            'DIA' => ['name' => 'Dow Jones', 'region' => 'US', 'type' => 'Blue Chip'],
            
            // International Indices
            'EFA' => ['name' => 'EAFE', 'region' => 'Developed Ex-US', 'type' => 'International'],
            'EEM' => ['name' => 'Emerging Markets', 'region' => 'Emerging', 'type' => 'International'],
            'VTI' => ['name' => 'Total Stock Market', 'region' => 'US', 'type' => 'Broad Market'],
            'VXUS' => ['name' => 'Total International', 'region' => 'International', 'type' => 'Broad Market'],
            
            // Canadian Indices
            'VTI.TO' => ['name' => 'TSX Composite', 'region' => 'Canada', 'type' => 'Broad Market'],
            'TDB902' => ['name' => 'Canadian Index', 'region' => 'Canada', 'type' => 'Broad Market'],
            
            // Bond Indices
            'BND' => ['name' => 'Total Bond Market', 'region' => 'US', 'type' => 'Fixed Income'],
            'TLT' => ['name' => '20+ Year Treasury', 'region' => 'US', 'type' => 'Government Bonds'],
            
            // Commodity Indices
            'GLD' => ['name' => 'Gold', 'region' => 'Global', 'type' => 'Precious Metals'],
            'SLV' => ['name' => 'Silver', 'region' => 'Global', 'type' => 'Precious Metals'],
            'USO' => ['name' => 'Oil', 'region' => 'Global', 'type' => 'Energy'],
            'UNG' => ['name' => 'Natural Gas', 'region' => 'Global', 'type' => 'Energy']
        ];
    }

    /**
     * Get volatility category based on recent performance
     */
    public function getVolatilityCategory(): string
    {
        $absChange = abs($this->getChangePercent());
        
        if ($absChange >= 3.0) return 'High';
        if ($absChange >= 2.0) return 'Moderate';
        if ($absChange >= 1.0) return 'Low';
        
        return 'Very Low';
    }
}
