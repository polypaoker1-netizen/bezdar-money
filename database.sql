CREATE DATABASE IF NOT EXISTS bezdarmoney;
USE bezdarmoney;

-- Пользователи
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50) UNIQUE NOT NULL,
    balance INT DEFAULT 1000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Депозиты
CREATE TABLE deposits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50) NOT NULL,
    amount INT NOT NULL,
    ton_amount DECIMAL(10,2) NOT NULL,
    ton_address VARCHAR(100) DEFAULT 'UQAXba2HyRqtUiZLIk5KnHznrD47jhy0FxcIZAu2rSZzmVEJ',
    tx_hash VARCHAR(100),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL
);

-- Выводы
CREATE TABLE withdrawals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50) NOT NULL,
    amount INT NOT NULL,
    telegram_username VARCHAR(100) NOT NULL,
    fragment_tx_id VARCHAR(100),
    status ENUM('pending','processing','completed','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL
);

-- Транзакции
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50) NOT NULL,
    type ENUM('deposit','withdraw','game_win','game_lose') NOT NULL,
    amount INT NOT NULL,
    game_type VARCHAR(50),
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
