<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get product ID
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Product not found.";
    redirect('index.php');
}

$product_id = $_GET['id'];

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $_SESSION['error'] = "Product not found.";
    redirect('index.php');
}

// Fetch reviews for this product
$stmt = $conn->prepare("
    SELECT r.*, u.username, r.admin_response, r.admin_response_at
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user has purchased this product (and hasn't reviewed yet)
$can_review = false;
$has_reviewed = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Check if user has completed order for this product
    $stmt = $conn->prepare("
        SELECT id FROM orders 
        WHERE user_id = ? AND product_id = ? AND status IN ('accepted', 'completed')
    ");
    $stmt->execute([$user_id, $product_id]);
    $user_order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_order) {
        // Check if user already reviewed
        $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $has_reviewed = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
        
        $can_review = !$has_reviewed;
    }
}

// Calculate average rating
$average_rating = 0;
if (!empty($reviews)) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $average_rating = round($total_rating / count($reviews), 1);
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
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($product['name']); ?> - Market-X</title>
    <link rel="stylesheet" href="assets/modern-styles.css">
    <style>
        .star-rating {
            transition: color 0.2s;
        }
        
        input[type="radio"]:checked ~ label.star-rating {
            color: #fbbf24 !important;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star-rating');
            const radios = document.querySelectorAll('input[type="radio"][name="rating"]');
            
            stars.forEach((star, index) => {
                star.addEventListener('click', function() {
                    // Reset all stars
                    stars.forEach(s => s.style.color = '#d1d5db');
                    
                    // Highlight selected stars
                    for(let i = 0; i <= index; i++) {
                        stars[i].style.color = '#fbbf24';
                    }
                });
                
                star.addEventListener('mouseover', function() {
                    for(let i = 0; i <= index; i++) {
                        stars[i].style.color = '#fbbf24';
                    }
                });
                
                star.addEventListener('mouseout', function() {
                    stars.forEach(s => s.style.color = '#d1d5db');
                    
                    // Restore checked state
                    radios.forEach((radio, idx) => {
                        if(radio.checked) {
                            for(let i = 0; i <= idx; i++) {
                                stars[i].style.color = '#fbbf24';
                            }
                        }
                    });
                });
            });
            
            // Auto-scroll to review form if review parameter is present
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('review') === '1') {
                const reviewForm = document.getElementById('review-form-section');
                if (reviewForm) {
                    setTimeout(function() {
                        reviewForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // Add highlight animation
                        reviewForm.style.animation = 'pulse 2s ease-in-out 3';
                    }, 500);
                }
            }
        });
    </script>
    <style>
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); box-shadow: 0 0 30px rgba(59, 130, 246, 0.5); }
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="container">
        <!-- Main Window -->
        <div class="xp-window">
            <!-- Title Bar -->
            <div class="xp-titlebar">
                <div class="title-content">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>
                    <span class="title-text">Product Details</span>
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
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="cart.php" class="xp-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="8" cy="21" r="1"/>
                        <circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                    Cart (<?php echo $cart_count; ?>)
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
                
                <!-- Product Detail -->
                <div class="product-detail">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img" />
                    </div>
                    
                    <div class="product-info">
                        <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                        <p class="price">$<?php echo htmlspecialchars($product['price']); ?></p>
                        
                        <!-- Rating Display -->
                        <?php if (!empty($reviews)): ?>
                        <div style="margin: 12px 0; display: flex; align-items: center; gap: 8px;">
                            <div style="color: #fbbf24; font-size: 1.2rem;">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= floor($average_rating)) {
                                        echo '★';
                                    } elseif ($i - $average_rating < 1) {
                                        echo '☆';
                                    } else {
                                        echo '☆';
                                    }
                                }
                                ?>
                            </div>
                            <span style="color: var(--text-muted); font-size: 0.9rem;">
                                <?php echo $average_rating; ?> (<?php echo count($reviews); ?> <?php echo count($reviews) == 1 ? 'reseña' : 'reseñas'; ?>)
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        
                        <p style="color: var(--text-muted); margin-bottom: 24px;">
                            <strong>Type:</strong> <?php echo htmlspecialchars(ucfirst($product['type'])); ?>
                        </p>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="cart.php?action=add&id=<?php echo $product['id']; ?>" class="button success" style="font-size: 1rem; padding: 14px 28px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                                    <circle cx="8" cy="21" r="1"/>
                                    <circle cx="19" cy="21" r="1"/>
                                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                                </svg>
                                Add to Cart
                            </a>
                        <?php else: ?>
                            <div class="notification error" style="margin-top: 20px;">
                                <span class="icon">🔒</span>
                                Please <a href="index.php" style="color: var(--error); text-decoration: underline;">login</a> to add this product to your cart.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Reviews Section -->
                <div style="margin-top: 60px;">
                    <h3 style="font-size: 1.5rem; margin-bottom: 24px;">⭐ Reseñas de Clientes</h3>
                    
                    <!-- Add Review Form -->
                    <?php if ($can_review): ?>
                    <div id="review-form-section" style="background: rgba(59, 130, 246, 0.05); border: 2px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 24px; margin-bottom: 30px; transition: all 0.3s ease;">
                        <h4 style="margin-bottom: 16px; font-size: 1.1rem;">✍️ Deja tu reseña</h4>
                        <?php if (isset($_GET['review']) && $_GET['review'] == '1'): ?>
                        <div style="background: rgba(251, 191, 36, 0.1); border-left: 4px solid #fbbf24; padding: 12px 16px; margin-bottom: 16px; border-radius: 6px;">
                            <p style="margin: 0; color: var(--text-secondary); font-weight: 600;">
                                🎉 ¡Gracias por tu compra! Tu opinión es muy importante para nosotros.
                            </p>
                        </div>
                        <?php endif; ?>
                        <form method="POST" action="add_review.php">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            
                            <div style="margin-bottom: 16px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Calificación:</label>
                                <div style="display: flex; gap: 8px; font-size: 2rem;">
                                    <input type="radio" name="rating" value="1" id="star1" required style="display: none;">
                                    <input type="radio" name="rating" value="2" id="star2" required style="display: none;">
                                    <input type="radio" name="rating" value="3" id="star3" required style="display: none;">
                                    <input type="radio" name="rating" value="4" id="star4" required style="display: none;">
                                    <input type="radio" name="rating" value="5" id="star5" required style="display: none;">
                                    
                                    <label for="star1" class="star-rating" style="cursor: pointer; color: #d1d5db;">★</label>
                                    <label for="star2" class="star-rating" style="cursor: pointer; color: #d1d5db;">★</label>
                                    <label for="star3" class="star-rating" style="cursor: pointer; color: #d1d5db;">★</label>
                                    <label for="star4" class="star-rating" style="cursor: pointer; color: #d1d5db;">★</label>
                                    <label for="star5" class="star-rating" style="cursor: pointer; color: #d1d5db;">★</label>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 16px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Comentario:</label>
                                <textarea name="comment" rows="4" required style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; font-family: inherit; resize: vertical;" placeholder="Cuéntanos tu experiencia con este producto..."></textarea>
                            </div>
                            
                            <button type="submit" class="button success">Publicar Reseña</button>
                        </form>
                    </div>
                    <?php elseif ($has_reviewed): ?>
                    <div class="notification" style="margin-bottom: 30px; background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3);">
                        <span class="icon">✓</span>
                        Ya has dejado una reseña para este producto.
                    </div>
                    <?php elseif (isset($_SESSION['user_id'])): ?>
                    <div class="notification" style="margin-bottom: 30px; background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3);">
                        <span class="icon">ℹ️</span>
                        Debes comprar este producto para poder dejar una reseña.
                    </div>
                    <?php endif; ?>
                    
                    <!-- Display Reviews -->
                    <?php if (empty($reviews)): ?>
                        <p style="text-align: center; color: var(--text-muted); padding: 40px;">
                            Aún no hay reseñas para este producto. ¡Sé el primero en dejar una!
                        </p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <?php foreach ($reviews as $review): ?>
                            <div style="background: var(--surface); border: 2px solid var(--border); border-radius: 12px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 4px;">
                                            👤 <?php echo htmlspecialchars($review['username']); ?>
                                        </div>
                                        <div style="color: #fbbf24; font-size: 1.1rem;">
                                            <?php 
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $review['rating'] ? '★' : '☆';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 0.875rem;">
                                        <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 12px;">
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                </p>
                                
                                <?php if (!empty($review['admin_response'])): ?>
                                <div style="background: rgba(59, 130, 246, 0.05); border-left: 3px solid var(--primary); padding: 12px 16px; margin-top: 12px; border-radius: 6px;">
                                    <div style="font-weight: 600; color: var(--primary); margin-bottom: 6px; font-size: 0.875rem;">
                                        💬 Respuesta del vendedor
                                    </div>
                                    <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                        <?php echo nl2br(htmlspecialchars($review['admin_response'])); ?>
                                    </p>
                                    <div style="color: var(--text-muted); font-size: 0.75rem; margin-top: 6px;">
                                        <?php echo date('d/m/Y', strtotime($review['admin_response_at'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
