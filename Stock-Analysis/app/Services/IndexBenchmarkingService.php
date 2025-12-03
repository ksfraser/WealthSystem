<?php

namespace App\Services;

use App\DAOs\IndexPerformanceDAO;
use App\Models\IndexPerformance;

/**
 * Index Benchmarking Service
 * 
 * Provides comprehensive index tracking, comparison, and benchmarking analysis.
 * 
 * Features:
 * - Track major market indexes (S&P 500, NASDAQ, Dow, Russell 2000)
 * - Compare stock/portfolio performance vs indexes
 * - Calculate alpha and beta
 * - Index membership detection
 * - Correlation analysis
 * 
 * @package App\Services
 */
class IndexBenchmarkingService
{
    private IndexPerformanceDAO $indexDAO;
    private MarketDataService $marketDataService;
    
    /**
     * Major Market Indexes
     * 
     * @var array
     */
    private const MAJOR_INDEXES = [
        'SPY' => [
            'name' => 'S&P 500',
            'symbol' => '^GSPC',
            'description' => 'Large-cap US stocks',
            'constituents' => 500,
            'region' => 'US'
        ],
        'QQQ' => [
            'name' => 'NASDAQ 100',
            'symbol' => '^IXIC',
            'description' => 'Tech-heavy large-cap',
            'constituents' => 100,
            'region' => 'US'
        ],
        'DIA' => [
            'name' => 'Dow Jones',
            'symbol' => '^DJI',
            'description' => 'Blue-chip US stocks',
            'constituents' => 30,
            'region' => 'US'
        ],
        'IWM' => [
            'name' => 'Russell 2000',
            'symbol' => '^RUT',
            'description' => 'Small-cap US stocks',
            'constituents' => 2000,
            'region' => 'US'
        ]
    ];
    
    /**
     * Constructor
     * 
     * @param IndexPerformanceDAO|null $indexDAO
     * @param MarketDataService|null $marketDataService
     */
    public function __construct(
        ?IndexPerformanceDAO $indexDAO = null,
        ?MarketDataService $marketDataService = null
    ) {
        $this->indexDAO = $indexDAO ?? new IndexPerformanceDAO();
        $this->marketDataService = $marketDataService ?? new MarketDataService();
    }
    
    /**
     * Compare stock performance vs index benchmark
     * 
     * @param string $symbol Stock symbol
     * @param string $indexSymbol Index symbol (SPY, QQQ, etc.)
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Comparison results
     */
    public function compareToIndex(
        string $symbol,
        string $indexSymbol,
        string $startDate,
        string $endDate
    ): array {
        // Get stock performance
        $stockPrices = $this->marketDataService->getHistoricalPrices($symbol, $startDate, $endDate);
        
        if (empty($stockPrices)) {
            return [
                'symbol' => $symbol,
                'index' => $indexSymbol,
                'error' => 'Insufficient stock data',
                'comparison' => null
            ];
        }
        
        $stockPerformance = $this->calculatePerformance($stockPrices);
        
        // Get index performance
        $indexInfo = self::MAJOR_INDEXES[$indexSymbol] ?? null;
        
        if (!$indexInfo) {
            return [
                'symbol' => $symbol,
                'index' => $indexSymbol,
                'error' => 'Unknown index',
                'comparison' => null
            ];
        }
        
        $indexPrices = $this->marketDataService->getHistoricalPrices(
            $indexInfo['symbol'],
            $startDate,
            $endDate
        );
        
        if (empty($indexPrices)) {
            return [
                'symbol' => $symbol,
                'index' => $indexSymbol,
                'error' => 'Insufficient index data',
                'comparison' => null
            ];
        }
        
        $indexPerformance = $this->calculatePerformance($indexPrices);
        
        // Calculate alpha and beta
        $alphaBeta = $this->calculateAlphaBeta($stockPrices, $indexPrices);
        
        // Calculate correlation
        $correlation = $this->calculateCorrelation($stockPrices, $indexPrices);
        
        return [
            'symbol' => $symbol,
            'index' => [
                'symbol' => $indexSymbol,
                'name' => $indexInfo['name'],
                'description' => $indexInfo['description']
            ],
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'stock_performance' => $stockPerformance,
            'index_performance' => $indexPerformance,
            'alpha' => $alphaBeta['alpha'],
            'beta' => $alphaBeta['beta'],
            'correlation' => $correlation,
            'outperformance' => $stockPerformance['total_return'] > $indexPerformance['total_return'],
            'excess_return' => $stockPerformance['total_return'] - $indexPerformance['total_return'],
            'sharpe_ratio' => $this->calculateSharpeRatio($stockPerformance),
            'information_ratio' => $this->calculateInformationRatio(
                $stockPerformance,
                $indexPerformance,
                $alphaBeta['tracking_error']
            )
        ];
    }
    
