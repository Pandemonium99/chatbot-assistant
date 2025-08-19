jQuery(document).ready(function($) {
    // DOM element references
    const bubble = $('#ai-chatbot-bubble');
    const widget = $('#ai-chatbot-widget');
    const closeBtn = $('#ai-chatbot-close');
    const sendBtn = $('#ai-chatbot-send');
    const input = $('#ai-chatbot-input');
    const messagesContainer = $('#ai-chatbot-messages');
    
    // Stores the conversation history
    let messageHistory = [];

    // Event listeners
    bubble.on('click', toggleWidget);
    closeBtn.on('click', toggleWidget);
    sendBtn.on('click', sendMessage);
    input.on('keypress', e => { 
        if (e.which === 13) {
            e.preventDefault(); // Prevent form submission on enter
            sendMessage(); 
        }
    });

    /**
     * Toggles the visibility of the chat widget and the bubble.
     */
    function toggleWidget() {
        widget.toggleClass('hidden');
        bubble.toggleClass('hidden');
        // Focus the input field when the widget is opened
        if (!widget.hasClass('hidden')) {
            input.focus();
        }
    }

    /**
     * Handles sending a message from the user.
     */
    function sendMessage() {
        const userMessage = input.val().trim();
        if (userMessage === '') return;

        addMessage(userMessage, 'user');
        messageHistory.push({ role: 'user', content: userMessage });
        input.val('');
        showTypingIndicator();

        // AJAX request to the backend
        $.ajax({
            url: chatbot_params.ajax_url,
            type: 'POST',
            data: { 
                action: 'send_chat_message', 
                nonce: chatbot_params.nonce, 
                history: JSON.stringify(messageHistory) 
            },
            success: function(response) {
                if (response.success) {
                    const aiReply = response.data.reply;
                    addMessage(aiReply, 'assistant');
                    messageHistory.push({ role: 'assistant', content: aiReply });
                } else {
                    addMessage('Error: ' + response.data.message, 'assistant', true);
                }
            },
            error: function() {
                addMessage('Sorry, something went wrong. Please try again.', 'assistant', true);
            },
            complete: function() {
                hideTypingIndicator();
            }
        });
    }

    /**
     * Adds a message to the chat window, converting URLs to links.
     * @param {string} text - The message text.
     * @param {string} sender - 'user' or 'assistant'.
     * @param {boolean} isError - Whether the message is an error message.
     */
    function addMessage(text, sender, isError = false) {
        const messageClass = `ai-chatbot-message ${sender} ${isError ? 'error' : ''}`;
        
        // First, create a temporary div and set the text content to escape any potential HTML.
        const sanitizedText = $('<div>').text(text).html();

        // Regex to find URLs in the now-safe text.
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        const textWithLinks = sanitizedText.replace(urlRegex, function(url) {
            // Wrap the found URL in an anchor tag.
            return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
        });

        // Use .html() to render the text with the new anchor tags.
        const messageDiv = $(`<div class="${messageClass}"></div>`).html(textWithLinks);
        messagesContainer.append(messageDiv);
        scrollToBottom();
    }

    /**
     * Shows the typing indicator.
     */
    function showTypingIndicator() {
        const typingIndicator = '<div id="typing-indicator" class="ai-chatbot-message assistant"><span></span><span></span><span></span></div>';
        messagesContainer.append(typingIndicator);
        scrollToBottom();
    }


    /**
     * Hides the typing indicator.
     */
    function hideTypingIndicator() {
        $('#typing-indicator').remove();
    }

    /**
     * Scrolls the message container to the bottom.
     */
    function scrollToBottom() {
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }
});
