-- Create GL transactions table for investment double-entry accounting
-- This table stores all investment-related transactions in GL format
--
-- Requirements Implemented:
-- FR-GL-1: Double-entry accounting for investment transactions
-- FR-GL-2: Track opening balances and adjustments
-- FR-GL-3: Match imported transactions with real transactions
-- FR-GL-4: User-specific transaction isolation
-- NFR-GL-1: Efficient querying by date and symbol
-- NFR-GL-2: Support for various transaction types
--
CREATE TABLE IF NOT EXISTS gl_trans_invest (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    gl_account VARCHAR(20) NOT NULL,
    stock_symbol VARCHAR(10) NOT NULL,
    tran_date DATE NOT NULL,
    tran_type ENUM('OPENING_BAL', 'OPENING_BAL_ADJ', 'BUY', 'SELL', 'DIV', 'FEE', 'ADJ') NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    quantity DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    price DECIMAL(15,4) NULL,
    fees DECIMAL(15,2) NULL DEFAULT 0.00,
    cost_basis DECIMAL(15,2) NULL,
    description TEXT,
    is_opening_balance TINYINT(1) NOT NULL DEFAULT 0,
    matched_tran_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (matched_tran_id) REFERENCES gl_trans_invest(id) ON DELETE SET NULL,

    INDEX idx_user_date (user_id, tran_date),
    INDEX idx_user_symbol (user_id, stock_symbol),
    INDEX idx_tran_type (tran_type),
    INDEX idx_matched (matched_tran_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;