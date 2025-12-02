<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

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

// Get public games if user is logged in
$public_games = [];
if ($is_logged_in) {
    $sql = "SELECT g.*, 
            (SELECT COUNT(*) FROM game_players WHERE game_id = g.id) as current_players 
            FROM games g 
            WHERE g.visibility = 'public' AND g.status = 'waiting'
            LIMIT 5";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $public_games[] = $row;
        }
    }
}

// Get top players for leaderboard
$top_players = [];
$leaderboard_sql = "SELECT username, games_played, games_won, total_points, 
                    ROUND((games_won / games_played) * 100, 1) as win_rate 
                    FROM users 
                    WHERE games_played > 0 
                    ORDER BY total_points DESC, win_rate DESC 
                    LIMIT 10";
$leaderboard_result = $conn->query($leaderboard_sql);

if ($leaderboard_result && $leaderboard_result->num_rows > 0) {
    while ($row = $leaderboard_result->fetch_assoc()) {
        $top_players[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amakarita - Rwandan Card Game</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Three.js for 3D effects -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .hero {
            background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1557683316-973673baf926?ixlib=rb-4.0.3');
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }
        
        .card-animation {
            animation: float 6s ease-in-out infinite;
            transform-style: preserve-3d;
            perspective: 1000px;
        }
        
        @keyframes float {
            0% {
                transform: translatey(0px) rotate(0deg);
            }
            50% {
                transform: translatey(-20px) rotate(5deg);
            }
            100% {
                transform: translatey(0px) rotate(0deg);
            }
        }
        
        .card-3d {
            transform-style: preserve-3d;
            transition: transform 0.6s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }
        
        .card-3d:hover {
            transform: rotateY(15deg) rotateX(5deg);
        }
        
        .leaderboard-item {
            transition: all 0.3s ease;
        }
        
        .leaderboard-item:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
            z-index: 10;
        }
        
        .top-player {
            position: relative;
            overflow: hidden;
        }
        
        .top-player::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent 65%, rgba(255,215,0,0.2) 80%, transparent 90%);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% { background-position: -100% 0; }
            100% { background-position: 200% 0; }
        }
        
        #canvas-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
    <!-- Navigation -->
    <nav class="bg-indigo-900 shadow-lg">
        <div class="container mx-auto px-6 py-4">
            <div class="flex flex-col md:flex-row md:justify-between md:items-center">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <a href="index.php" class="text-2xl font-bold text-white">
                            Amakarita
                        </a>
                        <span class="ml-2 text-indigo-200 text-sm">Rwandan Card Game</span>
                    </div>
                    <!-- Mobile menu button -->
                    <div class="flex md:hidden">
                        <button type="button" id="mobile-menu-button" class="text-white hover:text-indigo-200 focus:outline-none">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div id="mobile-menu" class="hidden md:flex md:items-center md:space-x-4 mt-4 md:mt-0">
                    <?php if ($is_logged_in): ?>
                        <a href="games.php" class="block w-full md:w-auto text-center mb-2 md:mb-0 bg-indigo-700 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg transition">
                            Play Now
                        </a>
                        <div class="relative group">
                            <button class="flex items-center space-x-2 text-white focus:outline-none">
                                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
                                <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-indigo-100">Profile</a>
                                <a href="logout.php" class="block px-4 py-2 text-gray-800 hover:bg-indigo-100">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="auth.php?action=login" class="block w-full md:w-auto text-center mb-2 md:mb-0 text-white hover:text-indigo-200 transition">Login</a>
                        <a href="auth.php?action=register" class="block w-full md:w-auto text-center bg-indigo-700 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg transition">
                            Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section with 3D Background -->
    <section class="hero min-h-screen flex items-center py-24 relative">
        <div id="canvas-container"></div>
        <div class="container mx-auto px-6 text-center mt-16 relative z-10">
            <h1 class="text-5xl md:text-6xl font-bold mb-12">Experience the Traditional <span class="text-red-500">Rwandan Card Game</span></h1>
            <p class="text-xl md:text-2xl text-gray-300 mb-16 max-w-3xl mx-auto">
                Play Amakarita online with friends or challenge the computer in this classic game of strategy and skill.
            </p>
            
            <div class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-6">
                <?php if ($is_logged_in): ?>
                    <a href="games.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-lg text-lg transition transform hover:scale-105 hover:shadow-lg hover:shadow-red-500/30">
                        Play Now
                    </a>
                    <a href="#how-to-play" class="bg-transparent border-2 border-white hover:bg-white hover:text-gray-900 text-white font-bold py-3 px-8 rounded-lg text-lg transition">
                        How to Play
                    </a>
                <?php else: ?>
                    <a href="auth.php?action=register" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-lg text-lg transition transform hover:scale-105 hover:shadow-lg hover:shadow-red-500/30">
                        Get Started
                    </a>
                    <a href="auth.php?action=login" class="bg-transparent border-2 border-white hover:bg-white hover:text-gray-900 text-white font-bold py-3 px-8 rounded-lg text-lg transition">
                        Login
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- 3D Floating Cards Animation -->
            <div class="relative mt-24 h-40">
                <div class="absolute left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <div class="relative">
                        <div class="card-animation card-3d absolute -left-40 -top-10 bg-white text-black rounded-lg w-20 h-28 flex flex-col items-center justify-center border-2 border-yellow-400 transform -rotate-12">
                            <div class="text-2xl font-bold text-red-600">A</div>
                            <div class="text-3xl text-red-600">♥</div>
                            <div class="text-xs mt-1">Bwana</div>
                        </div>
                        
                        <div class="card-animation card-3d absolute -left-12 -top-20 bg-white text-black rounded-lg w-20 h-28 flex flex-col items-center justify-center border-2 border-white transform rotate-6" style="animation-delay: 0.5s">
                            <div class="text-2xl font-bold text-red-600">7</div>
                            <div class="text-3xl text-red-600">♦</div>
                            <div class="text-xs mt-1">Seti</div>
                        </div>
                        
                        <div class="card-animation card-3d absolute left-16 -top-12 bg-white text-black rounded-lg w-20 h-28 flex flex-col items-center justify-center border-2 border-white transform -rotate-3" style="animation-delay: 1s">
                            <div class="text-2xl font-bold text-black">K</div>
                            <div class="text-3xl text-black">♠</div>
                            <div class="text-xs mt-1">Wayine</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Top Players Leaderboard Section -->
    <section class="py-16 px-6 bg-gradient-to-b from-indigo-900 to-gray-900">
        <div class="container mx-auto">
            <h2 class="text-3xl font-bold mb-8 text-center">Top Players</h2>
            
            <?php if (!empty($top_players)): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                    <?php 
                    // Display top 3 players with special styling
                    for ($i = 0; $i < min(3, count($top_players)); $i++): 
                        $player = $top_players[$i];
                        $medal_colors = ['from-yellow-300 to-yellow-600', 'from-gray-300 to-gray-500', 'from-amber-700 to-amber-900'];
                        $medal_icons = ['🥇', '🥈', '🥉'];
                    ?>
                        <div class="top-player leaderboard-item bg-gradient-to-br <?php echo $medal_colors[$i]; ?> rounded-xl p-6 shadow-xl transform transition-all duration-300 hover:scale-105">
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex items-center">
                                    <span class="text-3xl mr-3"><?php echo $medal_icons[$i]; ?></span>
                                    <h3 class="text-2xl font-bold"><?php echo htmlspecialchars($player['username']); ?></h3>
                                </div>
                                <div class="bg-white bg-opacity-20 rounded-full px-3 py-1">
                                    <span class="text-sm font-bold">#<?php echo $i + 1; ?></span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mt-6">
                                <div class="text-center">
                                    <p class="text-sm opacity-80">Total Points</p>
                                    <p class="text-2xl font-bold"><?php echo number_format($player['total_points']); ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm opacity-80">Win Rate</p>
                                    <p class="text-2xl font-bold"><?php echo $player['win_rate']; ?>%</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm opacity-80">Games Won</p>
                                    <p class="text-xl font-bold"><?php echo $player['games_won']; ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm opacity-80">Games Played</p>
                                    <p class="text-xl font-bold"><?php echo $player['games_played']; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <?php if (count($top_players) > 3): ?>
                    <div class="overflow-x-auto glass-effect rounded-xl p-6">
                        <h3 class="text-xl font-bold mb-4 text-center">Leaderboard</h3>
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="border-b border-gray-700">
                                    <th class="px-4 py-3 text-left">Rank</th>
                                    <th class="px-4 py-3 text-left">Player</th>
                                    <th class="px-4 py-3 text-center">Games</th>
                                    <th class="px-4 py-3 text-center">Wins</th>
                                    <th class="px-4 py-3 text-center">Win Rate</th>
                                    <th class="px-4 py-3 text-right">Total Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($i = 3; $i < count($top_players); $i++): 
                                    $player = $top_players[$i];
                                ?>
                                    <tr class="leaderboard-item border-b border-gray-800 hover:bg-indigo-900/30">
                                        <td class="px-4 py-3 text-left"><?php echo $i + 1; ?></td>
                                        <td class="px-4 py-3 text-left font-medium"><?php echo htmlspecialchars($player['username']); ?></td>
                                        <td class="px-4 py-3 text-center"><?php echo $player['games_played']; ?></td>
                                        <td class="px-4 py-3 text-center"><?php echo $player['games_won']; ?></td>
                                        <td class="px-4 py-3 text-center"><?php echo $player['win_rate']; ?>%</td>
                                        <td class="px-4 py-3 text-right font-bold"><?php echo number_format($player['total_points']); ?></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-8 bg-gray-800 rounded-xl">
                    <p class="text-xl">No player data available yet. Be the first to join the leaderboard!</p>
                </div>
            <?php endif; ?>
            
            <div class="mt-8 text-center">
                <a href="<?php echo $is_logged_in ? 'games.php' : 'auth.php?action=register'; ?>" class="bg-indigo-700 hover:bg-indigo-600 text-white font-bold py-3 px-8 rounded-lg text-lg transition inline-flex items-center">
                    <i class="fas fa-trophy mr-2"></i>
                    <?php echo $is_logged_in ? 'Join the Competition' : 'Create an Account to Compete'; ?>
                </a>
            </div>
        </div>
    </section>

    <?php if ($is_logged_in && !empty($public_games)): ?>
    <!-- Available Games Section -->
    <section class="py-16 px-6 bg-gray-800">
        <div class="container mx-auto">
            <h2 class="text-3xl font-bold mb-8">Available Games</h2>
            
            <div class="overflow-x-auto glass-effect rounded-xl p-6">
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
                        <?php foreach ($public_games as $index => $game): ?>
                            <tr class="<?php echo $index % 2 === 0 ? 'bg-gray-800' : 'bg-gray-700'; ?> hover:bg-gray-600 transition-colors">
                                <td class="px-4 py-3 text-left"><?php echo htmlspecialchars($game['host']); ?></td>
                                <td class="px-4 py-3 text-center"><?php echo $game['current_players'] . '/' . $game['player_count']; ?></td>
                                <td class="px-4 py-3 text-center"><?php echo $game['player_count'] . ' Players'; ?></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="bg-gray-900 px-2 py-1 rounded text-sm font-mono"><?php echo $game['game_code']; ?></span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <form method="post" action="games.php">
                                        <input type="hidden" name="code" value="<?php echo $game['game_code']; ?>">
                                        <button type="submit" name="join_game" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded transition transform hover:scale-105">
                                            Join
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 text-center">
                <a href="games.php" class="text-indigo-400 hover:text-indigo-300 inline-flex items-center">
                    View All Games
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- How to Play Section -->
    <section id="how-to-play" class="py-16 px-6">
        <div class="container mx-auto">
            <h2 class="text-3xl font-bold mb-12 text-center">How to Play Amakarita</h2>
            
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="bg-gray-800 rounded-xl p-6 shadow-lg transform transition-all duration-500 hover:shadow-indigo-500/20 hover:shadow-xl">
                        <h3 class="text-xl font-bold mb-4 text-yellow-400">Game Basics</h3>
                        <p class="text-gray-300 mb-6">
                            Amakarita is a traditional Rwandan card game played with 36 cards. The game combines strategy, memory, and a bit of luck.
                        </p>
                        
                        <h4 class="font-bold text-white mb-2">Card Values:</h4>
                        <ul class="list-disc pl-5 text-gray-300 mb-6 space-y-1">
                            <li>Ace (Bwana) - 11 points</li>
                            <li>7 (Seti/Madam) - 10 points</li>
                            <li>King (Wayine) - 4 points</li>
                            <li>Jack (Watatu) - 3 points</li>
                            <li>Queen (Wapili) - 2 points</li>
                            <li>6, 5, 4, 3 - 0 points</li>
                        </ul>
                        
                        <h4 class="font-bold text-white mb-2">Card Suits:</h4>
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="flex items-center">
                                <span class="text-red-500 text-2xl mr-2">♥</span>
                                <span>Hearts (Umutima)</span>
                            </div>
                            <div class="flex items-center">
                                <span class="text-red-500 text-2xl mr-2">♦</span>
                                <span>Diamonds (Ikaro)</span>
                            </div>
                            <div class="flex items-center">
                                <span class="text-white text-2xl mr-2">♣</span>
                                <span>Clubs (Umusaraba)</span>
                            </div>
                            <div class="flex items-center">
                                <span class="text-white text-2xl mr-2">♠</span>
                                <span>Spades (Isuka)</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="bg-gray-800 rounded-xl p-6 shadow-lg transform transition-all duration-500 hover:shadow-indigo-500/20 hover:shadow-xl">
                        <h3 class="text-xl font-bold mb-4 text-yellow-400">Game Rules</h3>
                        
                        <div class="space-y-4 text-gray-300">
                            <p>
                                <span class="font-bold text-white">1. Starting the Game:</span> Each player receives 3 cards. One card is displayed as the "major" suit.
                            </p>
                            
                            <p>
                                <span class="font-bold text-white">2. Taking Turns:</span> Players take turns playing one card each. The winner of each round collects the cards.
                            </p>
                            
                            <p>
                                <span class="font-bold text-white">3. Winning a Round:</span> The highest card of the major suit wins. If no major suit is played, the highest card of the leading suit wins.
                            </p>
                            
                            <p>
                                <span class="font-bold text-white">4. Drawing Cards:</span> After each round, players draw a new card from the deck.
                            </p>
                            
                            <p>
                                <span class="font-bold text-white">5. Game End:</span> The game ends after 15 rounds when all cards are played. The player with the most points wins.
                            </p>
                            
                            <p>
                                <span class="font-bold text-white">6. Team Play:</span> In 4 or 6 player mode, players form teams and combine their points at the end.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-12 text-center">
            <a href="<?php echo $is_logged_in ? 'games.php' : 'auth.php?action=register'; ?>" class="bg-indigo-700 hover:bg-indigo-600 text-white font-bold py-3 px-8 rounded-lg text-lg transition transform hover:scale-105 hover:shadow-lg">
                    <?php echo $is_logged_in ? 'Start Playing' : 'Create an Account'; ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 px-6 bg-gray-800">
        <div class="container mx-auto">
            <h2 class="text-3xl font-bold mb-12 text-center">Game Features</h2>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-gradient-to-br from-gray-700 to-gray-900 rounded-xl p-6 shadow-lg text-center transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                    <div class="w-16 h-16 mx-auto mb-4 flex items-center justify-center bg-indigo-600 rounded-full">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Multiplayer</h3>
                    <p class="text-gray-300">
                        Play with friends or join public games with players from around the world.
                    </p>
                </div>
                
                <div class="bg-gradient-to-br from-gray-700 to-gray-900 rounded-xl p-6 shadow-lg text-center transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                    <div class="w-16 h-16 mx-auto mb-4 flex items-center justify-center bg-indigo-600 rounded-full">
                        <i class="fas fa-trophy text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Leaderboards</h3>
                    <p class="text-gray-300">
                        Compete for the top spot on our global leaderboards and track your progress over time.
                    </p>
                </div>
                
                <div class="bg-gradient-to-br from-gray-700 to-gray-900 rounded-xl p-6 shadow-lg text-center transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                    <div class="w-16 h-16 mx-auto mb-4 flex items-center justify-center bg-indigo-600 rounded-full">
                        <i class="fas fa-robot text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">AI Opponents</h3>
                    <p class="text-gray-300">
                        Practice your skills against our intelligent AI opponents with varying difficulty levels.
                    </p>
                </div>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8 mt-12">
                <div class="bg-gradient-to-br from-gray-700 to-gray-900 rounded-xl p-6 shadow-lg transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 flex items-center justify-center bg-indigo-600 rounded-full mr-4">
                            <i class="fas fa-comments text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold">In-Game Chat</h3>
                    </div>
                    <p class="text-gray-300">
                        Communicate with other players during games using our real-time chat system. Share strategies, make friends, or engage in friendly banter.
                    </p>
                </div>
                
                <div class="bg-gradient-to-br from-gray-700 to-gray-900 rounded-xl p-6 shadow-lg transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 flex items-center justify-center bg-indigo-600 rounded-full mr-4">
                            <i class="fas fa-mobile-alt text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold">Mobile Friendly</h3>
                    </div>
                    <p class="text-gray-300">
                        Play Amakarita on any device with our responsive design. Enjoy the game on desktop, tablet, or mobile without losing any functionality.
                    </p>
                </div>
            </div>
        </div>
    </section>
    <!-- Game Statistics Section -->
    <section class="py-16 px-6 bg-gradient-to-b from-gray-900 to-indigo-900 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-full h-full bg-repeat" style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgdmlld0JveD0iMCAwIDYwIDYwIj48ZyBmaWxsPSJub25lIj48cGF0aCBkPSJNMzAgMzBjMCAzLTIuNSA1LjUtNS41IDUuNVM5IDMzIDkgMzBzMTItNSAxNS41LTVTMzAgMjcgMzAgMzB6IiBzdHJva2U9IiNmZmYiLz48cGF0aCBkPSJNMzAgMzBjMCAzIDIuNSA1LjUgNS41IDUuNVM1MSAzMyA1MSAzMHMtMTItNS0xNS41LTVTMzAgMjcgMzAgMzB6IiBzdHJva2U9IiNmZmYiLz48L2c+PC9zdmc+')"></div>
        </div>
        
        <div class="container mx-auto relative z-10">
            <h2 class="text-3xl font-bold mb-16 text-center">Game Statistics</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <?php
                // Get active players count (users who played at least one game)
                $active_players_sql = "SELECT COUNT(*) as count FROM users WHERE games_played > 0";
                $active_players_result = $conn->query($active_players_sql);
                $active_players = $active_players_result->fetch_assoc()['count'];
                
                // Get total games played
                $games_played_sql = "SELECT COUNT(*) as count FROM games";
                $games_played_result = $conn->query($games_played_sql);
                $games_played = $games_played_result->fetch_assoc()['count'];
                
                // Estimate cards played (each game has approximately 36 cards)
                $cards_played = $games_played * 36;
                
                // Get average rating if you have a ratings table, otherwise use a default
                $avg_rating = 4.8; // Default value
                ?>
                
                <div class="glass-effect rounded-xl p-6 transform transition-all duration-300 hover:scale-105">
                    <div class="text-4xl font-bold text-indigo-400 mb-2"><?php echo number_format($active_players); ?>+</div>
                    <div class="text-xl">Active Players</div>
                </div>
                
                <div class="glass-effect rounded-xl p-6 transform transition-all duration-300 hover:scale-105">
                    <div class="text-4xl font-bold text-indigo-400 mb-2"><?php echo number_format($games_played); ?>+</div>
                    <div class="text-xl">Games Played</div>
                </div>
                
                <div class="glass-effect rounded-xl p-6 transform transition-all duration-300 hover:scale-105">
                    <div class="text-4xl font-bold text-indigo-400 mb-2"><?php echo number_format($cards_played); ?>+</div>
                    <div class="text-xl">Cards Played</div>
                </div>
                
                <div class="glass-effect rounded-xl p-6 transform transition-all duration-300 hover:scale-105">
                    <div class="text-4xl font-bold text-indigo-400 mb-2"><?php echo $avg_rating; ?><span class="text-2xl">/5</span></div>
                    <div class="text-xl">Player Rating</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-16 px-6 bg-indigo-900 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-600 rounded-full"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-indigo-600 rounded-full"></div>
        </div>
        
        <div class="container mx-auto text-center relative z-10">
            <h2 class="text-3xl font-bold mb-6">Ready to Play Amakarita?</h2>
            <p class="text-xl text-indigo-200 mb-8 max-w-2xl mx-auto">
                Join thousands of players and experience the traditional Rwandan card game online.
            </p>
            <a href="<?php echo $is_logged_in ? 'games.php' : 'auth.php?action=register'; ?>" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-lg text-lg transition transform hover:scale-105 hover:shadow-lg hover:shadow-red-500/30 inline-block">
                <?php echo $is_logged_in ? 'Play Now' : 'Sign Up Free'; ?>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12 px-6">
        <div class="container mx-auto">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold text-white mb-4">Amakarita</h3>
                    <p class="mb-4">
                        Experience the traditional Rwandan card game online with friends or against AI opponents.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold text-white mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="hover:text-white transition">Home</a></li>
                        <li><a href="#how-to-play" class="hover:text-white transition">How to Play</a></li>
                        <li><a href="games.php" class="hover:text-white transition">Play Now</a></li>
                        <?php if (!$is_logged_in): ?>
                            <li><a href="auth.php?action=register" class="hover:text-white transition">Register</a></li>
                            <li><a href="auth.php?action=login" class="hover:text-white transition">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold text-white mb-4">Resources</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-white transition">Game Rules</a></li>
                        <li><a href="#" class="hover:text-white transition">Strategy Guide</a></li>
                        <li><a href="#" class="hover:text-white transition">FAQ</a></li>
                        <li><a href="#" class="hover:text-white transition">Support</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold text-white mb-4">Contact</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-envelope mt-1 mr-2"></i>
                            <span>support@amakarita.com</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-2"></i>
                            <span>Kigali, Rwanda</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p>&copy; <?php echo date('Y'); ?> Amakarita. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
        
        // 3D Background Animation with Three.js
        (function() {
            const container = document.getElementById('canvas-container');
            if (!container) return;
            
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            
            const renderer = new THREE.WebGLRenderer({ alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setClearColor(0x000000, 0);
            container.appendChild(renderer.domElement);
            
            // Create particles
            const particlesGeometry = new THREE.BufferGeometry();
            const particlesCount = 1000;
            
            const posArray = new Float32Array(particlesCount * 3);
            
            for (let i = 0; i < particlesCount * 3; i++) {
                posArray[i] = (Math.random() - 0.5) * 10;
            }
            
            particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
            
            // Materials
            const particlesMaterial = new THREE.PointsMaterial({
                size: 0.02,
                color: 0x4f46e5,
                transparent: true,
                opacity: 0.8
            });
            
            // Mesh
            const particlesMesh = new THREE.Points(particlesGeometry, particlesMaterial);
            scene.add(particlesMesh);
            
            // Position camera
            camera.position.z = 5;
            
            // Mouse movement effect
            let mouseX = 0;
            let mouseY = 0;
            
            function onDocumentMouseMove(event) {
                mouseX = (event.clientX - window.innerWidth / 2) / 100;
                mouseY = (event.clientY - window.innerHeight / 2) / 100;
            }
            
            document.addEventListener('mousemove', onDocumentMouseMove);
            
            // Handle window resize
            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
            
            // Animation loop
            function animate() {
                requestAnimationFrame(animate);
                
                particlesMesh.rotation.x += 0.0005;
                particlesMesh.rotation.y += 0.0005;
                
                // Responsive to mouse movement
                particlesMesh.rotation.x += mouseY * 0.0003;
                particlesMesh.rotation.y += mouseX * 0.0003;
                
                renderer.render(scene, camera);
            }
            
            animate();
        })();
        
        // Card 3D hover effect enhancement
        document.querySelectorAll('.card-3d').forEach(card => {
            card.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const angleX = (y - centerY) / 10;
                const angleY = (centerX - x) / 10;
                
                this.style.transform = `rotateX(${angleX}deg) rotateY(${angleY}deg)`;
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });
    </script>
</body>
</html>