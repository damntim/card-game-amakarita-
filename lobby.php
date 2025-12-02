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
$servername = "localhost";
$username = "ypwpowvu_amakarita";
$password = "QCu2LmWdGMRRdb6AnGjk";
$dbname = "ypwpowvu_amakarita";

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
$host = $game['host'];
$player_count = $game['player_count'];
$is_host = ($host === $current_user);

// Get all players in the game
$players_sql = "SELECT * FROM game_players WHERE game_id = $game_id ORDER BY player_number";
$players_result = $conn->query($players_sql);
$players = [];
while ($player = $players_result->fetch_assoc()) {
    $players[] = $player;
}

// Check if the current user is in the game
$user_in_game = false;
foreach ($players as $player) {
    if ($player['username'] === $current_user) {
        $user_in_game = true;
        break;
    }
}

if (!$user_in_game) {
    header("Location: games.php");
    exit();
}

// Add CPU player
if (isset($_POST['add_cpu']) && $is_host) {
    $current_player_count = count($players);
    
    if ($current_player_count < $player_count) {
        $player_number = $current_player_count + 1;
        $cpu_name = "CPU_" . $player_number;
        
        $sql = "INSERT INTO game_players (game_id, username, player_number, is_cpu) 
                VALUES ($game_id, '$cpu_name', $player_number, 1)";
        
        if ($conn->query($sql) === TRUE) {
            // If the game is now full, update status
            if ($player_number == $player_count) {
                $update_sql = "UPDATE games SET status = 'full' WHERE id = $game_id";
                $conn->query($update_sql);
            }
            
            // Refresh the page
            header("Location: lobby.php?code=$game_code");
            exit();
        } else {
            $error = "Error adding CPU player: " . $conn->error;
        }
    }
}

// Start the game
if (isset($_POST['start_game']) && $is_host) {
    $current_player_count = count($players);
    
    if ($current_player_count == $player_count) {
        // Update game status
        $update_sql = "UPDATE games SET status = 'in_progress' WHERE id = $game_id";
        
        if ($conn->query($update_sql) === TRUE) {
            // Deal initial cards (3 per player)
            dealInitialCards($conn, $game_id, $players);
            
            // Redirect to game room
            header("Location: playroom.php?code=$game_code");
            exit();
        } else {
            $error = "Error starting game: " . $conn->error;
        }
    } else {
        $error = "Cannot start game. Need exactly $player_count players.";
    }
}

// Function to deal initial cards
function dealInitialCards($conn, $game_id, $players) {
    // Get all cards in the deck
    $cards_sql = "SELECT * FROM game_cards WHERE game_id = $game_id AND status = 'deck'";
    $cards_result = $conn->query($cards_sql);
    
    $cards = [];
    while ($card = $cards_result->fetch_assoc()) {
        $cards[] = $card;
    }
    
    // Shuffle cards
    shuffle($cards);
    
    $player_count = count($players);
    $cards_per_player = 3; // Default for 2 players
    
    // For 3, 4, or 6 players, distribute all cards evenly
    if ($player_count == 3) {
        $cards_per_player = 12; // 36 cards / 3 players = 12 cards each
    } elseif ($player_count == 4) {
        $cards_per_player = 9; // 36 cards / 4 players = 9 cards each
    } elseif ($player_count == 6) {
        $cards_per_player = 6; // 36 cards / 6 players = 6 cards each
    }
    
    // Deal cards to each player
    foreach ($players as $player) {
        $player_id = $player['id'];
        
        for ($i = 0; $i < $cards_per_player; $i++) {
            if (count($cards) > 0) {
                $card = array_pop($cards);
                $card_id = $card['id'];
                
                $update_sql = "UPDATE game_cards SET player_id = $player_id, status = 'hand' 
                              WHERE id = $card_id";
                $conn->query($update_sql);
            }
        }
    }
}

// Check if game is full and auto-start for non-host
if ($game['status'] === 'in_progress' && !$is_host) {
    header("Location: playroom.php?code=$game_code");
    exit();
}

// Auto-refresh the page every 5 seconds for non-hosts
$auto_refresh = !$is_host;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Lobby - Amakarita</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .player-card {
            transition: all 0.3s ease;
        }
        
        .player-card.empty {
            opacity: 0.6;
        }
        
        .player-card.host {
            border-color: #FCD34D;
        }
        
        .player-card.cpu {
            border-color: #60A5FA;
        }
        
        .player-card.you {
            border-color: #34D399;
        }
        
        /* Added responsive styles */
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            
            .action-buttons > * {
                margin-bottom: 0.75rem;
                width: 100%;
            }
            
            .action-buttons button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <?php if ($auto_refresh): ?>
    <meta http-equiv="refresh" content="5">
    <?php endif; ?>
