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

// Create a new game
if (isset($_POST['create_game'])) {
    $player_count = intval($_POST['player_count']);
    $visibility = $_POST['visibility'];
    $host = $_SESSION['username'];
    
    // Validate input
    if (!in_array($player_count, [2, 3, 4, 6])) {
        $error = "Invalid player count";
    } else {
        // Generate a unique game code
        $code = generateGameCode();
        
        // Determine major suit randomly
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $major_suit = $suits[array_rand($suits)];
        
        // Create the game
        $sql = "INSERT INTO games (game_code, host, player_count, visibility, major_suit) 
                VALUES ('$code', '$host', $player_count, '$visibility', '$major_suit')";
        
        if ($conn->query($sql) === TRUE) {
            $game_id = $conn->insert_id;
            
            // Add the host as the first player
            $sql = "INSERT INTO game_players (game_id, username, player_number) 
                    VALUES ($game_id, '$host', 1)";
            
            if ($conn->query($sql) === TRUE) {
                // Create and shuffle the deck
                createDeck($conn, $game_id);
                
                // Set success message and redirect
                $_SESSION['game_created'] = true;
                $_SESSION['redirect_to'] = "lobby.php?code=$code";
                header("Location: lobby.php?code=$code");
                exit();
            } else {
                $error = "Error adding player: " . $conn->error;
            }
        } else {
            $error = "Error creating game: " . $conn->error;
        }
    }
}

// Join a game
if (isset($_POST['join_game'])) {
    $code = $_POST['code'];
    
    // Find the game
    $sql = "SELECT * FROM games WHERE game_code = '$code' AND status = 'waiting'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $game = $result->fetch_assoc();
        $game_id = $game['id'];
        
        // Check if the game is full
        $player_count_sql = "SELECT COUNT(*) as count FROM game_players WHERE game_id = $game_id";
        $player_count_result = $conn->query($player_count_sql);
        $player_count = $player_count_result->fetch_assoc()['count'];
        
        if ($player_count < $game['player_count']) {
            // Add user to the game
            $username = $_SESSION['username'];
            $player_number = $player_count + 1;
            
            $sql = "INSERT INTO game_players (game_id, username, player_number) VALUES ($game_id, '$username', $player_number)";
            if ($conn->query($sql) === TRUE) {
                // If the game is now full, update status
                if ($player_number == $game['player_count']) {
                    $update_sql = "UPDATE games SET status = 'full' WHERE id = $game_id";
                    $conn->query($update_sql);
                }
                
                // Redirect to lobby
                header("Location: lobby.php?code=$code");
                exit();
            } else {
                $join_error = "Error joining game: " . $conn->error;
            }
        } else {
            $join_error = "Game is full";
        }
    } else {
        $join_error = "Game not found or already started";
    }
}

// Check for redirects before including header
if (isset($_SESSION['redirect_to'])) {
    $redirect_url = $_SESSION['redirect_to'];
    unset($_SESSION['redirect_to']);
    unset($_SESSION['game_created']);
    unset($_SESSION['game_joined']);
    header("Location: $redirect_url");
    exit();
}

// Get available public games
$public_games_sql = "SELECT g.*, 
                      (SELECT COUNT(*) FROM game_players WHERE game_id = g.id) as current_players 
                    FROM games g 
                    WHERE g.visibility = 'public' AND g.status = 'waiting'";
$public_games_result = $conn->query($public_games_sql);

