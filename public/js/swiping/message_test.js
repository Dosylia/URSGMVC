let userIdElement = document.getElementById("senderId");
let friendIdElement = document.getElementById("receiverId");
export const userId = userIdElement ? userIdElement.value : null;
export const friendId = friendIdElement ? friendIdElement.value : null;
let currentMessages = [];
let isFirstFetch = true;
let friendData = document.getElementById('friendInfo');
export const chatInterface = document.querySelector('.chat-interface');
export const messageContainer = document.querySelector('.messages-container');
import { badWordsList } from './chatFilter.js';
let eventSource;

document.addEventListener("DOMContentLoaded", function() {
    if (userId && friendId) {
        setupSSEConnection();
    }
    setVhVariable();
    window.addEventListener('resize', setVhVariable);
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);

    document.querySelectorAll('.username_chat_friend').forEach(link => {
        link.addEventListener('click', function(event) {
            window.open(this.href, '_blank');
        });
    });
});

function showLoadingIndicator() {
    let messagesContainer = document.getElementById("messages");
    messagesContainer.innerHTML = '<p>Loading messages...</p>';
}

function setupSSEConnection() {
    const token = localStorage.getItem('masterTokenWebsite');
    const firstFriend = document.getElementById('firstFriend') ? 'yes' : 'no';
    
    if (isFirstFetch) {
        showLoadingIndicator();
        isFirstFetch = false; 
    }

    // Initialize EventSource here first
    eventSource = new EventSource(
        `index.php?action=messageStream` +
        `&userId=${userId}` +
        `&friendId=${friendId}` +
        `&token=${encodeURIComponent(token)}`
    );

    // Then add event listeners
    eventSource.onerror = function(e) {
        console.error('SSE error:', e);
        if (eventSource.readyState === EventSource.CLOSED) {
            setTimeout(setupSSEConnection, 5000);
        }
    };

    eventSource.addEventListener('message', function(e) {
        try {
            const data = JSON.parse(e.data);
            if (data.messages?.length) {
                handleNewMessages(data.messages);
            } else if (data.friend) {
                showFriendInfo(data.friend);
            }
        } catch (error) {
            console.error('Message handling error:', error);
        }
    });
}

function handleNewMessages(messages) {
    const existingIds = currentMessages.map(m => m.chat_id);
    const newMessages = messages.filter(m => !existingIds.includes(m.chat_id));
    
    if (newMessages.length) {
        currentMessages = [...currentMessages, ...newMessages];
        appendNewMessages(newMessages);
    }
}

function appendNewMessages(newMessages) {
    const messagesContainer = document.getElementById("messages");
    const fragment = document.createDocumentFragment();
    
    newMessages.forEach(message => {
        const messageElement = createMessageElement(message);
        fragment.appendChild(messageElement);
    });

    messagesContainer.appendChild(fragment);
    scrollToBottom();
}

function createMessageElement(message) {
    // You need to get user/friend data from somewhere - add these:
    // const user = /* Get current user data */;
    // const friend = /* Get friend data */;
    
    const isCurrentUser = (message.chat_senderId == userId);
    const messageUser = isCurrentUser ? user : friend;
    const pictureLink = messageUser.user_picture 
        ? `upload/${messageUser.user_picture}` 
        : "images/defaultprofilepicture.jpg";

    const messageDiv = document.createElement("div");
    messageDiv.id = `message-${message.chat_id}`;
    messageDiv.classList.add("message", isCurrentUser ? 'message-from-user' : 'message-to-user');

    messageDiv.innerHTML = `
        <p id="username_message" style="text-align: ${isCurrentUser ? 'right' : 'left'}">
            <img class="avatar" src="public/${pictureLink}" alt="Avatar ${messageUser.user_username}">
            <a class="username_chat_friend clickable" target="_blank" 
               href="/${isCurrentUser ? 'userProfile' : 'anotherUser'}&username=${encodeURIComponent(messageUser.user_username)}">
               <strong class="strong_text">${messageUser.user_username}</strong>
            </a>
            <span class="timestamp">${formatMessageTime(message.chat_date)}</span>
        </p>
        <p class="last-message">
            <span class="message-text">${processMessageContent(message)}</span>
            <span class="timestamp-hover">${formatMessageTime(message.chat_date)}</span>
        </p>
    `;

    return messageDiv;
}

function formatMessageTime(utcDate) {
    const date = new Date(utcDate);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// New helper function to process content
function processMessageContent(message) {
    let content = message.chat_message;
    if (user.user_hasChatFilter) content = chatfilter(content);
    return renderEmotes(content);
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
        messagesContainer.style.minHeight = 'calc(var(--vh, 1vh) * 60)';
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

