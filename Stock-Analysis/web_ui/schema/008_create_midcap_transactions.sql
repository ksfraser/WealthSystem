-- v1.0: Initial midcap_transactions table
CREATE TABLE IF NOT EXISTS midcap_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(128),
    account_number VARCHAR(64),
    symbol VARCHAR(32),
    txn_type VARCHAR(32),
    shares DECIMAL(18,4),
    price DECIMAL(18,4),
    amount DECIMAL(18,4),
    txn_date DATE,
    settlement_date DATE,
    currency_subaccount VARCHAR(16),
    market VARCHAR(32),
    description VARCHAR(255),
    currency_of_price VARCHAR(16),
    commission DECIMAL(18,4),
    exchange_rate DECIMAL(18,8),
    currency_of_amount VARCHAR(16),
    settlement_instruction VARCHAR(64),
    exchange_rate_cad DECIMAL(18,8),
    cad_equivalent DECIMAL(18,4),
    extra JSON NULL
);
