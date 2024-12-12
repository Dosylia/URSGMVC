import { chatInterface, messageContainer } from './get_message.js';

const buttonclose = document.getElementById('buttonSwitchChat');
const searchBar = document.getElementById('friendSearch');
let allFriends = []; // Store the full friend list in memory

if (buttonclose !== null && buttonclose !== undefined) {
    buttonclose.addEventListener('click', (event) => {
        event.preventDefault();
        if (chatInterface !== null) {
            chatInterface.style.display = 'flex';
        }
        if (messageContainer !== null) {
            messageContainer.style.display = 'none';
        }
    });
}

// Fetch the full friend list once
function fetchAllFriends(userId) {
    const token = localStorage.getItem('masterTokenWebsite');
    return fetch('index.php?action=getFriendlistWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(parseInt(userId))}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allFriends = data.friendlist; // Store the entire friend list
            } else {
                console.error('Error fetching friends:', data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
}

// Search and filter friends
function searchFriends(query) {
    const friendListContainer = document.getElementById('friendList');
    friendListContainer.innerHTML = ''; // Clear the current list

    const filteredFriends = allFriends.filter(friend => 
        friend.friend_username.toLowerCase().includes(query)
    );

    filteredFriends.forEach(friend => {
        const friendElement = document.createElement('a');
        friendElement.href = `/persoChat&friend_id=${friend.friend_id}&mark_as_read=true`;

        const friendDiv = document.createElement('div');
        friendDiv.className = 'friend';
        friendDiv.dataset.senderId = friend.friend_id;

        const avatarDiv = document.createElement('div');
        avatarDiv.className = 'friend-avatar';

        const img = document.createElement('img');
        img.src = friend.friend_picture ? `public/upload/${friend.friend_picture}` : 'public/images/defaultprofilepicture.jpg';
        img.alt = `Avatar ${friend.friend_username}`;
        avatarDiv.appendChild(img);

        const detailsDiv = document.createElement('div');
        detailsDiv.className = 'friend-details';

        const chatNameSpan = document.createElement('span');
        chatNameSpan.className = 'chat-name clickable';
        chatNameSpan.textContent = friend.friend_username;

        if (friend.friend_online === 1) {
            const onlineStatus = document.createElement('span');
            onlineStatus.className = 'online-status';
            chatNameSpan.appendChild(onlineStatus);
        }

        const gameLogo = document.createElement('img');
        gameLogo.src = friend.friend_game === 'League of Legends' ? 'public/images/lol-logo.png' : 'public/images/Valorant.png';
        gameLogo.alt = friend.friend_game;

        detailsDiv.appendChild(chatNameSpan);
        detailsDiv.appendChild(gameLogo);

        friendDiv.appendChild(avatarDiv);
        friendDiv.appendChild(detailsDiv);
        friendElement.appendChild(friendDiv);

        friendListContainer.appendChild(friendElement);
    });
}

document.addEventListener("DOMContentLoaded", function () {
    const userId = document.getElementById('userId').value;

    // Fetch all friends on load
    fetchAllFriends(userId).then(() => {
        // Attach search functionality
        searchBar.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            searchFriends(query);
        });
    });
});
