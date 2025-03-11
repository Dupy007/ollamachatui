document.addEventListener('DOMContentLoaded', () => {
    const chatMessages = document.getElementById('chat-messages');
    const userInput = document.getElementById('user-input');
    const sendBtn = document.getElementById('send-btn');
    const inputContainer = document.getElementById('input-container');
    let thinkingTimeout;

    function addMessage(message, isUser) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isUser ? 'user-message' : 'assistant-message'}`;
        messageDiv.textContent = message;
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showThinkingIndicator() {
        const thinkingDiv = document.createElement('div');
        thinkingDiv.className = 'thinking-message';
        thinkingDiv.innerHTML = `
            <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        chatMessages.appendChild(thinkingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function removeThinkingIndicator() {
        const thinkingDiv = chatMessages.querySelector('.thinking-message');
        if (thinkingDiv) {
            thinkingDiv.remove();
        }
    }

    async function sendMessage() {
        const message = userInput.value.trim();
        if (!message) return;
    
        // Ajouter le message de l'utilisateur
        addMessage(message, true);
        userInput.value = '';
        
        // Désactiver l'interface
        inputContainer.classList.add('disabled');
        sendBtn.disabled = true;
        
        // Délai minimum avant d'afficher l'indicateur
        thinkingTimeout = setTimeout(() => {
            showThinkingIndicator();
        }, 300);
    
        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message })
            });
    
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
    
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let assistantMessage = '';
            let messageDiv = null;
    
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
    
                const chunk = decoder.decode(value);
                const lines = chunk.split('\n').filter(line => line.trim() !== '');
    
                for (const line of lines) {
                    if (line.startsWith('data:')) {
                        const data = JSON.parse(line.slice(5).trim());
                        
                        if (data.error) {
                            throw new Error(data.error);
                        }
    
                        if (data.response) {
                            if (!messageDiv) {
                                clearTimeout(thinkingTimeout);
                                removeThinkingIndicator();
                                messageDiv = document.createElement('div');
                                messageDiv.className = 'message assistant-message';
                                chatMessages.appendChild(messageDiv);
                            }
                            
                            assistantMessage += data.response;
                            messageDiv.textContent = assistantMessage;
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error:', error);
            clearTimeout(thinkingTimeout);
            removeThinkingIndicator();
            addMessage(`Erreur : ${error.message}`, false);
        } finally {
            inputContainer.classList.remove('disabled');
            sendBtn.disabled = false;
            userInput.focus();
        }
    }

    function updateAssistantMessage(message) {
        let messageDiv = chatMessages.querySelector('.assistant-message:last-child');
        if (!messageDiv) {
            messageDiv = document.createElement('div');
            messageDiv.className = 'message assistant-message';
            chatMessages.appendChild(messageDiv);
        }
        messageDiv.textContent = message;
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    sendBtn.addEventListener('click', sendMessage);
    userInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    
    document.getElementById('model-select').addEventListener('change', function() {
        const selectedModel = this.value;
        
        fetch('set_model.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ model: selectedModel })
        });
    });
});