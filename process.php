<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php?action=login");
    exit();
}

// Database connection
// Database connection
$servername = "sql100.infinityfree.com";
$username = "if0_40577586";
$password = "amakarita123";
$dbname = "if0_40577586_amakarita";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if game code is provided
if (!isset($_GET['code'])) {
    header("Location: games.php");
    exit();
}

$game_code = $_GET['code'];
$current_user = $_SESSION['username'];

// Get game information
$game_sql = "SELECT * FROM games WHERE game_code = '$game_code'";
$game_result = $conn->query($game_sql);

if ($game_result->num_rows == 0) {
    // Game not found
    header("Location: games.php");
    exit();
}

$game = $game_result->fetch_assoc();
$game_id = $game['id'];
$player_count = $game['player_count'];
$major_suit = $game['major_suit'];
$current_round = $game['current_round'];
$current_player_number = $game['current_player'];

// Get all players in the game
$players_sql = "SELECT * FROM game_players WHERE game_id = $game_id ORDER BY player_number";
$players_result = $conn->query($players_sql);
$players = [];
$current_player_id = null;
$current_user_player_id = null;
$current_user_player_number = null;

while ($player = $players_result->fetch_assoc()) {
    $players[] = $player;
    if ($player['player_number'] == $current_player_number) {
        $current_player_id = $player['id'];
    }
    if ($player['username'] === $current_user) {
        $current_user_player_id = $player['id'];
        $current_user_player_number = $player['player_number'];
    }
}

// Check if the current user is in the game
if (!$current_user_player_id) {
    header("Location: games.php");
    exit();
}

// Assign teams for 4 or 6 player games
if ($player_count == 4 || $player_count == 6) {
    // Check if teams are already assigned
    $team_check_sql = "SELECT COUNT(*) as count FROM game_players WHERE game_id = $game_id AND team IS NOT NULL";
    $team_check_result = $conn->query($team_check_sql);
    $teams_assigned = $team_check_result->fetch_assoc()['count'] > 0;
    
    if (!$teams_assigned) {
        // Assign teams (1 or 2) - players 1,3,5 are team 1, players 2,4,6 are team 2
        foreach ($players as $player) {
            $player_number = $player['player_number'];
            $team = ($player_number % 2 == 0) ? 2 : 1; // Even numbers team 2, odd numbers team 1
            $player_id = $player['id'];
            $update_sql = "UPDATE game_players SET team = $team WHERE id = $player_id";
            $conn->query($update_sql);
        }
        
        // Refresh player data
        $players_result = $conn->query($players_sql);
        $players = [];
        while ($player = $players_result->fetch_assoc()) {
            $players[] = $player;
            if ($player['username'] === $current_user) {
                $current_user_player_id = $player['id'];
                $current_user_player_number = $player['player_number'];
            }
        }
    }
}
// Get current user's team
$current_user_team = null;
foreach ($players as $player) {
    if ($player['id'] == $current_user_player_id) {
        $current_user_team = $player['team'];
        break;
    }
}

// Get cards on the table for the current round (ordered by play time, not player number)
$table_cards_sql = "SELECT c.*, p.username, p.player_number, p.team, m.move_time 
                    FROM game_cards c 
                    JOIN game_players p ON c.player_id = p.id 
                    JOIN game_moves m ON c.id = m.card_id AND m.game_id = c.game_id AND m.round = c.round
                    WHERE c.game_id = $game_id AND c.status = 'table' AND c.round = $current_round
                    ORDER BY m.move_time ASC";
$table_cards_result = $conn->query($table_cards_sql);
$table_cards = [];
while ($card = $table_cards_result->fetch_assoc()) {
    $table_cards[] = $card;
}

// Get cards in current user's hand
$hand_cards_sql = "SELECT * FROM game_cards WHERE game_id = $game_id AND player_id = $current_user_player_id AND status = 'hand'";
$hand_cards_result = $conn->query($hand_cards_sql);
$hand_cards = [];
while ($card = $hand_cards_result->fetch_assoc()) {
    $hand_cards[] = $card;
}

// Get all played cards from previous rounds
$played_cards_sql = "SELECT c.*, p.username, p.player_number, r.round_number, r.winner_player_id 
                     FROM game_cards c 
                     JOIN game_players p ON c.player_id = p.id 
                     JOIN game_rounds r ON c.round = r.round_number AND r.game_id = c.game_id
                     WHERE c.game_id = $game_id AND c.status = 'won' AND r.completed = 1
                     ORDER BY r.round_number DESC, p.player_number";
