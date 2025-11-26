<?php
namespace Ksfraser\Finance\MarketFactors\Entities;

/**
 * Sector Performance Entity
 * 
 * Tracks performance of market sectors (Technology, Healthcare, Energy, etc.)
 */
class SectorPerformance extends MarketFactor
{
    private string $sectorName;
    private array $topStocks;
    private float $marketCapWeight;
    private string $classification; // GICS, ICB, etc.

    public function __construct(
        string $sectorCode,
        string $sectorName,
        float $performance,
        float $change = 0.0,
        float $changePercent = 0.0,
        array $topStocks = [],
        float $marketCapWeight = 0.0,
        string $classification = 'GICS',
        ?\DateTime $timestamp = null
    ) {
        parent::__construct(
            $sectorCode,
            $sectorName,
            'sector',
            $performance,
            $change,
            $changePercent,
            $timestamp,
            [
                'classification' => $classification,
                'market_cap_weight' => $marketCapWeight,
                'top_stocks' => $topStocks
            ]
        );
        
        $this->sectorName = $sectorName;
        $this->topStocks = $topStocks;
        $this->marketCapWeight = $marketCapWeight;
        $this->classification = $classification;
    }

    public function getSectorName(): string
    {
        return $this->sectorName;
    }

    public function getTopStocks(): array
    {
        return $this->topStocks;
    }

    public function getMarketCapWeight(): float
    {
        return $this->marketCapWeight;
    }

    public function getClassification(): string
    {
        return $this->classification;
    }

    public function setTopStocks(array $topStocks): void
    {
        $this->topStocks = $topStocks;
        $this->addMetadata('top_stocks', $topStocks);
    }

    public function addTopStock(string $symbol, float $weight = 0.0): void
    {
        $this->topStocks[] = ['symbol' => $symbol, 'weight' => $weight];
        $this->addMetadata('top_stocks', $this->topStocks);
    }

    /**
     * Get sector performance relative to market
     */
    public function getRelativePerformance(float $marketPerformance): float
    {
        return $this->getChangePercent() - $marketPerformance;
    }

    /**
     * Check if sector is outperforming market
     */
    public function isOutperforming(float $marketPerformance): bool
    {
        return $this->getRelativePerformance($marketPerformance) > 0;
    }

    /**
     * Get standard GICS sectors
     */
    public static function getGICSSectors(): array
    {
        return [
            'XLE' => 'Energy',
            'XLB' => 'Materials',
            'XLI' => 'Industrials',
            'XLY' => 'Consumer Discretionary',
            'XLP' => 'Consumer Staples',
            'XLV' => 'Health Care',
            'XLF' => 'Financials',
            'XLK' => 'Information Technology',
            'XLC' => 'Communication Services',
            'XLU' => 'Utilities',
            'XLRE' => 'Real Estate'
        ];
    }
}
