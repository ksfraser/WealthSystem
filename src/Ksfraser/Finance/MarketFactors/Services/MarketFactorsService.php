<?php
namespace Ksfraser\Finance\MarketFactors\Services;

use Ksfraser\Finance\MarketFactors\Entities\MarketFactor;
use Ksfraser\Finance\MarketFactors\Entities\SectorPerformance;
use Ksfraser\Finance\MarketFactors\Entities\IndexPerformance;
use Ksfraser\Finance\MarketFactors\Entities\ForexRate;
use Ksfraser\Finance\MarketFactors\Entities\EconomicIndicator;
use Ksfraser\Finance\MarketFactors\Repository\MarketFactorsRepository;

/**
 * Market Factors Service
 * 
 * Central service for managing and analyzing market factors with database integration
 */
class MarketFactorsService
{
    private array $factors = [];
    private array $correlations = [];
    private array $historicalData = [];
    private MarketFactorsRepository $repository;

    public function __construct(MarketFactorsRepository $repository = null)
    {
        $this->factors = [];
        $this->correlations = [];
        $this->historicalData = [];
        $this->repository = $repository;
    }

    /**
     * Add a market factor
     */
    public function addFactor(MarketFactor $factor): void
    {
        $this->factors[$factor->getSymbol()] = $factor;
        
        // Persist to database if repository is available
        if ($this->repository) {
            $this->repository->saveFactor($factor);
        }
    }

    /**
     * Get a specific factor by symbol
     */
    public function getFactor(string $symbol): ?MarketFactor
    {
        // First check in-memory cache
        if (isset($this->factors[$symbol])) {
            return $this->factors[$symbol];
        }
        
        // Then check database if repository is available
        if ($this->repository) {
            $factor = $this->repository->getFactorBySymbol($symbol);
            if ($factor) {
                $this->factors[$symbol] = $factor; // Cache it
                return $factor;
            }
        }
        
        return null;
    }

    /**
     * Get all factors
     */
    public function getAllFactors(): array
    {
        // If repository is available, get fresh data from database
        if ($this->repository) {
            $dbFactors = $this->repository->getAllFactors();
            foreach ($dbFactors as $factor) {
                $this->factors[$factor->getSymbol()] = $factor;
            }
        }
        
        return $this->factors;
    }

    /**
     * Get factors by type
     */
    public function getFactorsByType(string $type): array
    {
        return array_filter($this->factors, function($factor) use ($type) {
            return $factor->getType() === $type;
        });
    }

    /**
     * Get sector performance factors
     */
    public function getSectorPerformances(): array
    {
        return array_filter($this->factors, function($factor) {
            return $factor instanceof SectorPerformance;
        });
    }

    /**
     * Get index performance factors
     */
    public function getIndexPerformances(): array
    {
        return array_filter($this->factors, function($factor) {
            return $factor instanceof IndexPerformance;
        });
    }

    /**
     * Get forex rate factors
     */
    public function getForexRates(): array
    {
        return array_filter($this->factors, function($factor) {
            return $factor instanceof ForexRate;
        });
    }

    /**
     * Get economic indicator factors
     */
    public function getEconomicIndicators(): array
    {
        return array_filter($this->factors, function($factor) {
            return $factor instanceof EconomicIndicator;
        });
    }

    /**
     * Filter factors by various criteria
     */
    public function filterFactors(array $criteria): array
    {
        $filtered = $this->factors;

        if (isset($criteria['type'])) {
            $filtered = array_filter($filtered, function($factor) use ($criteria) {
                return $factor->getType() === $criteria['type'];
            });
        }

        if (isset($criteria['bullish'])) {
            $filtered = array_filter($filtered, function($factor) use ($criteria) {
                return $criteria['bullish'] ? $factor->isBullish() : $factor->isBearish();
            });
        }

        if (isset($criteria['min_strength'])) {
            $filtered = array_filter($filtered, function($factor) use ($criteria) {
                return $factor->getSignalStrength() >= $criteria['min_strength'];
            });
        }

        if (isset($criteria['max_age_minutes'])) {
            $filtered = array_filter($filtered, function($factor) use ($criteria) {
                return !$factor->isStale($criteria['max_age_minutes']);
            });
        }

        if (isset($criteria['country'])) {
            $filtered = array_filter($filtered, function($factor) use ($criteria) {
                if ($factor instanceof EconomicIndicator) {
                    return $factor->getCountry() === $criteria['country'];
                }
                return true;
            });
        }

        if (isset($criteria['region'])) {
            $filtered = array_filter($filtered, function($factor) use ($criteria) {
                if ($factor instanceof IndexPerformance) {
                    return $factor->getRegion() === $criteria['region'];
                }
                return true;
            });
        }

        return $filtered;
    }