$played_cards_result = $conn->query($played_cards_sql);
$played_cards = [];
while ($card = $played_cards_result->fetch_assoc()) {
    $played_cards[] = $card;
}

// Get player scores
$scores_sql = "SELECT p.username, p.player_number, p.team, p.points, p.is_cpu 
               FROM game_players p 
               WHERE p.game_id = $game_id 
               ORDER BY p.player_number";
$scores_result = $conn->query($scores_sql);
$scores = [];
while ($score = $scores_result->fetch_assoc()) {
    $scores[] = $score;
}

// Calculate team scores for team games
$team_scores = [];
if ($player_count == 4 || $player_count == 6) {
    foreach ($scores as $score) {
        if (!isset($team_scores[$score['team']])) {
            $team_scores[$score['team']] = 0;
        }
        $team_scores[$score['team']] += $score['points'];
    }
}

// Auto-play for inactive human player (used by JS after timeout)
if (isset($_POST['auto_play']) && $current_player_number == $current_user_player_number) {
    // Re-fetch current user's hand (fresh in case state changed)
    $auto_hand_sql = "SELECT * FROM game_cards WHERE game_id = $game_id AND player_id = $current_user_player_id AND status = 'hand'";
    $auto_hand_result = $conn->query($auto_hand_sql);
    $auto_hand_cards = [];
    while ($card = $auto_hand_result->fetch_assoc()) {
        $auto_hand_cards[] = $card;
    }

    if (count($auto_hand_cards) > 0) {
        // Use the same intelligent logic as CPU to choose a card
        // Build a "player" object for the current user
        $current_user_player = null;
        foreach ($players as $p) {
            if ($p['id'] == $current_user_player_id) {
                $current_user_player = $p;
                break;
            }
        }

        // Re-fetch table cards for this round (ordered by play time)
        $auto_table_sql = "SELECT c.*, p.player_number, p.team, m.move_time 
                           FROM game_cards c 
                           JOIN game_players p ON c.player_id = p.id 
                           JOIN game_moves m ON c.id = m.card_id AND m.game_id = c.game_id AND m.round = c.round
                           WHERE c.game_id = $game_id AND c.status = 'table' AND c.round = $current_round
                           ORDER BY m.move_time ASC";
        $auto_table_result = $conn->query($auto_table_sql);
        $auto_table_cards = [];
        while ($card = $auto_table_result->fetch_assoc()) {
            $auto_table_cards[] = $card;
        }

        if ($current_user_player) {
            $auto_card_to_play = makeCPUMove($auto_hand_cards, $auto_table_cards, $major_suit, $current_user_player, $players, $player_count);

            if ($auto_card_to_play) {
                $card_id = $auto_card_to_play['id'];

                // Play the card
                $update_sql = "UPDATE game_cards SET status = 'table', round = $current_round WHERE id = $card_id";
                if ($conn->query($update_sql) === TRUE) {
                    // Record the move
                    $move_sql = "INSERT INTO game_moves (game_id, player_id, card_id, round) 
                                 VALUES ($game_id, $current_user_player_id, $card_id, $current_round)";
                    $conn->query($move_sql);

                    // Check if all players have played a card for this round
                    $table_count_sql = "SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND status = 'table' AND round = $current_round";
                    $table_count_result = $conn->query($table_count_sql);
                    $table_count = $table_count_result->fetch_assoc()['count'];

                    if ($table_count == $player_count) {
                        // All players have played, determine the winner
                        determineRoundWinner($conn, $game_id, $current_round, $major_suit, $players);

                        // Check if the game is over
                        $cards_left_sql = "SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND status = 'deck'";
                        $cards_left_result = $conn->query($cards_left_sql);
                        $cards_left = $cards_left_result->fetch_assoc()['count'];

                        $hands_empty_sql = "SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND status = 'hand'";
                        $hands_empty_result = $conn->query($hands_empty_sql);
                        $hands_empty = $hands_empty_result->fetch_assoc()['count'] == 0;

                        if ($cards_left == 0 && $hands_empty) {
                            // Game is over
                            $update_game_sql = "UPDATE games SET status = 'completed' WHERE id = $game_id";
                            $conn->query($update_game_sql);
                        } else {
                            // Deal new cards if available
                            if ($cards_left > 0) {
                                dealNewCards($conn, $game_id, $players);
                            }

                            // Start new round
                            $new_round = $current_round + 1;

                            // Get the winner of the previous round
                            $winner_sql = "SELECT winner_player_id FROM game_rounds WHERE game_id = $game_id AND round_number = $current_round";
                            $winner_result = $conn->query($winner_sql);
                            $winner_id = $winner_result->fetch_assoc()['winner_player_id'];

                            // Find the winner's player number
                            $winner_player_number = 1; // Default
                            foreach ($players as $player) {
                                if ($player['id'] == $winner_id) {
                                    $winner_player_number = $player['player_number'];
                                    break;
                                }
                            }

                            // Update game for new round
                            $update_game_sql = "UPDATE games SET current_round = $new_round, current_player = $winner_player_number WHERE id = $game_id";
                            $conn->query($update_game_sql);
                        }
                    } else {
                        // Move to next player
                        $next_player = getNextPlayer($current_player_number, $player_count);
                        $update_game_sql = "UPDATE games SET current_player = $next_player WHERE id = $game_id";
                        $conn->query($update_game_sql);
                    }

                    // Refresh the page to update game state
                    header("Location: playroom.php?code=$game_code");
                    exit();
                }
            }
        }
    }
}

