<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$user_id = $_SESSION['user_id'];
$chat_id = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El mensaje no puede estar vacío']);
    exit;
}

if (strlen($message) > 500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El mensaje es demasiado largo']);
    exit;
}

if ($chat_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Chat ID inválido']);
    exit;
}

try {
    // Verificar que el chat pertenece al usuario
    $stmt = $conn->prepare("SELECT id FROM chats WHERE id = ? AND user_id = ?");
    $stmt->execute([$chat_id, $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Chat no encontrado']);
        exit;
    }
    
    // Preparar mensaje truncado para last_message (máximo 100 caracteres)
    $last_message_preview = mb_substr($message, 0, 100);
    if (mb_strlen($message) > 100) {
        $last_message_preview .= '...';
    }
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Insertar mensaje
    $stmt = $conn->prepare("
        INSERT INTO chat_messages (chat_id, sender_type, sender_id, message, is_read, created_at) 
        VALUES (?, 'user', ?, ?, 0, NOW())
    ");
    $stmt->execute([$chat_id, $user_id, $message]);
    $message_id = $conn->lastInsertId();
    
    // Actualizar el chat con el último mensaje y incrementar contador de admin
    $stmt = $conn->prepare("
        UPDATE chats 
        SET last_message = ?, 
            last_message_at = NOW(), 
            admin_unread_count = admin_unread_count + 1,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$last_message_preview, $chat_id]);
    
    // Commit transacción
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Mensaje enviado',
        'message_id' => (int)$message_id,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    // Rollback en caso de error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    error_log('Error en chat_send_message: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
?>