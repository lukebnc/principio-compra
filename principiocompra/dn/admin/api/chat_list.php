<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Verificar que sea admin
if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    // Obtener todos los chats con información del usuario
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            u.username,
            u.email,
            (SELECT COUNT(*) FROM chat_messages WHERE chat_id = c.id) as message_count
        FROM chats c
        JOIN users u ON c.user_id = u.id
        WHERE c.status = 'active'
        ORDER BY c.updated_at DESC
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
        'total_unread' => (int)$unread['total']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al obtener chats: ' . $e->getMessage()]);
}
?>
