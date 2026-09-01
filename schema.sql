-- FRP Tool API — MySQL schema
-- Import: mysql -u root -p frptool < schema.sql

CREATE DATABASE IF NOT EXISTS frptool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE frptool;

CREATE TABLE IF NOT EXISTS actived_server (
    id INT PRIMARY KEY,
    server_id INT DEFAULT 1,
    region VARCHAR(32) NOT NULL DEFAULT 'India',
    token TEXT,
    activeBy VARCHAR(32) DEFAULT 'ByID'
);

CREATE TABLE IF NOT EXISTS servers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    mac VARCHAR(255) DEFAULT '',
    region VARCHAR(32) DEFAULT 'India'
);

CREATE TABLE IF NOT EXISTS tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    generated_token VARCHAR(255) NOT NULL,
    original_token TEXT NOT NULL,
    status ENUM('unused', 'used') DEFAULT 'unused'
);

CREATE TABLE IF NOT EXISTS cotp (
    id INT PRIMARY KEY,
    otp VARCHAR(64) NOT NULL
);

CREATE TABLE IF NOT EXISTS devdata (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chipSn VARCHAR(255),
    mainPlatform VARCHAR(255),
    subPlatform VARCHAR(255),
    account VARCHAR(255),
    user VARCHAR(64),
    time VARCHAR(64)
);

-- Sample row for cert/upgrade.php and sign routing (region India)
INSERT INTO actived_server (id, server_id, region, token, activeBy)
VALUES (4, 1, 'India', '', 'ByID')
ON DUPLICATE KEY UPDATE region = VALUES(region);

-- OTP slot used by api/sign/login.php (id=1)
INSERT INTO cotp (id, otp) VALUES (1, 'TEST-OTP-CHANGE-ME')
ON DUPLICATE KEY UPDATE otp = VALUES(otp);