    /**
     * Compare portfolio performance vs multiple indexes
     * 
     * @param array $portfolioReturns Portfolio daily returns
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Multi-index comparison
     */
    public function comparePortfolioToIndexes(
        array $portfolioReturns,
        string $startDate,
        string $endDate
    ): array {
        $comparisons = [];
        
        foreach (self::MAJOR_INDEXES as $symbol => $info) {
            $indexPrices = $this->marketDataService->getHistoricalPrices(
                $info['symbol'],
                $startDate,
                $endDate
            );
            
            if (!empty($indexPrices)) {
                $indexPerformance = $this->calculatePerformance($indexPrices);
                
                $comparisons[$symbol] = [
                    'name' => $info['name'],
                    'return' => $indexPerformance['total_return'],
                    'volatility' => $indexPerformance['volatility'],
                    'excess_return' => $portfolioReturns['total_return'] - $indexPerformance['total_return']
                ];
            }
        }
        
        return [
            'portfolio' => $portfolioReturns,
            'indexes' => $comparisons,
            'best_benchmark' => $this->identifyBestBenchmark($portfolioReturns, $comparisons)
        ];
    }
    
    /**
     * Calculate alpha and beta
     * 
     * Alpha = Portfolio return - (Risk-free rate + Beta * (Market return - Risk-free rate))
     * Beta = Covariance(Stock, Market) / Variance(Market)
     * 
     * @param array $stockPrices Stock price data
     * @param array $indexPrices Index price data
     * @return array Alpha and beta values
     */
    public function calculateAlphaBeta(array $stockPrices, array $indexPrices): array
    {
        $stockReturns = $this->calculateDailyReturns($stockPrices);
        $indexReturns = $this->calculateDailyReturns($indexPrices);
        
        // Align arrays by date
        $aligned = $this->alignReturnsByDate($stockReturns, $indexReturns);
        
        if (count($aligned['stock']) < 2) {
            return [
                'alpha' => null,
                'beta' => null,
                'tracking_error' => null
            ];
        }
        
        // Calculate beta
        $covariance = $this->calculateCovariance($aligned['stock'], $aligned['index']);
        $indexVariance = $this->calculateVariance($aligned['index']);
        
        $beta = $indexVariance != 0 ? $covariance / $indexVariance : 0;
        
        // Calculate alpha (simplified, assuming risk-free rate = 0 for now)
        $stockAvgReturn = array_sum($aligned['stock']) / count($aligned['stock']);
        $indexAvgReturn = array_sum($aligned['index']) / count($aligned['index']);
        
        $alpha = $stockAvgReturn - ($beta * $indexAvgReturn);
        
        // Annualize alpha (daily to annual, assuming 252 trading days)
        $alphaAnnualized = $alpha * 252;
        
        // Calculate tracking error
        $excessReturns = [];
        for ($i = 0; $i < count($aligned['stock']); $i++) {
            $excessReturns[] = $aligned['stock'][$i] - ($beta * $aligned['index'][$i]);
        }
        
        $trackingError = $this->standardDeviation($excessReturns) * sqrt(252);
        
        return [
            'alpha' => round($alphaAnnualized, 4),
            'beta' => round($beta, 4),
            'tracking_error' => round($trackingError, 4)
        ];
    }
    
    /**
     * Get index constituents (if available)
     * 
     * @param string $indexSymbol Index symbol
     * @return array Constituent stocks
     */
    public function getIndexConstituents(string $indexSymbol): array
    {
        $indexInfo = self::MAJOR_INDEXES[$indexSymbol] ?? null;
        
        if (!$indexInfo) {
            return [
                'index' => $indexSymbol,
                'error' => 'Unknown index',
                'constituents' => []
            ];
        }
        
        // This would query a database of index constituents
        // For now, return structure for future implementation
        return [
            'index' => $indexSymbol,
            'name' => $indexInfo['name'],
            'total_constituents' => $indexInfo['constituents'],
            'constituents' => [
                // Will be populated from index constituent database
                // Example structure:
                // [
                //     'symbol' => 'AAPL',
                //     'name' => 'Apple Inc.',
                //     'weight' => 0.072,  // 7.2% of index
                //     'sector' => 'Information Technology'
                // ]
            ],
            'note' => 'Constituent data requires index composition database'
        ];
    }
    
