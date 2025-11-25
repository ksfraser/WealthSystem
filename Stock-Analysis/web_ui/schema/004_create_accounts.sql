-- v1.0: Initial accounts table
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    brokerage_id INT,
    account_type_id INT,
    account_number VARCHAR(64),
    nickname VARCHAR(128),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (brokerage_id) REFERENCES brokerages(id),
    FOREIGN KEY (account_type_id) REFERENCES account_types(id)
);
