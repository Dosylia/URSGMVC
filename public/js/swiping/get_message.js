document.addEventListener("DOMContentLoaded", function() {
    let userId = document.getElementById("senderId").value;
    let friendId = document.getElementById("receiverId").value;
    let currentMessages = []; // Store the current messages
    let isFirstFetch = true; // Flag to track the first fetch

    // Show loading indicator
    function showLoadingIndicator() {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = '<p>Loading messages...</p>';
    }

    // Function to fetch messages
    function fetchMessages(userId, friendId) {
        if (isFirstFetch) {
            showLoadingIndicator();
            isFirstFetch = false; // Reset the flag after the first fetch
        }

        console.log('Fetching messages for userId:', userId, 'and friendId:', friendId);

        fetch('index.php?action=getMessageData', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `userId=${encodeURIComponent(userId)}&friendId=${encodeURIComponent(friendId)}`
        })
        .then(response => response.json())
        .then(data => {

            if (data.success) {
                console.log('Messages fetched successfully:', data.messages);
                // Compare the fetched messages with the current messages
                if (JSON.stringify(currentMessages) !== JSON.stringify(data.messages)) {
                    currentMessages = data.messages; // Update the current messages
                    updateMessageContainer(data.messages, data.friend, data.user);
                } else {
                    console.log('No new messages. No update needed.');
                }
            } else {
                console.error('Error fetching messages:', data.error);
            }
        })
        .catch(error => {
            hideLoadingIndicator();
            console.error('Fetch error:', error);

            // Retry fetching messages after a delay
            setTimeout(() => fetchMessages(userId, friendId), 5000);
        });
    }

    // Function to update message container
    function updateMessageContainer(messages, friend, user) {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = ''; // Clear current messages
        console.log('Updating message container with messages:', messages);

        messages.forEach(message => {
            let isCurrentUser = (message.chat_senderId == userId);
            let messageClass = isCurrentUser ? 'message-from-user' : 'message-to-user';
            let messagePosition = isCurrentUser ? 'right' : 'left';
            let messageUser = isCurrentUser ? user : friend;
            let messageLink = isCurrentUser ? 'userProfile' : 'anotherUser';
            let pictureLink;

            let messageDiv = document.createElement("div");
            messageDiv.classList.add("message", messageClass);
            messageDiv.style.textAlign = messagePosition;

            if (messageUser.user_picture === null || messageUser.user_picture === undefined) {
                pictureLink = "images/defaultprofilepicture.jpg";
            } else {
                pictureLink = `upload/${messageUser.user_picture}`;
            }

            // Create message content
            let messageContent = `
                <p id="username_message">
                    <img class="avatar" src="public/${pictureLink}" alt="Avatar ${messageUser.user_username}">
                    <a class="username_chat_friend" target="_blank" href="index.php?action=${messageLink}&username=${encodeURIComponent(messageUser.user_username)}"><strong class="strong_text">${messageUser.user_username}</strong></a>
                    <span class="timestamp ${messagePosition}">${new Date(message.chat_date).toLocaleTimeString()}</span>
                </p>
                <p id="last-message">${message.chat_message}</p>
            `;

            messageDiv.innerHTML = messageContent;
            messagesContainer.appendChild(messageDiv);
        });

        console.log('Messages container updated. Now scrolling to bottom.');
        setTimeout(scrollToBottom, 100); // Delay scrolling to ensure container is updated
    }

    // Function to scroll to the bottom of the messages container
    function scrollToBottom() {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Initially fetch messages
    fetchMessages(userId, friendId);

    // Optionally, you can set an interval to fetch messages periodically
    setInterval(() => fetchMessages(userId, friendId), 5000); // Fetch messages every 5 seconds

    // Function to set the --vh variable
    function setVhVariable() {
        let vh = window.innerHeight * 0.01; // 1vh
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
    
    // Set the variable initially
    setVhVariable();
    
    // Update the variable on resize
    window.addEventListener('resize', setVhVariable);
});