// Function to generate a unique game code
function generateGameCode($length = 6) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Function to create and shuffle the deck
function createDeck($conn, $game_id) {
    $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
    $values = ['ace', 'king', 'queen', 'jack', '7', '6', '5', '4', '3'];
    
    // Create all 36 cards
    foreach ($suits as $suit) {
        foreach ($values as $value) {
            $sql = "INSERT INTO game_cards (game_id, suit, value, status) 
                    VALUES ($game_id, '$suit', '$value', 'deck')";
            $conn->query($sql);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Games - Amakarita</title>
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
    </style>
</head>
<body class="min-h-screen bg-gray-900 text-white py-12 px-4">
    <div class="max-w-7xl mx-auto">
        <!-- Welcome Message -->
        <div class="mb-8 text-center">
            <h1 class="text-4xl font-bold text-yellow-400 mb-2">Welcome to Amakarita</h1>
            <p class="text-xl">Hello, <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['username']); ?></span>! Ready to play the traditional Rwandan card game?</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-500 text-white p-4 rounded-md mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($join_error)): ?>
            <div class="bg-red-500 text-white p-4 rounded-md mb-6">
                <?php echo $join_error; ?>
            </div>
        <?php endif; ?>
        
        <div class="grid md:grid-cols-2 gap-8 mb-12">
            <!-- Create Game Card -->
            <div class="bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="bg-indigo-900 px-6 py-4">
                    <h2 class="text-2xl font-bold">Create a New Game</h2>
                </div>
                <div class="p-6">
                    <form method="post" action="games.php" class="space-y-6">
                        <div>
                            <label for="player_count" class="block text-gray-300 mb-2">Number of Players</label>
                            <select id="player_count" name="player_count" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="2">2 Players</option>
                                <option value="3">3 Players</option>
                                <option value="4">4 Players (2 Teams)</option>
                                <option value="6">6 Players (2 Teams)</option>
                            </select>
                            <p class="text-gray-400 text-sm mt-1">CPU players can fill empty slots if needed</p>
                        </div>
                        
                        <div>
                            <label class="block text-gray-300 mb-2">Game Visibility</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="visibility" value="public" checked class="mr-2 text-indigo-600">
                                    <span>Public (visible to everyone)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="visibility" value="private" class="mr-2 text-indigo-600">
                                    <span>Private (invite only)</span>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" name="create_game" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition">
                            Create Game
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Join Game Card -->
            <div class="bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="bg-green-900 px-6 py-4">
                    <h2 class="text-2xl font-bold">Join a Game</h2>
                </div>
                <div class="p-6">
                    <form method="post" action="games.php" class="mb-8">
                        <div class="flex">
                            <input type="text" name="code" placeholder="Enter game code" required 
                                class="flex-grow bg-gray-700 border border-gray-600 rounded-l-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                            <button type="submit" name="join_game" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-r-lg transition">
                                Join
                            </button>
                        </div>
                    </form>
                    
                    <div>
                        <h3 class="text-xl font-semibold mb-4">Available Public Games</h3>
                        
                        <?php if ($public_games_result->num_rows > 0): ?>
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="bg-gray-700 text-gray-200">
                                    <th class="px-4 py-3 text-left">Host</th>
                                    <th class="px-4 py-3 text-center">Players</th>
                                    <th class="px-4 py-3 text-center">Mode</th>
                                    <th class="px-4 py-3 text-center">Code</th>
                                    <th class="px-4 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $row_count = 0;
                                while ($game = $public_games_result->fetch_assoc()): 
                                    $row_class = $row_count % 2 === 0 ? 'bg-gray-800' : 'bg-gray-700';
                                    $row_count++;
                                ?>
                                <tr class="<?php echo $row_class; ?> hover:bg-gray-600">
                                    <td class="px-4 py-3 text-left"><?php echo htmlspecialchars($game['host']); ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <?php echo $game['current_players'] . '/' . $game['player_count']; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php echo $game['player_count'] . ' Players'; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="bg-gray-900 px-2 py-1 rounded text-sm font-mono">
                                            <?php echo $game['game_code']; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="post" action="games.php">
                                            <input type="hidden" name="code" value="<?php echo $game['game_code']; ?>">
                                            <button type="submit" name="join_game" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">
                                                Join
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="text-gray-400 italic">No public games available. Create one!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Back to Home Button -->
        <div class="text-center">
            <a href="index.php" class="inline-flex items-center text-indigo-400 hover:text-indigo-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>