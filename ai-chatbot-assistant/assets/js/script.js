jQuery(document).ready(function($) {
    const bubble = $('#ai-chatbot-bubble');
    const widget = $('#ai-chatbot-widget');
    const closeBtn = $('#ai-chatbot-close');
    const sendBtn = $('#ai-chatbot-send');
    const input = $('#ai-chatbot-input');
    const messagesContainer = $('#ai-chatbot-messages');
    let messageHistory = [];

    bubble.on('click', toggleWidget);
    closeBtn.on('click', toggleWidget);
    sendBtn.on('click', sendMessage);
    input.on('keypress', e => { if (e.which === 13) sendMessage(); });

    function toggleWidget() {
        widget.toggleClass('hidden');
        bubble.toggleClass('hidden');
        if (!widget.hasClass('hidden')) input.focus();
    }

    function sendMessage() {
        const userMessage = input.val().trim();
        if (userMessage === '') return;

        addMessage(userMessage, 'user');
        messageHistory.push({ role: 'user', content: userMessage });
        input.val('');
        showTypingIndicator();

        $.ajax({
            url: chatbot_params.ajax_url,
            type: 'POST',
            data: { action: 'send_chat_message', nonce: chatbot_params.nonce, history: JSON.stringify(messageHistory) },
            success: response => {
                if (response.success) {
                    const aiReply = response.data.reply;
                    addMessage(aiReply, 'assistant');
                    messageHistory.push({ role: 'assistant', content: aiReply });
                } else {
                    addMessage('Error: ' + response.data.message, 'assistant', true);
                }
            },
            error: () => addMessage('Sorry, something went wrong. Please try again.', 'assistant', true),
            complete: () => hideTypingIndicator()
        });
    }

    function addMessage(text, sender, isError = false) {
        const messageClass = `ai-chatbot-message ${sender} ${isError ? 'error' : ''}`;
        const messageDiv = $(`<div class="${messageClass}"></div>`).text(text);
        messagesContainer.append(messageDiv);
        scrollToBottom();
    }



    function showTypingIndicator() {
        const typingIndicator = '<div id="typing-indicator" class="ai-chatbot-message assistant"><span></span><span></span><span></span></div>';
        messagesContainer.append(typingIndicator);
        scrollToBottom();
    }

    function hideTypingIndicator() {
        $('#typing-indicator').remove();
    }

    function scrollToBottom() {
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }
});