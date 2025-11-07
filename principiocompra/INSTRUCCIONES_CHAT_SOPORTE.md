# 💬 Sistema de Chat de Soporte en Tiempo Real

## 📋 Descripción del Sistema

Se ha implementado un **sistema completo de chat en tiempo real** entre usuarios y administradores con las siguientes características:

### ✨ Características Principales:

#### Para Usuarios:
- 💬 Widget flotante en la esquina inferior derecha
- 🔔 Notificación visual de mensajes nuevos
- ⚡ Actualización automática cada 3 segundos
- 📱 Diseño responsive (móvil y desktop)
- 🎨 Interfaz moderna y atractiva
- ⌨️ Envío con Enter o botón
- 📝 Contador de caracteres (máx. 500)

#### Para Administradores:
- 📊 Panel completo de gestión de chats
- 👥 Lista de todas las conversaciones
- 🔴 Contador de mensajes no leídos
- ⚡ Actualización en tiempo real
- 💬 Respuesta rápida desde el panel
- 📈 Estadísticas de chats activos
- 🎯 Vista de conversación completa

---

## 🗄️ Instalación de la Base de Datos

### Paso 1: Importar las Tablas

**Opción A: Desde phpMyAdmin**
1. Abre phpMyAdmin
2. Selecciona la base de datos `ecommerce_db`
3. Ve a la pestaña "Importar"
4. Selecciona el archivo: `/app/principiocompra/migration_add_chat_system.sql`
5. Haz clic en "Continuar"

**Opción B: Desde línea de comandos**
```bash
mysql -u root -p ecommerce_db < /app/principiocompra/migration_add_chat_system.sql
```

**Opción C: Ejecutar SQL directamente**
Abre phpMyAdmin → SQL → Copia y pega:

```sql
-- Tabla de conversaciones
CREATE TABLE IF NOT EXISTS `chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  `admin_unread_count` int(11) DEFAULT 0,
  `user_unread_count` int(11) DEFAULT 0,
  `last_message` text DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de mensajes
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) NOT NULL,
  `sender_type` enum('user','admin') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `chat_id` (`chat_id`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para rendimiento
CREATE INDEX idx_chat_status ON chats(status);
CREATE INDEX idx_chat_updated ON chats(updated_at DESC);
CREATE INDEX idx_message_read ON chat_messages(is_read);
CREATE INDEX idx_message_created ON chat_messages(created_at DESC);
```

---

## 📁 Archivos Creados

### Backend (APIs):

#### APIs para Usuarios:
- `/dn/api/chat_init.php` - Inicializar o recuperar chat del usuario
- `/dn/api/chat_send_message.php` - Enviar mensaje (usuario)
- `/dn/api/chat_get_messages.php` - Obtener mensajes (usuario)

#### APIs para Admin:
- `/dn/admin/api/chat_list.php` - Listar todos los chats
- `/dn/admin/api/chat_get_messages.php` - Obtener mensajes (admin)
- `/dn/admin/api/chat_send_message.php` - Enviar mensaje (admin)

### Frontend:

#### Usuario:
- `/dn/includes/chat_widget.php` - Widget flotante de chat

#### Admin:
- `/dn/admin/manage_chats.php` - Panel completo de gestión

### Base de Datos:
- `migration_add_chat_system.sql` - Script de instalación

### Documentación:
- `INSTRUCCIONES_CHAT_SOPORTE.md` - Este archivo

---

## 🚀 Cómo Usar el Sistema

### Como Usuario:

1. **Iniciar sesión** en la plataforma
2. Verás un **botón flotante** 💬 en la esquina inferior derecha
3. **Haz clic** en el botón para abrir el chat
4. **Escribe tu mensaje** en el campo de texto
5. **Presiona Enter** o haz clic en el botón de enviar ➤
6. Los mensajes del admin aparecerán automáticamente
7. Recibirás **notificaciones visuales** de mensajes nuevos

### Como Administrador:

1. **Panel Admin** → Haz clic en "💬 Chat" en el menú
2. Verás la **lista de conversaciones** a la izquierda
3. Los chats con mensajes no leídos aparecen **destacados**
4. **Haz clic en una conversación** para abrirla
5. **Escribe tu respuesta** en el campo de texto
6. **Presiona Enter** o haz clic en enviar ➤
7. La conversación se actualiza automáticamente

---

## 🎨 Características del Widget de Chat

### Diseño:
- 🟣 Botón flotante con gradiente morado
- 📍 Posición fija en esquina inferior derecha
- 🔴 Indicador de mensajes no leídos (punto rojo pulsante)
- 📱 Responsive: se adapta a móviles
- ✨ Animaciones suaves al abrir/cerrar

### Funcionalidad:
- ⚡ Polling cada 3 segundos para nuevos mensajes
- 💾 Persistencia de conversación
- 📜 Scroll automático a último mensaje
- ⌨️ Atajos de teclado (Enter para enviar)
- 🚫 Validación de mensajes vacíos
- 📏 Límite de 500 caracteres

---

## 📊 Panel de Admin - Características

### Vista de Lista:
- 📋 Todas las conversaciones activas
- 👤 Nombre y email del usuario
- 💬 Preview del último mensaje
- ⏰ Tiempo transcurrido (Ahora, 5m, 2h, etc.)
- 🔴 Badge de mensajes no leídos
- 📊 Estadísticas: Total de chats y No leídos

### Vista de Conversación:
- 📝 Historial completo de mensajes
- 👥 Información del usuario (nombre, email)
- 💬 Diferenciación visual usuario/admin
- ⏰ Timestamp de cada mensaje
- ⚡ Actualización en tiempo real
- ⌨️ Campo de respuesta rápida

---

## 🔧 Personalización

### Cambiar Frecuencia de Actualización:

En `/dn/includes/chat_widget.php` línea ~449:
```javascript
this.pollInterval = setInterval(() => {
    // Cambiar 3000 a la cantidad de milisegundos deseada
}, 3000); // 3000ms = 3 segundos
```

En `/dn/admin/manage_chats.php` línea ~372:
```javascript
this.pollInterval = setInterval(() => {
    // Cambiar 3000 a la cantidad de milisegundos deseada
}, 3000); // 3000ms = 3 segundos
```

### Cambiar Colores del Widget:

En `/dn/includes/chat_widget.php`:
```css
/* Botón flotante */
#chat-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* Cambiar por tus colores */
}

