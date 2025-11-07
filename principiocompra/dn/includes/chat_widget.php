<?php
// Widget de chat para usuarios logueados
if (isset($_SESSION['user_id'])):
?>
<style>
    /* Estilos del widget de chat */
    #chat-widget {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }
    
    #chat-button {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
    }
    
    #chat-button:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    }
    
    #chat-button.has-unread::after {
        content: '';
        position: absolute;
        top: 5px;
        right: 5px;
        width: 12px;
        height: 12px;
        background: #ef4444;
        border-radius: 50%;
        border: 2px solid white;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.8; }
    }
    
    #chat-window {
        display: none;
        position: fixed;
        bottom: 90px;
        right: 20px;
        width: 380px;
        height: 550px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        flex-direction: column;
        overflow: hidden;
        animation: slideUp 0.3s ease;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    #chat-window.open {
        display: flex;
    }
    
    #chat-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    #chat-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }
    
    #chat-header small {
        display: block;
        opacity: 0.9;
        font-size: 12px;
        margin-top: 2px;
    }
    
    #chat-close {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    
    #chat-close:hover {
        background: rgba(255,255,255,0.3);
    }
    
    #chat-messages {
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
    
    .chat-message.user {
        flex-direction: row-reverse;
    }
    
    .message-bubble {
        max-width: 70%;
        padding: 10px 14px;
        border-radius: 16px;
        word-wrap: break-word;
        word-break: break-word;
        line-height: 1.4;
        font-size: 14px;
        white-space: pre-wrap;
    }
    
    .chat-message.user .message-bubble {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom-right-radius: 4px;
    }
    
    .chat-message.admin .message-bubble {
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
    
    #chat-input-container {
        padding: 16px;
        background: white;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 8px;
    }
    
    #chat-input {
        flex: 1;
        border: 2px solid #e5e7eb;
        border-radius: 24px;
        padding: 10px 16px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
        font-family: inherit;
    }
    
    #chat-input:focus {
        border-color: #667eea;
    }
    
    #chat-send {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        transition: transform 0.2s, opacity 0.2s;
        flex-shrink: 0;
    }
    
    #chat-send:hover:not(:disabled) {
        transform: scale(1.1);
    }
    
    #chat-send:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    #chat-send.sending {
        animation: rotate 1s linear infinite;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    #chat-loading {
        text-align: center;
        padding: 40px 20px;
        color: #6b7280;
    }
    
    .spinner {
        border: 3px solid #f3f4f6;
        border-top: 3px solid #667eea;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto 12px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .error-message {
        background: #fee2e2;
        color: #dc2626;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 13px;
        margin: 10px;
        text-align: center;
    }
    
    .success-indicator {
        position: absolute;
        bottom: 20px;
        right: 70px;
        background: #10b981;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.3s ease;
        pointer-events: none;
    }
    
    .success-indicator.show {
        opacity: 1;
        transform: translateY(0);
    }
    
    @media (max-width: 480px) {
        #chat-window {
            width: calc(100vw - 40px);
            height: calc(100vh - 120px);
            right: 20px;
            bottom: 90px;
        }
    }
</style>

<div id="chat-widget">
    <button id="chat-button" title="Chat de Soporte">
        💬
    </button>
    
    <div id="chat-window">
        <div id="chat-header">
            <div>
                <h3>Chat de Soporte</h3>
                <small>Responderemos pronto</small>
            </div>
            <button id="chat-close">×</button>
        </div>
        
        <div id="chat-messages">
            <div id="chat-loading">
                <div class="spinner"></div>
                <div>Cargando chat...</div>
            </div>
        </div>
        
        <div id="chat-input-container">
            <input type="text" id="chat-input" placeholder="Escribe tu mensaje..." maxlength="500">
            <button id="chat-send" disabled>➤</button>
        </div>
    </div>
    
    <div class="success-indicator" id="success-indicator">
        ✓ Mensaje enviado
    </div>
</div>

