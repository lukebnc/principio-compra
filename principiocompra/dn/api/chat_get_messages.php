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
$chat_id = isset($_GET['chat_id']) ? (int)$_GET['chat_id'] : 0;
$last_message_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if ($chat_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Chat ID inválido']);
    exit;
}

try {
    // Verificar que el chat pertenece al usuario
    $stmt = $conn->prepare("SELECT id FROM chats WHERE id = ? AND user_id = ?");
    $stmt->execute([$chat_id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Chat no encontrado']);
        exit;
    }
    
    // Obtener mensajes
    if ($last_message_id > 0) {
        // Solo mensajes nuevos
        $stmt = $conn->prepare("
            SELECT cm.*, u.username 
            FROM chat_messages cm
            LEFT JOIN users u ON cm.sender_id = u.id
            WHERE cm.chat_id = ? AND cm.id > ?
            ORDER BY cm.created_at ASC
        ");
        $stmt->execute([$chat_id, $last_message_id]);
    } else {
        // Todos los mensajes
        $stmt = $conn->prepare("
            SELECT cm.*, u.username 
            FROM chat_messages cm
            LEFT JOIN users u ON cm.sender_id = u.id
            WHERE cm.chat_id = ?
            ORDER BY cm.created_at ASC
        ");
        $stmt->execute([$chat_id]);
    }
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Marcar mensajes del admin como leídos por el usuario
    $stmt = $conn->prepare("
        UPDATE chat_messages 
        SET is_read = 1 
        WHERE chat_id = ? AND sender_type = 'admin' AND is_read = 0
    ");
    $stmt->execute([$chat_id]);
    
    // Resetear contador de mensajes no leídos del usuario
    $stmt = $conn->prepare("UPDATE chats SET user_unread_count = 0 WHERE id = ?");
    $stmt->execute([$chat_id]);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'count' => count($messages)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al obtener mensajes: ' . $e->getMessage()]);
}
?>