/* Mensajes del usuario */
.chat-message.user .message-bubble {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* Cambiar por tus colores */
}
```

### Cambiar Límite de Caracteres:

En los campos de input, cambiar `maxlength`:
```html
<input type="text" id="chat-input" maxlength="500">
<!-- Cambiar 500 por el límite deseado -->
```

---

## 🗃️ Estructura de la Base de Datos

### Tabla: `chats`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único de la conversación |
| user_id | INT | ID del usuario (FK) |
| status | ENUM | active/closed |
| admin_unread_count | INT | Mensajes no leídos por admin |
| user_unread_count | INT | Mensajes no leídos por usuario |
| last_message | TEXT | Último mensaje enviado |
| last_message_at | TIMESTAMP | Fecha del último mensaje |
| created_at | TIMESTAMP | Fecha de creación |
| updated_at | TIMESTAMP | Última actualización |

### Tabla: `chat_messages`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único del mensaje |
| chat_id | INT | ID de la conversación (FK) |
| sender_type | ENUM | user/admin |
| sender_id | INT | ID del remitente |
| message | TEXT | Contenido del mensaje |
| is_read | BOOLEAN | Si fue leído |
| created_at | TIMESTAMP | Fecha de creación |

---

## 🔒 Seguridad Implementada

### Autenticación:
- ✅ Verificación de sesión de usuario
- ✅ Verificación de sesión de admin
- ✅ Usuarios solo pueden ver sus propios chats
- ✅ Admins pueden ver todos los chats

### Validación:
- ✅ Mensajes no pueden estar vacíos
- ✅ Límite de caracteres aplicado
- ✅ Sanitización de HTML en salida
- ✅ Prepared statements en SQL

### Privacidad:
- ✅ Foreign Keys con CASCADE DELETE
- ✅ Índices para optimizar consultas
- ✅ Marcado automático de mensajes leídos

---

## 📈 Optimización y Rendimiento

### Índices Creados:
```sql
-- Para búsquedas por estado
CREATE INDEX idx_chat_status ON chats(status);

-- Para ordenar por fecha
CREATE INDEX idx_chat_updated ON chats(updated_at DESC);

-- Para filtrar mensajes no leídos
CREATE INDEX idx_message_read ON chat_messages(is_read);