</head>
<body class="min-h-screen bg-gray-900 text-white py-12 px-4">
    <div class="max-w-4xl mx-auto">
        <!-- Game Info Header -->
        <div class="bg-indigo-900 rounded-xl shadow-lg p-6 mb-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Game Lobby</h1>
                    <p class="text-indigo-200">Waiting for players to join...</p>
                </div>
                
                <div class="mt-4 md:mt-0 text-center">
                    <div class="bg-indigo-800 rounded-lg px-4 py-2 mb-2">
                        <span class="text-sm opacity-70">Game Code:</span>
                        <span class="font-bold font-mono"><?php echo $game_code; ?></span>
                    </div>
                    
                    <div class="text-sm">
                        <?php if ($game['visibility'] === 'private'): ?>
                            <span class="inline-flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                                Private Game
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                                </svg>
                                Public Game
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-500 text-white p-4 rounded-md mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Players Section -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold mb-4">Players (<?php echo count($players); ?>/<?php echo $player_count; ?>)</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php 
                // Display existing players
                foreach ($players as $player): 
                    $is_current_user = ($player['username'] === $current_user);
                    $is_game_host = ($player['username'] === $host);
                    $is_cpu = $player['is_cpu'];
                    
                    $card_classes = "player-card bg-gray-800 rounded-lg p-4 border-2";
                    if ($is_game_host) $card_classes .= " host";
                    if ($is_current_user) $card_classes .= " you";
                    if ($is_cpu) $card_classes .= " cpu";
                    if (!$is_game_host && !$is_current_user && !$is_cpu) $card_classes .= " border-gray-700";
                ?>
                <div class="<?php echo $card_classes; ?>">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-indigo-700 flex items-center justify-center font-bold mr-3">
                            <?php echo substr($player['username'], 0, 1); ?>
                        </div>
                        <div>
                            <div class="font-semibold">
                                <?php echo htmlspecialchars($player['username']); ?>
                                <?php if ($is_current_user): ?>
                                    <span class="text-green-400 text-sm">(You)</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-sm text-gray-400">
                                <?php if ($is_game_host): ?>
                                    <span class="text-yellow-400">Host</span>
                                <?php elseif ($is_cpu): ?>
                                    <span class="text-blue-400">CPU</span>
                                <?php else: ?>
                                    Player <?php echo $player['player_number']; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Empty player slots -->
                <?php for ($i = count($players); $i < $player_count; $i++): ?>
                <div class="player-card empty bg-gray-800 rounded-lg p-4 border-2 border-gray-700 border-dashed">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center font-bold mr-3">
                            ?
                        </div>
                        <div>
                            <div class="font-semibold text-gray-500">Waiting for player...</div>
                            <div class="text-sm text-gray-500">Player <?php echo $i + 1; ?></div>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0 action-buttons">
            <a href="games.php" class="text-indigo-400 hover:text-indigo-300 inline-flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Leave Game
            </a>
            
            <?php if ($is_host): ?>
            <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4 w-full md:w-auto">
                <?php if (count($players) < $player_count): ?>
                <form method="post" action="" class="w-full sm:w-auto">
                    <button type="submit" name="add_cpu" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center w-full justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                        </svg>
                        Add CPU Player
                    </button>
                </form>
                <?php endif; ?>
                
                <form method="post" action="" class="w-full sm:w-auto">
                    <button type="submit" name="start_game" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center w-full justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        Start Game
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="text-indigo-200">
                <div class="inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 animate-spin" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                    </svg>
                    Waiting for host to start the game...
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Game Rules -->
        <div class="mt-12 bg-gray-800 rounded-xl p-6">
            <h2 class="text-xl font-bold mb-4">Game Rules</h2>
            
            <div class="text-gray-300 space-y-2">
                <p>
                    <span class="text-yellow-400">•</span> Each player receives 3 cards at the start.
                </p>
                <p>
                    <span class="text-yellow-400">•</span> The major suit for this game is: <span class="font-bold text-yellow-400"><?php echo ucfirst($game['major_suit']); ?></span>
                </p>
                <p>
                    <span class="text-yellow-400">•</span> Players take turns playing one card each. The winner of each round collects the cards.
                </p>
                <p>
                    <span class="text-yellow-400">•</span> The highest card of the major suit wins. If no major suit is played, the highest card of the leading suit wins.
                </p>
                <p>
                    <span class="text-yellow-400">•</span> After each round, players draw a new card from the deck.
                </p>
                <p>
                    <span class="text-yellow-400">•</span> The game ends after all cards are played. The player with the most points wins.
                </p>
            </div>
        </div>
    </div>
    
    <script>
    // Notify when a new player joins (for future implementation with WebSockets)
    function notifyNewPlayer(playerName) {
        // This is a placeholder for future real-time notifications
        console.log("New player joined: " + playerName);
    }
    </script>
</body>
</html>