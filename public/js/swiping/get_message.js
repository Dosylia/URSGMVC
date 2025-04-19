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
let replyPreviewContainer = document.getElementById("reply-preview");
let chatInput = document.getElementById("message_text");
document.addEventListener("DOMContentLoaded", function () {
    // Gestion du clic sur les amis pour charger les messages
    document.addEventListener("click", function (event) {
        let link = event.target.closest(".username_chat_friend");
    
        // If the clicked element is a link with a valid href (not just '#'), allow navigation
        if (link && link.getAttribute("href") && link.getAttribute("href") !== "#") {
            return; // Allow the link to navigate normally
        }
    
        // Otherwise, handle custom logic (e.g., changing the chat)
        if (!link) return;
    
        event.preventDefault(); // Prevent the default behavior for non-navigational links
    
        let newFriendId = link.getAttribute("data-friend-id");
        console.log("New friend ID:", newFriendId);

        const isMax1018px = window.matchMedia("(max-width: 1018px)").matches;

        // Always check screen size after updating messages
        if (isMax1018px) {
            if (chatInterface && window.getComputedStyle(messageContainer).display === 'none') {
                chatInterface.style.display = 'none';
                messageContainer.style.display = 'block';
            }
        }
    
        if (newFriendId !== friendId) {
            const modalDiscord = document.getElementById('confirmationModalDiscord');
            modalDiscord.style.display = 'none'; // Hide the modal
            friendId = newFriendId; // Update the recipient ID
            replyPreviewContainer.style.display = "none"; // Hide the reply preview
            chatInput.dataset.replyTo = ""; // Clear the reply context
            let messageInput = document.getElementById("message_text");
            const username = messageInput.dataset.username;
            const previewContainer = document.getElementById('imagePreviewContainer');
            previewContainer.innerHTML = ''; // Clear the preview container
            messageInput.value = '';
            messageInput.placeholder = 'Talk to @' + username;
            fetchMessages(userId, friendId); // Load new messages
        }
    });
    

    // Vérifier si `userId` est défini avant de récupérer les messages
    if (typeof userId !== "undefined" && userId !== null) {
        fetchMessages(userId, friendId); // Chargement initial
        setInterval(() => fetchMessages(userId, friendId), 5000); // Rafraîchissement auto toutes les 5s
    }

    // Initialisation des variables CSS pour le responsive
    setVhVariable();
    checkScreenSize();

    // Mettre à jour lors d'un redimensionnement
    window.addEventListener("resize", () => {
        setVhVariable();
        checkScreenSize();
    });
})


    // Show loading indicator
    function showLoadingIndicator() {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = '<p>Loading messages...</p>';
    }

    // Function to fetch messages
    export function fetchMessages(userId, friendId) {
        const token = localStorage.getItem('masterTokenWebsite');
        const firstFriendInput = document.getElementById('firstFriend');
        const firstFriend = firstFriendInput ? firstFriendInput.value : null;


        if (firstFriend && friendId !== firstFriendId) {
            firstFriendInput.value = "no";
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
                if (data.messages !== null && data.messages !== undefined) {
                    if (JSON.stringify(currentMessages) !== JSON.stringify(data.messages)) {
                        currentMessages = data.messages;
                        showFriendInfo(data.friend).then(() => {
                            updateMessageContainer(data.messages, data.friend, data.user);
                        });
                    } else {
                        console.log('No new messages. No update needed.');
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
            console.error('Fetch or JSON parse error:', error);
        
            // Retry only for temporary issues (not "Friend not found", etc.)
            if (!error.message.includes('Friend not found') && !error.message.includes('User not found')) {
                setTimeout(() => fetchMessages(userId, friendId), 5000);
            } else {
                console.warn('Not retrying due to invalid user/friend.');
            }
        });        
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
                                <span class="message-text" style="text-align: ${messagePosition};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message)) : renderEmotes(message.chat_message)}
                                ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                                </span>
                                <span class="replied-message ${timestampPosition}" style="position: absolute; 
                                        top: 5px; 
                                        ${isCurrentUser ? 'right: 0' : 'left: 0'}; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${isCurrentUser ? 'text-align: right' : 'text-align: left'}; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;
                                    ">
                                        ${finalMessage}
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
                                    <span class="message-text" style="text-align: ${messagePosition};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message)) : renderEmotes(message.chat_message)}
                                    ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                                    </span>
                                    <span class="replied-message ${timestampPosition}" style="position: absolute; 
                                        top: -10px; 
                                        ${isCurrentUser ? 'right: 0' : 'left: 0'}; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${isCurrentUser ? 'text-align: right' : 'text-align: left'}; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;
                                    ">
                                        ${finalMessage}
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
                                    <span class="message-text" style="text-align: ${messagePosition};">
                                        ${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message)) : renderEmotes(message.chat_message)}
                                        ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                                    </span>
                                    <span class="replied-message ${timestampPosition}" style="position: absolute; 
                                        top: -10px; 
                                        ${isCurrentUser ? 'right: 0' : 'left: 0'}; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${isCurrentUser ? 'text-align: right' : 'text-align: left'}; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;
                                    ">
                                        ${finalMessage}
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
                                <span class="message-text" style="text-align: ${messagePosition};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message)) : renderEmotes(message.chat_message)}
                                ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                                </span>
                                <span class="replied-message ${timestampPosition}" style="position: absolute; 
                                        top: -10px; 
                                        ${isCurrentUser ? 'right: 0' : 'left: 0'}; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${isCurrentUser ? 'text-align: right' : 'text-align: left'}; 
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
                                    <span class="message-text" style="text-align: ${messagePosition};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message)) : renderEmotes(message.chat_message)}
                                    ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                                    </span>
                                    <span class="replied-message ${timestampPosition}" style="position: absolute; 
                                        top: -10px; 
                                        ${isCurrentUser ? 'right: 0' : 'left: 0'}; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${isCurrentUser ? 'text-align: right' : 'text-align: left'}; 
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
                                <span class="message-text" style="text-align: ${messagePosition};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message)) : renderEmotes(message.chat_message)}
                                ${isCurrentUser ? `<span class="message-status">${messageStatus}</span>` : ""}
                                </span>
                                <span class="replied-message ${timestampPosition}" style="position: absolute; 
                                        top: -10px; 
                                        ${isCurrentUser ? 'right: 0' : 'left: 0'}; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${isCurrentUser ? 'text-align: right' : 'text-align: left'}; 
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
                            <span class="message-text" style="text-align: ${messagePosition};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message)) : renderEmotes(message.chat_message)}
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
                                <span class="message-text" style="text-align: ${messagePosition};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message)) : renderEmotes(message.chat_message)}
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
                            <span class="message-text" style="text-align: ${messagePosition};">${user.user_hasChatFilter ? renderEmotes(chatfilter(message.chat_message)) : renderEmotes(message.chat_message)}
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
    
        console.log('Messages container updated. Now scrolling to bottom.');
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
            ':cat-licking:': '<img src="public/images/emotes/cat-licking.png" alt="cat-licking" class="emote">',
            ':cat-laugh:': '<img src="public/images/emotes/cat-laugh.png" alt="cat-laugh" class="emote">'
        };
    
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
        
        if (friend.user_username === currentFriendUsername) {
            return;
        }
    
        friendIdElement.value = friend.user_id;
        const usernameFriend = document.getElementById("message_text");
        usernameFriend.placeholder = `Talk to @${friend.user_username}`;
        
        currentFriendUsername = friend.user_username;
    
        const pictureLink = friend.user_picture ? `upload/${friend.user_picture}` : "images/defaultprofilepicture.jpg";
    
        let friendGameStatus = false;
        let friendLeagueStatus = null;
        let gamemode = '';
    
        if (friend.lol_verified === 1) {
            friendLeagueStatus = await getGameStatusLoL(friend.user_id);
            if (friendLeagueStatus) {
                friendGameStatus = true;
                if (friendLeagueStatus.gameMode === 'CHERRY') {
                    gamemode = 'ARENA';
                } else {
                    gamemode = friendLeagueStatus.gameMode;
                }
            }
        }
    
        let friendContent = `
            <div id="friendTop">
                <span style="width: 80%;">
                    <img class="avatar" src="public/${pictureLink}" alt="Avatar ${friend.user_username}">
                    <a class="username_chat_friend" target="_blank" href="/anotherUser&username=${encodeURIComponent(friend.user_username)}"><strong class="strong_text">${friend.user_username}</strong></a>
                </span>
                ${friend.lol_verified === 1 ? `<span class="friend-details-top"><img src="public/images/lol-logo.png" alt="League of Legends"><p>${friend.lol_account}</p></span>` : ''}
                ${friendGameStatus ? `<span class="ingame-status">🎮 Playing ${friendLeagueStatus.champion} (${gamemode})</span>` : ''}
        `;
    
        if (friend.user_isOnline === 1) {
            friendContent += friend.user_isLooking === 1
                ? `<span class="looking-game-status"></span>`
                : `<span class="online-status"></span>`;
        } else {
            friendContent += `<span class="offline-status"></span>`;
        }
    
        friendContent += `</div>`;
    
        if (!friendData) {
            friendData = document.createElement("div");
            friendData.id = "friendData";
            document.body.appendChild(friendData);
        }
    
        friendData.innerHTML = friendContent;
    
        let messagesContainer = document.getElementById("messages");
    
        if (!messagesContainer) {
            messagesContainer = document.createElement("div");
            messagesContainer.id = "messages";
            document.body.appendChild(messagesContainer);
        }
    
        messagesContainer.innerHTML = '';
        messagesContainer.style.minHeight = 'calc(var(--vh, 1vh) * 60)';
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

