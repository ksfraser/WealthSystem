<?php
/**
 * Stock Data Access Object
 * 
 * Provides comprehensive data access methods for stock-specific tables
 * Handles price data, fundamentals, technical indicators, news, and analysis
 */

require_once __DIR__ . '/StockDatabaseManager.php';

class StockDAO {
    private $pdo;
    private $dbManager;
    private $cache;
    
    public function __construct(PDO $pdo, array $config = []) {
        $this->pdo = $pdo;
        $this->dbManager = new StockDatabaseManager($pdo, $config);
        $this->cache = [];
    }
    
    /**
     * Initialize stock tables if they don't exist
     */
    public function initializeStock(string $symbol): bool {
        if (!$this->dbManager->stockTablesExist($symbol)) {
            return $this->dbManager->createStockTables($symbol);
        }
        return true;
    }
    
    // ===== PRICE DATA METHODS =====
    
    /**
     * Insert or update price data for a stock
     */
    public function upsertPriceData(string $symbol, array $priceData): bool {
        if (!$this->initializeStock($symbol)) {
            return false;
        }
        
        try {
            $tableName = $this->dbManager->getTableName($symbol, 'prices');
            
            $sql = "
                INSERT INTO `{$tableName}` (
                    date, open_price, high_price, low_price, close_price, 
                    adj_close_price, volume, split_coefficient, dividend_amount, data_source
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    open_price = VALUES(open_price),
                    high_price = VALUES(high_price),
                    low_price = VALUES(low_price),
                    close_price = VALUES(close_price),
                    adj_close_price = VALUES(adj_close_price),
                    volume = VALUES(volume),
                    split_coefficient = VALUES(split_coefficient),
                    dividend_amount = VALUES(dividend_amount),
                    data_source = VALUES(data_source),
                    updated_at = CURRENT_TIMESTAMP
            ";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $priceData['date'],
                $priceData['open'] ?? 0,
                $priceData['high'] ?? 0,
                $priceData['low'] ?? 0,
                $priceData['close'] ?? 0,
                $priceData['adj_close'] ?? $priceData['close'] ?? 0,
                $priceData['volume'] ?? 0,
                $priceData['split_coefficient'] ?? 1.0,
                $priceData['dividend_amount'] ?? 0.0,
                $priceData['data_source'] ?? 'yahoo'
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to upsert price data for {$symbol}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Batch insert price data
     */
    public function batchUpsertPriceData(string $symbol, array $priceDataArray): int {
        if (empty($priceDataArray) || !$this->initializeStock($symbol)) {
            return 0;
        }
        
        $successCount = 0;
        
        try {
            $this->pdo->beginTransaction();
            
            foreach ($priceDataArray as $priceData) {
                if ($this->upsertPriceData($symbol, $priceData)) {
                    $successCount++;
                }
            }
            
            $this->pdo->commit();
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Batch price data insert failed for {$symbol}: " . $e->getMessage());
        }
        
        return $successCount;
    }
    
    /**
     * Get price data for a date range
     */
    public function getPriceData(string $symbol, ?string $startDate = null, ?string $endDate = null, int $limit = 1000): array {
        if (!$this->dbManager->stockTablesExist($symbol)) {
            return [];
        }
        
        try {
            $tableName = $this->dbManager->getTableName($symbol, 'prices');
            $whereClause = '';
            $params = [];
            
            if ($startDate && $endDate) {
                $whereClause = 'WHERE date BETWEEN ? AND ?';
                $params = [$startDate, $endDate];
            } elseif ($startDate) {
                $whereClause = 'WHERE date >= ?';
                $params = [$startDate];
            } elseif ($endDate) {
                $whereClause = 'WHERE date <= ?';
                $params = [$endDate];
            }
            
            $sql = "
                SELECT date, open_price as open, high_price as high, low_price as low, 
                       close_price as close, adj_close_price as adj_close, volume,
                       split_coefficient, dividend_amount, data_source, created_at
                FROM `{$tableName}` 
                {$whereClause}
                ORDER BY date DESC 
                LIMIT {$limit}
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Failed to get price data for {$symbol}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get latest price for a stock
     */
    public function getLatestPrice(string $symbol): ?array {
        $prices = $this->getPriceData($symbol, null, null, 1);
        return !empty($prices) ? $prices[0] : null;
    }
    
    // ===== FUNDAMENTAL DATA METHODS =====
    
    /**
     * Update fundamental data for a stock
     */
    public function updateFundamentals(string $symbol, array $fundamentalData): bool {
        if (!$this->initializeStock($symbol)) {
            return false;
        }
        
        try {
            $tableName = $this->dbManager->getTableName($symbol, 'fundamentals');
            
            $sql = "
                INSERT INTO `{$tableName}` (
                    report_date, market_cap, enterprise_value, pe_ratio, forward_pe, peg_ratio,
                    price_to_book, price_to_sales, ev_to_revenue, ev_to_ebitda,
                    gross_margin, operating_margin, net_margin, return_on_equity, return_on_assets,
                    return_on_invested_capital, debt_to_equity, current_ratio, quick_ratio, cash_ratio,
                    revenue_growth_yoy, earnings_growth_yoy, free_cash_flow_growth,
                    earnings_per_share, book_value_per_share, cash_per_share, revenue_per_share,
                    dividend_yield, dividend_per_share, payout_ratio, beta, volatility_30d,
                    analyst_rating, target_price, analyst_count, data_source
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    market_cap = VALUES(market_cap),
                    enterprise_value = VALUES(enterprise_value),
                    pe_ratio = VALUES(pe_ratio),
                    forward_pe = VALUES(forward_pe),
                    peg_ratio = VALUES(peg_ratio),
                    price_to_book = VALUES(price_to_book),
                    price_to_sales = VALUES(price_to_sales),
                    ev_to_revenue = VALUES(ev_to_revenue),
                    ev_to_ebitda = VALUES(ev_to_ebitda),
                    gross_margin = VALUES(gross_margin),
                    operating_margin = VALUES(operating_margin),
                    net_margin = VALUES(net_margin),
                    return_on_equity = VALUES(return_on_equity),
                    return_on_assets = VALUES(return_on_assets),
                    return_on_invested_capital = VALUES(return_on_invested_capital),
                    debt_to_equity = VALUES(debt_to_equity),
                    current_ratio = VALUES(current_ratio),
                    quick_ratio = VALUES(quick_ratio),
                    cash_ratio = VALUES(cash_ratio),
                    revenue_growth_yoy = VALUES(revenue_growth_yoy),
                    earnings_growth_yoy = VALUES(earnings_growth_yoy),
                    free_cash_flow_growth = VALUES(free_cash_flow_growth),
                    earnings_per_share = VALUES(earnings_per_share),
                    book_value_per_share = VALUES(book_value_per_share),
                    cash_per_share = VALUES(cash_per_share),
                    revenue_per_share = VALUES(revenue_per_share),
                    dividend_yield = VALUES(dividend_yield),
                    dividend_per_share = VALUES(dividend_per_share),
                    payout_ratio = VALUES(payout_ratio),
                    beta = VALUES(beta),
                    volatility_30d = VALUES(volatility_30d),
                    analyst_rating = VALUES(analyst_rating),
                    target_price = VALUES(target_price),
                    analyst_count = VALUES(analyst_count),
                    data_source = VALUES(data_source),
                    updated_at = CURRENT_TIMESTAMP
            ";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $fundamentalData['report_date'] ?? date('Y-m-d'),
                $fundamentalData['market_cap'] ?? null,
                $fundamentalData['enterprise_value'] ?? null,
                $fundamentalData['pe_ratio'] ?? null,
                $fundamentalData['forward_pe'] ?? null,
                $fundamentalData['peg_ratio'] ?? null,
                $fundamentalData['price_to_book'] ?? null,
                $fundamentalData['price_to_sales'] ?? null,
                $fundamentalData['ev_to_revenue'] ?? null,
                $fundamentalData['ev_to_ebitda'] ?? null,
                $fundamentalData['gross_margin'] ?? null,
                $fundamentalData['operating_margin'] ?? null,
                $fundamentalData['net_margin'] ?? null,
                $fundamentalData['return_on_equity'] ?? null,
                $fundamentalData['return_on_assets'] ?? null,
                $fundamentalData['return_on_invested_capital'] ?? null,
                $fundamentalData['debt_to_equity'] ?? null,
                $fundamentalData['current_ratio'] ?? null,
                $fundamentalData['quick_ratio'] ?? null,
                $fundamentalData['cash_ratio'] ?? null,
                $fundamentalData['revenue_growth_yoy'] ?? null,
                $fundamentalData['earnings_growth_yoy'] ?? null,
                $fundamentalData['free_cash_flow_growth'] ?? null,
                $fundamentalData['earnings_per_share'] ?? null,
                $fundamentalData['book_value_per_share'] ?? null,
                $fundamentalData['cash_per_share'] ?? null,
                $fundamentalData['revenue_per_share'] ?? null,
                $fundamentalData['dividend_yield'] ?? null,
                $fundamentalData['dividend_per_share'] ?? null,
                $fundamentalData['payout_ratio'] ?? null,
                $fundamentalData['beta'] ?? null,
                $fundamentalData['volatility_30d'] ?? null,
                $fundamentalData['analyst_rating'] ?? null,
                $fundamentalData['target_price'] ?? null,
                $fundamentalData['analyst_count'] ?? null,
                $fundamentalData['data_source'] ?? 'yahoo'
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to update fundamentals for {$symbol}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get fundamental data for a stock
     */
    public function getFundamentals(string $symbol): ?array {
        if (!$this->dbManager->stockTablesExist($symbol)) {
            return null;
        }
        
        try {
            $tableName = $this->dbManager->getTableName($symbol, 'fundamentals');
            
            $sql = "SELECT * FROM `{$tableName}` ORDER BY report_date DESC LIMIT 1";
            $stmt = $this->pdo->query($sql);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            
        } catch (Exception $e) {
            error_log("Failed to get fundamentals for {$symbol}: " . $e->getMessage());
            return null;
        }
    }
    
    // ===== TECHNICAL INDICATORS METHODS =====
    
    /**
     * Update technical indicators for a date
     */
    public function updateTechnicalIndicators(string $symbol, string $date, array $indicators): bool {
        if (!$this->initializeStock($symbol)) {
            return false;
        }
        
        try {
            $tableName = $this->dbManager->getTableName($symbol, 'technical');
            
            // Build dynamic SQL based on available indicators
            $fields = ['date'];
            $placeholders = ['?'];
            $values = [$date];
            
            $updateClauses = [];
            
            foreach ($indicators as $field => $value) {
                if ($value !== null) {
                    $fields[] = $field;
                    $placeholders[] = '?';
                    $values[] = $value;
                    $updateClauses[] = "{$field} = VALUES({$field})";
                }
            }
            
            if (empty($updateClauses)) {
                return false;
            }
            
            $fieldsStr = implode(', ', $fields);
            $placeholdersStr = implode(', ', $placeholders);
            $updateStr = implode(', ', $updateClauses);
            
            $sql = "
                INSERT INTO `{$tableName}` ({$fieldsStr})
                VALUES ({$placeholdersStr})
                ON DUPLICATE KEY UPDATE
                    {$updateStr},
                    updated_at = CURRENT_TIMESTAMP
            ";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
            
        } catch (Exception $e) {
            error_log("Failed to update technical indicators for {$symbol}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get technical indicators for a date range
     */
    public function getTechnicalIndicators(string $symbol, ?string $startDate = null, ?string $endDate = null, int $limit = 100): array {
        if (!$this->dbManager->stockTablesExist($symbol)) {
            return [];
        }
        
        try {
            $tableName = $this->dbManager->getTableName($symbol, 'technical');
            $whereClause = '';
            $params = [];
            
            if ($startDate && $endDate) {
                $whereClause = 'WHERE date BETWEEN ? AND ?';
                $params = [$startDate, $endDate];
            } elseif ($startDate) {
                $whereClause = 'WHERE date >= ?';
                $params = [$startDate];
            } elseif ($endDate) {
                $whereClause = 'WHERE date <= ?';
                $params = [$endDate];
            }
            
            $sql = "
                SELECT * FROM `{$tableName}` 
                {$whereClause}
                ORDER BY date DESC 
                LIMIT {$limit}
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Failed to get technical indicators for {$symbol}: " . $e->getMessage());
            return [];
        }
    }
    
    // ===== NEWS METHODS =====
    
    /**
     * Insert news article
     */
    public function insertNews(string $symbol, array $newsData): bool {
        if (!$this->initializeStock($symbol)) {
            return false;
        }
        
        try {
            $tableName = $this->dbManager->getTableName($symbol, 'news');
            
            $sql = "
                INSERT INTO `{$tableName}` (
                    news_id, headline, summary, content, author, source, source_url, published_at,
                    sentiment_score, sentiment_label, confidence_score, category, importance
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    headline = VALUES(headline),
                    summary = VALUES(summary),
                    content = VALUES(content),
                    author = VALUES(author),
                    sentiment_score = VALUES(sentiment_score),
                    sentiment_label = VALUES(sentiment_label),
                    confidence_score = VALUES(confidence_score),
                    category = VALUES(category),
                    importance = VALUES(importance),
                    updated_at = CURRENT_TIMESTAMP
            ";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $newsData['news_id'] ?? null,
                $newsData['headline'],
                $newsData['summary'] ?? null,
                $newsData['content'] ?? null,
                $newsData['author'] ?? null,
                $newsData['source'],
                $newsData['source_url'] ?? null,
                $newsData['published_at'],
                $newsData['sentiment_score'] ?? null,
                $newsData['sentiment_label'] ?? null,
                $newsData['confidence_score'] ?? null,
                $newsData['category'] ?? 'GENERAL',
                $newsData['importance'] ?? 'MEDIUM'
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to insert news for {$symbol}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get news articles for a stock
     */
    public function getNews(string $symbol, int $limit = 50, ?string $category = null): array {
        if (!$this->dbManager->stockTablesExist($symbol)) {
            return [];
        }
        
        try {
            $tableName = $this->dbManager->getTableName($symbol, 'news');
            $whereClause = '';
            $params = [];
            
            if ($category) {
                $whereClause = 'WHERE category = ?';
                $params[] = $category;
            }
            
            $sql = "
                SELECT * FROM `{$tableName}` 
                {$whereClause}
                ORDER BY published_at DESC 
                LIMIT {$limit}
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Failed to get news for {$symbol}: " . $e->getMessage());
            return [];
        }
    }
    
    // ===== ANALYSIS METHODS =====
    
    /**
     * Save analysis results
     */
    public function saveAnalysis(string $symbol, array $analysisData): bool {
        if (!$this->initializeStock($symbol)) {
            return false;
        }
        
        try {
            $tableName = $this->dbManager->getTableName($symbol, 'analysis');
            
            $sql = "
                INSERT INTO `{$tableName}` (
                    analysis_date, fundamental_score, technical_score, momentum_score, 
                    sentiment_score, news_score, overall_score, confidence_level,
                    recommendation, target_price, stop_loss, take_profit,
                    risk_level, volatility_assessment, risk_factors,
                    llm_analysis, llm_reasoning, llm_model, llm_tokens_used,
                    analysis_version, data_freshness_score
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    fundamental_score = VALUES(fundamental_score),
                    technical_score = VALUES(technical_score),
                    momentum_score = VALUES(momentum_score),
                    sentiment_score = VALUES(sentiment_score),
                    news_score = VALUES(news_score),
                    overall_score = VALUES(overall_score),
                    confidence_level = VALUES(confidence_level),
                    recommendation = VALUES(recommendation),
                    target_price = VALUES(target_price),
                    stop_loss = VALUES(stop_loss),
                    take_profit = VALUES(take_profit),
                    risk_level = VALUES(risk_level),
                    volatility_assessment = VALUES(volatility_assessment),
                    risk_factors = VALUES(risk_factors),
                    llm_analysis = VALUES(llm_analysis),
                    llm_reasoning = VALUES(llm_reasoning),
                    llm_model = VALUES(llm_model),
                    llm_tokens_used = VALUES(llm_tokens_used),
                    analysis_version = VALUES(analysis_version),
                    data_freshness_score = VALUES(data_freshness_score),
                    updated_at = CURRENT_TIMESTAMP
            ";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $analysisData['analysis_date'] ?? date('Y-m-d'),
                $analysisData['fundamental_score'] ?? null,
                $analysisData['technical_score'] ?? null,
                $analysisData['momentum_score'] ?? null,
                $analysisData['sentiment_score'] ?? null,
                $analysisData['news_score'] ?? null,
                $analysisData['overall_score'] ?? null,
                $analysisData['confidence_level'] ?? null,
                $analysisData['recommendation'] ?? null,
                $analysisData['target_price'] ?? null,
                $analysisData['stop_loss'] ?? null,
                $analysisData['take_profit'] ?? null,
                $analysisData['risk_level'] ?? null,
                $analysisData['volatility_assessment'] ?? null,
                $analysisData['risk_factors'] ?? null,
                $analysisData['llm_analysis'] ?? null,
                $analysisData['llm_reasoning'] ?? null,
                $analysisData['llm_model'] ?? null,
                $analysisData['llm_tokens_used'] ?? null,
                $analysisData['analysis_version'] ?? '1.0',
                $analysisData['data_freshness_score'] ?? null
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to save analysis for {$symbol}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get latest analysis for a stock
     */
    public function getLatestAnalysis(string $symbol): ?array {
        if (!$this->dbManager->stockTablesExist($symbol)) {
            return null;
        }
        
        try {
            $tableName = $this->dbManager->getTableName($symbol, 'analysis');
            
            $sql = "SELECT * FROM `{$tableName}` ORDER BY analysis_date DESC LIMIT 1";
            $stmt = $this->pdo->query($sql);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            
        } catch (Exception $e) {
            error_log("Failed to get analysis for {$symbol}: " . $e->getMessage());
            return null;
        }
    }
    
    // ===== UTILITY METHODS =====
    
    /**
     * Get comprehensive stock summary
     */
    public function getStockSummary(string $symbol): ?array {
        try {
            return [
                'symbol' => $symbol,
                'latest_price' => $this->getLatestPrice($symbol),
                'fundamentals' => $this->getFundamentals($symbol),
                'latest_analysis' => $this->getLatestAnalysis($symbol),
                'recent_news' => $this->getNews($symbol, 5),
                'price_history' => $this->getPriceData($symbol, date('Y-m-d', strtotime('-30 days')), null, 30)
            ];
            
        } catch (Exception $e) {
            error_log("Failed to get stock summary for {$symbol}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Search stocks by criteria
     */
    public function searchStocks(array $criteria = []): array {
        try {
            $sql = "SELECT * FROM stocks WHERE is_active = TRUE";
            $params = [];
            
            if (!empty($criteria['sector'])) {
                $sql .= " AND sector = ?";
                $params[] = $criteria['sector'];
            }
            
            if (!empty($criteria['industry'])) {
                $sql .= " AND industry = ?";
                $params[] = $criteria['industry'];
            }
            
            $sql .= " ORDER BY symbol";
            
            if (isset($criteria['limit'])) {
                $sql .= " LIMIT " . intval($criteria['limit']);
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Failed to search stocks: " . $e->getMessage());
            return [];
        }
    }
}