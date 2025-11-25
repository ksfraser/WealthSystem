-- v1.0: Initial brokerages table
CREATE TABLE IF NOT EXISTS brokerages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) UNIQUE
);
