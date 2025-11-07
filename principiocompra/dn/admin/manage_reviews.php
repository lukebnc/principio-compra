<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Fetch all reviews with user and product information
$stmt = $conn->prepare("
    SELECT 
        r.*, 
        u.username, 
        p.name as product_name,
        p.image as product_image
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_reviews = count($reviews);
$average_rating = 0;
$rating_distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

if ($total_reviews > 0) {
    $total_rating = 0;
    foreach ($reviews as $review) {
        $total_rating += $review['rating'];
        $rating_distribution[$review['rating']]++;
    }
    $average_rating = round($total_rating / $total_reviews, 2);
}

$reviews_with_response = 0;
foreach ($reviews as $review) {
    if (!empty($review['admin_response'])) {
        $reviews_with_response++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Reseñas - Market-X Admin</title>
    <link rel="stylesheet" href="modern-admin-styles.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--surface), var(--bg-card));
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 8px;
        }
        
        .review-card {
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .review-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
    </style>
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
            <a href="manage_reviews.php" class="active">⭐ Reviews</a>
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
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_reviews; ?></div>
                    <div class="stat-label">Total Reseñas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #fbbf24;"><?php echo $average_rating; ?> ★</div>
                    <div class="stat-label">Calificación Promedio</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $reviews_with_response; ?></div>
                    <div class="stat-label">Reseñas Respondidas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_reviews - $reviews_with_response; ?></div>
                    <div class="stat-label">Sin Responder</div>
                </div>
            </div>
            
            <!-- Rating Distribution -->
            <div style="background: var(--surface); border: 2px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 30px;">
                <h3 style="margin-bottom: 16px;">Distribución de Calificaciones</h3>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <div style="display: flex; align-items: center; gap: 12px; margin: 10px 0;">
                        <span style="color: #fbbf24; width: 60px;"><?php echo $i; ?> ★</span>
                        <div style="flex: 1; height: 24px; background: rgba(209, 213, 219, 0.2); border-radius: 12px; overflow: hidden;">
                            <div style="height: 100%; background: linear-gradient(90deg, #fbbf24, #f59e0b); width: <?php echo $total_reviews > 0 ? ($rating_distribution[$i] / $total_reviews * 100) : 0; ?>%; transition: width 0.3s ease;"></div>
                        </div>
                        <span style="width: 60px; text-align: right; color: var(--text-muted);"><?php echo $rating_distribution[$i]; ?> (<?php echo $total_reviews > 0 ? round($rating_distribution[$i] / $total_reviews * 100) : 0; ?>%)</span>
                    </div>
                <?php endfor; ?>
            </div>
            
            <!-- Reviews List -->
            <?php if (empty($reviews)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 60px;">No hay reseñas todavía.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div style="display: grid; grid-template-columns: auto 1fr auto; gap: 20px; align-items: start;">
                            <!-- Product Image -->
                            <div>
                                <img src="../<?php echo htmlspecialchars($review['product_image']); ?>" alt="Product" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                            </div>
                            
                            <!-- Review Content -->
                            <div>
                                <div style="margin-bottom: 12px;">
                                    <strong style="font-size: 1.1rem; color: var(--primary);"><?php echo htmlspecialchars($review['product_name']); ?></strong>
                                    <span style="color: var(--text-muted); font-size: 0.85rem; margin-left: 8px;">(ID: <?php echo $review['product_id']; ?>)</span>
                                </div>
                                
                                <div style="margin-bottom: 8px;">
                                    <span style="font-weight: 600;">👤 <?php echo htmlspecialchars($review['username']); ?></span>
                                    <span style="color: var(--text-muted); margin-left: 12px; font-size: 0.9rem;">📅 <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></span>
                                </div>
                                
                                <div style="color: #fbbf24; font-size: 1.2rem; margin-bottom: 12px;">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $review['rating'] ? '★' : '☆';
                                    }
                                    ?>
                                </div>
                                
                                <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 12px;">
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                </p>
                                
                                <?php if (!empty($review['admin_response'])): ?>
                                    <div style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid var(--primary); padding: 12px 16px; margin-top: 12px; border-radius: 8px;">
                                        <div style="font-weight: 600; color: var(--primary); margin-bottom: 6px; font-size: 0.9rem;">
                                            💬 Tu Respuesta:
                                        </div>
                                        <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                                            <?php echo nl2br(htmlspecialchars($review['admin_response'])); ?>
                                        </p>
                                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 6px;">
                                            <?php echo date('d/m/Y H:i', strtotime($review['admin_response_at'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Actions -->
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <?php if (empty($review['admin_response'])): ?>
                                    <a href="respond_review.php?id=<?php echo $review['id']; ?>" class="button" style="font-size: 0.85rem; padding: 8px 16px; white-space: nowrap;">
                                        💬 Responder
                                    </a>
                                <?php else: ?>
                                    <a href="respond_review.php?id=<?php echo $review['id']; ?>" class="button" style="font-size: 0.85rem; padding: 8px 16px; white-space: nowrap;">
                                        ✏️ Editar Respuesta
                                    </a>
                                <?php endif; ?>
                                <a href="delete_review.php?id=<?php echo $review['id']; ?>" class="button danger" style="font-size: 0.85rem; padding: 8px 16px; white-space: nowrap;" onclick="return confirm('¿Estás seguro de eliminar esta reseña?');">
                                    🗑️ Eliminar
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <footer class="admin-footer">
            <p>© 2025 Market-X Admin Panel</p>
        </footer>
    </div>
</body>
</html>