// Process player's manual move
if (isset($_POST['play_card']) && $current_player_number == $current_user_player_number) {
    $card_id = $_POST['card_id'];
    
    // Verify the card belongs to the current user
    $verify_sql = "SELECT * FROM game_cards WHERE id = $card_id AND player_id = $current_user_player_id AND status = 'hand'";
    $verify_result = $conn->query($verify_sql);
    
    if ($verify_result->num_rows > 0) {
        $card = $verify_result->fetch_assoc();
        
        // Play the card
        $update_sql = "UPDATE game_cards SET status = 'table', round = $current_round WHERE id = $card_id";
        if ($conn->query($update_sql) === TRUE) {
            // Record the move
            $move_sql = "INSERT INTO game_moves (game_id, player_id, card_id, round) 
                         VALUES ($game_id, $current_user_player_id, $card_id, $current_round)";
            $conn->query($move_sql);
            
            // Check if all players have played a card for this round
            $table_count_sql = "SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND status = 'table' AND round = $current_round";
            $table_count_result = $conn->query($table_count_sql);
            $table_count = $table_count_result->fetch_assoc()['count'];
            
            if ($table_count == $player_count) {
                // All players have played, determine the winner
                determineRoundWinner($conn, $game_id, $current_round, $major_suit, $players);
                
                // Check if the game is over
                $cards_left_sql = "SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND status = 'deck'";
                $cards_left_result = $conn->query($cards_left_sql);
                $cards_left = $cards_left_result->fetch_assoc()['count'];
                
                $hands_empty_sql = "SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND status = 'hand'";
                $hands_empty_result = $conn->query($hands_empty_sql);
                $hands_empty = $hands_empty_result->fetch_assoc()['count'] == 0;
                
                if ($cards_left == 0 && $hands_empty) {
                    // Game is over
                    $update_game_sql = "UPDATE games SET status = 'completed' WHERE id = $game_id";
                    $conn->query($update_game_sql);
                    
                    // Update player stats
                    foreach ($players as $player) {
                        $username = $player['username'];
                        if (!$player['is_cpu']) {
                            $update_stats_sql = "UPDATE users SET games_played = games_played + 1, 
                                                total_points = total_points + (SELECT points FROM game_players WHERE id = {$player['id']})
                                                WHERE username = '$username'";
                            $conn->query($update_stats_sql);
                        }
                    }
                    
                    // Determine winner and update stats
                    if ($player_count == 4 || $player_count == 6) {
                        // Team game
                        $team_points = [];
                        foreach ($players as $player) {
                            if (!isset($team_points[$player['team']])) {
                                $team_points[$player['team']] = 0;
                            }
                            $team_points[$player['team']] += $player['points'];
                        }
                        
                        $winning_team = array_keys($team_points, max($team_points))[0];
                        
                        foreach ($players as $player) {
                            if ($player['team'] == $winning_team && !$player['is_cpu']) {
                                $username = $player['username'];
                                $update_win_sql = "UPDATE users SET games_won = games_won + 1 WHERE username = '$username'";
                                $conn->query($update_win_sql);
                            }
                        }
                    } else {
                        // Individual game
                        $max_points = 0;
                        $winner_username = '';
                        
                        foreach ($players as $player) {
                            if ($player['points'] > $max_points) {
                                $max_points = $player['points'];
                                $winner_username = $player['username'];
                            }
                        }
                        
                        if (!empty($winner_username)) {
                            $update_win_sql = "UPDATE users SET games_won = games_won + 1 WHERE username = '$winner_username'";
                            $conn->query($update_win_sql);
                        }
                    }
                } else {
                    // Deal new cards if available
                    if ($cards_left > 0) {
                        dealNewCards($conn, $game_id, $players);
                    }
                    
                    // Start new round
                    $new_round = $current_round + 1;
                    
                    // Get the winner of the previous round
                    $winner_sql = "SELECT winner_player_id FROM game_rounds WHERE game_id = $game_id AND round_number = $current_round";
                    $winner_result = $conn->query($winner_sql);
                    $winner_id = $winner_result->fetch_assoc()['winner_player_id'];
                    
                    // Find the winner's player number
                    $winner_player_number = 1; // Default
                    foreach ($players as $player) {
                        if ($player['id'] == $winner_id) {
                            $winner_player_number = $player['player_number'];
                            break;
                        }
                    }
                    
                    // Update game for new round
                    $update_game_sql = "UPDATE games SET current_round = $new_round, current_player = $winner_player_number WHERE id = $game_id";
                    $conn->query($update_game_sql);
                }
            } else {
                // Move to next player
                $next_player = getNextPlayer($current_player_number, $player_count);
                $update_game_sql = "UPDATE games SET current_player = $next_player WHERE id = $game_id";
                $conn->query($update_game_sql);
                
                // If next player is CPU, make their move after a short delay
                foreach ($players as $player) {
                    if ($player['player_number'] == $next_player && $player['is_cpu']) {
                        // CPU will play automatically on page refresh
                        break;
                    }
                }
            }
            
            // Refresh the page to update game state
            header("Location: playroom.php?code=$game_code");
            exit();
        }
    }
}