    /**
     * Update index performance data
     * 
     * @param string $indexSymbol Index symbol
     * @param float $value Current index value
     * @param float $changePercent Change percentage
     * @param array $metadata Additional metadata
     * @return bool Success
     */
    public function updateIndexPerformance(
        string $indexSymbol,
        float $value,
        float $changePercent,
        array $metadata = []
    ): bool {
        $indexInfo = self::MAJOR_INDEXES[$indexSymbol] ?? null;
        
        if (!$indexInfo) {
            return false;
        }
        
        $indexPerf = new IndexPerformance([
            'index_symbol' => $indexSymbol,
            'index_name' => $indexInfo['name'],
            'region' => $indexInfo['region'],
            'asset_class' => 'equity',
            'value' => $value,
            'change_percent' => $changePercent,
            'constituents' => $indexInfo['constituents'],
            'timestamp' => date('Y-m-d H:i:s'),
            'metadata' => $metadata
        ]);
        
        return $this->indexDAO->save($indexPerf);
    }
    
    /**
     * Get all major indexes
     * 
     * @return array List of indexes
     */
    public function getAllIndexes(): array
    {
        return self::MAJOR_INDEXES;
    }
    
    /**
     * Check if stock is likely in index
     * 
     * @param string $symbol Stock symbol
     * @param string $indexSymbol Index symbol
     * @return array Likelihood assessment
     */
    public function isLikelyInIndex(string $symbol, string $indexSymbol): array
    {
        $fundamentals = $this->marketDataService->getFundamentals($symbol);
        
        if (!$fundamentals) {
            return [
                'symbol' => $symbol,
                'index' => $indexSymbol,
                'likely_member' => false,
                'confidence' => 0,
                'reason' => 'Insufficient data'
            ];
        }
        
        $marketCap = $fundamentals['market_cap'] ?? 0;
        $indexInfo = self::MAJOR_INDEXES[$indexSymbol] ?? null;
        
        if (!$indexInfo) {
            return [
                'symbol' => $symbol,
                'index' => $indexSymbol,
                'likely_member' => false,
                'confidence' => 0,
                'reason' => 'Unknown index'
            ];
        }
        
        // Heuristic rules for index membership
        $likely = false;
        $confidence = 0;
        $reason = '';
        
        switch ($indexSymbol) {
            case 'SPY': // S&P 500 - Large cap
                $likely = $marketCap > 10000000000; // >$10B
                $confidence = $marketCap > 50000000000 ? 0.9 : 0.6;
                $reason = $likely ? 'Large-cap stock (>$10B)' : 'Too small for S&P 500';
                break;
                
            case 'QQQ': // NASDAQ 100 - Tech-heavy
                $sector = $fundamentals['sector'] ?? '';
                $techSectors = ['Information Technology', 'Communication Services', 'Consumer Discretionary'];
                $isTech = in_array($sector, $techSectors);
                $likely = $isTech && $marketCap > 10000000000;
                $confidence = $likely ? 0.7 : 0.3;
                $reason = $likely ? 'Large-cap tech/growth stock' : 'Not in NASDAQ 100 sectors';
                break;
                
            case 'IWM': // Russell 2000 - Small cap
                $likely = $marketCap > 300000000 && $marketCap < 5000000000; // $300M-$5B
                $confidence = $likely ? 0.8 : 0.2;
                $reason = $likely ? 'Small-cap range ($300M-$5B)' : 'Outside Russell 2000 range';
                break;
                
            case 'DIA': // Dow 30 - Blue chip
                $likely = $marketCap > 100000000000; // >$100B
                $confidence = $likely ? 0.5 : 0.1; // Lower confidence (only 30 stocks)
                $reason = $likely ? 'Large blue-chip stock' : 'Dow has only 30 constituents';
                break;
        }
        
        return [
            'symbol' => $symbol,
            'index' => $indexSymbol,
            'index_name' => $indexInfo['name'],
            'likely_member' => $likely,
            'confidence' => $confidence,
            'reason' => $reason,
            'market_cap' => $marketCap
        ];
    }
    
    // ========== PRIVATE HELPER METHODS ==========
    
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
        
        usort($priceData, function($a, $b) {
            return strtotime($a['date']) <=> strtotime($b['date']);
        });
        
        $startPrice = (float)$priceData[0]['close'];
        $endPrice = (float)$priceData[count($priceData) - 1]['close'];
        
        $totalReturn = (($endPrice - $startPrice) / $startPrice) * 100;
        