    /**
     * Sort factors by various criteria
     */
    public function sortFactors(array $factors, string $sortBy = 'change_percent', string $direction = 'desc'): array
    {
        usort($factors, function($a, $b) use ($sortBy, $direction) {
            $valueA = $this->getSortValue($a, $sortBy);
            $valueB = $this->getSortValue($b, $sortBy);

            if ($direction === 'desc') {
                return $valueB <=> $valueA;
            } else {
                return $valueA <=> $valueB;
            }
        });

        return $factors;
    }

    /**
     * Get sort value for a factor
     */
    private function getSortValue(MarketFactor $factor, string $sortBy)
    {
        switch ($sortBy) {
            case 'change_percent':
                return $factor->getChangePercent();
            case 'change':
                return $factor->getChange();
            case 'value':
                return $factor->getValue();
            case 'signal_strength':
                return $factor->getSignalStrength();
            case 'age':
                return $factor->getDataAge();
            case 'name':
                return $factor->getName();
            case 'symbol':
                return $factor->getSymbol();
            default:
                return $factor->getChangePercent();
        }
    }

    /**
     * Get top performers
     */
    public function getTopPerformers(int $limit = 10, string $type = null): array
    {
        $factors = $type ? $this->getFactorsByType($type) : $this->factors;
        $sorted = $this->sortFactors($factors, 'change_percent', 'desc');
        return array_slice($sorted, 0, $limit);
    }

    /**
     * Get worst performers
     */
    public function getWorstPerformers(int $limit = 10, string $type = null): array
    {
        $factors = $type ? $this->getFactorsByType($type) : $this->factors;
        $sorted = $this->sortFactors($factors, 'change_percent', 'asc');
        return array_slice($sorted, 0, $limit);
    }

    /**
     * Get overall market sentiment
     */
    public function getMarketSentiment(): array
    {
        $bullishCount = 0;
        $bearishCount = 0;
        $totalStrength = 0;
        $factorCount = 0;

        foreach ($this->factors as $factor) {
            if (!$factor->isStale(60)) { // Only consider fresh data
                if ($factor->isBullish()) {
                    $bullishCount++;
                } elseif ($factor->isBearish()) {
                    $bearishCount++;
                }
                $totalStrength += $factor->getSignalStrength();
                $factorCount++;
            }
        }

        if ($factorCount === 0) {
            return [
                'sentiment' => 'neutral',
                'confidence' => 0,
                'bullish_factors' => 0,
                'bearish_factors' => 0,
                'average_strength' => 0
            ];
        }

        $bullishRatio = $bullishCount / $factorCount;
        $averageStrength = $totalStrength / $factorCount;

        $sentiment = 'neutral';
        if ($bullishRatio > 0.6) {
            $sentiment = 'bullish';
        } elseif ($bullishRatio < 0.4) {
            $sentiment = 'bearish';
        }

        return [
            'sentiment' => $sentiment,
            'confidence' => $averageStrength,
            'bullish_factors' => $bullishCount,
            'bearish_factors' => $bearishCount,
            'neutral_factors' => $factorCount - $bullishCount - $bearishCount,
            'total_factors' => $factorCount,
            'bullish_ratio' => $bullishRatio,
            'average_strength' => $averageStrength
        ];
    }

    /**
     * Analyze correlation between factors
     */
    public function analyzeCorrelation(string $symbol1, string $symbol2, int $periods = 30): ?float
    {
        if (!isset($this->correlations[$symbol1][$symbol2])) {
            return null;
        }

        return $this->correlations[$symbol1][$symbol2];
    }

    /**
     * Set correlation between two factors
     */
    public function setCorrelation(string $symbol1, string $symbol2, float $correlation): void
    {
        $this->correlations[$symbol1][$symbol2] = $correlation;
        $this->correlations[$symbol2][$symbol1] = $correlation; // Symmetric
    }

    /**
     * Get factors that are highly correlated with a given factor
     */
    public function getCorrelatedFactors(string $symbol, float $minCorrelation = 0.7): array
    {
        $correlated = [];

        if (isset($this->correlations[$symbol])) {
            foreach ($this->correlations[$symbol] as $otherSymbol => $correlation) {
                if (abs($correlation) >= $minCorrelation && $otherSymbol !== $symbol) {
                    $correlated[] = [
                        'symbol' => $otherSymbol,
                        'correlation' => $correlation,
                        'factor' => $this->getFactor($otherSymbol)
                    ];
                }
            }
        }

        return $correlated;
    }