// Make CPU move if it's a CPU's turn
foreach ($players as $player) {
    if ($player['player_number'] == $current_player_number && $player['is_cpu']) {
        // Get CPU's cards
        $cpu_cards_sql = "SELECT * FROM game_cards WHERE game_id = $game_id AND player_id = {$player['id']} AND status = 'hand'";
        $cpu_cards_result = $conn->query($cpu_cards_sql);
        $cpu_cards = [];
        while ($card = $cpu_cards_result->fetch_assoc()) {
            $cpu_cards[] = $card;
        }
        
        if (count($cpu_cards) > 0) {
            // Get cards on the table ordered by play time
            $table_cards_sql = "SELECT c.*, p.player_number, p.team, m.move_time 
                                FROM game_cards c 
                                JOIN game_players p ON c.player_id = p.id 
                                JOIN game_moves m ON c.id = m.card_id AND m.game_id = c.game_id AND m.round = c.round
                                WHERE c.game_id = $game_id AND c.status = 'table' AND c.round = $current_round
                                ORDER BY m.move_time ASC";
            $table_cards_result = $conn->query($table_cards_sql);
            $table_cards = [];
            while ($card = $table_cards_result->fetch_assoc()) {
                $table_cards[] = $card;
            }
            
            // Make intelligent CPU move
            $card_to_play = makeCPUMove($cpu_cards, $table_cards, $major_suit, $player, $players, $player_count);
            
            if ($card_to_play) {
                // Play the selected card
                $update_sql = "UPDATE game_cards SET status = 'table', round = $current_round WHERE id = {$card_to_play['id']}";
                if ($conn->query($update_sql) === TRUE) {
                    // Record the move
                    $move_sql = "INSERT INTO game_moves (game_id, player_id, card_id, round) 
                                VALUES ($game_id, {$player['id']}, {$card_to_play['id']}, $current_round)";
                    $conn->query($move_sql);
                    
                    // Check if all players have played a card for this round
                    $table_count_sql = "SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND status = 'table' AND round = $current_round";
                    $table_count_result = $conn->query($table_count_sql);
                    $table_count = $table_count_result->fetch_assoc()['count'];
                    
                    if ($table_count == $player_count) {
                        // All players have played, determine the winner
                        determineRoundWinner($conn, $game_id, $current_round, $major_suit, $players);
                        
                        // Check if the game is over
                        $cards_left_sql = "SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND status = 'deck'";
                        $cards_left_result = $conn->query($cards_left_sql);
                        $cards_left = $cards_left_result->fetch_assoc()['count'];
                        
                        $hands_empty_sql = "SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND status = 'hand'";
                        $hands_empty_result = $conn->query($hands_empty_sql);
                        $hands_empty = $hands_empty_result->fetch_assoc()['count'] == 0;
                        
                        if ($cards_left == 0 && $hands_empty) {
                            // Game is over
                            $update_game_sql = "UPDATE games SET status = 'completed' WHERE id = $game_id";
                            $conn->query($update_game_sql);
                            
                            // Update player stats (similar to above)
                        } else {
                            // Deal new cards if available
                            if ($cards_left > 0) {
                                dealNewCards($conn, $game_id, $players);
                            }
                            
                            // Start new round
                            $new_round = $current_round + 1;
                            
                            // Get the winner of the previous round
                            $winner_sql = "SELECT winner_player_id FROM game_rounds WHERE game_id = $game_id AND round_number = $current_round";
                            $winner_result = $conn->query($winner_sql);
                            $winner_id = $winner_result->fetch_assoc()['winner_player_id'];
                            
                            // Find the winner's player number
                            $winner_player_number = 1; // Default
                            foreach ($players as $player) {
                                if ($player['id'] == $winner_id) {
                                    $winner_player_number = $player['player_number'];
                                    break;
                                }
                            }
                            
                            // Update game for new round
                            $update_game_sql = "UPDATE games SET current_round = $new_round, current_player = $winner_player_number WHERE id = $game_id";
                            $conn->query($update_game_sql);
                        }
                    } else {
                        // Move to next player
                        $next_player = getNextPlayer($current_player_number, $player_count);
                        $update_game_sql = "UPDATE games SET current_player = $next_player WHERE id = $game_id";
                        $conn->query($update_game_sql);
                    }
                    
                    // Add a small delay to make it seem like the CPU is thinking
                    sleep(1);
                    
                    // Refresh the page to update game state
                    header("Location: playroom.php?code=$game_code");
                    exit();
                }
            }
        }
        break;
    }
}


