#!/usr/bin/env php
<?php
/**
 * Script de Verificación del Sistema de Chat
 * 
 * Este script verifica que:
 * 1. Las tablas de BD existen
 * 2. Los archivos de API están presentes
 * 3. Los archivos tienen permisos correctos
 * 4. Las dependencias están disponibles
 */

echo "🔍 VERIFICACIÓN DEL SISTEMA DE CHAT DE SOPORTE\n";
echo "=============================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. Verificar conexión a BD
echo "1️⃣  Verificando conexión a base de datos...\n";
try {
    require_once __DIR__ . '/dn/includes/db.php';
    if (isset($conn) && $conn instanceof PDO) {
        $success[] = "✅ Conexión a BD establecida";
        echo "   ✅ Conexión a BD establecida\n";
    } else {
        $errors[] = "❌ No se pudo establecer conexión a BD";
        echo "   ❌ No se pudo establecer conexión a BD\n";
    }
} catch (Exception $e) {
    $errors[] = "❌ Error de BD: " . $e->getMessage();
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Verificar tablas
echo "2️⃣  Verificando tablas de base de datos...\n";
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'chats'");
    if ($stmt->rowCount() > 0) {
        $success[] = "✅ Tabla 'chats' existe";
        echo "   ✅ Tabla 'chats' existe\n";
    } else {
        $errors[] = "❌ Tabla 'chats' NO existe";
        echo "   ❌ Tabla 'chats' NO existe\n";
    }
    
    $stmt = $conn->query("SHOW TABLES LIKE 'chat_messages'");
    if ($stmt->rowCount() > 0) {
        $success[] = "✅ Tabla 'chat_messages' existe";
        echo "   ✅ Tabla 'chat_messages' existe\n";
    } else {
        $errors[] = "❌ Tabla 'chat_messages' NO existe";
        echo "   ❌ Tabla 'chat_messages' NO existe\n";
    }
    
    // Verificar columnas de la tabla chats
    $stmt = $conn->query("DESCRIBE chats");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $required_columns = ['id', 'user_id', 'status', 'admin_unread_count', 'user_unread_count', 'last_message', 'last_message_at'];
    
    $missing = array_diff($required_columns, $columns);
    if (empty($missing)) {
        $success[] = "✅ Todas las columnas de 'chats' están presentes";
        echo "   ✅ Todas las columnas de 'chats' están presentes\n";
    } else {
        $errors[] = "❌ Faltan columnas en 'chats': " . implode(', ', $missing);
        echo "   ❌ Faltan columnas: " . implode(', ', $missing) . "\n";
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Error verificando tablas: " . $e->getMessage();
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Verificar archivos de API de usuario
echo "3️⃣  Verificando archivos de API de usuario...\n";
$user_api_files = [
    'chat_init.php',
    'chat_get_messages.php',
    'chat_send_message.php'
];

foreach ($user_api_files as $file) {
    $path = __DIR__ . '/dn/api/' . $file;
    if (file_exists($path)) {
        if (is_readable($path)) {
            $success[] = "✅ $file existe y es legible";
            echo "   ✅ $file\n";
        } else {
            $warnings[] = "⚠️  $file existe pero no es legible";
            echo "   ⚠️  $file (no legible)\n";
        }
    } else {
        $errors[] = "❌ $file NO existe";
        echo "   ❌ $file NO existe\n";
    }
}

echo "\n";

// 4. Verificar archivos de API de admin
echo "4️⃣  Verificando archivos de API de admin...\n";
$admin_api_files = [
    'chat_list.php',
    'chat_get_messages.php',
    'chat_send_message.php'
];

foreach ($admin_api_files as $file) {
    $path = __DIR__ . '/dn/admin/api/' . $file;
    if (file_exists($path)) {
        if (is_readable($path)) {
            $success[] = "✅ admin/$file existe y es legible";
            echo "   ✅ $file\n";
        } else {
            $warnings[] = "⚠️  admin/$file existe pero no es legible";
            echo "   ⚠️  $file (no legible)\n";
        }
    } else {
        $errors[] = "❌ admin/$file NO existe";
        echo "   ❌ $file NO existe\n";
    }
}

echo "\n";

// 5. Verificar widget y panel
echo "5️⃣  Verificando componentes frontend...\n";

$widget_path = __DIR__ . '/dn/includes/chat_widget.php';
if (file_exists($widget_path)) {
    $success[] = "✅ Widget de chat existe";
    echo "   ✅ Widget de chat (chat_widget.php)\n";
} else {
    $errors[] = "❌ Widget de chat NO existe";
    echo "   ❌ Widget de chat NO existe\n";
}

$admin_panel_path = __DIR__ . '/dn/admin/manage_chats.php';
if (file_exists($admin_panel_path)) {
    $success[] = "✅ Panel de admin existe";
    echo "   ✅ Panel de admin (manage_chats.php)\n";
} else {
    $errors[] = "❌ Panel de admin NO existe";
    echo "   ❌ Panel de admin NO existe\n";
}

echo "\n";

// 6. Verificar índices de BD
echo "6️⃣  Verificando índices de base de datos...\n";
try {
    $stmt = $conn->query("SHOW INDEX FROM chats");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $index_count = count($indexes);
    
    if ($index_count >= 3) {
        $success[] = "✅ Índices de 'chats' presentes ($index_count)";
        echo "   ✅ Índices de 'chats' presentes ($index_count)\n";
    } else {
        $warnings[] = "⚠️  Pocos índices en 'chats' ($index_count)";
        echo "   ⚠️  Pocos índices en 'chats' ($index_count)\n";
    }
    
    $stmt = $conn->query("SHOW INDEX FROM chat_messages");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $index_count = count($indexes);
    
    if ($index_count >= 2) {
        $success[] = "✅ Índices de 'chat_messages' presentes ($index_count)";
        echo "   ✅ Índices de 'chat_messages' presentes ($index_count)\n";
    } else {
        $warnings[] = "⚠️  Pocos índices en 'chat_messages' ($index_count)";
        echo "   ⚠️  Pocos índices en 'chat_messages' ($index_count)\n";
    }
} catch (Exception $e) {
    $warnings[] = "⚠️  No se pudieron verificar índices: " . $e->getMessage();
    echo "   ⚠️  No se pudieron verificar índices\n";
}

echo "\n";

// Resumen
echo "\n";
echo "═══════════════════════════════════════════════\n";
echo "📊 RESUMEN DE VERIFICACIÓN\n";
echo "═══════════════════════════════════════════════\n\n";

echo "✅ Exitoso: " . count($success) . "\n";
echo "⚠️  Advertencias: " . count($warnings) . "\n";
echo "❌ Errores: " . count($errors) . "\n\n";

if (!empty($errors)) {
    echo "🔴 ERRORES ENCONTRADOS:\n";
    foreach ($errors as $error) {
        echo "   $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "🟡 ADVERTENCIAS:\n";
    foreach ($warnings as $warning) {
        echo "   $warning\n";
    }
    echo "\n";
}

if (empty($errors)) {
    echo "✅ ¡SISTEMA DE CHAT LISTO PARA PRODUCCIÓN!\n";
    echo "\n";
    echo "📝 Próximos pasos:\n";
    echo "   1. Probar el widget de usuario desde el frontend\n";
    echo "   2. Probar el panel de admin desde /admin/manage_chats.php\n";
    echo "   3. Enviar mensajes de prueba\n";
    echo "   4. Verificar notificaciones en tiempo real\n";
    echo "\n";
} else {
    echo "❌ HAY ERRORES QUE DEBEN CORREGIRSE ANTES DE USAR EL SISTEMA\n";
    echo "\n";
    echo "📝 Acciones requeridas:\n";
    echo "   1. Ejecutar migration_add_chat_system.sql en la BD\n";
    echo "   2. Verificar permisos de archivos\n";
    echo "   3. Verificar configuración de BD en includes/db.php\n";
    echo "\n";
    exit(1);
}

echo "═══════════════════════════════════════════════\n";
?>
