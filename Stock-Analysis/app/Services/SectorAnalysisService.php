<?php

namespace App\Services;

use App\DAOs\SectorPerformanceDAO;
use App\Models\SectorPerformance;

/**
 * Sector Analysis Service
 * 
 * Provides comprehensive sector classification, comparison, and performance analysis.
 * 
 * Features:
 * - GICS sector classification
 * - Stock vs sector performance comparison
 * - Sector peer ranking
 * - Sector rotation detection
 * - Relative strength analysis
 * 
 * @package App\Services
 */
class SectorAnalysisService
{
    private SectorPerformanceDAO $sectorDAO;
    private MarketDataService $marketDataService;
    
    /**
     * GICS Sector Classification Map
     * 
     * @var array
     */
    private const GICS_SECTORS = [
        '10' => 'Energy',
        '15' => 'Materials',
        '20' => 'Industrials',
        '25' => 'Consumer Discretionary',
        '30' => 'Consumer Staples',
        '35' => 'Health Care',
        '40' => 'Financials',
        '45' => 'Information Technology',
        '50' => 'Communication Services',
        '55' => 'Utilities',
        '60' => 'Real Estate'
    ];
    
    /**
     * Constructor
     * 
     * @param SectorPerformanceDAO|null $sectorDAO
     * @param MarketDataService|null $marketDataService
     */
    public function __construct(
        ?SectorPerformanceDAO $sectorDAO = null,
        ?MarketDataService $marketDataService = null
    ) {
        $this->sectorDAO = $sectorDAO ?? new SectorPerformanceDAO();
        $this->marketDataService = $marketDataService ?? new MarketDataService();
    }
    
    /**
     * Classify stock by GICS sector
     * 
     * @param string $symbol Stock symbol
     * @return array Sector information
     */
    public function classifyStock(string $symbol): array
    {
        $fundamentals = $this->marketDataService->getFundamentals($symbol);
        
        if (!$fundamentals) {
            return [
                'symbol' => $symbol,
                'sector' => 'Unknown',
                'industry' => 'Unknown',
                'sector_code' => null,
                'classification' => 'GICS'
            ];
        }
        
        $sector = $fundamentals['sector'] ?? 'Unknown';
        $industry = $fundamentals['industry'] ?? 'Unknown';
        
        return [
            'symbol' => $symbol,
            'sector' => $sector,
            'industry' => $industry,
            'sector_code' => $this->getSectorCode($sector),
            'classification' => 'GICS',
            'market_cap' => $fundamentals['market_cap'] ?? null,
            'country' => $fundamentals['country'] ?? 'US'
        ];
    }
    
    /**
     * Compare stock performance vs sector average
     * 
     * @param string $symbol Stock symbol
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Comparison results
     */
    public function compareToSector(string $symbol, string $startDate, string $endDate): array
    {
        // Get stock classification
        $classification = $this->classifyStock($symbol);
        $sector = $classification['sector'];
        
        // Get stock performance
        $stockPrice = $this->marketDataService->getHistoricalPrices($symbol, $startDate, $endDate);
        
        if (empty($stockPrice)) {
            return [
                'symbol' => $symbol,
                'sector' => $sector,
                'error' => 'Insufficient price data',
                'comparison' => null
            ];
        }
        
        $stockPerformance = $this->calculatePerformance($stockPrice);
        
        // Get sector performance
        $sectorPerformance = $this->sectorDAO->getSectorPerformance($sector, $startDate, $endDate);
        
        if (!$sectorPerformance) {
            return [
                'symbol' => $symbol,
                'sector' => $sector,
                'stock_performance' => $stockPerformance,
                'sector_performance' => null,
                'relative_performance' => null,
                'outperformance' => null
            ];
        }
        
        // Calculate relative performance
        $relativePerformance = $stockPerformance['total_return'] - $sectorPerformance['change_percent'];
        $outperformance = $relativePerformance > 0;
        
        return [
            'symbol' => $symbol,
            'sector' => $sector,
            'industry' => $classification['industry'],
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'stock_performance' => $stockPerformance,
            'sector_performance' => [
                'name' => $sector,
                'return' => $sectorPerformance['change_percent'],
                'constituents' => $sectorPerformance['constituents_count'] ?? null
            ],
            'relative_performance' => $relativePerformance,
            'outperformance' => $outperformance,
            'percentile_rank' => $this->calculatePercentileRank($symbol, $sector, $stockPerformance['total_return'])
        ];
    }
    
