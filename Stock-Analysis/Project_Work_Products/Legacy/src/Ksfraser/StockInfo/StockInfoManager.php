<?php

namespace Ksfraser\StockInfo;

use PDO;

/**
 * StockInfo Manager - Main entry point for the StockInfo library
 * 
 * Provides access to all models and high-level operations
 */
class StockInfoManager
{
    private $pdo;
    private $models = [];

    public function __construct($databaseConfig)
    {
        $factory = DatabaseFactory::getInstance($databaseConfig);
        $this->pdo = $factory->getConnection();
        $this->initializeModels();
    }

    /**
     * Initialize all model instances
     */
    private function initializeModels()
    {
        $this->models = [
            'stockInfo' => new StockInfo($this->pdo),
            'stockPrices' => new StockPrices($this->pdo),
            'portfolio' => new Portfolio($this->pdo),
            'transaction' => new Transaction($this->pdo),
            'evalSummary' => new EvalSummary($this->pdo),
            'stockExchange' => new StockExchange($this->pdo),
            'technicalAnalysis' => new TechnicalAnalysis($this->pdo)
        ];
    }

    /**
     * Get model instance
     */
    public function getModel($modelName)
    {
        if (!isset($this->models[$modelName])) {
            throw new \InvalidArgumentException("Model '{$modelName}' not found");
        }
        return $this->models[$modelName];
    }

    /**
     * Get stock info model
     */
    public function stockInfo()
    {
        return $this->models['stockInfo'];
    }

    /**
     * Get stock prices model
     */
    public function stockPrices()
    {
        return $this->models['stockPrices'];
    }

    /**
     * Get portfolio model
     */
    public function portfolio()
    {
        return $this->models['portfolio'];
    }

    /**
     * Get transaction model
     */
    public function transaction()
    {
        return $this->models['transaction'];
    }

    /**
     * Get evaluation summary model
     */
    public function evalSummary()
    {
        return $this->models['evalSummary'];
    }

    /**
     * Get stock exchange model
     */
    public function stockExchange()
    {
        return $this->models['stockExchange'];
    }

    /**
     * Get technical analysis model
     */
    public function technicalAnalysis()
    {
        return $this->models['technicalAnalysis'];
    }

    /**
     * Get comprehensive stock analysis
     */
    public function getStockAnalysis($stockSymbol)
    {
        $stockInfo = $this->stockInfo()->findBySymbol($stockSymbol);
        if (!$stockInfo) {
            throw new \Exception("Stock symbol '{$stockSymbol}' not found");
        }

        $analysis = [
            'basic_info' => $stockInfo,
            'latest_price' => $this->stockPrices()->getLatestPrice($stockSymbol),
            'evaluation' => $this->evalSummary()->getEvaluationWithStockInfo($stockInfo->idstockinfo),
            'technical_analysis' => $this->technicalAnalysis()->getLatestAnalysis($stockSymbol),
            'price_history' => $this->stockPrices()->getPriceHistory($stockSymbol, null, null, 30),
            'volatility' => $this->stockPrices()->getVolatility($stockSymbol, 30)
        ];

        // Calculate derived metrics
        $analysis['calculated_metrics'] = $this->calculateDerivedMetrics($analysis);

        return $analysis;
    }

    /**
     * Get portfolio analysis for a user
     */
    public function getPortfolioAnalysis($username, $account = null)
    {
        $portfolio = $this->portfolio()->getUserPortfolio($username, $account);
        $summary = $this->portfolio()->getUserPortfolioSummary($username, $account);
        $dividendSummary = $this->portfolio()->getDividendSummary($username, $account);
        $performance = $this->portfolio()->getPerformanceAnalysis($username, $account);

        // Get recent transactions
        $recentTransactions = $this->transaction()->getUserTransactions($username, 10);

        // Calculate portfolio metrics
        $portfolioMetrics = [
            'total_positions' => count($portfolio),
            'total_value' => $summary->total_market_value,
            'total_cost' => $summary->total_book_value,
            'total_gain_loss' => $summary->total_profit_loss,
            'total_return_percent' => $summary->total_return_percent,
            'annual_dividend_income' => $dividendSummary->total_annual_dividend,
            'portfolio_yield' => $dividendSummary->portfolio_yield
        ];

        return [
            'holdings' => $portfolio,
            'summary' => $summary,
            'dividend_summary' => $dividendSummary,
            'performance' => $performance,
            'recent_transactions' => $recentTransactions,
            'metrics' => $portfolioMetrics
        ];
    }

    /**
     * Get market overview
     */
    public function getMarketOverview()
    {
        $overview = [
            'stock_statistics' => $this->stockInfo()->getStatistics(),
            'evaluation_statistics' => $this->evalSummary()->getEvaluationStatistics(),
            'exchange_summary' => $this->stockExchange()->getStocksCountByExchange(),
            'top_rated_stocks' => $this->evalSummary()->getTopRatedStocks(10),
            'high_dividend_stocks' => $this->stockInfo()->getHighDividendStocks(4.0),
            'recent_evaluations' => $this->evalSummary()->getRecentEvaluations(5)
        ];

        // Get technical signals for today
        $today = date('Y-m-d');
        $overview['technical_signals'] = [
            'golden_cross' => $this->technicalAnalysis()->getGoldenCrossStocks($today),
            'death_cross' => $this->technicalAnalysis()->getDeathCrossStocks($today),
            'overbought' => $this->technicalAnalysis()->getOverboughtStocks(70, $today),
            'oversold' => $this->technicalAnalysis()->getOversoldStocks(30, $today)
        ];

        return $overview;
    }

