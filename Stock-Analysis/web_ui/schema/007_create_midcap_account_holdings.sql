-- v1.0: Initial midcap_account_holdings table
CREATE TABLE IF NOT EXISTS midcap_account_holdings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(128),
    account_number VARCHAR(64),
    symbol VARCHAR(32),
    shares DECIMAL(18,4),
    avg_cost DECIMAL(18,4),
    market_value DECIMAL(18,4),
    as_of_date DATE,
    asset_type VARCHAR(64),
    currency_held_in VARCHAR(16),
    market VARCHAR(32),
    description VARCHAR(255),
    book_value DECIMAL(18,4),
    gain_loss_value DECIMAL(18,4),
    gain_loss_percent DECIMAL(8,4),
    percent_of_portfolio DECIMAL(8,4),
    closing_price DECIMAL(18,4),
    closing_value DECIMAL(18,4),
    extra JSON NULL
);