    /**
     * Get sector peers for comparison
     * 
     * @param string $symbol Stock symbol
     * @param int $limit Maximum number of peers
     * @return array List of peer companies
     */
    public function getSectorPeers(string $symbol, int $limit = 10): array
    {
        $classification = $this->classifyStock($symbol);
        $sector = $classification['sector'];
        $industry = $classification['industry'];
        
        // In production, this would query a database of stocks filtered by sector
        // For now, return structure for implementation
        return [
            'symbol' => $symbol,
            'sector' => $sector,
            'industry' => $industry,
            'peers' => [
                // Will be populated from stock universe database
                // Example structure:
                // [
                //     'symbol' => 'PEER1',
                //     'name' => 'Peer Company 1',
                //     'market_cap' => 5000000000,
                //     'similarity_score' => 0.85
                // ]
            ],
            'note' => 'Peer discovery requires stock universe database'
        ];
    }
    
    /**
     * Rank stocks within sector by performance
     * 
     * @param string $sector Sector name
     * @param array $symbols List of symbols in sector
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Ranked list
     */
    public function rankSectorPerformance(string $sector, array $symbols, string $startDate, string $endDate): array
    {
        $rankings = [];
        
        foreach ($symbols as $symbol) {
            $priceData = $this->marketDataService->getHistoricalPrices($symbol, $startDate, $endDate);
            
            if (empty($priceData)) {
                continue;
            }
            
            $performance = $this->calculatePerformance($priceData);
            
            $rankings[] = [
                'symbol' => $symbol,
                'return' => $performance['total_return'],
                'volatility' => $performance['volatility'],
                'sharpe_ratio' => $performance['sharpe_ratio'] ?? null
            ];
        }
        
        // Sort by return descending
        usort($rankings, function($a, $b) {
            return $b['return'] <=> $a['return'];
        });
        
        // Add rank
        foreach ($rankings as $i => &$item) {
            $item['rank'] = $i + 1;
        }
        
        return [
            'sector' => $sector,
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_stocks' => count($rankings),
            'rankings' => $rankings
        ];
    }
    
    /**
     * Detect sector rotation (which sectors are gaining/losing momentum)
     * 
     * @param int $lookbackDays Number of days to analyze
     * @return array Sector rotation analysis
     */
    public function detectSectorRotation(int $lookbackDays = 30): array
    {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$lookbackDays} days"));
        
        $sectorPerformances = [];
        
        foreach (self::GICS_SECTORS as $code => $name) {
            $performance = $this->sectorDAO->getSectorPerformance($name, $startDate, $endDate);
            
            if ($performance) {
                $sectorPerformances[] = [
                    'sector' => $name,
                    'code' => $code,
                    'return' => $performance['change_percent'],
                    'trend' => $this->determineTrend($performance)
                ];
            }
        }
        
        // Sort by return
        usort($sectorPerformances, function($a, $b) {
            return $b['return'] <=> $a['return'];
        });
        
        // Identify leaders and laggards
        $leaders = array_slice($sectorPerformances, 0, 3);
        $laggards = array_slice($sectorPerformances, -3);
        
