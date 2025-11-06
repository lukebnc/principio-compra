<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect to login if not logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Fetch total number of users
$stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

// Fetch total number of products
$stmt = $conn->query("SELECT COUNT(*) as total_products FROM products");
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

// Fetch total number of orders
$stmt = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

// Fetch total revenue
$stmt = $conn->query("SELECT SUM(total_price) as total_revenue FROM orders WHERE status = 'completed'");
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

// Fetch recent orders (last 10)
$stmt = $conn->prepare("
    SELECT o.id AS order_id, o.user_id, u.username, o.total_price, o.status, o.admin_sent_link, o.created_at
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC LIMIT 10
");
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Market-X</title>
    <link rel="stylesheet" href="modern-admin-styles.css">
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen">
        <h1>🚀 Booting Admin Panel...</h1>
        <div class="loading-bar">
            <span></span>
        </div>
        <p class="boot-message">Initializing dashboard...</p>
    </div>
    
    <div class="window">
        <div class="title-bar">
            <span>🎛️ Admin Dashboard - Market-X</span>
            <div class="buttons">
                <a href="login.php?logout=true" class="button danger">Logout</a>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <div class="nav-menu">
            <a href="index.php">📊 Dashboard</a>
            <a href="manage_users.php">👥 Users</a>
            <a href="manage_products.php">📦 Products</a>
            <a href="add_product.php">➕ Add Product</a>
            <a href="admin_orders.php">🛍️ Orders</a>
            <a href="manage_payments.php">💳 Payments</a>
            <a href="assign_download_link.php">🔗 Assign Links</a>
        </div>
        
        <div class="content">
            <h2 style="margin-bottom: 24px; font-size: 1.75rem;">Welcome, Admin! 👋</h2>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>👥 Total Users</h3>
                    <div class="value"><?php echo number_format($total_users); ?></div>
                </div>
                <div class="stat-card">
                    <h3>📦 Total Products</h3>
                    <div class="value"><?php echo number_format($total_products); ?></div>
                </div>
                <div class="stat-card">
                    <h3>🛍️ Total Orders</h3>
                    <div class="value"><?php echo number_format($total_orders); ?></div>
                </div>
                <div class="stat-card">
                    <h3>💰 Total Revenue</h3>
                    <div class="value">$<?php echo number_format($total_revenue, 2); ?></div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <h3 style="font-size: 1.5rem; margin: 40px 0 20px;">📋 Recent Orders</h3>
            <?php if (empty($recent_orders)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 40px;">No recent orders found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Date</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Download Link</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($order['order_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td><strong>$<?php echo number_format($order['total_price'], 2); ?></strong></td>
                                <td>
                                    <?php if ($order['status'] === 'completed'): ?>
                                        <span style="background: rgba(16, 185, 129, 0.2); color: var(--success); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 0.75rem;">✓ Completed</span>
                                    <?php else: ?>
                                        <span style="background: rgba(245, 158, 11, 0.2); color: var(--warning); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 0.75rem;">⏳ Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($order['admin_sent_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($order['admin_sent_link']); ?>" target="_blank" style="color: var(--primary); font-size: 0.875rem;">View Link</a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.875rem;">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="assign_download_link.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="button" style="font-size: 0.75rem; padding: 6px 12px;">Assign Link</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <footer class="admin-footer">
            <p>© 2025 Market-X Admin Panel | System Time: <span id="system-time"><?php echo date('Y-m-d H:i:s'); ?></span></p>
        </footer>
    </div>
    
    <script>
        // Update system time every second
        setInterval(function() {
            const now = new Date();
            document.getElementById('system-time').textContent = now.toLocaleString();
        }, 1000);
    </script>
</body>
</html>
