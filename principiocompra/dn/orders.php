<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to view your orders.";
    redirect('index.php');
}

// Fetch orders for the logged-in user
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT o.id AS order_id, o.product_id, o.total_price, o.payment_address, o.status, o.admin_sent_link, o.created_at,
           (SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = o.product_id) as has_reviewed
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Market-X</title>
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
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span class="title-text">📦 My Orders</span>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="navigation">
                <a href="index.php" class="xp-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    </svg>
                    Home
                </a>
                <a href="cart.php" class="xp-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="8" cy="21" r="1"/>
                        <circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                    Cart (<?php echo $cart_count; ?>)
                </a>
            </div>
            
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
                
                <!-- Notification for new download links -->
                <?php 
                $has_new_downloads = false;
                foreach ($orders as $order) {
                    if (!empty($order['admin_sent_link']) && $order['has_reviewed'] == 0 && ($order['status'] === 'accepted' || $order['status'] === 'completed')) {
                        $has_new_downloads = true;
                        break;
                    }
                }
                if ($has_new_downloads): 
                ?>
                    <div style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border: 2px solid #f59e0b; border-radius: 12px; padding: 20px; margin-bottom: 24px; color: white; text-align: center;">
                        <h3 style="margin: 0 0 8px 0; font-size: 1.3rem;">⭐ ¡Tu descarga está lista!</h3>
                        <p style="margin: 0; font-size: 0.95rem; opacity: 0.95;">
                            Descarga tu producto y comparte tu experiencia dejando una reseña. ¡Tu opinión ayuda a otros usuarios! 💬
                        </p>
                    </div>
                <?php endif; ?>
                
                <!-- Orders Table -->
                <?php if (empty($orders)): ?>
                    <div style="text-align: center; padding: 60px 20px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 20px; opacity: 0.5;">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.29 7 12 12 20.71 7"/>
                            <line x1="12" y1="22" x2="12" y2="12"/>
                        </svg>
                        <h2 style="color: var(--text-secondary); margin-bottom: 16px;">No orders yet</h2>
                        <p style="color: var(--text-muted); margin-bottom: 24px;">Start shopping to see your orders here!</p>
                        <a href="index.php" class="button">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total Price</th>
                                <th>Payment Address</th>
                                <th>Status</th>
                                <th>Download Link</th>
                                <th>Reseña</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($order['order_id']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td><strong>$<?php echo number_format($order['total_price'], 2); ?></strong></td>
                                    <td style="font-family: monospace; font-size: 0.875rem;"><?php echo htmlspecialchars(substr($order['payment_address'], 0, 20)) . '...'; ?></td>
                                    <td>
                                        <?php if ($order['status'] === 'completed'): ?>
                                            <span style="background: rgba(16, 185, 129, 0.2); color: var(--success); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 0.75rem;">✓ Completada</span>
                                        <?php elseif ($order['status'] === 'accepted'): ?>
                                            <span style="background: rgba(59, 130, 246, 0.2); color: var(--primary); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 0.75rem;">✅ Aceptada</span>
                                        <?php elseif ($order['status'] === 'cancelled'): ?>
                                            <span style="background: rgba(239, 68, 68, 0.2); color: var(--error); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 0.75rem;">❌ Cancelada</span>
                                        <?php else: ?>
                                            <span style="background: rgba(245, 158, 11, 0.2); color: var(--warning); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 0.75rem;">⏳ Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($order['admin_sent_link'])): ?>
                                            <a href="<?php echo htmlspecialchars($order['admin_sent_link']); ?>" class="button success" target="_blank" style="font-size: 0.75rem; padding: 6px 12px;">
                                                📥 Descargar
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.875rem;">Procesando...</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($order['status'] === 'accepted' || $order['status'] === 'completed') && !empty($order['product_id'])): ?>
                                            <?php if ($order['has_reviewed'] > 0): ?>
                                                <span style="color: var(--success); font-size: 0.875rem; font-weight: 600;">✓ Reseñado</span>
                                            <?php else: ?>
                                                <a href="product.php?id=<?php echo $order['product_id']; ?>&review=1" 
                                                   class="button" 
                                                   style="font-size: 0.75rem; padding: 6px 12px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border-color: #f59e0b;">
                                                    ⭐ Dejar Reseña
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.875rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 30px; text-align: center;">
                        <a href="index.php" class="button">← Back to Store</a>
                    </div>
                <?php endif; ?>
                
                <!-- Footer -->
                <div class="footer">
                    <p>© 2025 Market-X - KEY TO O | Powered by Modern Design ✨</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>