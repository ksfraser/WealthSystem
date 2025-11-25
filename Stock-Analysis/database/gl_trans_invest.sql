-- gl_trans_invest.sql
-- Migration for investment GL transactions table (FA-compatible, double-entry)

CREATE TABLE IF NOT EXISTS gl_trans_invest (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    gl_account TEXT NOT NULL,
    stock_symbol TEXT DEFAULT NULL,
    tran_date TEXT NOT NULL,
    tran_type TEXT NOT NULL,
    amount REAL NOT NULL,
    quantity REAL DEFAULT NULL,
    price REAL DEFAULT NULL,
    fees REAL DEFAULT 0,
    cost_basis REAL DEFAULT NULL,
    description TEXT,
    matched_tran_id INTEGER DEFAULT NULL,
    is_opening_balance INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    -- Indexes omitted for SQLite test compatibility
);
