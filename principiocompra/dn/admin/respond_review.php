<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = (int)$_POST['review_id'];
    $admin_response = sanitizeInput($_POST['admin_response']);
    
    try {
        $stmt = $conn->prepare("
            UPDATE reviews 
            SET admin_response = ?, admin_response_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$admin_response, $review_id]);
        
        $_SESSION['success'] = "Respuesta guardada correctamente.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al guardar la respuesta.";
    }
}

redirect('manage_reviews.php');
?>
