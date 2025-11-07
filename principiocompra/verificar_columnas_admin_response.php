<?php
/**
 * Script de verificación de columnas admin_response en la tabla reviews
 * Ejecutar este archivo desde el navegador para verificar si las columnas existen
 */

require_once 'dn/includes/db.php';

echo "<h1>Verificación de Base de Datos - Sistema de Respuestas Admin</h1>";
echo "<hr>";

// Verificar si la tabla reviews existe
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'reviews'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        echo "<p style='color: red; font-weight: bold;'>❌ ERROR: La tabla 'reviews' no existe.</p>";
        echo "<p>Necesitas importar el archivo: <code>migration_add_reviews.sql</code></p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ La tabla 'reviews' existe.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error al verificar tabla: " . $e->getMessage() . "</p>";
    exit;
}

// Verificar columnas de la tabla reviews
try {
    $stmt = $conn->query("DESCRIBE reviews");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Columnas de la tabla 'reviews':</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Columna</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
    
    $admin_response_exists = false;
    $admin_response_at_exists = false;
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>" . $column['Field'] . "</strong></td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'admin_response') {
            $admin_response_exists = true;
        }
        if ($column['Field'] === 'admin_response_at') {
            $admin_response_at_exists = true;
        }
    }
    
    echo "</table>";
    
    echo "<h2>Resultado de la verificación:</h2>";
    
    if ($admin_response_exists && $admin_response_at_exists) {
        echo "<div style='background: #d4edda; border: 2px solid #28a745; padding: 20px; border-radius: 8px;'>";
        echo "<h3 style='color: #28a745; margin: 0;'>✅ ¡TODO CORRECTO!</h3>";
        echo "<p style='margin: 10px 0 0 0;'>Las columnas necesarias existen:</p>";
        echo "<ul>";
        echo "<li>✅ <code>admin_response</code> - Para guardar la respuesta del admin</li>";
        echo "<li>✅ <code>admin_response_at</code> - Para guardar la fecha de la respuesta</li>";
        echo "</ul>";
        echo "<p style='font-weight: bold; margin-top: 20px;'>🎉 NO necesitas actualizar la base de datos. El sistema está listo para funcionar.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 2px solid #dc3545; padding: 20px; border-radius: 8px;'>";
        echo "<h3 style='color: #dc3545; margin: 0;'>❌ FALTAN COLUMNAS</h3>";
        echo "<p>Las siguientes columnas NO existen:</p>";
        echo "<ul>";
        if (!$admin_response_exists) {
            echo "<li>❌ <code>admin_response</code></li>";
        }
        if (!$admin_response_at_exists) {
            echo "<li>❌ <code>admin_response_at</code></li>";
        }
        echo "</ul>";
        echo "<p style='font-weight: bold; margin-top: 20px;'>⚠️ Necesitas ejecutar el siguiente script SQL:</p>";
        echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
        echo "ALTER TABLE `reviews` \n";
        echo "ADD COLUMN `admin_response` TEXT DEFAULT NULL,\n";
        echo "ADD COLUMN `admin_response_at` TIMESTAMP NULL DEFAULT NULL;";
        echo "</pre>";
        echo "</div>";
    }
    
    // Contar reseñas
    $stmt = $conn->query("SELECT COUNT(*) as total FROM reviews");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_reviews = $result['total'];
    
    echo "<h2>Estadísticas:</h2>";
    echo "<p>Total de reseñas en la base de datos: <strong>" . $total_reviews . "</strong></p>";
    
    if ($total_reviews > 0) {
        $stmt = $conn->query("SELECT COUNT(*) as con_respuesta FROM reviews WHERE admin_response IS NOT NULL");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $con_respuesta = $result['con_respuesta'];
        
        echo "<p>Reseñas con respuesta del admin: <strong>" . $con_respuesta . "</strong></p>";
        echo "<p>Reseñas sin respuesta: <strong>" . ($total_reviews - $con_respuesta) . "</strong></p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error al verificar columnas: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='dn/admin/manage_reviews.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Ir al Panel de Reseñas</a></p>";
?>
