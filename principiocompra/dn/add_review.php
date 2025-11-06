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
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = "La calificación debe estar entre 1 y 5.";
        redirect("product.php?id=$product_id");
    }
    
    // Verify user has purchased this product
    $stmt = $conn->prepare("
        SELECT id FROM orders 
        WHERE user_id = ? AND product_id = ? AND status IN ('accepted', 'completed')
    ");
    $stmt->execute([$user_id, $product_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error'] = "Solo puedes dejar reseñas de productos que hayas comprado.";
        redirect("product.php?id=$product_id");
    }
    
    // Check if user already reviewed
    $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing_review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_review) {
        $_SESSION['error'] = "Ya has dejado una reseña para este producto.";
        redirect("product.php?id=$product_id");
    }
    
    // Insert review
    try {
        $stmt = $conn->prepare("
            INSERT INTO reviews (product_id, user_id, order_id, rating, comment) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$product_id, $user_id, $order['id'], $rating, $comment]);
        
        $_SESSION['success'] = "¡Gracias por tu reseña!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al guardar la reseña. Por favor intenta de nuevo.";
    }
    
    redirect("product.php?id=$product_id");
}

redirect('index.php');
?>
