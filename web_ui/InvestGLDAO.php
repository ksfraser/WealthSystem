<?php
/**
 * InvestGLDAO
 * Data access object for double-entry investment GL transactions (gl_trans_invest)
 *
 * @startuml InvestGLDAO_Class_Diagram
 * class InvestGLDAO {
 *   + addOpeningBalance($userId, $glAccount, $symbol, $date, $qty, $price, $marketValue, $desc)
 *   + addTransaction($userId, $glAccount, $symbol, $date, $type, $amount, $qty, $price, $fees, $costBasis, $desc)
 *   + addOpeningBalanceAdjustment($userId, $glAccount, $symbol, $date, $amount, $qty, $desc)
 *   + matchTransactions($dummyId, $realId)
 *   + getTransactions($userId, $symbol = null, $status = null)
 * }
 * @enduml
 */
class InvestGLDAO {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Add an opening balance (dummy) transaction
     * @param int $userId
     * @param string $glAccount
     * @param string $symbol
     * @param string $date
     * @param float $qty
     * @param float $price
     * @param float $marketValue
     * @param string $desc
     * @return int Inserted ID
     */
    public function addOpeningBalance($userId, $glAccount, $symbol, $date, $qty, $price, $marketValue, $desc = '') {
        $sql = "INSERT INTO gl_trans_invest (user_id, gl_account, stock_symbol, tran_date, tran_type, amount, quantity, price, description, is_opening_balance) VALUES (?, ?, ?, ?, 'OPENING_BAL', ?, ?, ?, ?, 1)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $glAccount, $symbol, $date, $marketValue, $qty, $price, $desc]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Add a normal transaction (buy/sell/div/etc)
     */
    public function addTransaction($userId, $glAccount, $symbol, $date, $type, $amount, $qty, $price, $fees, $costBasis, $desc = '') {
        $sql = "INSERT INTO gl_trans_invest (user_id, gl_account, stock_symbol, tran_date, tran_type, amount, quantity, price, fees, cost_basis, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $glAccount, $symbol, $date, $type, $amount, $qty, $price, $fees, $costBasis, $desc]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Add an opening balance adjustment transaction
     */
    public function addOpeningBalanceAdjustment($userId, $glAccount, $symbol, $date, $amount, $qty, $desc = '') {
        $sql = "INSERT INTO gl_trans_invest (user_id, gl_account, stock_symbol, tran_date, tran_type, amount, quantity, description) VALUES (?, ?, ?, ?, 'OPENING_BAL_ADJ', ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $glAccount, $symbol, $date, $amount, $qty, $desc]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Mark two transactions as matched (dummy and real)
     */
    public function matchTransactions($dummyId, $realId) {
        $sql = "UPDATE gl_trans_invest SET matched_tran_id = ? WHERE id = ?";
        $this->pdo->prepare($sql)->execute([$realId, $dummyId]);
        $this->pdo->prepare($sql)->execute([$dummyId, $realId]);
    }

    /**
     * Get transactions for a user (optionally filter by symbol or status)
     */
    public function getTransactions($userId, $symbol = null, $status = null) {
        $sql = "SELECT * FROM gl_trans_invest WHERE user_id = ?";
        $params = [$userId];
        if ($symbol) {
            $sql .= " AND stock_symbol = ?";
            $params[] = $symbol;
        }
        if ($status === 'unmatched') {
            $sql .= " AND matched_tran_id IS NULL";
        } elseif ($status === 'matched') {
            $sql .= " AND matched_tran_id IS NOT NULL";
        }
        $sql .= " ORDER BY tran_date DESC, id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