    /**
     * Get market factor summary for dashboard
     */
    public function getMarketSummary(): array
    {
        $sentiment = $this->getMarketSentiment();
        
        return [
            'timestamp' => new \DateTime(),
            'sentiment' => $sentiment,
            'top_performers' => $this->getTopPerformers(5),
            'worst_performers' => $this->getWorstPerformers(5),
            'sectors' => $this->getSectorSummary(),
            'indices' => $this->getIndexSummary(),
            'forex' => $this->getForexSummary(),
            'economics' => $this->getEconomicsSummary(),
            'total_factors' => count($this->factors),
            'fresh_data_count' => count($this->filterFactors(['max_age_minutes' => 60]))
        ];
    }

    /**
     * Get sector performance summary
     */
    private function getSectorSummary(): array
    {
        $sectors = $this->getSectorPerformances();
        $topSector = $this->getTopPerformers(1, 'sector')[0] ?? null;
        $worstSector = $this->getWorstPerformers(1, 'sector')[0] ?? null;

        return [
            'count' => count($sectors),
            'top_performer' => $topSector ? [
                'symbol' => $topSector->getSymbol(),
                'name' => $topSector->getName(),
                'change_percent' => $topSector->getChangePercent()
            ] : null,
            'worst_performer' => $worstSector ? [
                'symbol' => $worstSector->getSymbol(),
                'name' => $worstSector->getName(),
                'change_percent' => $worstSector->getChangePercent()
            ] : null
        ];
    }

    /**
     * Get index performance summary
     */
    private function getIndexSummary(): array
    {
        $indices = $this->getIndexPerformances();
        $topIndex = $this->getTopPerformers(1, 'index')[0] ?? null;
        $worstIndex = $this->getWorstPerformers(1, 'index')[0] ?? null;

        return [
            'count' => count($indices),
            'top_performer' => $topIndex ? [
                'symbol' => $topIndex->getSymbol(),
                'name' => $topIndex->getName(),
                'change_percent' => $topIndex->getChangePercent()
            ] : null,
            'worst_performer' => $worstIndex ? [
                'symbol' => $worstIndex->getSymbol(),
                'name' => $worstIndex->getName(),
                'change_percent' => $worstIndex->getChangePercent()
            ] : null
        ];
    }

    /**
     * Get forex summary
     */
    private function getForexSummary(): array
    {
        $forexRates = $this->getForexRates();
        $strongestCurrency = $this->getTopPerformers(1, 'forex')[0] ?? null;
        $weakestCurrency = $this->getWorstPerformers(1, 'forex')[0] ?? null;

        return [
            'count' => count($forexRates),
            'strongest_currency' => $strongestCurrency ? [
                'pair' => $strongestCurrency->getSymbol(),
                'name' => $strongestCurrency->getName(),
                'change_percent' => $strongestCurrency->getChangePercent()
            ] : null,
            'weakest_currency' => $weakestCurrency ? [
                'pair' => $weakestCurrency->getSymbol(),
                'name' => $weakestCurrency->getName(),
                'change_percent' => $weakestCurrency->getChangePercent()
            ] : null
        ];
    }

    /**
     * Get economics summary
     */
    private function getEconomicsSummary(): array
    {
        $indicators = $this->getEconomicIndicators();
        $improving = array_filter($indicators, function($indicator) {
            return $indicator->isImproving();
        });
        $deteriorating = array_filter($indicators, function($indicator) {
            return !$indicator->isImproving() && $indicator->getChangePercent() != 0;
        });

        return [
            'count' => count($indicators),
            'improving_count' => count($improving),
            'deteriorating_count' => count($deteriorating),
            'stable_count' => count($indicators) - count($improving) - count($deteriorating)
        ];
    }

    /**
     * Update a factor's value
     */
    public function updateFactor(string $symbol, float $newValue, ?\DateTime $timestamp = null): bool
    {
        if (!isset($this->factors[$symbol])) {
            return false;
        }

        $factor = $this->factors[$symbol];
        $oldValue = $factor->getValue();
        $change = $newValue - $oldValue;
        $changePercent = $oldValue != 0 ? ($change / $oldValue) * 100 : 0;

        $factor->setValue($newValue);
        $factor->setChange($change);
        $factor->setChangePercent($changePercent);
        $factor->setTimestamp($timestamp ?? new \DateTime());

        return true;
    }

