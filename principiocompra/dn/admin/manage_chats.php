<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat de Soporte - Market-X Admin</title>
    <link rel="stylesheet" href="modern-admin-styles.css">
    <style>
        .chat-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            height: calc(100vh - 200px);
            min-height: 600px;
        }
        
        .chat-list-panel {
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .chat-list-header {
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-weight: 600;
        }
        
        .chat-list-stats {
            display: flex;
            gap: 12px;
            padding: 12px 16px;
            background: rgba(59, 130, 246, 0.1);
            border-bottom: 1px solid var(--border);
        }
        
        .stat-item {
            flex: 1;
            text-align: center;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        
        .chat-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .chat-item {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: background 0.2s;
            position: relative;
        }
        
        .chat-item:hover {
            background: rgba(59, 130, 246, 0.05);
        }
        
        .chat-item.active {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid var(--primary);
        }
        
        .chat-item.has-unread {
            background: rgba(59, 130, 246, 0.05);
        }
        
        .chat-user {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-preview {
            font-size: 13px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .chat-time {
            font-size: 11px;
            color: var(--text-muted);
        }
        
        .unread-badge {
            background: #ef4444;
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .chat-panel {
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            padding: 16px 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-header h3 {
            margin: 0;
            font-size: 16px;
        }
        
        .chat-header small {
            display: block;
            opacity: 0.9;
            font-size: 12px;
            margin-top: 2px;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f9fafb;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .chat-message {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            animation: messageIn 0.3s ease;
        }
        
        @keyframes messageIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .chat-message.admin {
            flex-direction: row-reverse;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 10px 14px;
            border-radius: 16px;
            word-wrap: break-word;
            line-height: 1.4;
            font-size: 14px;
        }
        
        .chat-message.admin .message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .chat-message.user .message-bubble {
            background: white;
            color: #1f2937;
            border: 1px solid #e5e7eb;
            border-bottom-left-radius: 4px;
        }
        
        .message-time {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 4px;
        }
        
        .chat-input-container {
            padding: 16px;
            background: white;
            border-top: 2px solid var(--border);
            display: flex;
            gap: 8px;
        }
        
        .chat-input {
            flex: 1;
            border: 2px solid var(--border);
            border-radius: 24px;
            padding: 10px 16px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
            font-family: inherit;
        }
        
        .chat-input:focus {
            border-color: var(--primary);
        }
        
        .chat-send {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: transform 0.2s;
        }
        
        .chat-send:hover:not(:disabled) {
            transform: scale(1.1);
        }
        
        .chat-send:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-muted);
            padding: 40px;
            text-align: center;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        @media (max-width: 1024px) {
            .chat-layout {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .chat-list-panel {
                max-height: 400px;
            }
        }
    </style>
</head>
<body>
    <div class="window">
        <div class="title-bar">
            <span>💬 Chat de Soporte - Market-X</span>
            <div class="buttons">
                <a href="login.php?logout=true" class="button danger">Logout</a>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <div class="nav-menu">
            <a href="index.php">📊 Dashboard</a>
            <a href="manage_users.php">👥 Users</a>
            <a href="manage_products.php">📦 Products</a>
            <a href="add_product.php">➕ Add Product</a>
            <a href="admin_orders.php">🛍️ Orders</a>
            <a href="manage_payments.php">💳 Payments</a>
            <a href="assign_download_link.php">🔗 Assign Links</a>
            <a href="manage_reviews.php">⭐ Reviews</a>
            <a href="manage_chats.php" class="active">💬 Chat</a>
        </div>
        
        <div class="content">
            <h2 style="margin-bottom: 24px; font-size: 1.75rem;">💬 Chat de Soporte en Tiempo Real</h2>
            
            <div class="chat-layout">
                <!-- Lista de Chats -->
                <div class="chat-list-panel">
                    <div class="chat-list-header">
                        💬 Conversaciones
                    </div>
                    
                    <div class="chat-list-stats">
                        <div class="stat-item">
                            <div class="stat-value" id="total-chats">0</div>
                            <div class="stat-label">Total</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="unread-chats">0</div>
                            <div class="stat-label">No Leídos</div>
                        </div>
                    </div>
                    
                    <div class="chat-list" id="chat-list">
                        <div class="empty-state">
                            <div class="empty-state-icon">💬</div>
                            <div>Cargando conversaciones...</div>
                        </div>
                    </div>
                </div>
                
                <!-- Panel de Chat -->
                <div class="chat-panel">
                    <div id="no-chat-selected" class="empty-state">
                        <div class="empty-state-icon">👈</div>
                        <h3 style="margin: 0 0 8px 0;">Selecciona una conversación</h3>
                        <p style="margin: 0; font-size: 14px;">Elige un chat de la lista para empezar a responder</p>
                    </div>
                    
                    <div id="chat-container" style="display: none; height: 100%; flex-direction: column;">
                        <div class="chat-header">
                            <div>
                                <h3 id="current-chat-user">Usuario</h3>
                                <small id="current-chat-email">email@example.com</small>
                            </div>
                        </div>
                        
                        <div class="chat-messages" id="chat-messages">
                            <!-- Mensajes se cargarán aquí -->
                        </div>
                        
                        <div class="chat-input-container">
                            <input type="text" class="chat-input" id="message-input" placeholder="Escribe tu respuesta..." maxlength="500">
                            <button class="chat-send" id="send-button" disabled>➤</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="admin-footer">
            <p>© 2025 Market-X Admin Panel</p>
        </footer>
    </div>
    
    <script>
        let chatManager = {
            currentChatId: null,
            lastMessageId: 0,
            chats: [],
            pollInterval: null,
            
            init: function() {
                this.bindEvents();
                this.loadChats();
                this.startPolling();
            },
            
            bindEvents: function() {
                const sendBtn = document.getElementById('send-button');
                const input = document.getElementById('message-input');
                
                sendBtn.addEventListener('click', () => this.sendMessage());
                
                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.sendMessage();
                    }
                });
                
                input.addEventListener('input', (e) => {
                    sendBtn.disabled = e.target.value.trim().length === 0;
                });
            },
            
            loadChats: function() {
                fetch('api/chat_list.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.chats = data.chats;
                        this.renderChatList(data.chats);
                        
                        document.getElementById('total-chats').textContent = data.chats.length;
                        document.getElementById('unread-chats').textContent = data.total_unread;
                    }
                })
                .catch(err => console.error('Error loading chats:', err));
            },
            
            renderChatList: function(chats) {
                const container = document.getElementById('chat-list');
                
                if (chats.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">📭</div>
                            <div>No hay conversaciones activas</div>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = '';
                
                chats.forEach(chat => {
                    const div = document.createElement('div');
                    div.className = 'chat-item' + (chat.admin_unread_count > 0 ? ' has-unread' : '');
                    if (chat.id == this.currentChatId) {
                        div.classList.add('active');
                    }
                    
                    const time = chat.last_message_at ? this.formatTime(chat.last_message_at) : 'Nuevo';
                    const preview = chat.last_message || 'Nueva conversación';
                    
                    div.innerHTML = `
                        <div class="chat-user">
                            <span>👤 ${this.escapeHtml(chat.username)}</span>
                            ${chat.admin_unread_count > 0 ? `<span class="unread-badge">${chat.admin_unread_count}</span>` : ''}
                        </div>
                        <div class="chat-preview">${this.escapeHtml(preview)}</div>
                        <div class="chat-time">${time}</div>
                    `;
                    
                    div.addEventListener('click', () => this.selectChat(chat));
                    container.appendChild(div);
                });
            },
            
            selectChat: function(chat) {
                this.currentChatId = chat.id;
                this.lastMessageId = 0;
                
                document.getElementById('no-chat-selected').style.display = 'none';
                const container = document.getElementById('chat-container');
                container.style.display = 'flex';
                
                document.getElementById('current-chat-user').textContent = chat.username;
                document.getElementById('current-chat-email').textContent = chat.email;
                
                // Actualizar UI de lista
                document.querySelectorAll('.chat-item').forEach(item => {
                    item.classList.remove('active');
                });
                event.currentTarget.classList.add('active');
                
                this.loadMessages();
            },
            
            loadMessages: function(isPolling = false) {
                if (!this.currentChatId) return;
                
                const url = `api/chat_get_messages.php?chat_id=${this.currentChatId}&last_id=${this.lastMessageId}`;
                
                fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (!isPolling || data.messages.length > 0) {
                            this.renderMessages(data.messages, isPolling);
                        }
                        
                        if (data.messages.length > 0) {
                            this.lastMessageId = Math.max(...data.messages.map(m => m.id));
                        }
                        
                        // Actualizar contador de no leídos
                        if (data.messages.length > 0) {
                            this.loadChats();
                        }
                    }
                })
                .catch(err => {
                    if (!isPolling) {
                        console.error('Error loading messages:', err);
                    }
                });
            },
            
            renderMessages: function(messages, append = false) {
                const container = document.getElementById('chat-messages');
                
                if (!append) {
                    container.innerHTML = '';
                }
                
                messages.forEach(msg => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `chat-message ${msg.sender_type}`;
                    
                    const time = new Date(msg.created_at).toLocaleTimeString('es-ES', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    messageDiv.innerHTML = `
                        <div>
                            <div class="message-bubble">${this.escapeHtml(msg.message)}</div>
                            <div class="message-time">${time}</div>
                        </div>
                    `;
                    
                    container.appendChild(messageDiv);
                });
                
                this.scrollToBottom();
            },
            
            sendMessage: function() {
                const input = document.getElementById('message-input');
                const message = input.value.trim();
                
                if (!message || !this.currentChatId) return;
                
                const sendBtn = document.getElementById('send-button');
                sendBtn.disabled = true;
                input.disabled = true;
                
                fetch('api/chat_send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `chat_id=${this.currentChatId}&message=${encodeURIComponent(message)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        input.value = '';
                        this.loadMessages(true);
                        this.loadChats();
                    } else {
                        alert(data.error || 'Error al enviar mensaje');
                    }
                })
                .catch(err => {
                    alert('Error de conexión');
                })
                .finally(() => {
                    input.disabled = false;
                    input.focus();
                });
            },
            
            startPolling: function() {
                this.pollInterval = setInterval(() => {
                    if (this.currentChatId) {
                        this.loadMessages(true);
                    }
                    this.loadChats();
                }, 3000); // Cada 3 segundos
            },
            
            scrollToBottom: function() {
                const container = document.getElementById('chat-messages');
                container.scrollTop = container.scrollHeight;
            },
            
            formatTime: function(timestamp) {
                const date = new Date(timestamp);
                const now = new Date();
                const diff = now - date;
                
                if (diff < 60000) return 'Ahora';
                if (diff < 3600000) return Math.floor(diff / 60000) + 'm';
                if (diff < 86400000) return Math.floor(diff / 3600000) + 'h';
                return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
            },
            
            escapeHtml: function(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        };
        
        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', () => chatManager.init());
    </script>
</body>
</html>