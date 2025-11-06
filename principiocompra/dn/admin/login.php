<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);

    // Hardcoded admin credentials
    $admin_username = 'admin';
    $admin_password = 'admin';

    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        redirect('index.php');
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Market-X</title>
    <link rel="stylesheet" href="modern-admin-styles.css">
</head>
<body>
    <div style="display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px;">
        <div class="window" style="max-width: 500px;">
            <div class="title-bar">
                <span>🔒 Admin Login</span>
            </div>
            <div class="content">
                <?php if (isset($error)): ?>
                    <div class="notification error">
                        <span>✕</span>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" placeholder="Enter admin username" required>
                    
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" placeholder="Enter admin password" required>
                    
                    <button type="submit" class="button" style="width: 100%; font-size: 1rem; padding: 14px;">
                        ✓ Login to Dashboard
                    </button>
                </form>
                
                <p style="text-align: center; margin-top: 20px; color: var(--text-muted); font-size: 0.875rem;">
                    Default credentials: admin / admin
                </p>
            </div>
        </div>
    </div>
</body>
</html>