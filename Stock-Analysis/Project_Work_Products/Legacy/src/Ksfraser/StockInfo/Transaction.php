<?php

namespace Ksfraser\StockInfo;

/**
 * Transaction Model - represents trading transactions
 * 
 * This model handles transaction records including:
 * - Buy/sell transactions
 * - Transaction types and amounts
 * - User and account associations
 * - Transaction history and reporting
 */
class Transaction extends BaseModel
{
    protected $table = 'transaction';
    protected $primaryKey = 'sequence';
    
    protected $fillable = [
        'username',
        'idstockinfo',
        'stocksymbol',
        'numbershares',
        'transactiontype',
        'dollar',
        'idusers',
        'idcurrency',
        'idaccount',
        'transactiondate',
        'createduser',
        'createddate',
        'reviseddate',
        'reviseduser'
    ];

    /**
     * Get transactions for a user
     */
    public function getUserTransactions($username, $limit = null, $offset = 0)
    {
        $sql = "SELECT t.*, s.corporatename, tt.description as transaction_description
                FROM {$this->table} t
                LEFT JOIN stockinfo s ON t.stocksymbol = s.stocksymbol
                LEFT JOIN transactiontype tt ON t.transactiontype = tt.transactiontype
                WHERE t.username = ?
                ORDER BY t.transactiondate DESC, t.sequence DESC";
        
        $params = [$username];
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get transactions for a specific stock
     */
    public function getStockTransactions($stockSymbol, $username = null)
    {
        $sql = "SELECT t.*, tt.description as transaction_description
                FROM {$this->table} t
                LEFT JOIN transactiontype tt ON t.transactiontype = tt.transactiontype
                WHERE t.stocksymbol = ?";
        $params = [$stockSymbol];

        if ($username) {
            $sql .= " AND t.username = ?";
            $params[] = $username;
        }

        $sql .= " ORDER BY t.transactiondate DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get transactions by type
     */
    public function getTransactionsByType($transactionType, $username = null, $dateFrom = null, $dateTo = null)
    {
        $sql = "SELECT t.*, s.corporatename
                FROM {$this->table} t
                LEFT JOIN stockinfo s ON t.stocksymbol = s.stocksymbol
                WHERE t.transactiontype = ?";
        $params = [$transactionType];

        if ($username) {
            $sql .= " AND t.username = ?";
            $params[] = $username;
        }

        if ($dateFrom) {
            $sql .= " AND t.transactiondate >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND t.transactiondate <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY t.transactiondate DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get transaction summary for a user
     */
    public function getUserTransactionSummary($username, $dateFrom = null, $dateTo = null)
    {
        $sql = "SELECT 
                COUNT(*) as total_transactions,
                SUM(CASE WHEN transactiontype = 'BUY' THEN dollar ELSE 0 END) as total_purchases,
                SUM(CASE WHEN transactiontype = 'SELL' THEN dollar ELSE 0 END) as total_sales,
                SUM(CASE WHEN transactiontype = 'DIVIDEND' THEN dollar ELSE 0 END) as total_dividends,
                COUNT(DISTINCT stocksymbol) as unique_stocks,
                AVG(dollar) as avg_transaction_amount
                FROM {$this->table} 
                WHERE username = ?";
        $params = [$username];

        if ($dateFrom) {
            $sql .= " AND transactiondate >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND transactiondate <= ?";
            $params[] = $dateTo;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * Get monthly transaction summary
     */
    public function getMonthlyTransactionSummary($username, $year = null)
    {
        $year = $year ?: date('Y');
        
        $sql = "SELECT 
                MONTH(transactiondate) as month,
                MONTHNAME(transactiondate) as month_name,
                COUNT(*) as transaction_count,
                SUM(dollar) as total_amount,
                AVG(dollar) as avg_amount
                FROM {$this->table} 
                WHERE username = ? AND YEAR(transactiondate) = ?
                GROUP BY MONTH(transactiondate), MONTHNAME(transactiondate)
                ORDER BY MONTH(transactiondate)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username, $year]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Calculate cost basis for a stock position
     */
    public function getCostBasis($username, $stockSymbol, $asOfDate = null)
    {
        $asOfDate = $asOfDate ?: date('Y-m-d');
        
        $sql = "SELECT 
                SUM(CASE 
                    WHEN transactiontype = 'BUY' THEN numbershares 
                    WHEN transactiontype = 'SELL' THEN -numbershares 
                    ELSE 0 
                END) as net_shares,
                SUM(CASE 
                    WHEN transactiontype = 'BUY' THEN dollar 
                    WHEN transactiontype = 'SELL' THEN -dollar 
                    ELSE 0 
                END) as net_cost
                FROM {$this->table} 
                WHERE username = ? 
                AND stocksymbol = ? 
                AND transactiondate <= ?
                AND transactiontype IN ('BUY', 'SELL')";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username, $stockSymbol, $asOfDate]);
        $result = $stmt->fetch(\PDO::FETCH_OBJ);
        
        if ($result && $result->net_shares > 0) {
            $result->avg_cost_per_share = $result->net_cost / $result->net_shares;
        }
        
        return $result;
    }

    /**
     * Get realized gains/losses
     */
    public function getRealizedGainsLosses($username, $dateFrom = null, $dateTo = null)
    {
        // This is a simplified calculation - proper implementation would need FIFO/LIFO logic
        $sql = "SELECT 
                stocksymbol,
                SUM(CASE 
                    WHEN transactiontype = 'SELL' THEN dollar 
                    ELSE 0 
                END) as total_sales,
                COUNT(CASE WHEN transactiontype = 'SELL' THEN 1 END) as sell_transactions
                FROM {$this->table} 
                WHERE username = ?";
        $params = [$username];

        if ($dateFrom) {
            $sql .= " AND transactiondate >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND transactiondate <= ?";
            $params[] = $dateTo;
        }

        $sql .= " GROUP BY stocksymbol HAVING total_sales > 0 ORDER BY total_sales DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get dividend history
     */
    public function getDividendHistory($username, $stockSymbol = null, $year = null)
    {
        $sql = "SELECT 
                stocksymbol,
                transactiondate,
                dollar as dividend_amount,
                numbershares
                FROM {$this->table} 
                WHERE username = ? AND transactiontype = 'DIVIDEND'";
        $params = [$username];

        if ($stockSymbol) {
            $sql .= " AND stocksymbol = ?";
            $params[] = $stockSymbol;
        }

        if ($year) {
            $sql .= " AND YEAR(transactiondate) = ?";
            $params[] = $year;
        }

        $sql .= " ORDER BY transactiondate DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Add a new transaction
     */
    public function addTransaction($data)
    {
        $data['createddate'] = date('Y-m-d H:i:s');
        $data['reviseddate'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }

    /**
     * Get most traded stocks
     */
    public function getMostTradedStocks($username = null, $limit = 20)
    {
        $sql = "SELECT 
                stocksymbol,
                s.corporatename,
                COUNT(*) as transaction_count,
                SUM(ABS(numbershares)) as total_shares_traded,
                SUM(dollar) as total_dollar_volume
                FROM {$this->table} t
                LEFT JOIN stockinfo s ON t.stocksymbol = s.stocksymbol
                WHERE transactiontype IN ('BUY', 'SELL')";
        $params = [];

        if ($username) {
            $sql .= " AND username = ?";
            $params[] = $username;
        }

        $sql .= " GROUP BY stocksymbol 
                  ORDER BY transaction_count DESC 
                  LIMIT ?";
        $params[] = $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get trading activity by date range
     */
    public function getTradingActivity($dateFrom, $dateTo, $username = null)
    {
        $sql = "SELECT 
                DATE(transactiondate) as trade_date,
                COUNT(*) as transaction_count,
                SUM(dollar) as total_volume,
                COUNT(DISTINCT username) as active_users,
                COUNT(DISTINCT stocksymbol) as unique_stocks
                FROM {$this->table} 
                WHERE transactiondate BETWEEN ? AND ?
                AND transactiontype IN ('BUY', 'SELL')";
        $params = [$dateFrom, $dateTo];

        if ($username) {
            $sql .= " AND username = ?";
            $params[] = $username;
        }

        $sql .= " GROUP BY DATE(transactiondate) ORDER BY trade_date DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
