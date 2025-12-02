<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
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
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Get current user
$current_user = $_SESSION['username'];

// Handle POST request to send a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    $game_code = $_POST['game_code'];
    $message = htmlspecialchars(trim($_POST['message']));
    
    if (empty($message)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Message cannot be empty']);
        exit();
    }
    
    // Get game ID from game code
    $game_sql = "SELECT id FROM games WHERE game_code = ?";
    $stmt = $conn->prepare($game_sql);
    $stmt->bind_param("s", $game_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Game not found']);
        exit();
    }
    
    $game = $result->fetch_assoc();
    $game_id = $game['id'];
    
    // Insert message
    $insert_sql = "INSERT INTO game_chat (game_id, username, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iss", $game_id, $current_user, $message);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to send message']);
    }
    exit();
}

// Handle GET request to fetch messages
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {
    $game_code = $_GET['game_code'];
    $last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    
    // Get game ID from game code
    $game_sql = "SELECT id FROM games WHERE game_code = ?";
    $stmt = $conn->prepare($game_sql);
    $stmt->bind_param("s", $game_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Game not found']);
        exit();
    }
    
    $game = $result->fetch_assoc();
    $game_id = $game['id'];
    
    // Fetch messages newer than last_id
    $messages_sql = "SELECT id, username, message, created_at FROM game_chat 
                    WHERE game_id = ? AND id > ? 
                    ORDER BY created_at ASC LIMIT 50";
    $stmt = $conn->prepare($messages_sql);
    $stmt->bind_param("ii", $game_id, $last_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $row['is_current_user'] = ($row['username'] === $current_user);
        $messages[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['messages' => $messages]);
    exit();
}

// Handle GET request to fetch players for @mentions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'players') {
    $game_code = $_GET['game_code'];
    
    // Get game ID from game code
    $game_sql = "SELECT id FROM games WHERE game_code = ?";
    $stmt = $conn->prepare($game_sql);
    $stmt->bind_param("s", $game_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Game not found']);
        exit();
    }
    
    $game = $result->fetch_assoc();
    $game_id = $game['id'];
    
    // Fetch players
    $players_sql = "SELECT username FROM game_players WHERE game_id = ? ORDER BY player_number";
    $stmt = $conn->prepare($players_sql);
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $players = [];
    while ($row = $result->fetch_assoc()) {
        $players[] = $row['username'];
    }
    
    header('Content-Type: application/json');
    echo json_encode(['players' => $players]);
    exit();
}

// Default response for invalid requests
header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid request']);
exit();
?>