        $returns = $this->calculateDailyReturns($priceData);
        $volatility = $this->standardDeviation($returns) * sqrt(252); // Annualized
        
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
            'volatility' => round($volatility * 100, 2), // Convert to percentage
            'max_drawdown' => round($maxDrawdown, 2)
        ];
    }
    
    /**
     * Calculate daily returns from price data
     * 
     * @param array $priceData Price data
     * @return array Daily returns
     */
    private function calculateDailyReturns(array $priceData): array
    {
        usort($priceData, function($a, $b) {
            return strtotime($a['date']) <=> strtotime($b['date']);
        });
        
        $returns = [];
        for ($i = 1; $i < count($priceData); $i++) {
            $prevClose = (float)$priceData[$i - 1]['close'];
            $currentClose = (float)$priceData[$i]['close'];
            $returns[] = ($currentClose - $prevClose) / $prevClose;
        }
        
        return $returns;
    }
    
    /**
     * Align returns by date
     * 
     * @param array $stockReturns Stock returns
     * @param array $indexReturns Index returns
     * @return array Aligned returns
     */
    private function alignReturnsByDate(array $stockReturns, array $indexReturns): array
    {
        // Simplified: assume returns are already aligned
        // In production, would match by date
        $minLength = min(count($stockReturns), count($indexReturns));
        
        return [
            'stock' => array_slice($stockReturns, 0, $minLength),
            'index' => array_slice($indexReturns, 0, $minLength)
        ];
    }
    
    /**
     * Calculate covariance
     * 
     * @param array $x First array
     * @param array $y Second array
     * @return float Covariance
     */
    private function calculateCovariance(array $x, array $y): float
    {
        $n = count($x);
        if ($n === 0 || $n !== count($y)) {
            return 0.0;
        }
        
        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;
        
        $covariance = 0;
        for ($i = 0; $i < $n; $i++) {
            $covariance += ($x[$i] - $meanX) * ($y[$i] - $meanY);
        }
        
        return $covariance / $n;
    }
    
    /**
     * Calculate variance
     * 
     * @param array $values Array of values
     * @return float Variance
     */
    private function calculateVariance(array $values): float
    {
        $n = count($values);
        if ($n === 0) {
            return 0.0;
        }
        
        $mean = array_sum($values) / $n;
        $variance = 0;
        
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        return $variance / $n;
    }
    
    /**
     * Calculate correlation
     * 
     * @param array $stockPrices Stock prices
     * @param array $indexPrices Index prices
     * @return float Correlation coefficient (-1 to 1)
     */
    private function calculateCorrelation(array $stockPrices, array $indexPrices): float
    {
        $stockReturns = $this->calculateDailyReturns($stockPrices);
        $indexReturns = $this->calculateDailyReturns($indexPrices);
        
        $aligned = $this->alignReturnsByDate($stockReturns, $indexReturns);
        
        if (count($aligned['stock']) < 2) {
            return 0.0;
        }
        
        $covariance = $this->calculateCovariance($aligned['stock'], $aligned['index']);
        $stockStdDev = $this->standardDeviation($aligned['stock']);
        $indexStdDev = $this->standardDeviation($aligned['index']);
        
        if ($stockStdDev == 0 || $indexStdDev == 0) {
            return 0.0;
        }
        
        return $covariance / ($stockStdDev * $indexStdDev);
    }
    
    /**
     * Calculate standard deviation
     * 
     * @param array $values Array of values
     * @return float Standard deviation
     */
    private function standardDeviation(array $values): float
    {
        return sqrt($this->calculateVariance($values));
    }
    
    /**
     * Calculate Sharpe ratio
     * 
     * @param array $performance Performance data
     * @param float $riskFreeRate Risk-free rate (default 0)
     * @return float Sharpe ratio
     */
    private function calculateSharpeRatio(array $performance, float $riskFreeRate = 0): float
    {
        $returnPercent = $performance['total_return'] ?? 0;
        $volatility = $performance['volatility'] ?? 1;
        
        if ($volatility == 0) {
            return 0.0;
        }
        
        return round(($returnPercent - $riskFreeRate) / $volatility, 4);
    }
    
    /**
     * Calculate information ratio
     * 
     * @param array $stockPerformance Stock performance
     * @param array $indexPerformance Index performance
     * @param float $trackingError Tracking error
     * @return float Information ratio
     */
    private function calculateInformationRatio(
        array $stockPerformance,
        array $indexPerformance,
        float $trackingError
    ): float {
        $excessReturn = $stockPerformance['total_return'] - $indexPerformance['total_return'];
        
        if ($trackingError == 0) {
            return 0.0;
        }
        
        return round($excessReturn / $trackingError, 4);
    }
    
    /**
     * Identify best benchmark for portfolio
     * 
     * @param array $portfolioReturns Portfolio returns
     * @param array $indexComparisons Index comparisons
     * @return array Best benchmark
     */
    private function identifyBestBenchmark(array $portfolioReturns, array $indexComparisons): array
    {
        $bestMatch = null;
        $smallestDiff = PHP_FLOAT_MAX;
        
        foreach ($indexComparisons as $symbol => $comparison) {
            $diff = abs($portfolioReturns['total_return'] - $comparison['return']);
            if ($diff < $smallestDiff) {
                $smallestDiff = $diff;
                $bestMatch = [
                    'symbol' => $symbol,
                    'name' => $comparison['name'],
                    'similarity' => round(100 - $smallestDiff, 2)
                ];
            }
        }
        
        return $bestMatch ?? ['symbol' => null, 'name' => 'None', 'similarity' => 0];
    }
}
