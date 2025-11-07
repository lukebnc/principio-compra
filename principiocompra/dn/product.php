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

// Fetch reviews for this product with rating distribution
$stmt = $conn->prepare("
    SELECT r.*, u.username, r.admin_response, r.admin_response_at
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate rating statistics
$rating_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $review) {
    $rating_counts[$review['rating']]++;
}

// Check if user has purchased this product (and hasn't reviewed yet)
$can_review = false;
$has_reviewed = false;
$user_order_status = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Check if user has completed order for this product
    $stmt = $conn->prepare("
        SELECT id, status FROM orders 
        WHERE user_id = ? AND product_id = ? AND status IN ('accepted', 'completed')
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id, $product_id]);
    $user_order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_order) {
        $user_order_status = $user_order['status'];
        // Check if user already reviewed
        $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $has_reviewed = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
        
        $can_review = !$has_reviewed;
    }
}

// Calculate average rating
$average_rating = 0;
$total_reviews = count($reviews);
if ($total_reviews > 0) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $average_rating = round($total_rating / $total_reviews, 1);
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($product['name']); ?> - Market-X</title>
    <link rel="stylesheet" href="assets/modern-styles.css">
    <style>
        .star-rating {
            cursor: pointer;
            font-size: 2rem;
            color: #d1d5db;
            transition: all 0.2s ease;
            display: inline-block;
        }
        
        .star-rating:hover {
            transform: scale(1.1);
        }
        
        .star-rating.active {
            color: #fbbf24;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0;
        }
        
        .rating-bar-fill {
            height: 8px;
            background: linear-gradient(90deg, #fbbf24, #f59e0b);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .rating-bar-bg {
            flex: 1;
            height: 8px;
            background: rgba(209, 213, 219, 0.2);
            border-radius: 4px;
            overflow: hidden;
        }
        
        @keyframes highlight-pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 rgba(59, 130, 246, 0); }
            50% { transform: scale(1.02); box-shadow: 0 0 30px rgba(59, 130, 246, 0.3); }
        }
        
        .review-form-highlight {
            animation: highlight-pulse 2s ease-in-out 3;
        }
        
        .review-card {
            transition: all 0.3s ease;
        }
        
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star-rating');
            const ratingInput = document.getElementById('rating-value');
            let selectedRating = 0;
            
            stars.forEach((star, index) => {
                const ratingValue = index + 1;
                
                star.addEventListener('click', function() {
                    selectedRating = ratingValue;
                    ratingInput.value = selectedRating;
                    updateStars(selectedRating);
                });
                
                star.addEventListener('mouseover', function() {
                    updateStars(ratingValue);
                });
            });
            
            const starsContainer = document.querySelector('.stars-container');
            if (starsContainer) {
                starsContainer.addEventListener('mouseout', function() {
                    updateStars(selectedRating);
                });
            }
            
            function updateStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
            }
            
            // Auto-scroll to review form if review parameter is present
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('review') === '1') {
                const reviewForm = document.getElementById('review-form-section');
                if (reviewForm) {
                    setTimeout(function() {
                        reviewForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        reviewForm.classList.add('review-form-highlight');
                    }, 500);
                }
            }
        });
    </script>
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
                    <span class="title-text">Detalles del Producto</span>
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
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="cart.php" class="xp-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="8" cy="21" r="1"/>
                        <circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                    Carrito (<?php echo $cart_count; ?>)
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
                        <?php if ($total_reviews > 0): ?>
                        <div style="margin: 16px 0; padding: 16px; background: rgba(251, 191, 36, 0.05); border-radius: 12px; border: 1px solid rgba(251, 191, 36, 0.2);">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                <div style="color: #fbbf24; font-size: 1.5rem; font-weight: 700;">
                                    <?php echo $average_rating; ?>
                                </div>
                                <div style="color: #fbbf24; font-size: 1.3rem;">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= floor($average_rating)) {
                                            echo '★';
                                        } elseif ($i - $average_rating < 1 && $average_rating - floor($average_rating) >= 0.5) {
                                            echo '★';
                                        } else {
                                            echo '☆';
                                        }
                                    }
                                    ?>
                                </div>
                                <div style="color: var(--text-muted); font-size: 0.9rem;">
                                    (<?php echo $total_reviews; ?> <?php echo $total_reviews == 1 ? 'reseña' : 'reseñas'; ?>)
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <p style="color: var(--text-secondary); margin: 16px 0;"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        
                        <p style="color: var(--text-muted); margin-bottom: 24px;">
                            <strong>Tipo:</strong> <?php echo htmlspecialchars(ucfirst($product['type'])); ?>
                        </p>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="cart.php?action=add&id=<?php echo $product['id']; ?>" class="button success" style="font-size: 1rem; padding: 14px 28px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px; vertical-align: middle;">
                                    <circle cx="8" cy="21" r="1"/>
                                    <circle cx="19" cy="21" r="1"/>
                                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                                </svg>
                                Agregar al Carrito
                            </a>
                        <?php else: ?>
                            <div class="notification error" style="margin-top: 20px;">
                                <span class="icon">🔒</span>
                                Por favor <a href="index.php" style="color: var(--error); text-decoration: underline;">inicia sesión</a> para agregar este producto a tu carrito.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Reviews Section -->
                <div style="margin-top: 60px;">
                    <h3 style="font-size: 1.8rem; margin-bottom: 24px; background: linear-gradient(135deg, #fbbf24, #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        ⭐ Reseñas de Clientes
                    </h3>
                    
                    <!-- Rating Summary -->
                    <?php if ($total_reviews > 0): ?>
                    <div style="background: var(--surface); border: 2px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 30px;">
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: center;">
                            <div style="text-align: center;">
                                <div style="font-size: 3.5rem; font-weight: 700; color: #fbbf24; margin-bottom: 8px;">
                                    <?php echo $average_rating; ?>
                                </div>
                                <div style="color: #fbbf24; font-size: 1.5rem; margin-bottom: 8px;">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= round($average_rating) ? '★' : '☆';
                                    }
                                    ?>
                                </div>
                                <div style="color: var(--text-muted);">
                                    <?php echo $total_reviews; ?> <?php echo $total_reviews == 1 ? 'reseña' : 'reseñas'; ?>
                                </div>
                            </div>
                            <div>
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                <div class="rating-bar">
                                    <span style="color: #fbbf24; width: 80px;"><?php echo $i; ?> ★</span>
                                    <div class="rating-bar-bg" style="flex: 1;">
                                        <div class="rating-bar-fill" style="width: <?php echo $total_reviews > 0 ? ($rating_counts[$i] / $total_reviews * 100) : 0; ?>%;"></div>
                                    </div>
                                    <span style="color: var(--text-muted); width: 40px; text-align: right;"><?php echo $rating_counts[$i]; ?></span>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Add Review Form -->
                    <?php if ($can_review): ?>
                    <div id="review-form-section" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(147, 51, 234, 0.08)); border: 2px solid rgba(59, 130, 246, 0.3); border-radius: 16px; padding: 28px; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
                        <h4 style="margin-bottom: 16px; font-size: 1.3rem; color: var(--primary);">✍️ Deja tu Reseña</h4>
                        <?php if (isset($_GET['review']) && $_GET['review'] == '1'): ?>
                        <div style="background: rgba(251, 191, 36, 0.15); border-left: 4px solid #fbbf24; padding: 14px 18px; margin-bottom: 20px; border-radius: 8px;">
                            <p style="margin: 0; color: var(--text-primary); font-weight: 600;">
                                🎉 ¡Gracias por tu compra! Tu opinión es muy valiosa para nosotros y ayuda a otros compradores.
                            </p>
                        </div>
                        <?php endif; ?>
                        <form method="POST" action="add_review.php" onsubmit="return validateReview();">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <input type="hidden" name="rating" id="rating-value" value="0">
                            
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 12px; font-weight: 600; font-size: 1.05rem;">Calificación: <span style="color: var(--error);">*</span></label>
                                <div class="stars-container" style="display: flex; gap: 8px;">
                                    <span class="star-rating" data-rating="1">★</span>
                                    <span class="star-rating" data-rating="2">★</span>
                                    <span class="star-rating" data-rating="3">★</span>
                                    <span class="star-rating" data-rating="4">★</span>
                                    <span class="star-rating" data-rating="5">★</span>
                                </div>
                                <small style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px; display: block;">Haz clic en las estrellas para calificar</small>
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 1.05rem;">Comentario: <span style="color: var(--error);">*</span></label>
                                <textarea name="comment" id="review-comment" rows="5" required minlength="10" maxlength="1000" style="width: 100%; padding: 14px; border: 2px solid var(--border); border-radius: 10px; font-family: inherit; resize: vertical; font-size: 1rem;" placeholder="Cuéntanos tu experiencia con este producto... (ímimo 10 caracteres)"></textarea>
                                <small style="color: var(--text-muted); font-size: 0.85rem;"><span id="char-count">0</span>/1000 caracteres</small>
                            </div>
                            
                            <button type="submit" class="button success" style="font-size: 1.05rem; padding: 12px 32px;">
                                📤 Publicar Reseña
                            </button>
                        </form>
                    </div>
                    
                    <script>
                        // Character counter
                        const commentField = document.getElementById('review-comment');
                        const charCount = document.getElementById('char-count');
                        if (commentField && charCount) {
                            commentField.addEventListener('input', function() {
                                charCount.textContent = this.value.length;
                            });
                        }
                        
                        // Form validation
                        function validateReview() {
                            const rating = document.getElementById('rating-value').value;
                            const comment = document.getElementById('review-comment').value;
                            
                            if (rating == 0) {
                                alert('⚠️ Por favor selecciona una calificación de estrellas.');
                                return false;
                            }
                            
                            if (comment.trim().length < 10) {
                                alert('⚠️ El comentario debe tener al menos 10 caracteres.');
                                return false;
                            }
                            
                            return true;
                        }
                    </script>
                    
                    <?php elseif ($has_reviewed): ?>
                    <div class="notification" style="margin-bottom: 30px; background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3);">
                        <span class="icon">✓</span>
                        Ya has dejado una reseña para este producto. ¡Gracias por tu opinión!
                    </div>
                    <?php elseif (isset($_SESSION['user_id'])): ?>
                    <div class="notification" style="margin-bottom: 30px; background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3);">
                        <span class="icon">ℹ️</span>
                        Para dejar una reseña, primero debes comprar este producto y esperar a que tu orden sea aceptada o completada.
                    </div>
                    <?php else: ?>
                    <div class="notification" style="margin-bottom: 30px; background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3);">
                        <span class="icon">🔒</span>
                        Debes <a href="index.php" style="color: var(--error); text-decoration: underline;">iniciar sesión</a> y comprar este producto para dejar una reseña.
                    </div>
                    <?php endif; ?>
                    
                    <!-- Display Reviews -->
                    <?php if (empty($reviews)): ?>
                        <div style="text-align: center; padding: 60px 20px; background: var(--surface); border-radius: 12px; border: 2px dashed var(--border);">
                            <div style="font-size: 4rem; margin-bottom: 16px; opacity: 0.5;">💬</div>
                            <p style="color: var(--text-muted); font-size: 1.1rem; margin: 0;">
                                Aún no hay reseñas para este producto.<br>
                                <strong>¡Sé el primero en dejar una!</strong>
                            </p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <?php foreach ($reviews as $review): ?>
                            <div class="review-card" style="background: var(--surface); border: 2px solid var(--border); border-radius: 12px; padding: 24px;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 14px;">
                                    <div>
                                        <div style="font-weight: 600; font-size: 1.05rem; margin-bottom: 6px;">
                                            👤 <?php echo htmlspecialchars($review['username']); ?>
                                        </div>
                                        <div style="color: #fbbf24; font-size: 1.2rem;">
                                            <?php 
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $review['rating'] ? '★' : '☆';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 0.875rem;">
                                        📅 <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <p style="color: var(--text-secondary); line-height: 1.7; margin-bottom: 12px; font-size: 1rem;">
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                </p>
                                
                                <?php if (!empty($review['admin_response'])): ?>
                                <div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(147, 51, 234, 0.08)); border-left: 4px solid var(--primary); padding: 14px 18px; margin-top: 14px; border-radius: 8px;">
                                    <div style="font-weight: 600; color: var(--primary); margin-bottom: 8px; font-size: 0.95rem;">
                                        💬 Respuesta del Vendedor
                                    </div>
                                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0; line-height: 1.6;">
                                        <?php echo nl2br(htmlspecialchars($review['admin_response'])); ?>
                                    </p>
                                    <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 8px;">
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