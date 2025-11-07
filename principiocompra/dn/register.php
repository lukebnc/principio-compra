<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);
    $not_a_robot = isset($_POST['not_a_robot']) ? true : false;

    // Validate "I'm not a robot" checkbox
    if (!$not_a_robot) {
        $_SESSION['error'] = "Please confirm that you are not a robot.";
        redirect('register.php');
    }

    // Check if username already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Username already exists.";
    } else {
        // Insert new user
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hashed_password]);
        $_SESSION['success'] = "Registration successful! Please login.";
        redirect('index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Market-X</title>
    <link rel="stylesheet" href="assets/modern-styles.css">
</head>
<body>
    <div class="container">
        <div class="xp-window">
            <!-- Title Bar -->
            <div class="xp-titlebar">
                <div class="title-content">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="19" y1="8" x2="19" y2="14"/>
                        <line x1="22" y1="11" x2="16" y2="11"/>
                    </svg>
                    <span class="title-text">Create Account</span>
                </div>
            </div>
            
            <div class="content">
                <!-- Notifications -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="notification error">
                        <span class="icon">✕</span>
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="notification success">
                        <span class="icon">✓</span>
                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <div class="register-form">
                    <h3>🚀 Join Market-X</h3>
                    <form method="POST" action="">
                        <label for="username">Username:</label>
                        <input type="text" name="username" id="username" placeholder="Choose a username" required>

                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password" placeholder="Create a strong password" required>

                        <!-- "I'm not a robot" Checkbox -->
                        <div class="checkbox-container">
                            <input type="checkbox" name="not_a_robot" id="not_a_robot" required>
                            <label for="not_a_robot">I'm not a robot 🤖</label>
                        </div>

                        <button type="submit">Create Account</button>
                    </form>
                    <p>Already have an account? <a href="index.php">Login here</a></p>
                </div>
                
                <!-- Footer -->
                <div class="footer">
                    <p>© 2025 Market-X - KEY TO O | Powered by Modern Design ✨</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>