    /**
     * Search stocks with comprehensive criteria
     */
    public function searchStocks($criteria = [])
    {
        $results = [];

        // Search by symbol or company name
        if (isset($criteria['search_term'])) {
            $results['by_name'] = $this->stockInfo()->searchByCompanyName($criteria['search_term']);
        }

        // Search by evaluation criteria
        if (isset($criteria['evaluation'])) {
            $results['by_evaluation'] = $this->evalSummary()->searchByEvaluationCriteria($criteria['evaluation']);
        }

        // Search by market cap
        if (isset($criteria['market_cap_min']) || isset($criteria['market_cap_max'])) {
            $results['by_market_cap'] = $this->stockInfo()->getStocksByMarketCap(
                $criteria['market_cap_min'] ?? null,
                $criteria['market_cap_max'] ?? null
            );
        }

        // Search by dividend yield
        if (isset($criteria['min_dividend_yield'])) {
            $results['by_dividend'] = $this->stockInfo()->getHighDividendStocks($criteria['min_dividend_yield']);
        }

        // Search by PE ratio
        if (isset($criteria['max_pe_ratio'])) {
            $results['by_pe_ratio'] = $this->stockInfo()->getLowPEStocks($criteria['max_pe_ratio']);
        }

        return $results;
    }

    /**
     * Calculate derived metrics from analysis data
     */
    private function calculateDerivedMetrics($analysis)
    {
        $metrics = [];

        // Basic info exists
        if ($analysis['basic_info']) {
            $stock = $analysis['basic_info'];
            
            // Dividend yield
            if ($stock->currentprice > 0 && $stock->annualdividendpershare > 0) {
                $metrics['dividend_yield'] = ($stock->annualdividendpershare / $stock->currentprice) * 100;
            }

            // Price performance
            if ($stock->yearlow > 0) {
                $metrics['year_performance'] = (($stock->currentprice - $stock->yearlow) / $stock->yearlow) * 100;
            }

            // Price relative to year range
            if ($stock->yearhigh > 0 && $stock->yearlow > 0) {
                $range = $stock->yearhigh - $stock->yearlow;
                if ($range > 0) {
                    $metrics['price_position_in_range'] = (($stock->currentprice - $stock->yearlow) / $range) * 100;
                }
            }
        }

        // Technical strength score
        if ($analysis['technical_analysis']) {
            $metrics['technical_strength'] = $this->technicalAnalysis()->getTechnicalStrengthScore(
                $analysis['basic_info']->stocksymbol
            );
        }

        // Evaluation score interpretation
        if ($analysis['evaluation']) {
            $eval = $analysis['evaluation'];
            $metrics['evaluation_grade'] = $this->getEvaluationGrade($eval->totalscore);
            $metrics['margin_safety_category'] = $this->getMarginSafetyCategory($eval->marginsafety);
        }

        return $metrics;
    }

    /**
     * Get evaluation grade based on total score
     */
    private function getEvaluationGrade($totalScore)
    {
        if ($totalScore >= 20) return 'A+';
        if ($totalScore >= 15) return 'A';
        if ($totalScore >= 12) return 'B+';
        if ($totalScore >= 10) return 'B';
        if ($totalScore >= 8) return 'B-';
        if ($totalScore >= 6) return 'C+';
        if ($totalScore >= 4) return 'C';
        if ($totalScore >= 2) return 'C-';
        if ($totalScore > 0) return 'D';
        return 'F';
    }

    /**
     * Get margin of safety category
     */
    private function getMarginSafetyCategory($marginSafety)
    {
        if ($marginSafety > 1000000) return 'Excellent';
        if ($marginSafety > 500000) return 'Very Good';
        if ($marginSafety > 100000) return 'Good';
        if ($marginSafety > 0) return 'Fair';
        return 'Poor';
    }

    /**
     * Get database connection for custom queries
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Execute custom query
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Test all database connections and models
     */
    public function healthCheck()
    {
        $health = [
            'database' => DatabaseFactory::getInstance()->getDatabaseInfo(),
            'models' => []
        ];

        foreach ($this->models as $name => $model) {
            try {
                $count = $model->count();
                $health['models'][$name] = [
                    'status' => 'OK',
                    'record_count' => $count
                ];
            } catch (\Exception $e) {
                $health['models'][$name] = [
                    'status' => 'ERROR',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $health;
    }

    /**
     * Get total count of stocks
     */
    public function getStockCount(): int
    {
        return $this->stockInfo()->count();
    }

    /**
     * Get recent stocks added
     */
    public function getRecentStocks(int $limit = 10): array
    {
        return $this->stockInfo()->getRecent($limit);
    }
}
