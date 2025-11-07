# 📊 Instrucciones de Importación SQL - Sistema de Reseñas Mejorado

## 🎯 Opciones de Importación

Tienes **3 opciones** dependiendo de tu situación:

---

## ✨ OPCIÓN 1: Instalación Limpia (RECOMENDADO para nuevos proyectos)

### Cuándo usar:
- Estás empezando un proyecto nuevo
- No tienes datos importantes que conservar
- Quieres empezar desde cero con la estructura correcta

### Pasos:

```sql
-- Paso 1: Eliminar base de datos existente (¡CUIDADO! Perderás todos los datos)
DROP DATABASE IF EXISTS ecommerce_db;

-- Paso 2: Importar el archivo actualizado
-- Ejecuta en phpMyAdmin o desde terminal:
source /ruta/a/ecommerce_db_updated.sql;

-- O en phpMyAdmin:
-- 1. Crear nueva base de datos 'ecommerce_db'
-- 2. Seleccionarla
-- 3. Ir a pestaña "Importar"
-- 4. Seleccionar archivo: ecommerce_db_updated.sql
-- 5. Click en "Continuar"
```

### Resultado:
- ✅ Base de datos limpia con estructura correcta
- ✅ Tabla `orders` con `product_id` y `quantity` correctos
- ✅ Tabla `reviews` completa
- ✅ Datos de ejemplo funcionales
- ✅ Usuario demo incluido

---

## 🔄 OPCIÓN 2: Migración (RECOMENDADO para proyectos existentes)

### Cuándo usar:
- Ya tienes datos importantes (usuarios, productos, órdenes)
- Quieres conservar tus datos existentes
- Solo necesitas actualizar la estructura

### Pasos:

```sql
-- Paso 1: Hacer backup de tu base de datos actual
mysqldump -u root -p ecommerce_db > backup_antes_migracion.sql

-- Paso 2: Ejecutar el script de migración
source /ruta/a/migration_update_orders.sql;

-- Paso 3: Revisar las órdenes problemáticas
SELECT * FROM orders WHERE product_id = 0;

-- Paso 4: Decidir qué hacer con órdenes problemáticas:

-- Opción A: Eliminar órdenes con product_id = 0
DELETE FROM orders WHERE product_id = 0;

-- Opción B: Asignar un product_id válido manualmente
-- (reemplaza 1 con el ID del producto correcto)
UPDATE orders SET product_id = 1 WHERE id = X AND product_id = 0;
```

### Resultado:
- ✅ Conservas todos tus datos existentes
- ✅ Estructura actualizada
- ✅ Tabla `reviews` creada si no existía
- ✅ Índices optimizados
- ⚠️ Órdenes con product_id = 0 identificadas para corrección manual

---

## 🔧 OPCIÓN 3: Solo agregar tabla Reviews (Si ya está todo bien)

### Cuándo usar:
- Tu tabla `orders` ya tiene `product_id` correcto
- Solo necesitas agregar la funcionalidad de reseñas
- No tienes problemas con la estructura actual

### Pasos:

```sql
-- Solo ejecutar el script original de reviews
USE ecommerce_db;
source /ruta/a/migration_add_reviews.sql;
```

### Resultado:
- ✅ Tabla `reviews` creada
- ✅ Estados de órdenes actualizados
- ✅ Todo lo demás sin cambios

---

## 🚨 Verificación Post-Importación

Después de importar, verifica que todo está correcto:

```sql
-- 1. Verificar estructura de orders
DESCRIBE orders;
-- Debe mostrar: product_id (int NOT NULL), quantity (int NOT NULL)

-- 2. Verificar que no hay órdenes con product_id = 0
SELECT COUNT(*) as ordenes_problematicas FROM orders WHERE product_id = 0;
-- Debe mostrar: 0

-- 3. Verificar que tabla reviews existe
SHOW TABLES LIKE 'reviews';
-- Debe mostrar: reviews

-- 4. Verificar estructura de reviews
DESCRIBE reviews;
-- Debe tener: id, product_id, user_id, order_id, rating, comment, etc.

-- 5. Verificar Foreign Keys
SHOW CREATE TABLE reviews;
-- Debe mostrar las 3 foreign keys (product_id, user_id, order_id)
```

