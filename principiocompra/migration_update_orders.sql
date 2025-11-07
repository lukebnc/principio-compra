-- ========================================
-- MIGRACIÓN: Actualizar órdenes existentes
-- ========================================
-- Usar este script si ya tienes datos y quieres mantenerlos
-- Este script corrige órdenes con product_id = 0
--
-- IMPORTANTE: Ejecutar con precaución en base de datos de producción
-- ========================================

USE ecommerce_db;

-- Paso 1: Verificar estructura de tabla orders
SELECT 'Verificando estructura de tabla orders...' as mensaje;
DESCRIBE orders;

-- Paso 2: Ver órdenes problemáticas (con product_id = 0)
SELECT 'Órdenes con product_id = 0:' as mensaje;
SELECT id, user_id, product_id, quantity, total_price, status, created_at 
FROM orders 
WHERE product_id = 0;

-- Paso 3: ADVERTENCIA antes de continuar
SELECT '
========================================
ADVERTENCIA: Las órdenes con product_id = 0
no se pueden corregir automáticamente.

Soluciones:
1. Eliminar órdenes con product_id = 0
2. Asignar manualmente un product_id válido

Para eliminar, descomenta la siguiente línea:
========================================
' as mensaje;

-- DESCOMENTA ESTA LÍNEA PARA ELIMINAR ÓRDENES PROBLEMÁTICAS:
-- DELETE FROM orders WHERE product_id = 0;

-- Paso 4: Asegurar que la estructura es correcta
ALTER TABLE `orders` 
  MODIFY COLUMN `product_id` int(11) NOT NULL,
  MODIFY COLUMN `quantity` int(11) NOT NULL DEFAULT 1,
  MODIFY COLUMN `payment_method` varchar(50) NOT NULL DEFAULT 'xmr',
  MODIFY COLUMN `status` enum('pending','accepted','completed','cancelled') DEFAULT 'pending';

SELECT '✓ Estructura de tabla orders actualizada' as mensaje;

-- Paso 5: Agregar índices si no existen
ALTER TABLE `orders` 
  ADD INDEX IF NOT EXISTS `idx_user_id` (`user_id`),
  ADD INDEX IF NOT EXISTS `idx_product_id` (`product_id`),
  ADD INDEX IF NOT EXISTS `idx_status` (`status`);

SELECT '✓ Índices agregados para mejor rendimiento' as mensaje;

-- Paso 6: Verificar que la tabla reviews existe
SELECT 'Verificando tabla reviews...' as mensaje;
SHOW TABLES LIKE 'reviews';

-- Si la tabla reviews no existe, crearla
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_response` text DEFAULT NULL,
  `admin_response_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT '✓ Tabla reviews verificada/creada' as mensaje;

-- Paso 7: Resumen final
SELECT '
========================================
MIGRACIÓN COMPLETADA
========================================

Resumen:
- Estructura de orders actualizada
- Índices optimizados
- Tabla reviews verificada

Próximos pasos:
1. Verificar que no hay órdenes con product_id = 0
2. Las nuevas compras ahora guardarán product_id correcto
3. Los usuarios podrán dejar reseñas

Si tienes órdenes con product_id = 0:
- Elimínalas o asígnales un product_id válido
========================================
' as mensaje;

-- Ver estadísticas finales
SELECT 
  COUNT(*) as total_orders,
  SUM(CASE WHEN product_id = 0 THEN 1 ELSE 0 END) as problematic_orders,
  SUM(CASE WHEN product_id > 0 THEN 1 ELSE 0 END) as correct_orders
FROM orders;