<?php

namespace Ksfraser\StockInfo;

/**
 * Portfolio Model - represents user portfolio holdings
 * 
 * This model handles portfolio information including:
 * - Stock holdings per user and account
 * - Book value, market value, and profit/loss calculations
 * - Dividend information and yields
 * - Portfolio performance metrics
 */
class Portfolio extends BaseModel
{
    protected $table = 'portfolio';
    protected $primaryKey = 'idportfolio';
    
    protected $fillable = [
        'stocksymbol',
        'username',
        'numbershares',
        'bookvalue',
        'marketvalue',
        'currentprice',
        'profitloss',
        'marketbook',
        'annualdividendpershare',
        'dividendpercentbookvalue',
        'dividendpercentmarketvalue',
        'lastupdate',
        'dividendyield',
        'percenttotalbookvalue',
        'percenttotalmarketvalue',
        'percenttotaldividend',
        'yield',
        'annualdividend',
        'dividendbookvalue',
        'account'
    ];

    /**
     * Get portfolio for a specific user
     */
    public function getUserPortfolio($username, $account = null)
    {
        $sql = "SELECT p.*, s.corporatename 
                FROM {$this->table} p
                LEFT JOIN stockinfo s ON p.stocksymbol = s.stocksymbol
                WHERE p.username = ?";
        $params = [$username];

        if ($account) {
            $sql .= " AND p.account = ?";
            $params[] = $account;
        }

        $sql .= " ORDER BY p.marketvalue DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get portfolio summary for a user
     */
    public function getUserPortfolioSummary($username, $account = null)
    {
        $sql = "SELECT 
                COUNT(*) as total_positions,
                SUM(bookvalue) as total_book_value,
                SUM(marketvalue) as total_market_value,
                SUM(profitloss) as total_profit_loss,
                SUM(annualdividend) as total_annual_dividend,
                AVG(dividendyield) as avg_dividend_yield,
                (SUM(profitloss) / SUM(bookvalue)) * 100 as total_return_percent
                FROM {$this->table} 
                WHERE username = ? AND numbershares > 0";
        $params = [$username];

        if ($account) {
            $sql .= " AND account = ?";
            $params[] = $account;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * Get holdings for a specific stock symbol
     */
    public function getStockHoldings($stockSymbol)
    {
        return $this->where(['stocksymbol' => $stockSymbol]);
    }

    /**
     * Get top holdings by market value
     */
    public function getTopHoldings($username, $limit = 10, $account = null)
    {
        $sql = "SELECT p.*, s.corporatename 
                FROM {$this->table} p
                LEFT JOIN stockinfo s ON p.stocksymbol = s.stocksymbol
                WHERE p.username = ? AND p.numbershares > 0";
        $params = [$username];

        if ($account) {
            $sql .= " AND p.account = ?";
            $params[] = $account;
        }

        $sql .= " ORDER BY p.marketvalue DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get dividend income summary
     */
    public function getDividendSummary($username, $account = null)
    {
        $sql = "SELECT 
                SUM(annualdividend) as total_annual_dividend,
                AVG(dividendyield) as avg_yield,
                COUNT(CASE WHEN annualdividendpershare > 0 THEN 1 END) as dividend_paying_stocks,
                (SUM(annualdividend) / SUM(marketvalue)) * 100 as portfolio_yield
                FROM {$this->table} 
                WHERE username = ? AND numbershares > 0";
        $params = [$username];

        if ($account) {
            $sql .= " AND account = ?";
            $params[] = $account;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * Get performance analysis
     */
    public function getPerformanceAnalysis($username, $account = null)
    {
        $sql = "SELECT 
                stocksymbol,
                corporatename,
                numbershares,
                bookvalue,
                marketvalue,
                profitloss,
                (profitloss / bookvalue) * 100 as return_percent,
                dividendyield,
                percenttotalmarketvalue as portfolio_weight
                FROM {$this->table} p
                LEFT JOIN stockinfo s ON p.stocksymbol = s.stocksymbol
                WHERE p.username = ? AND p.numbershares > 0";
        $params = [$username];

        if ($account) {
            $sql .= " AND p.account = ?";
            $params[] = $account;
        }

        $sql .= " ORDER BY return_percent DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Update portfolio position
     */
    public function updatePosition($username, $stockSymbol, $data, $account = 'ALL')
    {
        $existing = $this->where([
            'username' => $username,
            'stocksymbol' => $stockSymbol,
            'account' => $account
        ]);

        if (empty($existing)) {
            // Create new position
            $data['username'] = $username;
            $data['stocksymbol'] = $stockSymbol;
            $data['account'] = $account;
            $data['lastupdate'] = date('Y-m-d H:i:s');
            return $this->create($data);
        } else {
            // Update existing position
            $data['lastupdate'] = date('Y-m-d H:i:s');
            return $this->update($existing[0]->idportfolio, $data);
        }
    }

    /**
     * Calculate and update portfolio percentages
     */
    public function updatePortfolioPercentages($username, $account = null)
    {
        // Get total values
        $summary = $this->getUserPortfolioSummary($username, $account);
        $totalBookValue = $summary->total_book_value;
        $totalMarketValue = $summary->total_market_value;
        $totalDividend = $summary->total_annual_dividend;

        // Update each position
        $positions = $this->getUserPortfolio($username, $account);
        foreach ($positions as $position) {
            $data = [];
            
            if ($totalBookValue > 0) {
                $data['percenttotalbookvalue'] = ($position->bookvalue / $totalBookValue) * 100;
            }
            
            if ($totalMarketValue > 0) {
                $data['percenttotalmarketvalue'] = ($position->marketvalue / $totalMarketValue) * 100;
            }
            
            if ($totalDividend > 0) {
                $data['percenttotaldividend'] = ($position->annualdividend / $totalDividend) * 100;
            }

            if (!empty($data)) {
                $this->update($position->idportfolio, $data);
            }
        }
    }

    /**
     * Get account allocation
     */
    public function getAccountAllocation($username)
    {
        $sql = "SELECT 
                account,
                COUNT(*) as positions,
                SUM(bookvalue) as total_book_value,
                SUM(marketvalue) as total_market_value,
                SUM(profitloss) as total_profit_loss,
                SUM(annualdividend) as total_dividend
                FROM {$this->table} 
                WHERE username = ? AND numbershares > 0
                GROUP BY account
                ORDER BY total_market_value DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get sector allocation (requires joining with stock info)
     */
    public function getSectorAllocation($username, $account = null)
    {
        $sql = "SELECT 
                COALESCE(s.sector, 'Unknown') as sector,
                COUNT(*) as positions,
                SUM(p.marketvalue) as total_market_value,
                AVG(p.dividendyield) as avg_dividend_yield
                FROM {$this->table} p
                LEFT JOIN stockinfo s ON p.stocksymbol = s.stocksymbol
                WHERE p.username = ? AND p.numbershares > 0";
        $params = [$username];

        if ($account) {
            $sql .= " AND p.account = ?";
            $params[] = $account;
        }

        $sql .= " GROUP BY sector ORDER BY total_market_value DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get users with largest holdings of a stock
     */
    public function getTopHoldersOfStock($stockSymbol, $limit = 10)
    {
        $sql = "SELECT 
                username,
                account,
                numbershares,
                marketvalue,
                profitloss,
                (profitloss / bookvalue) * 100 as return_percent
                FROM {$this->table} 
                WHERE stocksymbol = ? AND numbershares > 0
                ORDER BY numbershares DESC 
                LIMIT ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$stockSymbol, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
