-- v1.0: Initial holdings_performance table
CREATE TABLE IF NOT EXISTS holdings_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holding_id INT,
    date DATE,
    market_value DECIMAL(18,4),
    book_value DECIMAL(18,4),
    gain_loss_value DECIMAL(18,4),
    gain_loss_percent DECIMAL(8,4),
    percent_of_portfolio DECIMAL(8,4),
    closing_price DECIMAL(18,4),
    closing_value DECIMAL(18,4),
    extra JSON NULL,
    FOREIGN KEY (holding_id) REFERENCES holdings(id)
);
