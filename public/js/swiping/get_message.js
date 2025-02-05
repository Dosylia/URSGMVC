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

document.addEventListener("DOMContentLoaded", function () {
    console.log("Script loaded");

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
        console.log("Trying to change chat");
    
        let newFriendId = link.getAttribute("data-friend-id");
        console.log("New friend ID:", newFriendId);
    
        if (newFriendId !== friendId) {
            friendId = newFriendId; // Update the recipient ID
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
        const firstFriend = document.getElementById('firstFriend') ? 'yes' : 'no';
        console.log('firstFriend:', firstFriend);
        if (isFirstFetch) {
            showLoadingIndicator();
            isFirstFetch = false; // Reset the flag after the first fetch
        }

        console.log('Fetching messages for userId:', userId, 'and friendId:', friendId);

        fetch('index.php?action=getMessageDataWebsite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Authorization': `Bearer ${token}`,
            },
            body: `userId=${encodeURIComponent(userId)}&friendId=${encodeURIComponent(friendId)}&firstFriend=${encodeURIComponent(firstFriend)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showFriendInfo(data.friend);
                // Compare the fetched messages with the current messages
                if (data.messages !== null && data.messages !== undefined) {
                    if (JSON.stringify(currentMessages) !== JSON.stringify(data.messages)) {
                        currentMessages = data.messages; // Update the current messages
                        updateMessageContainer(data.messages, data.friend, data.user);
                    } else {
                        console.log('No new messages. No update needed.');
                    }
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

            // Format for today
            const isToday = new Date(message.chat_date).toDateString() === new Date().toDateString();

            // Format time
            let formattedTime = new Date(message.chat_date).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });

            // Format full date for other days (MM/DD/YYYY or DD/MM/YYYY based on the locale)
            let formattedDate;
            if (!isToday) {
                // French date format (DD/MM/YYYY)
                formattedDate = new Date(message.chat_date).toLocaleDateString('fr-FR');
            } else {
                // "Today" format
                formattedDate = `Today ${formattedTime}`;
            }

            // Check if previous message exists and is from the same sender within 5 minutes
            if (previousMessage && previousMessage.chat_senderId === message.chat_senderId) {
                let timeDifference = new Date(message.chat_date) - new Date(previousMessage.chat_date);
                if (timeDifference <= 5 * 60 * 1000) { // 5 minutes in milliseconds
                    // Display only the message without icon, avatar, and timestamp
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
                            <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(messageUser.user_username)}"><strong class="strong_text">${messageUser.user_username}</strong></a>
                            <span class="timestamp ${messagePosition}">${formattedDate}</span>
                        </p>
                        <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px;">
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
                        <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(messageUser.user_username)}"><strong class="strong_text">${messageUser.user_username}</strong></a>
                        <span class="timestamp ${messagePosition}">${formattedDate}</span>
                    </p>
                    <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px;">
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

    function showFriendInfo(friend) {
        if (!friend) return;
    
        // If the same friend is being passed, do nothing
        if (friend.user_username === currentFriendUsername) {
            return;
        }

        friendIdElement.value = friend.user_id; // Update the hidden input value
        const usernameFriend = document.getElementById("message_text");
        usernameFriend.placeholder = `Talk to @${friend.user_username}`;
    
        currentFriendUsername = friend.user_username; // Update the stored friend
    
        const pictureLink = friend.user_picture ? `upload/${friend.user_picture}` : "images/defaultprofilepicture.jpg";
    
        // Prepare the basic friend content
        let friendContent = `
            <p id="friendTop">
                <img class="avatar" src="public/${pictureLink}" alt="Avatar ${friend.user_username}">
                <a class="username_chat_friend" target="_blank" href="/anotherUser&username=${encodeURIComponent(friend.user_username)}"><strong class="strong_text">${friend.user_username}</strong></a>
            `;
    
        // Add online status or looking-for-game status
        if (friend.user_isOnline === 1) {
            if (friend.user_isLooking === 1) {
                // If the friend is online and looking for someone
                friendContent += `<span class="looking-game-status"></span>`;
            } else {
                // If the friend is just online
                friendContent += `<span class="online-status"></span>`;
            }
        } else {
            // If the friend is offline
            friendContent += `<span class="offline-status"></span>`;
        }

        friendContent += `</p>`; // Close the <p> tag
    
        // Ensure the element exists, create it if necessary
        if (!friendData) {
            friendData = document.createElement("div");
            friendData.id = "friendData";
            document.body.appendChild(friendData); // Adjust parent container if needed
        }

        friendData.innerHTML = '';
    
        friendData.innerHTML = friendContent;
    
        let messagesContainer = document.getElementById("messages");
    
        if (!messagesContainer) {
            messagesContainer = document.createElement("div");
            messagesContainer.id = "messages";
            document.body.appendChild(messagesContainer); // Adjust parent container if needed
        }
    
        messagesContainer.innerHTML = '';
        messagesContainer.style.minHeight = 'calc(var(--vh, 1vh) * 60)';
    }

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

