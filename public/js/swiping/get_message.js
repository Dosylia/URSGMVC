let userIdElement = document.getElementById("senderId");
let friendIdElement = document.getElementById("receiverId");
export const userId = userIdElement ? userIdElement.value : null;
export const friendId = friendIdElement ? friendIdElement.value : null;
let currentMessages = []; // Store the current messages
let isFirstFetch = true; // Flag to track the first fetch
let friendData = document.getElementById('friendInfo');
export const chatInterface = document.querySelector('.chat-interface');
export const messageContainer = document.querySelector('.messages-container');
import { badWordsList } from './chatFilter.js';

document.addEventListener("DOMContentLoaded", function() {
        
    if(userId !== null && userId !== undefined)
        {
            // Initially fetch messages
            fetchMessages(userId, friendId);
            
            // Optionally, you can set an interval to fetch messages periodically
            setInterval(() => fetchMessages(userId, friendId), 5000); // Fetch messages every 5 seconds
        }

    // Set the variable initially
    setVhVariable();
    
    // Update the variable on resize
    window.addEventListener('resize', setVhVariable);

    // Initial check
    checkScreenSize();

    // Add event listener for screen resize
    window.addEventListener('resize', checkScreenSize);

    document.querySelectorAll('.username_chat_friend').forEach(link => {
        link.addEventListener('click', function(event) {
            window.open(this.href, '_blank');
        });
    });

});


    // Show loading indicator
    function showLoadingIndicator() {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = '<p>Loading messages...</p>';
    }

    // Function to fetch messages
    export function fetchMessages(userId, friendId) {
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
            body: `userId=${encodeURIComponent(parseInt(userId))}&friendId=${encodeURIComponent(friendId)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Compare the fetched messages with the current messages
                if (data.messages !== null && data.messages !== undefined) {
                    if (JSON.stringify(currentMessages) !== JSON.stringify(data.messages)) {
                        currentMessages = data.messages; // Update the current messages
                        updateMessageContainer(data.messages, data.friend, data.user);
                    } else {
                        console.log('No new messages. No update needed.');
                    }
                }
                else
                {
                    showFriendInfo(data.friend);
                }
            } else {
                console.error('Error fetching messages:', data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);

            // Retry fetching messages after a delay
            setTimeout(() => fetchMessages(userId, friendId), 5000);
        });
    }

    // Function to update message container
    function updateMessageContainer(messages, friend, user) {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = ''; // Clear current messages
    
        let previousMessage = null; // Variable to store the previous message
    
        messages.forEach(message => {
            let isCurrentUser = (message.chat_senderId == userId);
            let messageClass = isCurrentUser ? 'message-from-user' : 'message-to-user';
            let messagePosition = isCurrentUser  ? 'left' : 'left';
            let userPosition = isCurrentUser ? 'right' : 'left';
            let lastMessagePosition = isCurrentUser ? 'flex-end' : 'flex-start';
            let messageUser = isCurrentUser ? user : friend;
            let messageLink = isCurrentUser ? 'userProfile' : 'anotherUser';
            let timestampPosition = isCurrentUser ? "normal" : "inverted";
            let pictureLink;
    
            if (messageUser.user_picture === null || messageUser.user_picture === undefined) {
                pictureLink = "images/defaultprofilepicture.jpg";
            } else {
                pictureLink = `upload/${messageUser.user_picture}`;
            }
    
            let messageDiv = document.createElement("div");
            messageDiv.classList.add("message", messageClass);
    
            // Create message content
            let messageContent = '';

            let utcDate = new Date(message.chat_date);
            let localOffset = utcDate.getTimezoneOffset();
            let localDate = new Date(utcDate.getTime() - localOffset * 60000);
            let formattedTime = localDate.toLocaleTimeString();
    
            // Check if previous message exists and is from the same sender within 5 minutes
            if (previousMessage && previousMessage.chat_senderId === message.chat_senderId) {
                let timeDifference = new Date(message.chat_date) - new Date(previousMessage.chat_date);
                if (timeDifference <= 5 * 60 * 1000) { // 5 minutes in milliseconds
                    // Display only the message without icon, avatar, and timestamp
                    //For chat filter: <span class="message-text" style="text-align: ${messagePosition};">${userId.user_hasChatFilter ? chatfilter(renderEmotes(message.chat_message)) : renderEmotes(message.chat_message)}</span>
                    messageContent = `
                    <p class="last-message" style="text-align: ${messagePosition};">
                        <span class="timestamp-hover">${formattedTime}</span>
                        <span class="message-text" style="text-align: ${messagePosition};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message)) : renderEmotes(message.chat_message)}</span>
                    </p>
                    `;
                } else {
                    // Display full message with icon, avatar, and timestamp
                    messageContent = `
                        <p id="username_message" style="text-align: ${userPosition};">
                            <img class="avatar" src="public/${pictureLink}" alt="Avatar ${messageUser.user_username}">
                            <a class="username_chat_friend" target="_blank" href="/${messageLink}&username=${encodeURIComponent(messageUser.user_username)}"><strong class="strong_text">${messageUser.user_username}</strong></a>
                            <span class="timestamp ${messagePosition}">${formattedTime}</span>
                        </p>
                        <p class="last-message" style="text-align: ${messagePosition};">
                            <span class="timestamp-hover">${formattedTime}</span>
                            <span class="message-text" style="text-align: ${messagePosition};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message)) : renderEmotes(message.chat_message)}</span>
                        </p>
                    `;
                }
            } else {
                // First message from this sender or different sender
                messageContent = `
                    <p id="username_message" style="text-align: ${userPosition};">
                        <img class="avatar" src="public/${pictureLink}" alt="Avatar ${messageUser.user_username}">
                        <a class="username_chat_friend" target="_blank" href="/${messageLink}&username=${encodeURIComponent(messageUser.user_username)}"><strong class="strong_text">${messageUser.user_username}</strong></a>
                        <span class="timestamp ${messagePosition}">${formattedTime}</span>
                    </p>
                    <p class="last-message" style="text-align: ${messagePosition};">
                        <span class="timestamp-hover">${formattedTime}</span>
                        <span class="message-text" style="text-align: ${messagePosition};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message)) : renderEmotes(message.chat_message)}</span>
                    </p>
                `;
            }
    
            messageDiv.innerHTML = messageContent;
            messagesContainer.appendChild(messageDiv);
    
            // Store the current message as previousMessage for the next iteration
            previousMessage = message;

            
            const lastMessage = messageDiv.querySelector('.last-message');
            lastMessage.classList.add(timestampPosition);
            lastMessage.style.justifyContent = lastMessagePosition;
    
            // Add hover behavior for timestamp
            let timestampSpan = messageDiv.querySelector('.timestamp-hover');
            if (timestampSpan) {
                timestampSpan.style.display = 'none'; // Initially hide the timestamp
    
                messageDiv.addEventListener('mouseenter', function() {
                    timestampSpan.style.display = 'inline-block'; // Show timestamp on hover
                });
    
                messageDiv.addEventListener('mouseleave', function() {
                    timestampSpan.style.display = 'none'; // Hide timestamp when not hovering
                });
            }
        });
    
        console.log('Messages container updated. Now scrolling to bottom.');
        setTimeout(scrollToBottom, 100); // Delay scrolling to ensure container is updated
    }
    
    
    // Function to replace emote codes with actual emote images
    function renderEmotes(message) {
        const emoteMap = {
            ':surprised-cat:': '<img src="public/images/emotes/surprised-cat.png" alt="surprised-cat" class="emote">',
            ':cat-smile:': '<img src="public/images/emotes/cat-smile.png" alt="cat-smile" class="emote">',
            ':cat-cute:': '<img src="public/images/emotes/cat-cute.png" alt="cat-cute" class="emote">',
            ':goofy-ah-cat:': '<img src="public/images/emotes/goofy-ah-cat.png" alt="goofy-ah-cat" class="emote">',
            ':cat-surprised:': '<img src="public/images/emotes/cat-surprised.png" alt="cat-surprised" class="emote">',
            ':cat-liked:': '<img src="public/images/emotes/cat-liked.png" alt="cat-liked" class="emote">',
            ':cat-sus:': '<img src="public/images/emotes/cat-sus.png" alt="cat-sus" class="emote">',
            ':cat-bruh:': '<img src="public/images/emotes/cat-bruh.png" alt="cat-bruh" class="emote">',
            ':cat-licking:': '<img src="public/images/emotes/cat-licking.png" alt="cat-licking" class="emote">'
        };
    
        const replacedMessage = message.replace(/:\w+(-\w+)*:/g, function(match) {
            console.log("Matching emote:", match);
            return emoteMap[match] || match;
        });
    
        return replacedMessage;
    }

    // Function to see friend's data
    function showFriendInfo(friend) {

        const pictureLink = friend.user_picture ? `upload/${friend.user_picture}` : "images/defaultprofilepicture.jpg";

        let friendContent = `
        <p id="friendTop">
            <img class="avatar" src="public/${pictureLink}" alt="Avatar ${friend.user_username}">
            <a class="username_chat_friend" target="_blank" href="/anotherUser&username=${encodeURIComponent(friend.user_username)}"><strong class="strong_text">${friend.user_username}</strong></a>
        </p>
        <p id="firstToChat">Be the first one to chat <i class="fa-regular fa-comments"></i></p>`;

        if (friendData) {
            friendData.innerHTML = friendContent;
        } else {
            console.error("friendData element not found");
        }

        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = '';
    };

    // Function to scroll to the bottom of the messages container
    function scrollToBottom() {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    const chatfilter = (textToFilter) => {
        console.log('Filtering chat message:', textToFilter);   
        // Combine all bad words from all languages into a single array
        const allBadWords = badWordsList.flatMap(([, badWords]) => badWords);
    
        // Create a regular expression from all the bad words
        const badWordsRegex = new RegExp(allBadWords.join('|'), 'gi');
    
        // Replace bad words with '***'
        const filteredText = textToFilter.replace(badWordsRegex, (match) => {
            return '*'.repeat(match.length);
        });
    
        return filteredText;
    }

    // Function to set the --vh variable
    function setVhVariable() {
        let vh = window.innerHeight * 0.01; // 1vh
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }

    function checkScreenSize() {
        const isMax1018px = window.matchMedia("(max-width: 1018px)").matches;

        if (isMax1018px) {
            if (chatInterface !== null && window.getComputedStyle(messageContainer).display !== 'none') {
                chatInterface.style.display = 'none';
            }
        } else {
            if (chatInterface !== null && chatInterface !== undefined) {
                chatInterface.style.display = 'flex';
            }
            messageContainer.style.display = 'block';
        }
    }

