<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Fetch all reviews with product and user info
$stmt = $conn->prepare("
    SELECT r.*, u.username, p.name as product_name, p.id as product_id
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Reseñas - Market-X Admin</title>
    <link rel="stylesheet" href="modern-admin-styles.css">
</head>
<body>
    <div class="window">
        <div class="title-bar">
            <span>⭐ Gestionar Reseñas - Market-X</span>
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
            <h2 style="margin-bottom: 24px; font-size: 1.75rem;">⭐ Gestión de Reseñas</h2>
            
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
            
            <?php if (empty($reviews)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 60px;">No hay reseñas todavía.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach ($reviews as $review): ?>
                    <div style="background: var(--surface); border: 2px solid var(--border); border-radius: 12px; padding: 24px;">
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 20px; margin-bottom: 16px;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                    <span style="font-weight: 600; font-size: 1.1rem;">
                                        👤 <?php echo htmlspecialchars($review['username']); ?>
                                    </span>
                                    <div style="color: #fbbf24; font-size: 1.2rem;">
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $review['rating'] ? '★' : '☆';
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 12px;">
                                    📦 Producto: <a href="../product.php?id=<?php echo $review['product_id']; ?>" target="_blank" style="color: var(--primary);"><?php echo htmlspecialchars($review['product_name']); ?></a>
                                    | 📅 <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                                </div>
                                
                                <p style="color: var(--text-secondary); line-height: 1.6; background: rgba(0,0,0,0.1); padding: 12px; border-radius: 8px;">
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                </p>
                                
                                <?php if (!empty($review['admin_response'])): ?>
                                <div style="background: rgba(59, 130, 246, 0.1); border-left: 3px solid var(--primary); padding: 12px 16px; margin-top: 12px; border-radius: 6px;">
                                    <div style="font-weight: 600; color: var(--primary); margin-bottom: 6px; font-size: 0.9rem;">
                                        💬 Tu respuesta:
                                    </div>
                                    <p style="color: var(--text-secondary); font-size: 0.95rem;">
                                        <?php echo nl2br(htmlspecialchars($review['admin_response'])); ?>
                                    </p>
                                    <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 6px;">
                                        <?php echo date('d/m/Y H:i', strtotime($review['admin_response_at'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <a href="delete_review.php?id=<?php echo $review['id']; ?>" 
                                   class="button danger" 
                                   style="font-size: 0.875rem; padding: 8px 16px; white-space: nowrap;"
                                   onclick="return confirm('¿Estás seguro de eliminar esta reseña?')">
                                    🗑️ Eliminar
                                </a>
                                
                                <?php if (empty($review['admin_response'])): ?>
                                <button onclick="showResponseForm(<?php echo $review['id']; ?>)" 
                                        class="button" 
                                        style="font-size: 0.875rem; padding: 8px 16px; white-space: nowrap;">
                                    💬 Responder
                                </button>
                                <?php else: ?>
                                <button onclick="showResponseForm(<?php echo $review['id']; ?>)" 
                                        class="button" 
                                        style="font-size: 0.875rem; padding: 8px 16px; white-space: nowrap;">
                                    ✏️ Editar
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Response Form (Hidden by default) -->
                        <div id="response-form-<?php echo $review['id']; ?>" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--border);">
                            <form method="POST" action="respond_review.php">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                
                                <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                                    <?php echo empty($review['admin_response']) ? 'Escribe tu respuesta:' : 'Editar respuesta:'; ?>
                                </label>
                                <textarea name="admin_response" rows="3" required 
                                          style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; font-family: inherit; resize: vertical; margin-bottom: 12px;"
                                          placeholder="Responde a esta reseña..."><?php echo htmlspecialchars($review['admin_response'] ?? ''); ?></textarea>
                                
                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="button success">Guardar Respuesta</button>
                                    <button type="button" onclick="hideResponseForm(<?php echo $review['id']; ?>)" class="button">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <footer class="admin-footer">
            <p>© 2025 Market-X Admin Panel</p>
        </footer>
    </div>
    
    <script>
        function showResponseForm(reviewId) {
            document.getElementById('response-form-' + reviewId).style.display = 'block';
        }
        
        function hideResponseForm(reviewId) {
            document.getElementById('response-form-' + reviewId).style.display = 'none';
        }
    </script>
</body>
</html>
