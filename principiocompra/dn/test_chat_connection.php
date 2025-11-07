<?php
/**
 * Script de diagnóstico para verificar el sistema de chat
 * Accede a: /dn/test_chat_connection.php
 */
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Chat</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
        }
        .status.success { background: #d1fae5; color: #065f46; }
        .status.error { background: #fee2e2; color: #991b1b; }
        .status.warning { background: #fef3c7; color: #92400e; }
        h1 { color: #1f2937; }
        h2 { color: #374151; font-size: 18px; margin-top: 0; }
        pre {
            background: #f9fafb;
            padding: 12px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 13px;
        }
        .test-item {
            padding: 12px;
            border-left: 3px solid #e5e7eb;
            margin-bottom: 10px;
        }
        .test-item.success { border-color: #10b981; background: #f0fdf4; }
        .test-item.error { border-color: #ef4444; background: #fef2f2; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 10px;
        }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico del Sistema de Chat</h1>
    
    <div class="card">
        <h2>1️⃣ Estado de Sesión</h2>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="test-item success">
                ✅ <strong>Usuario logueado</strong><br>
                User ID: <?php echo $_SESSION['user_id']; ?><br>
                Username: <?php echo $_SESSION['username'] ?? 'N/A'; ?>
            </div>
        <?php else: ?>
            <div class="test-item error">
                ❌ <strong>Usuario NO logueado</strong><br>
                El chat requiere que el usuario esté logueado.
                <br><a href="index.php" class="btn">Ir a login</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>2️⃣ Conexión a Base de Datos</h2>
        <?php
        try {
            if (isset($conn) && $conn instanceof PDO) {
                echo '<div class="test-item success">✅ <strong>Conexión establecida</strong></div>';
            } else {
                echo '<div class="test-item error">❌ <strong>No se pudo conectar a la BD</strong></div>';
            }
        } catch (Exception $e) {
            echo '<div class="test-item error">❌ <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
    
    <div class="card">
        <h2>3️⃣ Tablas de Chat</h2>
        <?php
        try {
            // Verificar tabla chats
            $stmt = $conn->query("SHOW TABLES LIKE 'chats'");
            if ($stmt->rowCount() > 0) {
                echo '<div class="test-item success">✅ Tabla <code>chats</code> existe</div>';
                
                // Verificar estructura
                $stmt = $conn->query("DESCRIBE chats");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo '<details><summary>Ver estructura de chats</summary><pre>';
                foreach ($columns as $col) {
                    echo $col['Field'] . ' - ' . $col['Type'] . "\n";
                }
                echo '</pre></details>';
            } else {
                echo '<div class="test-item error">❌ Tabla <code>chats</code> NO existe</div>';
                echo '<p><strong>Solución:</strong> Importa el archivo <code>migration_add_chat_system.sql</code></p>';
            }
            
            // Verificar tabla chat_messages
            $stmt = $conn->query("SHOW TABLES LIKE 'chat_messages'");
            if ($stmt->rowCount() > 0) {
                echo '<div class="test-item success">✅ Tabla <code>chat_messages</code> existe</div>';
                
                // Verificar estructura
                $stmt = $conn->query("DESCRIBE chat_messages");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo '<details><summary>Ver estructura de chat_messages</summary><pre>';
                foreach ($columns as $col) {
                    echo $col['Field'] . ' - ' . $col['Type'] . "\n";
                }
                echo '</pre></details>';
            } else {
                echo '<div class="test-item error">❌ Tabla <code>chat_messages</code> NO existe</div>';
                echo '<p><strong>Solución:</strong> Importa el archivo <code>migration_add_chat_system.sql</code></p>';
            }
            
        } catch (Exception $e) {
            echo '<div class="test-item error">❌ <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
    
    <div class="card">
        <h2>4️⃣ Archivos de API</h2>
        <?php
        $api_files = [
            'api/chat_init.php',
            'api/chat_get_messages.php',
            'api/chat_send_message.php'
        ];
        
        foreach ($api_files as $file) {
            $path = __DIR__ . '/' . $file;
            if (file_exists($path)) {
                if (is_readable($path)) {
                    echo '<div class="test-item success">✅ <code>' . $file . '</code> existe y es legible</div>';
                } else {
                    echo '<div class="test-item error">❌ <code>' . $file . '</code> existe pero no es legible</div>';
                }
            } else {
                echo '<div class="test-item error">❌ <code>' . $file . '</code> NO existe</div>';
            }
        }
        ?>
    </div>
    
    <div class="card">
        <h2>5️⃣ Test de API chat_init.php</h2>
        <?php if (isset($_SESSION['user_id'])): ?>
            <button onclick="testChatInit()" class="btn">Probar API</button>
            <div id="api-result"></div>
            
            <script>
            function testChatInit() {
                const result = document.getElementById('api-result');
                result.innerHTML = '<p>Probando...</p>';
                
                fetch('api/chat_init.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                })
                .then(res => {
                    console.log('Response status:', res.status);
                    return res.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        result.innerHTML = `
                            <div class="test-item success">
                                ✅ <strong>API funciona correctamente</strong><br>
                                Chat ID: ${data.chat_id}<br>
                                Mensaje: ${data.message}
                            </div>
                        `;
                    } else {
                        result.innerHTML = `
                            <div class="test-item error">
                                ❌ <strong>API retornó error</strong><br>
                                Error: ${data.error || 'Desconocido'}
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    result.innerHTML = `
                        <div class="test-item error">
                            ❌ <strong>Error en la petición</strong><br>
                            ${err.message}
                        </div>
                    `;
                });
            }
            </script>
        <?php else: ?>
            <div class="test-item error">
                ⚠️ Debes estar logueado para probar la API
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>6️⃣ Datos de Chats Existentes</h2>
        <?php
        try {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM chats");
            $total = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<div class="test-item success">📊 Total de chats: ' . $total['total'] . '</div>';
            
            $stmt = $conn->query("SELECT COUNT(*) as total FROM chat_messages");
            $total = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<div class="test-item success">💬 Total de mensajes: ' . $total['total'] . '</div>';
            
        } catch (Exception $e) {
            echo '<div class="test-item error">❌ No se pudieron obtener estadísticas</div>';
        }
        ?>
    </div>
    
    <div class="card">
        <h2>📝 Conclusión</h2>
        <p>Si todos los checks están en ✅ verde, el sistema debería funcionar correctamente.</p>
        <p>Si hay ❌ rojos, corrige esos problemas primero.</p>
        <br>
        <a href="index.php" class="btn">Volver al inicio</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="#" onclick="window.open('', '_blank').location.reload(); return false;" class="btn">Probar Chat</a>
        <?php endif; ?>
    </div>
</body>
</html>