    /**
     * Remove stale factors
     */
    public function removeStaleFactors(int $maxAgeMinutes = 120): int
    {
        $removed = 0;
        foreach ($this->factors as $symbol => $factor) {
            if ($factor->isStale($maxAgeMinutes)) {
                unset($this->factors[$symbol]);
                $removed++;
            }
        }
        return $removed;
    }

    /**
     * Export factors to array for JSON serialization
     */
    public function exportToArray(): array
    {
        $export = [];
        foreach ($this->factors as $symbol => $factor) {
            $export[$symbol] = $factor->toArray();
        }
        return $export;
    }

    /**
     * Import factors from array
     */
    public function importFromArray(array $data): void
    {
        foreach ($data as $factorData) {
            $factor = MarketFactor::fromArray($factorData);
            $this->addFactor($factor);
        }
    }

    /**
     * Search factors with filters
     */
    public function searchFactors(array $filters): array
    {
        $results = $this->factors;

        if (isset($filters['type'])) {
            $results = array_filter($results, function($factor) use ($filters) {
                return $factor->getType() === $filters['type'];
            });
        }

        if (isset($filters['symbol_like'])) {
            $results = array_filter($results, function($factor) use ($filters) {
                return stripos($factor->getSymbol(), $filters['symbol_like']) !== false;
            });
        }

        if (isset($filters['name_like'])) {
            $results = array_filter($results, function($factor) use ($filters) {
                return stripos($factor->getName(), $filters['name_like']) !== false;
            });
        }

        if (isset($filters['min_change_percent'])) {
            $results = array_filter($results, function($factor) use ($filters) {
                return $factor->getChangePercent() >= $filters['min_change_percent'];
            });
        }

        if (isset($filters['max_change_percent'])) {
            $results = array_filter($results, function($factor) use ($filters) {
                return $factor->getChangePercent() <= $filters['max_change_percent'];
            });
        }

        if (isset($filters['min_value'])) {
            $results = array_filter($results, function($factor) use ($filters) {
                return $factor->getValue() >= $filters['min_value'];
            });
        }

        if (isset($filters['max_value'])) {
            $results = array_filter($results, function($factor) use ($filters) {
                return $factor->getValue() <= $filters['max_value'];
            });
        }

        return array_values($results);
    }

    /**
     * Track correlation between two factors
     */
    public function trackCorrelation(string $symbol1, string $symbol2, float $correlation, int $periods = 30): void
    {
        $this->setCorrelation($symbol1, $symbol2, $correlation);
        
        // Store correlation in database if repository is available
        if ($this->repository && method_exists($this->repository, 'saveCorrelation')) {
            $this->repository->saveCorrelation($symbol1, $symbol2, $correlation, $periods);
        }
    }

    /**
     * Get correlation matrix for all factors
     */
    public function getCorrelationMatrix(): array
    {
        $matrix = [];
        
        foreach ($this->correlations as $symbol1 => $correlations) {
            foreach ($correlations as $symbol2 => $correlation) {
                $key = $symbol1 . '-' . $symbol2;
                $matrix[$key] = $correlation;
            }
        }
        
        return $matrix;
    }

    /**
     * Calculate market sentiment as a percentage
     */
    public function calculateMarketSentiment(): float
    {
        if (empty($this->factors)) {
            return 50.0; // Neutral
        }

        $totalChange = 0;
        $factorCount = 0;

        foreach ($this->factors as $factor) {
            if (!$factor->isStale(60)) { // Only consider fresh data
                $totalChange += $factor->getChangePercent();
                $factorCount++;
            }
        }

        if ($factorCount === 0) {
            return 50.0; // Neutral
        }

        $averageChange = $totalChange / $factorCount;
        
        // Convert to 0-100 scale (assuming max change of ±10%)
        $sentiment = 50 + ($averageChange * 5);
        
        // Clamp between 0 and 100
        return max(0, min(100, $sentiment));
    }

    /**
     * Export all data for backup/transfer
     */
    public function exportData(): array
    {
        return [
            'factors' => $this->exportToArray(),
            'correlations' => $this->correlations,
            'metadata' => [
                'export_timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                'factor_count' => count($this->factors),
                'correlation_count' => count($this->correlations)
            ]
        ];
    }

    /**
     * Import data from backup/transfer
     */
    public function importData(array $data): bool
    {
        try {
            if (isset($data['factors'])) {
                $this->importFromArray($data['factors']);
            }
            
            if (isset($data['correlations'])) {
                $this->correlations = $data['correlations'];
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