<script>
let chatWidget = {
    chatId: null,
    lastMessageId: 0,
    pollInterval: null,
    isOpen: false,
    retryCount: 0,
    maxRetries: 3,
    
    init: function() {
        this.bindEvents();
        this.checkForUnread();
    },
    
    bindEvents: function() {
        const button = document.getElementById('chat-button');
        const closeBtn = document.getElementById('chat-close');
        const sendBtn = document.getElementById('chat-send');
        const input = document.getElementById('chat-input');
        
        button.addEventListener('click', () => this.toggleChat());
        closeBtn.addEventListener('click', () => this.closeChat());
        sendBtn.addEventListener('click', () => this.sendMessage());
        
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        input.addEventListener('input', (e) => {
            sendBtn.disabled = e.target.value.trim().length === 0;
        });
    },
    
    toggleChat: function() {
        if (this.isOpen) {
            this.closeChat();
        } else {
            this.openChat();
        }
    },
    
    openChat: function() {
        const window = document.getElementById('chat-window');
        window.classList.add('open');
        this.isOpen = true;
        
        if (!this.chatId) {
            this.initChat();
        } else {
            this.loadMessages(false);
        }
        
        this.startPolling();
        this.clearUnreadIndicator();
    },
    
    closeChat: function() {
        const window = document.getElementById('chat-window');
        window.classList.remove('open');
        this.isOpen = false;
        this.stopPolling();
    },
    
    initChat: function() {
        this.showLoading();
        
        fetch('api/chat_init.php', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(res => {
            console.log('Chat init response status:', res.status);
            if (!res.ok) {
                return res.json().then(data => {
                    throw new Error(data.error || 'Error de red: ' + res.status);
                }).catch(() => {
                    throw new Error('Error de red: ' + res.status);
                });
            }
            return res.json();
        })
        .then(data => {
            console.log('Chat init data:', data);
            if (data.success) {
                this.chatId = data.chat_id;
                this.retryCount = 0;
                this.loadMessages(false);
            } else {
                throw new Error(data.error || 'Error al iniciar chat');
            }
        })
        .catch(err => {
            console.error('Error iniciando chat:', err);
            console.error('Error detallado:', err.message);
            this.showError('Error al iniciar chat: ' + err.message + '<br><small>Revisa la consola del navegador (F12) para más detalles</small>');
        });
    },
    
    loadMessages: function(isPolling = false) {
        if (!this.chatId) return;
        
        const lastId = isPolling ? this.lastMessageId : 0;
        const url = `api/chat_get_messages.php?chat_id=${this.chatId}&last_id=${lastId}`;
        
        fetch(url, {
            credentials: 'same-origin'
        })
        .then(res => {
            if (!res.ok) throw new Error('Error de red');
            return res.json();
        })
        .then(data => {
            if (data.success) {
                this.retryCount = 0;
                
                if (data.messages && data.messages.length > 0) {
                    this.renderMessages(data.messages, isPolling);
                    this.lastMessageId = Math.max(...data.messages.map(m => parseInt(m.id)));
                } else if (!isPolling) {
                    this.renderMessages([], false);
                }
                
                if (!isPolling) {
                    document.getElementById('chat-loading').style.display = 'none';
                }
            } else {
                throw new Error(data.error || 'Error al cargar mensajes');
            }
        })
        .catch(err => {
            if (!isPolling) {
                console.error('Error cargando mensajes:', err);
                if (this.retryCount < this.maxRetries) {
                    this.retryCount++;
                    setTimeout(() => this.loadMessages(isPolling), 2000);
                } else {
                    this.showError('Error al cargar mensajes. Por favor, intenta de nuevo.');
                }
            }
        });
    },
    
    renderMessages: function(messages, append = false) {
        const container = document.getElementById('chat-messages');
        
        if (!append) {
            container.innerHTML = '';
        }
        
        if (messages.length === 0 && !append) {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                    <div style="font-size: 48px; margin-bottom: 12px;">👋</div>
                    <div style="font-weight: 600; margin-bottom: 4px;">¡Hola! ¿En qué podemos ayudarte?</div>
                    <div style="font-size: 14px;">Escribe tu mensaje abajo</div>
                </div>
            `;
            return;
        }
        
        messages.forEach(msg => {
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${msg.sender_type}`;
            messageDiv.dataset.messageId = msg.id;
            
            const time = new Date(msg.created_at).toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            messageDiv.innerHTML = `
                <div>
                    <div class="message-bubble">${this.escapeAndFormatHtml(msg.message)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;
            
            container.appendChild(messageDiv);
        });
        
        this.scrollToBottom();
    },
    
    sendMessage: function() {
        const input = document.getElementById('chat-input');
        const message = input.value.trim();
        
        if (!message || !this.chatId) return;
        
        const sendBtn = document.getElementById('chat-send');
        sendBtn.disabled = true;
        sendBtn.classList.add('sending');
        input.disabled = true;
        
        const formData = new URLSearchParams();
        formData.append('chat_id', this.chatId);
        formData.append('message', message);
        
        fetch('api/chat_send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'same-origin',
            body: formData.toString()
        })
        .then(res => {
            if (!res.ok) throw new Error('Error de red');
            return res.json();
        })
        .then(data => {
            if (data.success) {
                input.value = '';
                this.showSuccessIndicator();
                setTimeout(() => {
                    this.loadMessages(false);
                }, 300);
            } else {
                throw new Error(data.error || 'Error al enviar mensaje');
            }
        })
        .catch(err => {
            console.error('Error enviando mensaje:', err);
            alert('Error al enviar el mensaje. Por favor, intenta de nuevo.');
        })
        .finally(() => {
            sendBtn.classList.remove('sending');
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();
        });
    },
    
    startPolling: function() {
        this.stopPolling();
        this.pollInterval = setInterval(() => {
            if (this.isOpen && this.chatId) {
                this.loadMessages(true);
            }
        }, 3000);
    },
    
    stopPolling: function() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    },
    
    checkForUnread: function() {
        // Check for unread messages every 5 seconds when chat is closed
        setInterval(() => {
            if (!this.isOpen && this.chatId) {
                fetch(`api/chat_get_messages.php?chat_id=${this.chatId}&last_id=${this.lastMessageId}`, {
                    credentials: 'same-origin'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.messages && data.messages.length > 0) {
                        const hasAdminMessages = data.messages.some(m => m.sender_type === 'admin');
                        if (hasAdminMessages) {
                            this.showUnreadIndicator();
                        }
                    }
                })
                .catch(err => console.error('Error checking unread:', err));
            }
        }, 5000);
    },
    
    showUnreadIndicator: function() {
        const button = document.getElementById('chat-button');
        button.classList.add('has-unread');
    },
    
    clearUnreadIndicator: function() {
        const button = document.getElementById('chat-button');
        button.classList.remove('has-unread');
    },
    
    scrollToBottom: function() {
        const container = document.getElementById('chat-messages');
        setTimeout(() => {
            container.scrollTop = container.scrollHeight;
        }, 100);
    },
    
    showLoading: function() {
        const container = document.getElementById('chat-messages');
        container.innerHTML = `
            <div id="chat-loading">
                <div class="spinner"></div>
                <div>Cargando chat...</div>
            </div>
        `;
    },
    
    showError: function(message) {
        const container = document.getElementById('chat-messages');
        container.innerHTML = `
            <div style="text-align: center; padding: 40px 20px;">
                <div style="font-size: 48px; margin-bottom: 12px;">⚠️</div>
                <div class="error-message">${message}</div>
                <button onclick="chatWidget.initChat()" style="margin-top: 16px; padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">Reintentar</button>
            </div>
        `;
    },
    
    showSuccessIndicator: function() {
        const indicator = document.getElementById('success-indicator');
        indicator.classList.add('show');
        setTimeout(() => {
            indicator.classList.remove('show');
        }, 2000);
    },
    
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    escapeAndFormatHtml: function(text) {
        // Escape HTML first
        const escaped = this.escapeHtml(text);
        // Convert newlines to <br> but preserve the text nature
        return escaped;
    }
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => chatWidget.init());
} else {
    chatWidget.init();
}
</script>

<?php endif; ?>