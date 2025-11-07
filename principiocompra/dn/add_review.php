<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Debes iniciar sesión para dejar una reseña.";
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $user_id = $_SESSION['user_id'];
    $rating = (int)$_POST['rating'];
    $comment = sanitizeInput($_POST['comment']);
    
    // Validate product exists
    $stmt = $conn->prepare("SELECT id, name FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $_SESSION['error'] = "El producto no existe.";
        redirect('index.php');
    }
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = "La calificación debe estar entre 1 y 5 estrellas.";
        redirect("product.php?id=$product_id");
    }
    
    // Validate comment length
    if (strlen($comment) < 10) {
        $_SESSION['error'] = "El comentario debe tener al menos 10 caracteres.";
        redirect("product.php?id=$product_id");
    }
    
    if (strlen($comment) > 1000) {
        $_SESSION['error'] = "El comentario no puede exceder 1000 caracteres.";
        redirect("product.php?id=$product_id");
    }
    
    // Verify user has purchased this product with completed or accepted status
    $stmt = $conn->prepare("
        SELECT o.id, o.status FROM orders o
        WHERE o.user_id = ? AND o.product_id = ? AND o.status IN ('accepted', 'completed')
        LIMIT 1
    ");
    $stmt->execute([$user_id, $product_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error'] = "❌ Solo puedes dejar reseñas de productos que hayas comprado y cuya orden esté aceptada o completada. Verifica el estado de tu orden en 'My Orders'.";
        redirect("product.php?id=$product_id");
    }
    
    // Check if user already reviewed this product
    $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing_review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_review) {
        $_SESSION['error'] = "Ya has dejado una reseña para este producto. Solo se permite una reseña por producto.";
        redirect("product.php?id=$product_id");
    }
    
    // Insert review
    try {
        $stmt = $conn->prepare("
            INSERT INTO reviews (product_id, user_id, order_id, rating, comment) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$product_id, $user_id, $order['id'], $rating, $comment]);
        
        $_SESSION['success'] = "🎉 ¡Gracias por tu reseña! Tu opinión ayuda a otros compradores. La reseña se ha publicado correctamente.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al guardar la reseña. Por favor intenta de nuevo más tarde.";
    }
    
    redirect("product.php?id=$product_id");
}

redirect('index.php');
?>