---

## 📋 Checklist de Importación

### Antes de importar:
- [ ] Hacer backup de la base de datos actual
- [ ] Decidir qué opción usar (1, 2 o 3)
- [ ] Tener acceso a phpMyAdmin o terminal MySQL
- [ ] Verificar credenciales de base de datos en `/dn/includes/db.php`

### Durante la importación:
- [ ] Ejecutar el archivo SQL correspondiente
- [ ] Verificar que no hay errores en la consola/log
- [ ] Revisar que todas las tablas se crearon

### Después de importar:
- [ ] Verificar estructura de `orders` (product_id NOT NULL)
- [ ] Verificar que tabla `reviews` existe
- [ ] Comprobar que no hay órdenes con product_id = 0
- [ ] Verificar Foreign Keys en reviews
- [ ] Probar crear una orden de prueba
- [ ] Probar dejar una reseña de prueba

---

## 🛠️ Comandos Útiles

### Desde Terminal:

```bash
# Importar archivo SQL
mysql -u root -p ecommerce_db < ecommerce_db_updated.sql

# Hacer backup
mysqldump -u root -p ecommerce_db > backup.sql

# Conectar a MySQL
mysql -u root -p
```

### Desde MySQL:

```sql
-- Ver todas las bases de datos
SHOW DATABASES;

-- Usar base de datos
USE ecommerce_db;

-- Ver todas las tablas
SHOW TABLES;

-- Ver estructura de una tabla
DESCRIBE nombre_tabla;

-- Ver registros de una tabla
SELECT * FROM nombre_tabla LIMIT 10;
```

---

## ❌ Solución de Problemas Comunes

### Error: "Table 'reviews' already exists"
**Solución**: La tabla ya existe, usa OPCIÓN 3 o continúa sin problemas.

### Error: "Cannot add foreign key constraint"
**Solución**: 
```sql
-- Verificar que las tablas referenciadas existen
SHOW TABLES;

-- Verificar tipos de datos coinciden
DESCRIBE orders;
DESCRIBE products;
DESCRIBE users;
```

### Error: "Duplicate entry for key 'PRIMARY'"
**Solución**:
```sql
-- Limpiar datos de ejemplo antes de importar
TRUNCATE TABLE orders;
TRUNCATE TABLE reviews;
```

### Órdenes con product_id = 0
**Solución**:
```sql
-- Ver cuántas hay
SELECT COUNT(*) FROM orders WHERE product_id = 0;

-- Eliminarlas
DELETE FROM orders WHERE product_id = 0;

-- O asignar un ID válido
UPDATE orders SET product_id = 1 WHERE product_id = 0;
```

---

## 📞 Soporte

Si encuentras problemas:

1. **Verifica los logs de MySQL**: `SHOW WARNINGS;`
2. **Revisa el archivo de error**: `/var/log/mysql/error.log`
3. **Consulta la documentación**: Ver `SISTEMA_RESENAS_MEJORADO.md`
4. **Revisa la configuración**: `/dn/includes/db.php`

---

## 🎉 Todo Listo

Una vez completada la importación:

1. ✅ Abre tu aplicación: `http://localhost/principiocompra/dn/`
2. ✅ Registra un usuario de prueba
3. ✅ Compra un producto
4. ✅ Ve al panel de admin: `http://localhost/principiocompra/dn/admin/`
5. ✅ Acepta/Completa la orden
6. ✅ Como usuario, deja una reseña
7. ✅ ¡Sistema funcionando! 🎊

---

**Base de Datos Actualizada - Sistema de Reseñas v2.0**
© 2025 Market-X