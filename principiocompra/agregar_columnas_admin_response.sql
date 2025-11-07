-- Script para agregar columnas de respuestas del admin
-- Solo ejecutar si las columnas NO existen (verificar primero con verificar_columnas_admin_response.php)

-- Verificar si la tabla reviews existe
SELECT 'Verificando tabla reviews...' as paso;

-- Agregar columnas si no existen
-- Nota: Este script dará error si las columnas ya existen, lo cual es normal

ALTER TABLE `reviews` 
ADD COLUMN `admin_response` TEXT DEFAULT NULL COMMENT 'Respuesta del administrador a la reseña',
ADD COLUMN `admin_response_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha y hora de la respuesta del admin';

-- Verificar que las columnas se agregaron correctamente
SELECT 'Columnas agregadas exitosamente!' as resultado;

-- Mostrar estructura actualizada
DESCRIBE reviews;
