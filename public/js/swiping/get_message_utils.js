let userIdElement = document.getElementById("senderId");
let friendIdElement = document.getElementById("receiverId");
export const userId = userIdElement ? userIdElement.value : null;
export let friendId = friendIdElement ? friendIdElement.value : null;
let currentMessages = []; // Store the current messages
let isFirstFetch = true; // Flag to track the first fetch
let friendData = document.getElementById('friendInfo');
export const chatInterface = document.querySelector('.chat-interface');
export const messageContainer = document.querySelector('.messages-container');
import { badWordsList } from './chatFilter.js';
let currentFriendUsername = null;
let firstFriendId = friendId;
export const replyPreviewContainer = document.getElementById("reply-preview");
export const chatInput = document.getElementById("message_text");
export let clearImageVar = false;
let numberofFail = 0;
let lastFriendStatus = null;
const RatingModal = document.getElementById('rating-modal');
export const closeRatingModalBtn = document.getElementById('close-rating-modal');
const RatingButton = document.getElementById('rating-button');
export const submitRating = document.getElementById('submit-rating');


// Function to fetch messages
export function fetchMessages(userId, friendId) {

    if (numberofFail >= 5) {
        console.error('Too many failed attempts. Stopping fetch loop.');
        return;
    }

    const token = localStorage.getItem('masterTokenWebsite');
    const firstFriendInput = document.getElementById('firstFriend');
    let firstFriend = firstFriendInput ? firstFriendInput.value : null;


    if (firstFriend && friendId !== firstFriendId) {
        firstFriendInput.value = "no";
    }

    const hasFocus = document.hasFocus();
    if (!hasFocus) {
        firstFriend = "yes";
    }

    if (isFirstFetch) {
        showLoadingIndicator();
        isFirstFetch = false; // Reset the flag after the first fetch
    }

    fetch('/getMessageDataWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'Authorization': `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(userId)}&friendId=${encodeURIComponent(friendId)}&firstFriend=${encodeURIComponent(firstFriend)}`
    })
    .then(async response => {
        if (!response.ok) {
            throw new Error(`HTTP error ${response.status}`);
        }
    
        let data;
        try {
            data = await response.json();
        } catch (jsonError) {
            throw new Error('Invalid JSON response from server');
        }
    
        return data;
    })
    .then(data => {
        if (data.success) {
            numberofFail = 0; // Reset the fail counter on success
            if (data.messages !== null && data.messages !== undefined) {
                if (JSON.stringify(currentMessages) !== JSON.stringify(data.messages)) {
                    currentMessages = data.messages;
                    showFriendInfo(data.friend).then(() => {
                        updateMessageContainer(data.messages, data.friend, data.user);
                    });
                } else {
                    showFriendInfo(data.friend);
                }
            } else {
                // Handle empty messages case
                currentMessages = [];
                showFriendInfo(data.friend).then(() => {
                    updateMessageContainer([], data.friend, data.user); // Pass empty array
                });
                console.log('No messages found.');
            }
        } else {
            numberofFail++;
            console.error('Error fetching messages:', data.error);
    
            if (
                data.error.includes('Friend not found') ||
                data.error.includes('User not found')
            ) {
                console.warn('Stopping message fetch loop due to missing friend/user.');
                return;
            }
    
            // Optional: retry for other types of logical errors
            setTimeout(() => fetchMessages(userId, friendId), 5000);
        }
    })
    .catch(error => {
        numberofFail++;
        console.error('Fetch or JSON parse error:', error);
    
        // Retry only for temporary issues (not "Friend not found", etc.)
        if (!error.message.includes('Friend not found') && !error.message.includes('User not found')) {
            setTimeout(() => fetchMessages(userId, friendId), 5000);
        } else {
            console.warn('Not retrying due to invalid user/friend.');
        }
    });        
}

    // Show loading indicator
    function showLoadingIndicator() {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = '<p>Loading messages...</p>';
    }

    function getGameStatusLoL(friendId) {
        return fetch('/getGameStatusLoL', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `friendId=${encodeURIComponent(friendId)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                return data; // ✅ Return the actual game data here
            } else {
                console.log(data.error);
                return null;
            }
        })
        .catch(error => {
            return null;
        });
    }
    

    // Function to update message container
    function updateMessageContainer(messages, friend, user) {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = ''; // Clear current messages
    
        let previousMessage = null; // Variable to store the previous message
        let unreadSectionStarted = false;
    
        messages.forEach(message => {
            let isCurrentUser = (message.chat_senderId == userId);
            let messageClass = isCurrentUser ? 'message-from-user' : 'message-to-user';
            let messagePosition = isCurrentUser  ? 'left' : 'left'; 
            let userPosition = isCurrentUser ? 'left' : 'left'; // right - left to go back to chat on sides
            let lastMessagePosition = isCurrentUser ? 'flex-start' : 'flex-start'; // flex-end - flex-start to go back to chat on sides
            let messageUser = isCurrentUser ? user : friend;
            let messageLink = isCurrentUser ? 'userProfile' : 'anotherUser';
            let timestampPosition = isCurrentUser ? "inverted" : "inverted"; // normal - inverted to go back to chat on sides
            let replyContainerClass = isCurrentUser ? "normal" : "inverted";
            let backgroundColor = isCurrentUser ? '#e84056' : '';
            let pictureLink;
            let senderOwnsVIPEmotes = (message.chat_senderId == user.user_id) ? user.ownVIPEmotes : friend.ownVIPEmotes;
    
            if (messageUser.user_picture === null || messageUser.user_picture === undefined) {
                pictureLink = "images/defaultprofilepicture.jpg";
            } else {
                pictureLink = `upload/${messageUser.user_picture}`;
            }

            if (!unreadSectionStarted && !isCurrentUser && message.chat_status === 'unread') {
                // Create separator for unread messages
                let separator = document.createElement('div');
                separator.className = 'unread-separator';
                separator.innerHTML = `
                    <span>New message</span>
                    <hr>
                `;
                messagesContainer.appendChild(separator);
                unreadSectionStarted = true;
            }
    
            let messageDiv = document.createElement("div");
            messageDiv.classList.add("message", messageClass);
            messageDiv.id = `message-${message.chat_id}`;
    
            // Message Status (for sent messages only)
            let messageStatus = "";
            if (isCurrentUser) {
                messageStatus = message.chat_status === "read" 
                ? '<i style="color:rgb(196, 220, 17);" class="fa-solid fa-envelope-circle-check"></i>' 
                : '<i class="fa-solid fa-envelope"></i>';
            }
    
            let utcDate = new Date(message.chat_date);
            let localOffset = utcDate.getTimezoneOffset();
            let localDate = new Date(utcDate.getTime() - localOffset * 60000);
    
            // Format for today
            const isToday = new Date(message.chat_date).toDateString() === new Date().toDateString();
    
            // Format time
            let formattedTime = localDate.toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit"
            });
    
            // Format full date for other days
            let formattedDate;
            if (!isToday) {
                formattedDate = new Date(message.chat_date).toLocaleDateString('fr-FR');
            } else {
                formattedDate = `Today ${formattedTime}`;
            }
    
            // Check if previous message exists and is from the same sender within 5 minutes
            let messageContent;

            if (message.chat_replyTo) {
                let originalMessage = messages.find(m => m.chat_id == message.chat_replyTo);
                if (originalMessage) {
                    let replacedMessage = originalMessage.chat_message.replace(/\[img\](.*?)\[\/img\]/g, 'Contains a 📷');

                    const truncatedMessage = replacedMessage.length > 50 
                        ? replacedMessage.substring(0, 50) + "..." 
                        : replacedMessage;
                    
                    let finalMessage = truncatedMessage;
                    if (previousMessage && previousMessage.chat_senderId === message.chat_senderId) {
                        let timeDifference = new Date(message.chat_date) - new Date(previousMessage.chat_date);
                        if (timeDifference <= 5 * 60 * 1000) {
                            messageContent = `
                            <p class="last-message" style="text-align: ${messagePosition}; padding-top: 20px; padding-bottom: 5px; position: relative; z-index: 950;">
                                <span class="timestamp-hover">${formattedTime}</span>
                                <span class="message-text" style="text-align: ${messagePosition}; background-color: ${backgroundColor};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message, senderOwnsVIPEmotes)) : renderEmotes(message.chat_message, senderOwnsVIPEmotes)}
                                ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                                </span>
                                <span class="replied-message ${replyContainerClass}" style="position: absolute; 
                                        top: 5px; 
                                        ${isCurrentUser ? 'left: 0' : 'left: 0'}; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${isCurrentUser ? 'text-align: left' : 'text-align: left'}; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;"
                                    data-reply-id="${message.chat_replyTo}">
                                        ${renderEmotes(finalMessage)}
                                </span>
                            </p>
                            `;
                        } else {
                            messageContent = `
                                <p id="username_message" style="text-align: ${userPosition}; padding-bottom: 20px;">
                                    <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(messageUser.user_username)}"><strong class="strong_text">${messageUser.user_username}</strong></a>
                                    <span class="timestamp ${messagePosition}">${formattedDate}</span>
                                </p>
                                <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px; position: relative; z-index: 950;">
                                    <span class="timestamp-hover">${formattedTime}</span>
                                    <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message, senderOwnsVIPEmotes)) : renderEmotes(message.chat_message, senderOwnsVIPEmotes)}
                                    ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                                    </span>
                                    <span class="replied-message ${replyContainerClass}" style="position: absolute; 
                                        top: -10px; 
                                        ${isCurrentUser ? 'left: 0' : 'left: 0'}; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${isCurrentUser ? 'text-align: left' : 'text-align: left'}; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;"
                                    data-reply-id="${message.chat_replyTo}">
                                        ${renderEmotes(finalMessage)}
                                    </span>
                                </p>
                            `;
                        }
                    } else {
                        messageContent = `
                                <p id="username_message" style="text-align: ${userPosition}; padding-bottom: 20px;">
                                    <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(messageUser.user_username)}">
                                        <strong class="strong_text">${messageUser.user_username}</strong>
                                    </a>
                                    <span class="timestamp ${messagePosition}">${formattedDate}</span>
                                </p>
                                <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px; position: relative; z-index: 950;">
                                    <span class="timestamp-hover">${formattedTime}</span>
                                    <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">
                                        ${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message, senderOwnsVIPEmotes)) : renderEmotes(message.chat_message, senderOwnsVIPEmotes)}
                                        ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                                    </span>
                                    <span class="replied-message ${replyContainerClass}" style="position: absolute; 
                                        top: -10px; 
                                        ${isCurrentUser ? 'left: 0' : 'left: 0'}; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${isCurrentUser ? 'text-align: left' : 'text-align: left'}; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;"
                                    data-reply-id="${message.chat_replyTo}">
                                        ${renderEmotes(finalMessage, senderOwnsVIPEmotes)}
                                    </span>
                                </p>

                        `;
                    }
                } else {
                    if (previousMessage && previousMessage.chat_senderId === message.chat_senderId) {
                        let timeDifference = new Date(message.chat_date) - new Date(previousMessage.chat_date);
                        if (timeDifference <= 5 * 60 * 1000) {
                            messageContent = `
                            <p class="last-message" style="text-align: ${messagePosition}; position: relative;">
                                <span class="timestamp-hover">${formattedTime}</span>
                                <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message, senderOwnsVIPEmotes)) : renderEmotes(message.chat_message, senderOwnsVIPEmotes)}
                                ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                                </span>
                                <span class="replied-message ${replyContainerClass}" style="position: absolute; 
                                        top: -10px; 
                                        ${isCurrentUser ? 'left: 0' : 'left: 0'}; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${isCurrentUser ? 'text-align: left' : 'text-align: left'}; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;">
                                    [Message unavailable]
                                </span>
                            </p>
                            `;
                        } else {
                            // Build message with sender info and timestamp
                            messageContent = `
                                <p id="username_message" style="text-align: ${userPosition}; padding-bottom: 20px;">
                                    <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(messageUser.user_username)}"><strong class="strong_text">${messageUser.user_username}</strong></a>
                                    <span class="timestamp ${messagePosition}">${formattedDate}</span>
                                </p>
                                <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px; position: relative;">
                                    <span class="timestamp-hover">${formattedTime}</span>
                                    <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message, senderOwnsVIPEmotes)) : renderEmotes(message.chat_message, senderOwnsVIPEmotes)}
                                    ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                                    </span>
                                    <span class="replied-message ${replyContainerClass}" style="position: absolute; 
                                        top: -10px; 
                                        ${isCurrentUser ? 'left: 0' : 'left: 0'}; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${isCurrentUser ? 'text-align: left' : 'text-align: left'}; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;">
                                        [Message unavailable]
                                    </span>
                                </p>
                            `;
                        }
                    } else {
                        // Build message with sender info
                        messageContent = `
                            <p id="username_message" style="text-align: ${userPosition}; padding-bottom: 20px;">
                                <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(messageUser.user_username)}"><strong class="strong_text">${messageUser.user_username}</strong></a>
                                <span class="timestamp ${messagePosition}">${formattedDate}</span>
                            </p>
                            <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px; position: relative;">
                                <span class="timestamp-hover">${formattedTime}</span>
                                <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message, senderOwnsVIPEmotes)) : renderEmotes(message.chat_message, senderOwnsVIPEmotes)}
                                ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                                </span>
                                <span class="replied-message ${replyContainerClass}" style="position: absolute; 
                                        top: -10px; 
                                        ${isCurrentUser ? 'left: 0' : 'left: 0'}; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${isCurrentUser ? 'text-align: left' : 'text-align: left'}; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;">
                                    [Message unavailable]
                                </span>
                            </p>
                        `;
                    }                    
                }
            } else {
                if (previousMessage && previousMessage.chat_senderId === message.chat_senderId) {
                    let timeDifference = new Date(message.chat_date) - new Date(previousMessage.chat_date);
                    if (timeDifference <= 5 * 60 * 1000) {
                        messageContent = `
                        <p class="last-message" style="text-align: ${messagePosition};">
                            <span class="timestamp-hover">${formattedTime}</span>
                            <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message, senderOwnsVIPEmotes)) : renderEmotes(message.chat_message, senderOwnsVIPEmotes)}
                            ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                            </span>
                        </p>
                        `;
                    } else {
                        messageContent = `
                            <p id="username_message" style="text-align: ${userPosition};">
                                <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(messageUser.user_username)}"><strong class="strong_text">${messageUser.user_username}</strong></a>
                                <span class="timestamp ${messagePosition}">${formattedDate}</span>
                            </p>
                            <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px;">
                                <span class="timestamp-hover">${formattedTime}</span>
                                <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message, senderOwnsVIPEmotes)) : renderEmotes(message.chat_message, senderOwnsVIPEmotes)}
                                ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                                </span>
                            </p>
                        `;
                    }
                } else {
                    messageContent = `
                        <p id="username_message" style="text-align: ${userPosition};">
                            <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(messageUser.user_username)}"><strong class="strong_text">${messageUser.user_username}</strong></a>
                            <span class="timestamp ${messagePosition}">${formattedDate}</span>
                        </p>
                        <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px;">
                            <span class="timestamp-hover">${formattedTime}</span>
                            <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message, senderOwnsVIPEmotes)) : renderEmotes(message.chat_message, senderOwnsVIPEmotes)}
                            ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                            </span>
                        </p>
                    `;
                }
            }

            messageContent = messageContent.replace(/https:\/\/discord\.gg\/[a-zA-Z0-9]+/g, function(url) {
                return `<a href="${url}" target="_blank" class="discord-link">Click to join</a>`;
            });

            messageContent = processMessageContent(messageContent);
            
            messageDiv.innerHTML = messageContent;

        // **Create Hover Menu**
        let hoverMenu = document.createElement("div");
        hoverMenu.classList.add("hover-menu");
        hoverMenu.innerHTML = `<span class="menu-button">...</span>`;

        let options = document.createElement("div");
        options.classList.add("hover-options");
        options.style.display = "none"; // Initially hidden

    // Toggle menu when clicking the three dots
    hoverMenu.querySelector('.menu-button').addEventListener("click", (event) => {
        event.stopPropagation(); // Prevent the click from closing immediately
        options.style.display = options.style.display === "none" ? "block" : "none";
    });

    // Close menu when clicking anywhere outside
    document.addEventListener("click", () => {
        options.style.display = "none";
    });

    // Reply Button
    let replyButton = document.createElement("button");
    replyButton.textContent = "Reply";
    replyButton.type = "button";
    replyButton.addEventListener("click", () => replyToMessage(message.chat_id, message.chat_message, messageUser.user_username));
    options.appendChild(replyButton);

    // Delete Button (Only for Current User)
    if (isCurrentUser) {
        let deleteButton = document.createElement("button");
        deleteButton.textContent = "Delete";
        deleteButton.addEventListener("click", () => deleteMessage(message.chat_id, messageUser.user_id));
        options.appendChild(deleteButton);
    }

        hoverMenu.appendChild(options);

        // **Show/Hide on Hover**
        hoverMenu.style.display = "none";
        messageDiv.addEventListener('mouseenter', () => hoverMenu.style.display = "block");
        messageDiv.addEventListener('mouseleave', () => hoverMenu.style.display = "none");


            messagesContainer.appendChild(messageDiv);
    
            // Store the current message as previousMessage for the next iteration
            previousMessage = message;
    
            const lastMessage = messageDiv.querySelector('.last-message');
            if (lastMessage) {
                lastMessage.classList.add(timestampPosition);
                lastMessage.style.justifyContent = lastMessagePosition;
            }
    
            // Add hover behavior for timestamp
            let timestampSpan = messageDiv.querySelector('.timestamp-hover');
            if (timestampSpan) {
                timestampSpan.parentNode.insertBefore(hoverMenu, timestampSpan);
                timestampSpan.style.display = 'none';
                messageDiv.addEventListener('mouseenter', function() {
                    timestampSpan.style.display = 'inline-block';
                });
    
                messageDiv.addEventListener('mouseleave', function() {
                    timestampSpan.style.display = 'none';
                });
            }
        });

        // Add click handlers to replied message previews
        document.querySelectorAll('.replied-message').forEach(element => {
            element.addEventListener('click', function(e) {
                console.log('Clicked on replied message preview!');
                e.stopPropagation();
                const replyId = this.dataset.replyId;
                if (replyId) {
                    const originalMessage = document.getElementById(`message-${replyId}`);
                    if (originalMessage) {
                        // Smooth scroll to original message
                        originalMessage.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center' 
                        });
                        
                        // Visual feedback (optional)
                        const originalMessageSpan = originalMessage.querySelector('span.message-text');
                        originalMessageSpan.style.backgroundColor = '#ffeb3b40';
                        setTimeout(() => {
                            originalMessageSpan.style.backgroundColor = '';
                        }, 3000);
                    }
                }
            });
        });
    
        setTimeout(scrollToBottom, 100);
    }

    function processMessageContent(messageContent) {
        // Replace [img][/img] with a sanitized image URL
        messageContent = messageContent.replace(/\[img\](.*?)\[\/img\]/g, function(match, url) {
            // Sanitize the URL
            const sanitizedUrl = sanitizeUrl(url);
    
            if (sanitizedUrl) {
                // Return a safe, clickable image if the URL is valid
                return `<a href="${sanitizedUrl}" target="_blank"><img src="${sanitizedUrl}" class="chat-image" alt="Sent image"></a>`;
            } else {
                // If the URL is invalid or harmful, return an empty string or a warning placeholder
                return '<span class="invalid-url-warning">Invalid image URL</span>';
            }
        });
    
        return messageContent;
    }

    function sanitizeUrl(url) {
        const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp'];
        const urlLower = url.toLowerCase();
    
        // Check if the URL contains a valid image extension
        const isValidImage = imageExtensions.some(ext => urlLower.endsWith(ext));
    
        // If the URL is relative (i.e., doesn't start with 'http://', 'https://', or '/'), allow it
        if (isValidImage) {
            if (url.startsWith('/') || urlLower.startsWith('http://') || urlLower.startsWith('https://')) {
                return url; // Valid relative or absolute image URL
            } else {
                // Allow relative paths (no protocol)
                return `/${url}`; // Prepend a '/' to make it a valid relative URL
            }
        }
    
        return null;  // If not a valid image, return null
    }

    function replyToMessage(chatId, messageText, senderName) {
        replyPreviewContainer.style.display = "block";
        const truncatedMessage = messageText.length > 50 ? messageText.substring(0, 50) + "..." : messageText;

        let finalMessage = messageText; 
        if (messageText.includes('[img]') && messageText.includes('[/img]')) {
            finalMessage = messageText.replace(/\[img\](.*?)\[\/img\]/g, 'Replying to a picture 📷');
        }
    
        replyPreviewContainer.innerHTML = `
            <div class="reply-preview-content">
                <strong>${senderName}:</strong> ${finalMessage}
                <button type="button" id="cancel-reply-btn">✖</button>
            </div>
        `;
        
        // Set a hidden field or state variable to track reply context
        chatInput.dataset.replyTo = chatId;
    
        // Attach event listener to cancel button
        let cancelReplyBtn = document.getElementById("cancel-reply-btn");
        if (cancelReplyBtn) {
            cancelReplyBtn.addEventListener("click", cancelReply);
        }
    
        // Set focus on the chat input field
        if (chatInput) {
            chatInput.focus();
        }
    }
    
    // Function to cancel reply mode
    function cancelReply() {
        replyPreviewContainer.style.display = "none";
        chatInput.dataset.replyTo = ""; // Clear reply context
    }
    
    
    // Function to replace emote codes with actual emote images
    function renderEmotes(message, ownVIPEmotes) {
        const emoteMap = {
            ':surprised-cat:': '<img src="public/images/emotes/surprised-cat.png" alt="surprised-cat" class="emote">',
            ':cat-smile:': '<img src="public/images/emotes/cat-smile.png" alt="cat-smile" class="emote">',
            ':cat-cute:': '<img src="public/images/emotes/cat-cute.png" alt="cat-cute" class="emote">',
            ':goofy-ah-cat:': '<img src="public/images/emotes/goofy-ah-cat.png" alt="goofy-ah-cat" class="emote">',
            ':cat-surprised:': '<img src="public/images/emotes/cat-surprised.png" alt="cat-surprised" class="emote">',
            ':cat-liked:': '<img src="public/images/emotes/cat-liked.png" alt="cat-liked" class="emote">',
            ':cat-sus:': '<img src="public/images/emotes/cat-sus.png" alt="cat-sus" class="emote">',
            ':cat-bruh:': '<img src="public/images/emotes/cat-bruh.png" alt="cat-bruh" class="emote">',
            ':cat-licking:': '<img src="public/images/emotes/cat-licking.png" alt="cat-licking" class="emote">',
            ':cat-laugh:': '<img src="public/images/emotes/cat-laugh.png" alt="cat-laugh" class="emote">',
            ':cat-crying:': '<img src="public/images/emotes/cat-crying.png" alt="cat-crying" class="emote">',
            ':cat-love:': '<img src="public/images/emotes/cat-love.png" alt="cat-love" class="emote">',
        };

        
        if (ownVIPEmotes) {
            emoteMap[':urpe-stonks:'] = '<img src="public/images/emotes/urpe-stonks.png" alt="urpe-stonks" class="emote">';
            emoteMap[':vipurpe-stonks:'] = '<img src="public/images/emotes/urpe-stonks.png" alt="urpe-stonks" class="emote">';
            emoteMap[':urpe-cry:'] = '<img src="public/images/emotes/urpe-cry.png" alt="urpe-cry" class="emote">';
            emoteMap[':vipurpe-cry:'] = '<img src="public/images/emotes/urpe-cry.png" alt="urpe-cry" class="emote">';
            emoteMap[':urpe-sip:'] = '<img src="public/images/emotes/urpe-sip.png" alt="urpe-sip" class="emote">';
            emoteMap[':vipurpe-sip:'] = '<img src="public/images/emotes/urpe-sip.png" alt="urpe-sip" class="emote">';
            emoteMap[':urpe-jesus:'] = '<img src="public/images/emotes/urpe-jesus.png" alt="urpe-jesus" class="emote">';
            emoteMap[':vipurpe-jesus:'] = '<img src="public/images/emotes/urpe-jesus.png" alt="urpe-jesus" class="emote">';
            emoteMap[':urpe-hype:'] = '<img src="public/images/emotes/urpe-hype.png" alt="urpe-hype" class="emote">';
            emoteMap[':vipurpe-hype:'] = '<img src="public/images/emotes/urpe-hype.png" alt="urpe-hype" class="emote">';
            emoteMap[':urpe-hide:'] = '<img src="public/images/emotes/urpe-hide.png" alt="urpe-hide" class="emote">';
            emoteMap[':vipurpe-hide:'] = '<img src="public/images/emotes/urpe-hide.png" alt="urpe-hide" class="emote">';
            emoteMap[':urpe-heart:'] = '<img src="public/images/emotes/urpe-heart.png" alt="urpe-heart" class="emote">';
            emoteMap[':vipurpe-heart:'] = '<img src="public/images/emotes/urpe-heart.png" alt="urpe-heart" class="emote">';
            emoteMap[':urpe-dead:'] = '<img src="public/images/emotes/urpe-dead.png" alt="urpe-dead" class="emote">';
            emoteMap[':vipurpe-dead:'] = '<img src="public/images/emotes/urpe-dead.png" alt="urpe-dead" class="emote">';
            emoteMap[':urpe-blush:'] = '<img src="public/images/emotes/urpe-blush.png" alt="urpe-blush" class="emote">';
            emoteMap[':vipurpe-blush:'] = '<img src="public/images/emotes/urpe-blush.png" alt="urpe-blush" class="emote">';
            emoteMap[':urpe-blanket:'] = '<img src="public/images/emotes/urpe-blanket.png" alt="urpe-blanket" class="emote">';
            emoteMap[':vipurpe-blanket:'] = '<img src="public/images/emotes/urpe-blanket.png" alt="urpe-blanket" class="emote">';
            emoteMap[':urpe-cool:'] = '<img src="public/images/emotes/urpe-cool.png" alt="urpe-cool" class="emote">';
            emoteMap[':vipurpe-cool:'] = '<img src="public/images/emotes/urpe-cool.png" alt="urpe-cool" class="emote">';
            emoteMap[':urpe-eat:'] = '<img src="public/images/emotes/urpe-eat.png" alt="urpe-eat" class="emote">';
            emoteMap[':vipurpe-eat:'] = '<img src="public/images/emotes/urpe-eat.png" alt="urpe-eat" class="emote">';
            emoteMap[':urpe-notstonks:'] = '<img src="public/images/emotes/urpe-notstonks.png" alt="urpe-notstonks" class="emote">';
            emoteMap[':vipurpe-notstonks:'] = '<img src="public/images/emotes/urpe-notstonks.png" alt="urpe-notstonks" class="emote">';
            emoteMap[':urpe-madaf:'] = '<img src="public/images/emotes/urpe-madaf.png" alt="urpe-madaf" class="emote">';
            emoteMap[':vipurpe-madaf:'] = '<img src="public/images/emotes/urpe-madaf.png" alt="urpe-madaf" class="emote">';
            emoteMap[':urpe-sad:'] = '<img src="public/images/emotes/urpe-sad.png" alt="urpe-sad" class="emote">';
            emoteMap[':vipurpe-sad:'] = '<img src="public/images/emotes/urpe-sad.png" alt="urpe-sad" class="emote">';
            emoteMap[':urpe-run:'] = '<img src="public/images/emotes/urpe-run.png" alt="urpe-run" class="emote">';
            emoteMap[':vipurpe-run:'] = '<img src="public/images/emotes/urpe-run.png" alt="urpe-run" class="emote">';
        }
    
        const replacedMessage = message.replace(/:\w+(-\w+)*:/g, function(match) {
            return emoteMap[match] || match;
        });
    
        return replacedMessage;
    }

    function deleteMessage(chatId, userId) {
        const token = localStorage.getItem('masterTokenWebsite');
        fetch('/deleteMessageWebsite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Authorization': `Bearer ${token}`,
            },
            body: `userId=${encodeURIComponent(userId)}&chatId=${encodeURIComponent(chatId)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fetchMessages(userId, friendId); // Reload messages after deletion
            } else {
                console.error('Error deleting message:', data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error (accepted requests):', error);
        });
    }

    async function showFriendInfo(friend) {
        if (!friend) return;

        const isSameFriend = friend.user_username === currentFriendUsername;
        const hasSameStatus = friend.user_isOnline === lastFriendStatus;

        if (isSameFriend && hasSameStatus) return;

        if (isSameFriend && !hasSameStatus) {
            const statusSpan = document.querySelector("#friendTop .online-status, #friendTop .offline-status, #friendTop .looking-game-status");
            if (statusSpan) {
                statusSpan.className = friend.user_isOnline === 1 ? "online-status" : "offline-status";
            }
            if (friend.user_isOnline === 0) {
                const statusGame = document.querySelector(".ingame-status");
                if (statusGame) {
                    statusGame.remove();
                }
            }
            lastFriendStatus = friend.user_isOnline;
            return;
        }
    
        friendIdElement.value = friend.user_id;
        const usernameFriend = document.getElementById("message_text");
        usernameFriend.placeholder = `Talk to @${friend.user_username}`;
        currentFriendUsername = friend.user_username;
    
        const pictureLink = friend.user_picture
            ? `upload/${friend.user_picture}`
            : "images/defaultprofilepicture.jpg";
    
        let friendGameStatus = false;
        let friendLeagueStatus = null;
        let gamemode = '';
    
        if (friend.lol_verified === 1) {

            checkIfUsersPlayedTogether(friend.user_id, userId);
            friendLeagueStatus = await getGameStatusLoL(friend.user_id);
            if (friendLeagueStatus) {
                friendGameStatus = true;
                gamemode = friendLeagueStatus.gameMode === 'CHERRY' ? 'ARENA' : friendLeagueStatus.gameMode;
            }
        }
    
        // Build the entire friend card as a DOM element for easier referencing
        if (!friendData) {
            friendData = document.createElement("div");
            friendData.id = "friendData";
            document.body.appendChild(friendData);
        }
    
        const container = document.createElement("div");
        container.id = "friendTop";
    
        const userInfo = document.createElement("span");
        userInfo.style.width = "80%";
        userInfo.style.display = "flex";
        userInfo.style.gap = "5px";
        userInfo.innerHTML = `
            <img class="avatar" src="public/${pictureLink}" alt="Avatar ${friend.user_username}">
            <a class="username_chat_friend" target="_blank" href="/anotherUser&username=${encodeURIComponent(friend.user_username)}">
                <strong class="strong_text">${friend.user_username}</strong>
            </a>
        `;
        container.appendChild(userInfo);
    
        // League of Legends section with copy interaction
        if (friend.lol_verified === 1) {
            const lolSection = document.createElement("span");
            lolSection.className = "friend-details-top";
    
            const lolLogo = document.createElement("img");
            lolLogo.src = "public/images/lol-logo.png";
            lolLogo.alt = "League of Legends";
    
            const lolUsername = document.createElement("p");
            lolUsername.className = "friends-lol-username";
            lolUsername.dataset.username = friend.lol_account;
            lolUsername.innerHTML = `${friend.lol_account} <i class="fa-solid fa-copy"></i>`;
    
            // 👇 Attach the copy functionality here
            lolUsername.addEventListener("click", () => {
                const username = friend.lol_account;
                const temp = document.createElement("textarea");
                temp.value = username;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand("copy");
                document.body.removeChild(temp);
    
                const copyIcon = lolUsername.querySelector('.fa-copy');
                if (copyIcon) {
                    copyIcon.classList.add('visible');
                    setTimeout(() => copyIcon.classList.remove('visible'), 1000);
                }
            });
    
            lolSection.appendChild(lolLogo);
            lolSection.appendChild(lolUsername);
            container.appendChild(lolSection);
        }
    
        // Game status
        if (friendGameStatus) {
            const status = document.createElement("span");
            status.className = "ingame-status";
            status.innerText = `🎮 Playing ${friendLeagueStatus.champion} (${gamemode})`;
            container.appendChild(status);
        }
    
        // Online/Offline status
        const statusSpan = document.createElement("span");
        if (friend.user_isOnline === 1) {
            statusSpan.className = friend.user_isLooking === 1 ? "looking-game-status" : "online-status";
        } else {
            statusSpan.className = "offline-status";
        }
        container.appendChild(statusSpan);
    
        // Render
        friendData.innerHTML = '';
        friendData.appendChild(container);
    
        // Message container setup
        let messagesContainer = document.getElementById("messages");
        if (!messagesContainer) {
            messagesContainer = document.createElement("div");
            messagesContainer.id = "messages";
            document.body.appendChild(messagesContainer);
        }
    
        messagesContainer.innerHTML = '';
        messagesContainer.style.minHeight = 'calc(var(--vh, 1vh) * 65)';
    }
    
    

    // Function to scroll to the bottom of the messages container
    function scrollToBottom() {
        const messagesContainer = document.getElementById("messages");
        if (!messagesContainer) return;
    
        // Smooth scroll to bottom
        messagesContainer.scrollTo({
            top: messagesContainer.scrollHeight,
            behavior: 'auto'
        });
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

    export function clearImageTrue() {
        clearImageVar = true;
        console.log("clearImageVar set to true");
    }
    
    export function clearImageFalse() {
        clearImageVar = false;
        console.log("clearImageVar set to false");
    }

    function checkIfUsersPlayedTogether(friendId, userId) {
        const token = localStorage.getItem('masterTokenWebsite');
        fetch('/checkIfUsersPlayedTogether', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Authorization': `Bearer ${token}`,
            },
            body: `userId=${encodeURIComponent(parseInt(userId))}&friendId=${encodeURIComponent(parseInt(friendId))}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.playedTogether) {
                handleRatingPrompt(friendId, data.commonMatches);
            }
        })
        .catch(error => console.error('Fetch error:', error));
    }

function handleRatingPrompt(friendId, commonMatches) {
    const ratingData = JSON.parse(localStorage.getItem('ratingData') || '{}');
    const friendKey = `friendId_${friendId}`;
    const friendData = ratingData[friendKey] || { ratedMatches: {}, lastRatingTime: 0 };
    const oneWeek = 7 * 24 * 60 * 60 * 1000;
    const now = Date.now();

    // Don't ask if rated in the last week
    if (now - friendData.lastRatingTime < oneWeek) return;

    // Find a matchId that hasn't been rated yet
    const newMatchId = commonMatches.find(id => !friendData.ratedMatches[id]);
    if (!newMatchId) return;

    showRatingModal(friendId, newMatchId);
}

function showRatingModal(friendId, matchId) {
    RatingModal.classList.remove('rating-modal-hidden');
    document.getElementById("overlay").style.display = "block";
    submitRating.setAttribute('data-friend-id', friendId);
    submitRating.setAttribute('data-match-id', matchId);
}

export function closeRatingModal(type) {
    RatingModal.classList.add('hidden');
    document.getElementById("overlay").style.display = "none";

    const friendId = submitRating.getAttribute('data-friend-id');
    const friendKey = `friendId_${friendId}`;
    let ratingData = JSON.parse(localStorage.getItem('ratingData') || '{}');
    const friendData = ratingData[friendKey] || { ratedMatches: {}, lastRatingTime: 0 };

    // Only update if not a successful rating
    if (type !== 'success') {
        friendData.lastRatingTime = Date.now();
        ratingData[friendKey] = friendData;
        localStorage.setItem('ratingData', JSON.stringify(ratingData));
    }
}

export function sendRating() {
    const friendId = submitRating.getAttribute('data-friend-id');
    const matchId = submitRating.getAttribute('data-match-id');
    const rating = document.getElementById('rating-score').value;
    const token = localStorage.getItem('masterTokenWebsite');

    fetch('/rateFriendWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: `friendId=${encodeURIComponent(friendId)}&matchId=${encodeURIComponent(matchId)}&rating=${encodeURIComponent(rating)}&userId=${encodeURIComponent(userId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let ratingData = JSON.parse(localStorage.getItem('ratingData') || '{}');
            const friendKey = `friendId_${friendId}`;
            const friendData = ratingData[friendKey] || { ratedMatches: {}, lastRatingTime: 0 };

            friendData.ratedMatches[matchId] = rating;
            friendData.lastRatingTime = Date.now();

            ratingData[friendKey] = friendData;
            localStorage.setItem('ratingData', JSON.stringify(ratingData));

            closeRatingModal('success');
        } else {
            console.error('Rating failed:', data.error);
        }
    })
    .catch(error => console.error('Fetch error:', error));
}

    