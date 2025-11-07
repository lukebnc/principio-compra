-- Migration script para agregar sistema de chat en tiempo real
-- Ejecutar este script en la base de datos ecommerce_db

-- Crear tabla de chats (conversaciones)
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

-- Crear tabla de mensajes del chat
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

-- Crear índices para mejorar el rendimiento
CREATE INDEX idx_chat_status ON chats(status);
CREATE INDEX idx_chat_updated ON chats(updated_at DESC);
CREATE INDEX idx_message_read ON chat_messages(is_read);
CREATE INDEX idx_message_created ON chat_messages(created_at DESC);

-- Mensaje de éxito
SELECT 'Sistema de chat creado exitosamente!' as mensaje;
SELECT 'Tablas creadas: chats, chat_messages' as info;
