<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    // Validate status
    $valid_statuses = ['pending', 'accepted', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        $_SESSION['error'] = "Estado inválido.";
        redirect('admin_orders.php');
    }
    
    try {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        
        $status_labels = [
            'pending' => 'Pendiente',
            'accepted' => 'Aceptada',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada'
        ];
        
        $_SESSION['success'] = "Estado de la orden actualizado a: " . $status_labels[$status];
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al actualizar el estado de la orden.";
    }
}

redirect('admin_orders.php');
?>