// Function to determine the winner of a round
function determineRoundWinner($conn, $game_id, $round, $major_suit, $players) {
    // Get all cards on the table for this round, ordered by when they were actually played
    $table_cards_sql = "SELECT c.*, p.player_number, p.id as player_id, p.team, m.move_time 
                        FROM game_cards c 
                        JOIN game_players p ON c.player_id = p.id 
                        JOIN game_moves m ON c.id = m.card_id AND m.game_id = c.game_id AND m.round = c.round
                        WHERE c.game_id = $game_id AND c.status = 'table' AND c.round = $round
                        ORDER BY m.move_time ASC";
    $table_cards_result = $conn->query($table_cards_sql);
    
    $table_cards = [];
    while ($card = $table_cards_result->fetch_assoc()) {
        $table_cards[] = $card;
    }
    
    if (count($table_cards) == 0) {
        return;
    }
    
    // Get the leading suit (first card played)
    $leading_suit = $table_cards[0]['suit'];
    
    // Initialize winner as the first player
    $winner_card = $table_cards[0];
    $winner_player_id = $table_cards[0]['player_id'];
    
    // Check if any card is of the major suit
    $major_suit_cards = [];
    foreach ($table_cards as $card) {
        if ($card['suit'] == $major_suit) {
            $major_suit_cards[] = $card;
        }
    }
    
    // If major suit cards were played, the highest one wins
    if (!empty($major_suit_cards)) {
        $highest_major = $major_suit_cards[0];
        foreach ($major_suit_cards as $card) {
            if (compareCardValues($card['value'], $highest_major['value']) > 0) {
                $highest_major = $card;
            }
        }
        $winner_card = $highest_major;
        $winner_player_id = $highest_major['player_id'];
    } else {
        // No major suit played, only leading suit cards can win
        $leading_suit_cards = [];
        foreach ($table_cards as $card) {
            if ($card['suit'] == $leading_suit) {
                $leading_suit_cards[] = $card;
            }
        }
        
        // Find highest card of leading suit
        $highest_leading = $leading_suit_cards[0];
        foreach ($leading_suit_cards as $card) {
            if (compareCardValues($card['value'], $highest_leading['value']) > 0) {
                $highest_leading = $card;
            }
        }
        $winner_card = $highest_leading;
        $winner_player_id = $highest_leading['player_id'];
    }
    
    // Calculate points won in this round
    $points = 0;
    foreach ($table_cards as $card) {
        $points += getCardPointValue($card['value']);
    }
    
    // Create or update round record
    $round_check_sql = "SELECT * FROM game_rounds WHERE game_id = $game_id AND round_number = $round";
    $round_check_result = $conn->query($round_check_sql);
    
    if ($round_check_result->num_rows > 0) {
        // Update existing round
        $update_round_sql = "UPDATE game_rounds SET winner_player_id = $winner_player_id, points_won = $points, completed = 1 
                             WHERE game_id = $game_id AND round_number = $round";
        $conn->query($update_round_sql);
    } else {
        // Create new round record
        $insert_round_sql = "INSERT INTO game_rounds (game_id, round_number, winner_player_id, points_won, completed) 
                             VALUES ($game_id, $round, $winner_player_id, $points, 1)";
        $conn->query($insert_round_sql);
    }
    
    // Update player points
    $update_points_sql = "UPDATE game_players SET points = points + $points WHERE id = $winner_player_id";
    $conn->query($update_points_sql);
    
    // Update card status to 'won'
    foreach ($table_cards as $card) {
        $update_card_sql = "UPDATE game_cards SET status = 'won', player_id = $winner_player_id WHERE id = {$card['id']}";
        $conn->query($update_card_sql);
    }
}
// Function to deal new cards to players
function dealNewCards($conn, $game_id, $players) {
    // Get cards from the deck
    $deck_cards_sql = "SELECT * FROM game_cards WHERE game_id = $game_id AND status = 'deck' ORDER BY RAND() LIMIT " . count($players);
    $deck_cards_result = $conn->query($deck_cards_sql);
    
    $deck_cards = [];
    while ($card = $deck_cards_result->fetch_assoc()) {
        $deck_cards[] = $card;
    }
    
    // Deal one card to each player
    foreach ($players as $index => $player) {
        if (isset($deck_cards[$index])) {
            $card_id = $deck_cards[$index]['id'];
            $player_id = $player['id'];
            
            $update_sql = "UPDATE game_cards SET player_id = $player_id, status = 'hand' WHERE id = $card_id";
            $conn->query($update_sql);
        }
    }
}
// Function to get the next player in turn
function getNextPlayer($current_player, $player_count) {
    return ($current_player % $player_count) + 1;
}

