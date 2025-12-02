-- Create database if not exists
CREATE DATABASE IF NOT EXISTS amakarita;
USE amakarita;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    games_played INT DEFAULT 0,
    games_won INT DEFAULT 0,
    total_points INT DEFAULT 0
);

-- Games table
CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_code VARCHAR(10) NOT NULL UNIQUE,
    host VARCHAR(50) NOT NULL,
    player_count INT NOT NULL,
    visibility ENUM('public', 'private') NOT NULL,
    status ENUM('waiting', 'full', 'in_progress', 'completed') DEFAULT 'waiting',
    current_player INT DEFAULT 1,
    current_round INT DEFAULT 1,
    major_suit ENUM('hearts', 'diamonds', 'clubs', 'spades') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Game players table
CREATE TABLE IF NOT EXISTS game_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    player_number INT NOT NULL,
    is_cpu BOOLEAN DEFAULT FALSE,
    team INT DEFAULT NULL,
    points INT DEFAULT 0,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

-- Game cards table
CREATE TABLE IF NOT EXISTS game_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    player_id INT,
    suit ENUM('hearts', 'diamonds', 'clubs', 'spades') NOT NULL,
    value ENUM('ace', 'king', 'queen', 'jack', '7', '6', '5', '4', '3') NOT NULL,
    status ENUM('deck', 'hand', 'table', 'won') DEFAULT 'deck',
    round INT DEFAULT NULL,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES game_players(id) ON DELETE SET NULL
);

-- Game rounds table
CREATE TABLE IF NOT EXISTS game_rounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    round_number INT NOT NULL,
    winner_player_id INT,
    points_won INT DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (winner_player_id) REFERENCES game_players(id) ON DELETE SET NULL
);

-- Game moves table
CREATE TABLE IF NOT EXISTS game_moves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    player_id INT NOT NULL,
    card_id INT NOT NULL,
    round INT NOT NULL,
    move_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES game_players(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES game_cards(id) ON DELETE CASCADE
);

-- Game chat messages table
CREATE TABLE IF NOT EXISTS game_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

ALTER TABLE games 
ADD COLUMN show_all_cards TINYINT(1) DEFAULT 0,
ADD COLUMN cards_shown_time DATETIME NULL;