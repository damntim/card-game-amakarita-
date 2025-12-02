<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
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

// Determine action (login or register)
$action = isset($_GET['action']) ? $_GET['action'] : 'login';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        // Login process
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];
        
        $sql = "SELECT id, username, password FROM users WHERE username = '$username'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Redirect to games page
                header("Location: games.php");
                exit();
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "User not found";
        }
    } elseif (isset($_POST['register'])) {
        // Registration process
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate input
        if (strlen($username) < 3) {
            $error = "Username must be at least 3 characters";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } else {
            // Check if username already exists
            $check_sql = "SELECT id FROM users WHERE username = '$username'";
            $check_result = $conn->query($check_sql);
            
            if ($check_result->num_rows > 0) {
                $error = "Username already taken";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insert_sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hashed_password')";
                
                if ($conn->query($insert_sql) === TRUE) {
                    // Registration successful
                    $_SESSION['user_id'] = $conn->insert_id;
                    $_SESSION['username'] = $username;
                    
                    // Redirect to games page
                    header("Location: games.php");
                    exit();
                } else {
                    $error = "Registration failed: " . $conn->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($action); ?> - Amakarita</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('https://images.unsplash.com/photo-1557683316-973673baf926?ixlib=rb-4.0.3');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
        }
        
        .card {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% {
                transform: translatey(0px);
            }
            50% {
                transform: translatey(-20px);
            }
            100% {
                transform: translatey(0px);
            }
        }
        
        .auth-form {
            backdrop-filter: blur(8px);
            background-color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Navigation Bar -->
    <nav class="bg-indigo-900 text-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="flex items-center space-x-2 hover:opacity-90 transition">
                <span class="text-2xl font-bold">Amakarita</span>
                <span class="text-sm text-indigo-200">Rwandan Card Game</span>
            </a>
            
            <div>
                <a href="index.php" class="hover:text-indigo-200 transition flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Home
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="flex-grow flex justify-center items-center p-4">
        <div class="max-w-lg w-full">
            <!-- Auth Card -->
            <div class="bg-white rounded-xl shadow-2xl overflow-hidden flex">
                <!-- Card Image -->
                <div class="w-1/3 bg-indigo-600 p-8 hidden md:flex flex-col justify-between">
                    <div class="text-white">
                        <h2 class="text-2xl font-bold mb-6">Amakarita</h2>
                        <p class="text-indigo-200">Experience the traditional Rwandan card game online.</p>
                    </div>
                    
                    <div class="card">
                        <div class="bg-white text-black rounded-lg w-20 h-28 flex flex-col items-center justify-center border-2 border-yellow-400 transform rotate-12">
                            <div class="text-2xl font-bold text-red-600">A</div>
                            <div class="text-3xl text-red-600">♥</div>
                            <div class="text-xs mt-1">Bwana</div>
                        </div>
                    </div>
                </div>
                
                <!-- Auth Form -->
                <div class="w-full md:w-2/3 p-8">
                    <div class="text-center mb-8">
                        <h1 class="text-2xl font-bold text-gray-800">
                            <?php echo $action === 'login' ? 'Welcome Back' : 'Create Account'; ?>
                        </h1>
                        <p class="text-gray-600 mt-2">
                            <?php echo $action === 'login' ? 'Sign in to access your account' : 'Join the Rwandan card game community'; ?>
                        </p>
                    </div>
                    
                    <form method="post" action="auth.php?action=<?php echo $action; ?>" class="space-y-6">
                        <?php if (isset($error)): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline"><?php echo $error; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <label for="username" class="block text-gray-700 font-medium mb-2">Username</label>
                            <input type="text" id="username" name="username" required 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Enter your username">
                        </div>
                        
                        <?php if ($action === 'register'): ?>
                            <div>
                                <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                                <input type="email" id="email" name="email" required 
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Enter your email">
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                            <input type="password" id="password" name="password" required 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Enter your password">
                        </div>
                        
                        <?php if ($action === 'register'): ?>
                            <div>
                                <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required 
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Confirm your password">
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <button type="submit" name="<?php echo $action; ?>" 
                                class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition">
                                <?php echo $action === 'login' ? 'Sign In' : 'Create Account'; ?>
                            </button>
                        </div>
                        
                        <div class="text-center text-gray-600">
                            <?php if ($action === 'login'): ?>
                                Don't have an account? <a href="auth.php?action=register" class="text-indigo-600 hover:underline">Register</a>
                            <?php else: ?>
                                Already have an account? <a href="auth.php?action=login" class="text-indigo-600 hover:underline">Login</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-indigo-900 text-white py-6">
        <div class="container mx-auto px-4 text-center">
            <p class="text-indigo-200">
                &copy; <?php echo date('Y'); ?> Amakarita - Traditional Rwandan Card Game
            </p>
        </div>
    </footer>

    <script>
    // Simple form validation
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long.');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return;
            }
            
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword && confirmPassword.value !== password) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
        });
    });
    </script>
</body>
</html>