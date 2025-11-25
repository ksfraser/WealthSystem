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
     * @param int|null $bankAccountId
     * @return int Inserted ID
     */
    public function addOpeningBalance($userId, $glAccount, $symbol, $date, $qty, $price, $marketValue, $desc = '', $bankAccountId = null) {
        $sql = "INSERT INTO gl_trans_invest (user_id, gl_account, stock_symbol, tran_date, tran_type, amount, quantity, price, description, is_opening_balance, bank_account_id) VALUES (?, ?, ?, ?, 'OPENING_BAL', ?, ?, ?, ?, 1, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $glAccount, $symbol, $date, $marketValue, $qty, $price, $desc, $bankAccountId]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Add a normal transaction (buy/sell/div/etc)
     */
    public function addTransaction($userId, $glAccount, $symbol, $date, $type, $amount, $qty, $price, $fees, $costBasis, $desc = '', $bankAccountId = null) {
        $sql = "INSERT INTO gl_trans_invest (user_id, gl_account, stock_symbol, tran_date, tran_type, amount, quantity, price, fees, cost_basis, description, bank_account_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $glAccount, $symbol, $date, $type, $amount, $qty, $price, $fees, $costBasis, $desc, $bankAccountId]);
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
    public function getTransactions($userId, $symbol = null, $status = null, $bankAccountId = null) {
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
        if ($bankAccountId) {
            $sql .= " AND bank_account_id = ?";
            $params[] = $bankAccountId;
        }
        $sql .= " ORDER BY tran_date DESC, id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single transaction by its ID, ensuring it belongs to the user.
     */
    public function getTransactionById($transactionId, $userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM gl_trans_invest WHERE id = ? AND user_id = ?");
        $stmt->execute([$transactionId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update a transaction.
     */
    public function updateTransaction($transactionId, $data, $userId) {
        $sql = "UPDATE gl_trans_invest SET 
                    tran_date = :tran_date,
                    tran_type = :tran_type,
                    stock_symbol = :stock_symbol,
                    quantity = :quantity,
                    price = :price,
                    amount = :amount,
                    description = :description
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        
        $params = [
            ':tran_date' => $data['tran_date'],
            ':tran_type' => $data['tran_type'],
            ':stock_symbol' => $data['stock_symbol'],
            ':quantity' => $data['quantity'],
            ':price' => $data['price'],
            ':amount' => $data['amount'],
            ':description' => $data['description'],
            ':id' => $transactionId,
            ':user_id' => $userId
        ];
        
        return $stmt->execute($params);
    }

    /**
     * Delete a transaction.
     */
    public function deleteTransaction($transactionId, $userId) {
        $stmt = $this->pdo->prepare("DELETE FROM gl_trans_invest WHERE id = ? AND user_id = ?");
        $stmt->execute([$transactionId, $userId]);
        return $stmt->rowCount() > 0;
    }
}
