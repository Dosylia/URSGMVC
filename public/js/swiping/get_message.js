document.addEventListener("DOMContentLoaded", function() {
    let userId = document.getElementById("senderId").value;
    let friendId = document.getElementById("receiverId").value;

    // Show loading indicator
    function showLoadingIndicator() {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = '<p>Loading messages...</p>';
    }

    // Hide loading indicator
    function hideLoadingIndicator() {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = '';
    }

    // Function to fetch messages
    function fetchMessages(userId, friendId) {
        showLoadingIndicator();

        fetch('index.php?action=getMessageData', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `userId=${encodeURIComponent(userId)}&friendId=${encodeURIComponent(friendId)}`
        })
        .then(response => response.json())
        .then(data => {
            hideLoadingIndicator();

            if (data.success) {
                updateMessageContainer(data.messages, data.friend, data.user);
            } else {
                console.error('Error:', data.error);
            }
        })
        .catch(error => {
            hideLoadingIndicator();
            console.error('Error:', error);

            // Retry fetching messages after a delay
            setTimeout(() => fetchMessages(userId, friendId), 5000);
        });
    }

    // Function to update message container
    function updateMessageContainer(messages, friend, user) {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = ''; // Clear current messages

        messages.forEach(message => {
            let isCurrentUser = (message.chat_senderId == userId);
            let messageClass = isCurrentUser ? 'message-from-user' : 'message-to-user';
            let messagePosition = isCurrentUser ? 'right' : 'left';
            let messageUser = isCurrentUser ? user : friend;
            let messageLink = isCurrentUser ? 'userProfile' : 'anotherUser';

            let messageDiv = document.createElement("div");
            messageDiv.classList.add("message", messageClass);
            messageDiv.style.textAlign = messagePosition;

            // Create message content
            let messageContent = `
                <p id="username_message">
                    <img class="avatar" src="public/upload/${messageUser.user_picture}" alt="Avatar ${messageUser.user_username}">
                    <a class="username_chat_friend" target="_blank" href="index.php?action=${messageLink}&username=${encodeURIComponent(messageUser.user_username)}"><strong class="strong_text">${messageUser.user_username}</strong></a>
                    <span class="timestamp ${messagePosition}">${new Date(message.chat_date).toLocaleTimeString()}</span>
                </p>
                <p id="last-message">${message.chat_message}</p>
            `;

            messageDiv.innerHTML = messageContent;
            messagesContainer.appendChild(messageDiv);
        });
    }

    // Initially fetch messages
    fetchMessages(userId, friendId);

    // Optionally, you can set an interval to fetch messages periodically
    setInterval(() => fetchMessages(userId, friendId), 5000); // Fetch messages every 5 seconds
});