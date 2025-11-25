-- v1.0: Initial holdings table
CREATE TABLE IF NOT EXISTS holdings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT,
    symbol VARCHAR(32),
    as_of_date DATE,
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);
