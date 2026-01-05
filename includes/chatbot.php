<link rel="stylesheet" href="/my-website/assets/styles.css"> <!-- Learned that adding '/' in front of the path makes it absolute -->
<div id="chatbot-container">
    <div id="chatbot-header">Budget Assistant</div>
    <div id="chatbot-messages"></div>
    <div id="chatbot-input">
        <input type="text" id="chatbot-text" placeholder="Get financial/budgeting advice:" />
        <button id="chatbot-send">Send</button>
    </div>
</div>

<script>
    /* Grab references to DOM elements */
    const sendBtn = document.getElementById('chatbot-send');
    const input = document.getElementById('chatbot-text');
    const messageDiv = document.getElementById('chatbot-messages');

    /* Send button click event */
    sendBtn.addEventListener('click', () => { /* Get input value, remove extra spaces */
        const message = input.value.trim();
        if (!message) {
            return;
        }

        // Show user message
        const userMessageDiv = document.createElement('div');
        userMessageDiv.classList.add('message', 'user');
        userMessageDiv.textContent = message;
        messageDiv.appendChild(userMessageDiv);
        input.value = ''; /* Clear input field */

        /* Always scroll to the bottom */
        messageDiv.scrollTop = messageDiv.scrollHeight;

        /* Send message to backend API */
        fetch('/my-website/chatbot_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message })
        })
        .then(response => response.json())
        .then(data => {
            /* Display bot's reply */
            if (data.reply) {
                const botMessageDiv = document.createElement('div');
                botMessageDiv.classList.add('message', 'bot');
                botMessageDiv.textContent = data.reply; /* bot's response text */
                messageDiv.appendChild(botMessageDiv);

                messageDiv.scrollTop = messageDiv.scrollHeight;
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('An error occurred while sending your message: ' + error);
        });
    });

    /* Show welcome message when page is loaded */
    document.addEventListener('DOMContentLoaded', () => {
        const messageDiv = document.getElementById('chatbot-messages');
        const welcomeMessage = document.createElement('div');
        welcomeMessage.classList.add('message', 'bot');
        welcomeMessage.innerHTML = ` <span class='bot'>Your friendly budget bot</span>
        Hello! How can I assist you today?`;
        messageDiv.appendChild(welcomeMessage);
    });

    /* Keeps chat scrolled to bottom */
    function scrollToBottom() {
        const messageDiv = document.getElementById('chatbot-messages');
        messageDiv.scrollTop = messageDiv.scrollHeight;
    }

    scrollToBottom();
</script>
