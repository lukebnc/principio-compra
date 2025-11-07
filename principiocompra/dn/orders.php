<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Por favor inicia sesión para ver tus órdenes.";
    redirect('index.php');
}

// Fetch orders for the logged-in user with product information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT 
        o.id AS order_id, 
        o.product_id, 
        o.quantity,
        o.total_price, 
        o.payment_address, 
        o.status, 
        o.admin_sent_link, 
        o.created_at,
        p.name as product_name,
        p.image as product_image,
        (SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = o.product_id) as has_reviewed
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count orders that can be reviewed
$can_review_count = 0;
foreach ($orders as $order) {
    if (($order['status'] === 'accepted' || $order['status'] === 'completed') && $order['has_reviewed'] == 0 && !empty($order['product_id'])) {
        $can_review_count++;
    }
}

// Count cart items
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Órdenes - Market-X</title>
    <link rel="stylesheet" href="assets/modern-styles.css">
    <style>
        .review-button-pulse {
            animation: pulse-glow 2s ease-in-out infinite;
        }
        
        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(251, 191, 36, 0);
            }
            50% {
                box-shadow: 0 0 20px 5px rgba(251, 191, 36, 0.4);
            }
        }
        
        .order-row {
            transition: all 0.3s ease;
        }
        
        .order-row:hover {
            background: rgba(59, 130, 246, 0.05);
        }
    </style>
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
                    <span class="title-text">📦 Mis Órdenes</span>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="navigation">
                <a href="index.php" class="xp-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    </svg>
                    Inicio
                </a>
                <a href="cart.php" class="xp-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="8" cy="21" r="1"/>
                        <circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                    Carrito (<?php echo $cart_count; ?>)
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
                
                <!-- Notification for pending reviews -->
                <?php if ($can_review_count > 0): ?>
                    <div style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border: 3px solid #f59e0b; border-radius: 16px; padding: 24px; margin-bottom: 28px; color: white; box-shadow: 0 8px 20px rgba(251, 191, 36, 0.3);">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <div style="font-size: 3rem;">⭐</div>
                            <div style="flex: 1;">
                                <h3 style="margin: 0 0 8px 0; font-size: 1.4rem; font-weight: 700;">
                                    ¡Tienes <?php echo $can_review_count; ?> <?php echo $can_review_count == 1 ? 'producto' : 'productos'; ?> listo<?php echo $can_review_count == 1 ? '' : 's'; ?> para reseñar!
                                </h3>
                                <p style="margin: 0; font-size: 1rem; opacity: 0.95;">
                                    Tu opinión es muy valiosa. Comparte tu experiencia y ayuda a otros compradores. 💬
                                </p>
                            </div>
                        </div>
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
                        <h2 style="color: var(--text-secondary); margin-bottom: 16px;">No tienes órdenes aún</h2>
                        <p style="color: var(--text-muted); margin-bottom: 24px;">¡Comienza a comprar para ver tus órdenes aquí!</p>
                        <a href="index.php" class="button">Ir a la Tienda</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Total</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Descarga</th>
                                    <th>Reseña</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="order-row">
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <?php if (!empty($order['product_image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($order['product_image']); ?>" alt="<?php echo htmlspecialchars($order['product_name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                                <?php endif; ?>
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($order['product_name'] ?? 'Producto no disponible'); ?></div>
                                                    <div style="color: var(--text-muted); font-size: 0.85rem;">ID: #<?php echo htmlspecialchars($order['order_id']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($order['quantity']); ?></strong></td>
                                        <td><strong style="color: var(--primary);">$<?php echo number_format($order['total_price'], 2); ?></strong></td>
                                        <td style="color: var(--text-muted); font-size: 0.9rem;"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <?php if ($order['status'] === 'completed'): ?>
                                                <span style="background: rgba(16, 185, 129, 0.2); color: var(--success); padding: 6px 14px; border-radius: 8px; font-weight: 600; font-size: 0.8rem; display: inline-block;">✓ Completada</span>
                                            <?php elseif ($order['status'] === 'accepted'): ?>
                                                <span style="background: rgba(59, 130, 246, 0.2); color: var(--primary); padding: 6px 14px; border-radius: 8px; font-weight: 600; font-size: 0.8rem; display: inline-block;">✅ Aceptada</span>
                                            <?php elseif ($order['status'] === 'cancelled'): ?>
                                                <span style="background: rgba(239, 68, 68, 0.2); color: var(--error); padding: 6px 14px; border-radius: 8px; font-weight: 600; font-size: 0.8rem; display: inline-block;">❌ Cancelada</span>
                                            <?php else: ?>
                                                <span style="background: rgba(245, 158, 11, 0.2); color: var(--warning); padding: 6px 14px; border-radius: 8px; font-weight: 600; font-size: 0.8rem; display: inline-block;">⏳ Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($order['admin_sent_link'])): ?>
                                                <a href="<?php echo htmlspecialchars($order['admin_sent_link']); ?>" class="button success" target="_blank" style="font-size: 0.8rem; padding: 8px 16px; white-space: nowrap;">
                                                    📥 Descargar
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 0.875rem;">Procesando...</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($order['status'] === 'accepted' || $order['status'] === 'completed') && !empty($order['product_id'])): ?>
                                                <?php if ($order['has_reviewed'] > 0): ?>
                                                    <span style="color: var(--success); font-size: 0.875rem; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <polyline points="20 6 9 17 4 12"/>
                                                        </svg>
                                                        Reseñado
                                                    </span>
                                                <?php else: ?>
                                                    <a href="product.php?id=<?php echo $order['product_id']; ?>&review=1" 
                                                       class="button review-button-pulse" 
                                                       style="font-size: 0.85rem; padding: 8px 16px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border-color: #f59e0b; color: white; font-weight: 600; white-space: nowrap;">
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
                    </div>
                    
                    <div style="margin-top: 30px; text-align: center;">
                        <a href="index.php" class="button">← Volver a la Tienda</a>
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