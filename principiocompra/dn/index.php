<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Handle User Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);
    // Fetch user from the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        // Successful login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['success'] = "Login successful!";
    } else {
        // Invalid credentials
        $_SESSION['error'] = "Invalid username or password.";
    }
    redirect('index.php');
}

// Handle User Logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('index.php');
}

// Fetch All Products
$stmt = $conn->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count cart items
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Market-X - Modern Marketplace</title>
    <link rel="stylesheet" href="assets/modern-styles.css">
</head>
<body>
    <!-- Booting Screen -->
    <div class="booting-screen">
        <h1>🚀 Launching Market-X...</h1>
        <div class="loading-bar">
            <span></span>
        </div>
        <p>Please wait while we prepare your experience...</p>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Main Window -->
        <div class="xp-window">
            <!-- Title Bar -->
            <div class="xp-titlebar">
                <div class="title-content">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="21" r="1"/>
                        <circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                    <span class="title-text">Market-X - Your Digital Store</span>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="navigation">
                <a href="index.php" class="xp-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Home
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="cart.php" class="xp-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="8" cy="21" r="1"/>
                        <circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                    Cart (<?php echo $cart_count; ?>)
                </a>
                <a href="orders.php" class="xp-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Orders
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Content -->
            <div class="content">
                <!-- Notifications -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="notification success">
                        <span class="icon">✓</span>
                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="notification error">
                        <span class="icon">✕</span>
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- User Dashboard -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-dashboard">
                        <h3>👋 Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h3>
                        <p><strong>My Orders:</strong> <a href="orders.php">View Order History</a></p>
                        <p><strong>Shopping Cart:</strong> <a href="cart.php">View Cart (<?php echo $cart_count; ?> items)</a></p>
                        <a href="?logout" class="button danger" style="margin-top: 16px;">Logout</a>
                    </div>
                <?php else: ?>
                    <!-- Login Form -->
                    <div class="login-form">
                        <h3>🔐 Login to Your Account</h3>
                        <form method="POST" action="">
                            <label for="username">Username:</label>
                            <input type="text" name="username" id="username" placeholder="Enter your username" required>
                            
                            <label for="password">Password:</label>
                            <input type="password" name="password" id="password" placeholder="Enter your password" required>
                            
                            <button type="submit" name="login">Login</button>
                        </form>
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                    </div>
                    
                    <div class="notification error" style="margin-top: 20px;">
                        <span class="icon">ℹ</span>
                        You must be logged in to add products to cart and make purchases.
                    </div>
                <?php endif; ?>
                
                <!-- Featured Products -->
                <div class="xp-window products-window">
                    <div class="xp-titlebar">
                        <div class="title-content">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                            </svg>
                            <span>✨ Featured Products</span>
                        </div>
                    </div>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="xp-product">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="specs"><?php echo htmlspecialchars($product['description']); ?></p>
                                <p class="price">$<?php echo htmlspecialchars($product['price']); ?></p>
                                <a href="product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="xp-button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    View Details
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="footer">
                    <p>© 2025 Market-X - KEY TO O | Powered by Modern Design ✨</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/chat_widget.php'; ?>
</body>
</html>
