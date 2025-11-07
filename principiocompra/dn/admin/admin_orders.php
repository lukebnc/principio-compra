<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Fetch all orders with user information
$stmt = $conn->prepare("
    SELECT o.*, u.username, p.name as product_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN products p ON o.product_id = p.id
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Órdenes - Market-X Admin</title>
    <link rel="stylesheet" href="modern-admin-styles.css">
</head>
<body>
    <div class="window">
        <div class="title-bar">
            <span>🛍️ Gestionar Órdenes - Market-X</span>
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
            <a href="manage_reviews.php">⭐ Reviews</a>
        </div>
        
        <div class="content">
            <h2 style="margin-bottom: 24px; font-size: 1.75rem;">🛍️ Gestión de Órdenes</h2>
            
            <!-- Notifications -->
            <?php if (isset($_SESSION['success'])): ?>
                <div style="background: rgba(16, 185, 129, 0.1); border: 2px solid rgba(16, 185, 129, 0.3); color: var(--success); padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                    <span style="font-weight: 600;">✓</span> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 2px solid rgba(239, 68, 68, 0.3); color: var(--error); padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                    <span style="font-weight: 600;">✕</span> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($orders)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 60px;">No hay órdenes todavía.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Orden</th>
                            <th>Usuario</th>
                            <th>Producto</th>
                            <th>Precio Total</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Link de Descarga</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($order['id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name'] ?? 'N/A'); ?></td>
                                <td><strong>$<?php echo number_format($order['total_price'], 2); ?></strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <form method="POST" action="update_order_status.php" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" 
                                                style="padding: 6px 10px; border: 2px solid var(--border); border-radius: 6px; font-weight: 600; font-size: 0.875rem; cursor: pointer;
                                                <?php 
                                                    if ($order['status'] === 'completed') {
                                                        echo 'background: rgba(16, 185, 129, 0.2); color: var(--success);';
                                                    } elseif ($order['status'] === 'accepted') {
                                                        echo 'background: rgba(59, 130, 246, 0.2); color: var(--primary);';
                                                    } elseif ($order['status'] === 'cancelled') {
                                                        echo 'background: rgba(239, 68, 68, 0.2); color: var(--error);';
                                                    } else {
                                                        echo 'background: rgba(245, 158, 11, 0.2); color: var(--warning);';
                                                    }
                                                ?>
                                                ">
                                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>⏳ Pendiente</option>
                                            <option value="accepted" <?php echo $order['status'] === 'accepted' ? 'selected' : ''; ?>>✅ Aceptada</option>
                                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>✓ Completada</option>
                                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>❌ Cancelada</option>
                                        </select>
                                    </form>
                                </td>
                                <td style="font-size: 0.875rem;">
                                    <?php if (!empty($order['admin_sent_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($order['admin_sent_link']); ?>" target="_blank" style="color: var(--primary);">Ver Link</a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="assign_download_link.php?order_id=<?php echo urlencode($order['id']); ?>" 
                                       class="button" 
                                       style="font-size: 0.75rem; padding: 6px 12px;">
                                        🔗 Asignar Link
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <footer class="admin-footer">
            <p>© 2025 Market-X Admin Panel</p>
        </footer>
    </div>
</body>
</html>