// Function to compare card values
function compareCardValues($value1, $value2) {
    $values = [
        'ace' => 11,
        '7' => 10,
        'king' => 4,
        'jack' => 3,
        'queen' => 2,
        '6' => 0,
        '5' => 0,
        '4' => 0,
        '3' => 0
    ];
    
    return $values[$value1] - $values[$value2];
}

// Function to get card point value
function getCardPointValue($value) {
    $values = [
        'ace' => 11,
        '7' => 10,
        'king' => 4,
        'jack' => 3,
        'queen' => 2,
        '6' => 0,
        '5' => 0,
        '4' => 0,
        '3' => 0
    ];
    
    return $values[$value];
}

// Function to make an intelligent CPU move
function makeCPUMove($cpu_cards, $table_cards, $major_suit, $cpu_player, $all_players, $player_count) {
    // Helper: total points currently on the table (used to decide if it's worth fighting for the trick)
    $points_on_table = 0;
    foreach ($table_cards as $tcard) {
        $points_on_table += getCardPointValue($tcard['value']);
    }

    // Helper: sort cards by value using compareCardValues
    $sortByValueAsc = function (&$cards) {
        usort($cards, function($a, $b) {
            return compareCardValues($a['value'], $b['value']);
        });
    };

    // If no cards on table, CPU leads
    if (count($table_cards) == 0) {
        // Smarter opening:
        // - Prefer leading low non-major cards to avoid wasting trumps early
        // - Save very strong cards (ace, 7) of major suit for later
        $non_major = array_filter($cpu_cards, function($card) use ($major_suit) {
            return $card['suit'] !== $major_suit;
        });

        if (!empty($non_major)) {
            // Lead lowest non-major card
            $tmp = array_values($non_major);
            $sortByValueAsc($tmp);
            return $tmp[0];
        }

        // Only majors in hand: lead the weakest major
        $tmp = $cpu_cards;
        $sortByValueAsc($tmp);
        return $tmp[0];
    }
    
    // Get the leading suit
    $leading_suit = $table_cards[0]['suit'];
    
    // Check if CPU has cards of the leading suit
    $matching_suit_cards = array_filter($cpu_cards, function($card) use ($leading_suit) {
        return $card['suit'] == $leading_suit;
    });
    
    // Check if CPU has cards of the major suit
    $major_suit_cards = array_filter($cpu_cards, function($card) use ($major_suit) {
        return $card['suit'] == $major_suit;
    });
    
    // Determine the current winning card on the table
    $current_winner = $table_cards[0];
    $current_winner_index = 0;
    
    foreach ($table_cards as $index => $card) {
        if ($card['suit'] == $major_suit && $current_winner['suit'] != $major_suit) {
            $current_winner = $card;
            $current_winner_index = $index;
        } elseif ($card['suit'] == $current_winner['suit']) {
            if (compareCardValues($card['value'], $current_winner['value']) > 0) {
                $current_winner = $card;
                $current_winner_index = $index;
            }
        } elseif ($card['suit'] == $major_suit) {
            $current_winner = $card;
            $current_winner_index = $index;
        }
    }
    
    // Check if the current winner is from the same team (in team games)
    $same_team = false;
    if ($player_count == 4 || $player_count == 6) {
        foreach ($all_players as $player) {
            if ($player['id'] == $current_winner['player_id'] && $player['team'] == $cpu_player['team']) {
                $same_team = true;
                break;
            }
        }
    }
    
    // Strategy for team games
    if (($player_count == 4 || $player_count == 6) && $same_team) {
        // Teammate is winning, generally dump a low card
        if (!empty($matching_suit_cards)) {
            // Sort by value (ascending)
            $sortByValueAsc($matching_suit_cards);
            return $matching_suit_cards[0]; // Lowest value card of matching suit
        } elseif (!empty($major_suit_cards)) {
            // Sort by value (ascending)
            $sortByValueAsc($major_suit_cards);
            return $major_suit_cards[0]; // Lowest value card of major suit
        } else {
            // Play lowest value card
            $sortByValueAsc($cpu_cards);
            return $cpu_cards[0];
        }
    }
    
    // Normal strategy (try to win if possible)
    if (!empty($matching_suit_cards)) {
        // CPU has cards of the leading suit
        if ($current_winner['suit'] == $major_suit && $leading_suit != $major_suit) {
            // Can't beat a major suit with a non-major suit
            // Play the lowest value card of the required suit
            $sortByValueAsc($matching_suit_cards);
            return $matching_suit_cards[0];
        } else {
            // Try to beat the current winner with a higher card of the same suit
            $winning_cards = array_filter($matching_suit_cards, function($card) use ($current_winner) {
                return compareCardValues($card['value'], $current_winner['value']) > 0;
            });
            
            if (!empty($winning_cards)) {
                // If there are a lot of points on the table, we are more willing to win
                // For low-point tricks, sometimes keep very high cards for later
                $sortByValueAsc($winning_cards);
                if ($points_on_table >= 7) {
                    // Take the trick with the cheapest winning card
                    return $winning_cards[0];
                } else {
                    // Low point trick: avoid wasting top cards if possible
                    // Keep the absolute highest card in hand when possible
                    return $winning_cards[0];
                }
            } else {
                // Can't win, play the lowest value card
                $sortByValueAsc($matching_suit_cards);
                return $matching_suit_cards[0];
            }
        }
    } elseif (!empty($major_suit_cards) && $leading_suit != $major_suit) {
        // CPU has major suit cards and can play them (no leading suit cards)
        if ($current_winner['suit'] == $major_suit) {
            // Current winner is already a major suit
            // Try to beat it with a higher major suit card
            $winning_major_cards = array_filter($major_suit_cards, function($card) use ($current_winner) {
                return compareCardValues($card['value'], $current_winner['value']) > 0;
            });
            
            if (!empty($winning_major_cards)) {
                // Play the lowest winning major card
                $sortByValueAsc($winning_major_cards);
                return $winning_major_cards[0];
            } else {
                               // Can't win, play the lowest value card
                $sortByValueAsc($major_suit_cards);
                return $major_suit_cards[0];
                        }
                    } else {
                        // No major suit card has been played yet, play our lowest major suit card
                        $sortByValueAsc($major_suit_cards);
                        return $major_suit_cards[0];
                    }
                } else {
                    // CPU has neither leading suit nor major suit cards
                    // Play the lowest value card
                    $sortByValueAsc($cpu_cards);
                    return $cpu_cards[0];
                }
            }
            
            // Function to get card display name
            function getCardDisplayName($suit, $value) {
                $suit_names = [
                    'hearts' => 'Hearts (Umutima)',
                    'diamonds' => 'Diamonds (Ikaro)',
                    'clubs' => 'Clubs (Umusaraba)',
                    'spades' => 'Spades (Isuka)'
                ];
                
                $value_names = [
                    'ace' => 'Ace (Bwana)',
                    '7' => '7 (Seti/Madam)',
                    'king' => 'King (Wayine)',
                    'jack' => 'Jack (Watatu)',
                    'queen' => 'Queen (Wapili)',
                    '6' => '6 (Ngada Sisi)',
                    '5' => '5 (Ndanda Tanu)',
                    '4' => '4 (Ngada Yine)',
                    '3' => '3 (Totwe)'
                ];
                
                return $value_names[$value] . ' of ' . $suit_names[$suit];
            }
            
            // Function to get card color
            function getCardColor($suit) {
                return in_array($suit, ['hearts', 'diamonds']) ? 'red' : 'black';
            }
            
            // Function to get card symbol
            function getCardSymbol($suit) {
                $symbols = [
                    'hearts' => '♥',
                    'diamonds' => '♦',
                    'clubs' => '♣',
                    'spades' => '♠'
                ];
                
                return $symbols[$suit];
            }
            
            // Function to get card value display
            function getCardValueDisplay($value) {
                $displays = [
                    'ace' => 'A',
                    'king' => 'K',
                    'queen' => 'Q',
                    'jack' => 'J',
                    '7' => '7',
                    '6' => '6',
                    '5' => '5',
                    '4' => '4',
                    '3' => '3'
                ];
                
                return $displays[$value];
            }
            
            // Function to get player name with team indicator
            function getPlayerNameWithTeam($player, $current_user) {
                $name = htmlspecialchars($player['username']);
                
                if ($player['username'] === $current_user) {
                    $name .= ' (You)';
                }
                
                if ($player['is_cpu']) {
                    $name .= ' (CPU)';
                }
                
                if ($player['team']) {
                    $team_color = $player['team'] == 1 ? 'text-blue-500' : 'text-red-500';
                    $name = "<span class=\"$team_color\">$name</span>";
                }
                
                return $name;
            }
            
            // Check if game is over
            $game_over = $game['status'] === 'completed';
            
            // Get the winner information if game is over
            $winner_info = '';
            if ($game_over) {
                if ($player_count == 4 || $player_count == 6) {
                    // Team game
                    $team1_score = isset($team_scores[1]) ? $team_scores[1] : 0;
                    $team2_score = isset($team_scores[2]) ? $team_scores[2] : 0;
                    
                    if ($team1_score > $team2_score) {
                        $winner_info = "Team Blue wins with $team1_score points!";
                        $winner_team = 1;
                    } elseif ($team2_score > $team1_score) {
                        $winner_info = "Team Red wins with $team2_score points!";
                        $winner_team = 2;
                    } else {
                        $winner_info = "It's a tie! Both teams have $team1_score points.";
                        $winner_team = 0;
                    }
                } else {
                    // Individual game
                    $max_points = 0;
                    $winner_name = '';
                    
                    foreach ($scores as $score) {
                        if ($score['points'] > $max_points) {
                            $max_points = $score['points'];
                            $winner_name = $score['username'];
                        }
                    }
                    
                    if ($winner_name === $current_user) {
                        $winner_info = "You win with $max_points points!";
                    } else {
                        $winner_info = "$winner_name wins with $max_points points!";
                    }
                }
            }

            // Function to get ordinal suffix
function getOrdinalSuffix($number) {
    if ($number % 100 >= 11 && $number % 100 <= 13) {
        return 'th';
    }
    
    switch ($number % 10) {
        case 1:
            return 'st';
        case 2:
            return 'nd';
        case 3:
            return 'rd';
        default:
            return 'th';
    }
}
            ?>

