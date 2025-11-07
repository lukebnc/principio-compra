<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Verificar si el usuario ya tiene un chat activo
    $stmt = $conn->prepare("SELECT id FROM chats WHERE user_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $chat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($chat) {
        // Ya tiene un chat activo
        echo json_encode([
            'success' => true,
            'chat_id' => $chat['id'],
            'message' => 'Chat existente'
        ]);
    } else {
        // Crear nuevo chat
        $stmt = $conn->prepare("INSERT INTO chats (user_id, status) VALUES (?, 'active')");
        $stmt->execute([$user_id]);
        $chat_id = $conn->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'chat_id' => $chat_id,
            'message' => 'Chat creado'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al iniciar chat: ' . $e->getMessage()]);
}
?>
