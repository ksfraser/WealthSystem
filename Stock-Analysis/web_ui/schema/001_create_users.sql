-- v1.0: Initial users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) UNIQUE,
    email VARCHAR(128),
    password_hash VARCHAR(255),
    is_admin TINYINT(1) DEFAULT 0
);