-- Para ordenar mensajes
CREATE INDEX idx_message_created ON chat_messages(created_at DESC);
```

### Polling Inteligente:
- Solo se cargan mensajes nuevos (usando `last_id`)
- No se cargan mensajes si no hay cambios
- Intervalo ajustable (default 3 segundos)

---

## 🐛 Solución de Problemas

### El widget no aparece:
1. ✅ Verificar que el usuario esté logueado
2. ✅ Revisar que `chat_widget.php` esté incluido
3. ✅ Verificar console del navegador por errores JavaScript

### Los mensajes no se envían:
1. ✅ Verificar que las tablas existen en la BD
2. ✅ Revisar permisos de los archivos API
3. ✅ Verificar que las sesiones estén funcionando
4. ✅ Revisar Network tab del navegador

### El admin no ve los chats:
1. ✅ Verificar que está logueado como admin
2. ✅ Revisar que `isAdminLoggedIn()` funcione
3. ✅ Verificar que existan chats en la BD
4. ✅ Revisar console por errores de API

### No se actualiza en tiempo real:
1. ✅ Verificar que el polling esté activo
2. ✅ Revisar intervalo de actualización (3 segundos default)
3. ✅ Verificar conexión a las APIs
4. ✅ Limpiar caché del navegador

---

## 📱 Compatibilidad

### Navegadores Soportados:
- ✅ Chrome/Edge (últimas versiones)
- ✅ Firefox (últimas versiones)
- ✅ Safari (últimas versiones)
- ✅ Opera (últimas versiones)
- ⚠️ Internet Explorer no soportado

### Dispositivos:
- ✅ Desktop (Windows, Mac, Linux)
- ✅ Tablet (iPad, Android)
- ✅ Móvil (iOS, Android)

---

## 🎯 Próximas Mejoras (Opcionales)

### Posibles Expansiones:
- 📎 Adjuntar archivos/imágenes
- 🔔 Notificaciones push del navegador
- 🎤 Mensajes de voz
- 👀 Indicador "escribiendo..."
- 📊 Reportes y analytics de chats
- 🏷️ Sistema de etiquetas para chats
- ⭐ Calificación del soporte
- 🤖 Respuestas automáticas/bot
- 📧 Notificación por email de nuevos mensajes

---

## ✅ Checklist de Verificación

Usa este checklist para verificar que todo funciona:

### Base de Datos:
- [ ] Tabla `chats` creada correctamente
- [ ] Tabla `chat_messages` creada correctamente
- [ ] Índices creados
- [ ] Foreign keys funcionando

### Frontend Usuario:
- [ ] Widget aparece en esquina inferior derecha
- [ ] Se puede abrir/cerrar el chat
- [ ] Se pueden enviar mensajes
- [ ] Se reciben respuestas del admin
- [ ] Contador de mensajes no leídos funciona
- [ ] Animaciones y estilos correctos

### Frontend Admin:
- [ ] Panel de chat accesible desde menú
- [ ] Lista de conversaciones se muestra
- [ ] Se puede seleccionar una conversación
- [ ] Se pueden enviar respuestas
- [ ] Contador de no leídos funciona
- [ ] Actualización en tiempo real funciona

### Funcionalidad:
- [ ] Usuario puede iniciar chat nuevo
- [ ] Usuario puede enviar mensajes
- [ ] Admin recibe notificación de nuevos mensajes
- [ ] Admin puede responder
- [ ] Usuario recibe respuesta en tiempo real
- [ ] Mensajes se marcan como leídos
- [ ] Scroll automático funciona

Si todos los checkboxes están marcados: **¡Sistema 100% funcional! 🎉**

---

## 📞 Soporte

### Archivos de Referencia:
- **Widget Usuario:** `/dn/includes/chat_widget.php`
- **Panel Admin:** `/dn/admin/manage_chats.php`
- **APIs Usuario:** `/dn/api/chat_*.php`
- **APIs Admin:** `/dn/admin/api/chat_*.php`

### Para Debug:
1. Abrir Console del navegador (F12)
2. Ir a Network tab
3. Filtrar por "chat"
4. Ver requests/responses

---

## 🎉 Resultado Final

**Sistema de Chat Completo y Funcional:**

✅ Widget flotante moderno para usuarios
✅ Panel profesional para administradores
✅ Actualización en tiempo real
✅ Notificaciones visuales
✅ Diseño responsive
✅ Optimizado y seguro
✅ Fácil de usar y personalizar

---

**Implementado:** Enero 2025  
**Estado:** ✅ Completamente funcional  
**Versión:** 1.0

© 2025 Market-X - Sistema de Chat de Soporte en Tiempo Real
