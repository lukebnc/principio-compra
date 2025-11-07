<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Verificar que sea admin
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    // Obtener todos los chats con información del usuario
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.user_id,
            c.status,
            c.admin_unread_count,
            c.user_unread_count,
            c.last_message,
            c.last_message_at,
            c.created_at,
            c.updated_at,
            u.username,
            u.email,
            (SELECT COUNT(*) FROM chat_messages WHERE chat_id = c.id) as message_count
        FROM chats c
        JOIN users u ON c.user_id = u.id
        WHERE c.status = 'active'
        ORDER BY 
            c.admin_unread_count DESC,
            c.updated_at DESC
    ");
    $stmt->execute();
    $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener total de mensajes no leídos
    $stmt = $conn->prepare("SELECT SUM(admin_unread_count) as total FROM chats WHERE status = 'active'");
    $stmt->execute();
    $unread = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'chats' => $chats,
        'total_chats' => count($chats),
        'total_unread' => (int)($unread['total'] ?? 0)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Error en chat_list: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
?>