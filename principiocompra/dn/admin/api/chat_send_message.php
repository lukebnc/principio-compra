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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$admin_id = $_SESSION['admin_id'] ?? 0;
$chat_id = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'El mensaje no puede estar vacío']);
    exit;
}

if ($chat_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Chat ID inválido']);
    exit;
}

try {
    // Verificar que el chat existe
    $stmt = $conn->prepare("SELECT id FROM chats WHERE id = ?");
    $stmt->execute([$chat_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Chat no encontrado']);
        exit;
    }
    
    // Insertar mensaje
    $stmt = $conn->prepare("
        INSERT INTO chat_messages (chat_id, sender_type, sender_id, message, is_read) 
        VALUES (?, 'admin', ?, ?, 0)
    ");
    $stmt->execute([$chat_id, $admin_id, $message]);
    
    // Actualizar el chat con el último mensaje y incrementar contador de usuario
    $stmt = $conn->prepare("
        UPDATE chats 
        SET last_message = ?, 
            last_message_at = NOW(), 
            user_unread_count = user_unread_count + 1,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$message, $chat_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mensaje enviado',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al enviar mensaje: ' . $e->getMessage()]);
}
?>
