<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Handle POST request (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = (int)$_POST['review_id'];
    $admin_response = sanitizeInput($_POST['admin_response']);
    
    // Validate response is not empty
    if (empty(trim($admin_response))) {
        $_SESSION['error'] = "La respuesta no puede estar vacía.";
        redirect("respond_review.php?id=$review_id");
    }
    
    try {
        $stmt = $conn->prepare("
            UPDATE reviews 
            SET admin_response = ?, admin_response_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$admin_response, $review_id]);
        
        $_SESSION['success'] = "Respuesta guardada correctamente.";
        redirect('manage_reviews.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al guardar la respuesta.";
        redirect('manage_reviews.php');
    }
    exit;
}

// Handle GET request (show form)
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID de reseña no especificado.";
    redirect('manage_reviews.php');
}

$review_id = (int)$_GET['id'];

// Fetch review details
$stmt = $conn->prepare("
    SELECT 
        r.*, 
        u.username, 
        p.name as product_name,
        p.image as product_image
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    WHERE r.id = ?
");
$stmt->execute([$review_id]);
$review = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$review) {
    $_SESSION['error'] = "Reseña no encontrada.";
    redirect('manage_reviews.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responder Reseña - Market-X Admin</title>
    <link rel="stylesheet" href="modern-admin-styles.css">
    <style>
        .review-display {
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        
        .form-group textarea {
            width: 100%;
            min-height: 150px;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            background: var(--bg-secondary);
            color: var(--text-primary);
            transition: border-color 0.3s ease;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        
        .char-counter {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="window">
        <div class="title-bar">
            <span>💬 Responder Reseña - Market-X</span>
            <div class="buttons">
                <a href="manage_reviews.php" class="button">← Volver</a>
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
            <h2 style="margin-bottom: 24px; font-size: 1.75rem;">
                <?php echo !empty($review['admin_response']) ? '✏️ Editar Respuesta' : '💬 Responder Reseña'; ?>
            </h2>
            
            <!-- Notifications -->
            <?php if (isset($_SESSION['error'])): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 2px solid rgba(239, 68, 68, 0.3); color: var(--error); padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                    <span style="font-weight: 600;">✕</span> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Review Display -->
            <div class="review-display">
                <div style="display: grid; grid-template-columns: auto 1fr; gap: 20px; align-items: start;">
                    <!-- Product Image -->
                    <div>
                        <img src="../<?php echo htmlspecialchars($review['product_image']); ?>" alt="Product" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                    </div>
                    
                    <!-- Review Content -->
                    <div>
                        <div style="margin-bottom: 12px;">
                            <strong style="font-size: 1.2rem; color: var(--primary);"><?php echo htmlspecialchars($review['product_name']); ?></strong>
                        </div>
                        
                        <div style="margin-bottom: 8px;">
                            <span style="font-weight: 600;">👤 <?php echo htmlspecialchars($review['username']); ?></span>
                            <span style="color: var(--text-muted); margin-left: 12px; font-size: 0.9rem;">📅 <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></span>
                        </div>
                        
                        <div style="color: #fbbf24; font-size: 1.3rem; margin-bottom: 12px;">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $review['rating'] ? '★' : '☆';
                            }
                            ?>
                            <span style="color: var(--text-muted); font-size: 0.9rem; margin-left: 8px;">(<?php echo $review['rating']; ?>/5)</span>
                        </div>
                        
                        <div style="background: rgba(209, 213, 219, 0.1); border-left: 4px solid var(--primary); padding: 12px 16px; border-radius: 8px;">
                            <div style="font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Comentario del Cliente:</div>
                            <p style="color: var(--text-secondary); line-height: 1.6; margin: 0;">
                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Response Form -->
            <div style="background: var(--surface); border: 2px solid var(--border); border-radius: 12px; padding: 24px;">
                <form method="POST" action="respond_review.php" id="responseForm">
                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                    
                    <div class="form-group">
                        <label for="admin_response">Tu Respuesta Pública:</label>
                        <textarea 
                            name="admin_response" 
                            id="admin_response" 
                            placeholder="Escribe tu respuesta aquí... Esta será visible para todos los usuarios."
                            required
                            maxlength="1000"
                        ><?php echo !empty($review['admin_response']) ? htmlspecialchars($review['admin_response']) : ''; ?></textarea>
                        <div class="char-counter">
                            <span id="charCount">0</span>/1000 caracteres
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="button" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; font-weight: 600;">
                            💾 Guardar Respuesta
                        </button>
                        <a href="manage_reviews.php" class="button" style="background: rgba(107, 114, 128, 0.2);">
                            ❌ Cancelar
                        </a>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($review['admin_response'])): ?>
                <div style="margin-top: 20px; padding: 12px; background: rgba(59, 130, 246, 0.1); border-radius: 8px;">
                    <small style="color: var(--text-muted);">
                        ℹ️ Respuesta actual publicada el: <?php echo date('d/m/Y H:i', strtotime($review['admin_response_at'])); ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
        
        <footer class="admin-footer">
            <p>© 2025 Market-X Admin Panel</p>
        </footer>
    </div>
    
    <script>
        // Character counter
        const textarea = document.getElementById('admin_response');
        const charCount = document.getElementById('charCount');
        
        function updateCharCount() {
            charCount.textContent = textarea.value.length;
        }
        
        textarea.addEventListener('input', updateCharCount);
        
        // Initialize counter on page load
        updateCharCount();
        
        // Form validation
        document.getElementById('responseForm').addEventListener('submit', function(e) {
            const response = textarea.value.trim();
            
            if (response.length === 0) {
                e.preventDefault();
                alert('Por favor escribe una respuesta antes de guardar.');
                textarea.focus();
                return false;
            }
            
            if (response.length < 10) {
                e.preventDefault();
                alert('La respuesta debe tener al menos 10 caracteres.');
                textarea.focus();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
