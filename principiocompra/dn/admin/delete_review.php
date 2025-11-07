<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

if (isset($_GET['id'])) {
    $review_id = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        
        $_SESSION['success'] = "Reseña eliminada correctamente.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al eliminar la reseña.";
    }
}

redirect('manage_reviews.php');
?>
