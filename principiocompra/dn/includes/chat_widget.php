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
        line-height: 1.4;
        font-size: 14px;
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
        transition: transform 0.2s;
    }
    
    #chat-send:hover:not(:disabled) {
        transform: scale(1.1);
    }
    
    #chat-send:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    #chat-loading {
        text-align: center;
        padding: 40px 20px;
        color: #6b7280;
    }
    
    .typing-indicator {
        display: none;
        padding: 10px 14px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        border-bottom-left-radius: 4px;
        width: fit-content;
    }
    
    .typing-indicator.show {
        display: block;
    }
    
    .typing-indicator span {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: #9ca3af;
        border-radius: 50%;
        margin: 0 2px;
        animation: typing 1.4s infinite;
    }
    
    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }
    
    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
    }
    
    @keyframes typing {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-10px); }
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
                <div style="font-size: 24px; margin-bottom: 8px;">💬</div>
                <div>Cargando chat...</div>
            </div>
        </div>
        
        <div id="chat-input-container">
            <input type="text" id="chat-input" placeholder="Escribe tu mensaje..." maxlength="500">
            <button id="chat-send" disabled>➤</button>
        </div>
    </div>
</div>

<script>
let chatWidget = {
    chatId: null,
    lastMessageId: 0,
    pollInterval: null,
    isOpen: false,
    
    init: function() {
        this.bindEvents();
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
            if (e.key === 'Enter') {
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
            this.loadMessages();
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
        fetch('api/chat_init.php', {
            method: 'POST'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.chatId = data.chat_id;
                this.loadMessages();
            } else {
                this.showError('Error al iniciar chat');
            }
        })
        .catch(err => this.showError('Error de conexión'));
    },
    
    loadMessages: function(isPolling = false) {
        const url = `api/chat_get_messages.php?chat_id=${this.chatId}&last_id=${this.lastMessageId}`;
        
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
                
                if (!isPolling) {
                    document.getElementById('chat-loading').style.display = 'none';
                }
            }
        })
        .catch(err => {
            if (!isPolling) {
                this.showError('Error al cargar mensajes');
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
        const input = document.getElementById('chat-input');
        const message = input.value.trim();
        
        if (!message || !this.chatId) return;
        
        const sendBtn = document.getElementById('chat-send');
        sendBtn.disabled = true;
        input.disabled = true;
        
        fetch('api/chat_send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `chat_id=${this.chatId}&message=${encodeURIComponent(message)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                this.loadMessages(true);
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
        this.stopPolling();
        this.pollInterval = setInterval(() => {
            if (this.isOpen && this.chatId) {
                this.loadMessages(true);
            }
        }, 3000); // Cada 3 segundos
    },
    
    stopPolling: function() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    },
    
    scrollToBottom: function() {
        const container = document.getElementById('chat-messages');
        container.scrollTop = container.scrollHeight;
    },
    
    showError: function(message) {
        const container = document.getElementById('chat-messages');
        container.innerHTML = `
            <div style="text-align: center; padding: 40px 20px; color: #ef4444;">
                <div style="font-size: 48px; margin-bottom: 12px;">⚠️</div>
                <div>${message}</div>
            </div>
        `;
    },
    
    clearUnreadIndicator: function() {
        const button = document.getElementById('chat-button');
        button.classList.remove('has-unread');
    },
    
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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
