<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    
    // Validate status
    $valid_statuses = ['pending', 'accepted', 'completed', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['error'] = "Estado inválido.";
        redirect('admin_orders.php');
    }
    
    try {
        // Get the old status first
        $stmt = $conn->prepare("SELECT status, user_id, product_id FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        $old_status = $order['status'];
        
        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        $status_labels = [
            'pending' => 'Pendiente',
            'accepted' => 'Aceptada',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada'
        ];
        
        $_SESSION['success'] = "✓ Estado de la orden #$order_id actualizado a: " . $status_labels[$new_status];
        
        // If status changed to completed or accepted, notify user they can leave a review
        if (($new_status === 'completed' || $new_status === 'accepted') && ($old_status !== 'completed' && $old_status !== 'accepted')) {
            $_SESSION['success'] .= " | El usuario ahora puede dejar una reseña del producto.";
        }
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al actualizar el estado de la orden.";
    }
}

redirect('admin_orders.php');
?>