        return [
            'period' => ['start' => $startDate, 'end' => $endDate, 'days' => $lookbackDays],
            'all_sectors' => $sectorPerformances,
            'leaders' => $leaders,
            'laggards' => $laggards,
            'rotation_detected' => $this->isRotationOccurring($sectorPerformances)
        ];
    }
    
    /**
     * Calculate relative strength of stock vs sector
     * 
     * @param string $symbol Stock symbol
     * @param int $period Period in days
     * @return array Relative strength metrics
     */
    public function calculateRelativeStrength(string $symbol, int $period = 90): array
    {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$period} days"));
        
        $comparison = $this->compareToSector($symbol, $startDate, $endDate);
        
        if (!$comparison['sector_performance']) {
            return [
                'symbol' => $symbol,
                'relative_strength' => null,
                'error' => 'Sector data unavailable'
            ];
        }
        
        $stockReturn = $comparison['stock_performance']['total_return'];
        $sectorReturn = $comparison['sector_performance']['return'];
        
        // Relative Strength Ratio = Stock Return / Sector Return
        $rsRatio = $sectorReturn != 0 ? $stockReturn / $sectorReturn : null;
        
        return [
            'symbol' => $symbol,
            'sector' => $comparison['sector'],
            'period_days' => $period,
            'stock_return' => $stockReturn,
            'sector_return' => $sectorReturn,
            'relative_strength_ratio' => $rsRatio,
            'interpretation' => $this->interpretRelativeStrength($rsRatio),
            'outperforming' => $comparison['outperformance']
        ];
    }
    
    /**
     * Update sector performance data
     * 
     * @param string $sector Sector name
     * @param float $performanceValue Current performance value
     * @param float $changePercent Change percentage
     * @param float $marketCapWeight Market cap weighting
     * @return bool Success
     */
    public function updateSectorPerformance(
        string $sector,
        float $performanceValue,
        float $changePercent,
        float $marketCapWeight = 0.0
    ): bool {
        $sectorCode = $this->getSectorCode($sector);
        
        $sectorPerf = new SectorPerformance([
            'sector_code' => $sectorCode,
            'sector_name' => $sector,
            'classification' => 'GICS',
            'performance_value' => $performanceValue,
            'change_percent' => $changePercent,
            'market_cap_weight' => $marketCapWeight,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        return $this->sectorDAO->save($sectorPerf);
    }
    
    /**
     * Get all GICS sectors
     * 
     * @return array List of sectors
     */
    public function getAllSectors(): array
    {
        return self::GICS_SECTORS;
    }
    
    // ========== PRIVATE HELPER METHODS ==========
    
    /**
     * Get GICS sector code from name
     * 
     * @param string $sectorName Sector name
     * @return string|null Sector code
     */
    private function getSectorCode(string $sectorName): ?string
    {
        foreach (self::GICS_SECTORS as $code => $name) {
            if (strcasecmp($name, $sectorName) === 0) {
                return $code;
            }
        }
        return null;
    }
    
    /**
     * Calculate performance metrics from price data
     * 
     * @param array $priceData Price data array
     * @return array Performance metrics
     */
    private function calculatePerformance(array $priceData): array
    {
        if (empty($priceData)) {
            return [
                'total_return' => 0,
                'volatility' => 0,
                'max_drawdown' => 0
            ];
        }
        
        // Sort by date ascending
        usort($priceData, function($a, $b) {
            return strtotime($a['date']) <=> strtotime($b['date']);
        });
        
        $startPrice = (float)$priceData[0]['close'];
        $endPrice = (float)$priceData[count($priceData) - 1]['close'];
        
        $totalReturn = (($endPrice - $startPrice) / $startPrice) * 100;
        
        // Calculate daily returns for volatility
        $returns = [];
        for ($i = 1; $i < count($priceData); $i++) {
            $prevClose = (float)$priceData[$i - 1]['close'];
            $currentClose = (float)$priceData[$i]['close'];
            $returns[] = (($currentClose - $prevClose) / $prevClose) * 100;
        }
        
        $volatility = count($returns) > 1 ? $this->standardDeviation($returns) : 0;
        
        // Calculate max drawdown
        $peak = $startPrice;
        $maxDrawdown = 0;
        
        foreach ($priceData as $data) {
            $price = (float)$data['close'];
            if ($price > $peak) {
                $peak = $price;
            }
            $drawdown = (($peak - $price) / $peak) * 100;
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }
        
        return [
            'total_return' => round($totalReturn, 2),
            'volatility' => round($volatility, 2),
            'max_drawdown' => round($maxDrawdown, 2),
            'start_price' => $startPrice,
            'end_price' => $endPrice
        ];
    }
    
    /**
     * Calculate standard deviation
     * 
     * @param array $values Array of values
     * @return float Standard deviation
     */
    private function standardDeviation(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }
        
        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / $count;
        
        return sqrt($variance);
    }
    
    /**
     * Calculate percentile rank within sector
     * 
     * @param string $symbol Stock symbol
     * @param string $sector Sector name
     * @param float $return Stock return
     * @return float|null Percentile (0-100)
     */
    private function calculatePercentileRank(string $symbol, string $sector, float $return): ?float
    {
        // This would query all stocks in sector and calculate percentile
        // Placeholder for now
        return null;
    }
    
    /**
     * Determine sector trend
     * 
     * @param array $performance Sector performance data
     * @return string Trend direction
     */
    private function determineTrend(array $performance): string
    {
        $changePercent = $performance['change_percent'] ?? 0;
        
        if ($changePercent > 5) {
            return 'strong_uptrend';
        } elseif ($changePercent > 0) {
            return 'uptrend';
        } elseif ($changePercent < -5) {
            return 'strong_downtrend';
        } elseif ($changePercent < 0) {
            return 'downtrend';
        } else {
            return 'neutral';
        }
    }
    
    /**
     * Determine if sector rotation is occurring
     * 
     * @param array $sectorPerformances All sector performances
     * @return bool True if rotation detected
     */
    private function isRotationOccurring(array $sectorPerformances): bool
    {
        if (empty($sectorPerformances)) {
            return false;
        }
        
        // Check if there's significant dispersion between sectors
        $returns = array_column($sectorPerformances, 'return');
        $spread = max($returns) - min($returns);
        
        // If spread > 10%, consider it rotation
        return $spread > 10.0;
    }
    
    /**
     * Interpret relative strength ratio
     * 
     * @param float|null $rsRatio Relative strength ratio
     * @return string Interpretation
     */
    private function interpretRelativeStrength(?float $rsRatio): string
    {
        if ($rsRatio === null) {
            return 'Unable to calculate';
        }
        
        if ($rsRatio > 1.5) {
            return 'Significantly outperforming sector';
        } elseif ($rsRatio > 1.1) {
            return 'Outperforming sector';
        } elseif ($rsRatio > 0.9) {
            return 'In line with sector';
        } elseif ($rsRatio > 0.5) {
            return 'Underperforming sector';
        } else {
            return 'Significantly underperforming sector';
        }
    }
}
