let userId = document.getElementById('userId').value;
let currentPage = 1;
const itemsPerPage = 10; 
let totalFriends = 0;
let showOnlineOnly = localStorage.getItem('showOnlineFriends') === 'true';

// Fetch and render friend list
function getFriendList(userId, page = 1) {
    const token = localStorage.getItem('masterTokenWebsite');
    fetch('index.php?action=getFriendlistWebsite', {
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
                totalFriends = data.friendlist.length;
                let filteredFriends = data.friendlist;

                if (showOnlineOnly) {
                    filteredFriends = filteredFriends.filter(friend => friend.friend_online === 1);
                }

                renderFriendList(filteredFriends, page);
                setupPagination(filteredFriends.length, itemsPerPage);
            } else {
                console.error('Error fetching messages:', data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
}

// Render the friend list dynamically
function renderFriendList(friendList, page) {
    const startIndex = (page - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, friendList.length);
    const paginatedFriends = friendList.slice(startIndex, endIndex);

    const friendListContainer = document.getElementById('friendList');
    friendListContainer.innerHTML = '';

    paginatedFriends.forEach(friend => {
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
        img.loading = 'lazy'; // Lazy loading images
        avatarDiv.appendChild(img);

        const detailsDiv = document.createElement('div');
        detailsDiv.className = 'friend-details';

        const chatNameSpan = document.createElement('span');
        chatNameSpan.className = 'chat-name clickable';
        chatNameSpan.innerHTML = `${friend.friend_username} <span id="unread_messages_for_friend_container_${friend.friend_id}"></span>`;

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

// Set up pagination
function setupPagination(totalItems, itemsPerPage) {
    const paginationContainer = document.getElementById('pagination');
    paginationContainer.innerHTML = '';
    const totalPages = Math.ceil(totalItems / itemsPerPage);

    // First Button
    const firstButton = document.createElement('button');
    firstButton.className = 'pagination-button';
    firstButton.textContent = '<<';
    firstButton.disabled = currentPage === 1;
    firstButton.addEventListener('click', () => {
        currentPage = 1;
        getFriendList(userId, currentPage);
    });
    paginationContainer.appendChild(firstButton);

    // Previous Button
    const prevButton = document.createElement('button');
    prevButton.className = 'pagination-button';
    prevButton.textContent = '<';
    prevButton.disabled = currentPage === 1;
    prevButton.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            getFriendList(userId, currentPage);
        }
    });
    paginationContainer.appendChild(prevButton);

    // Page Buttons
    const maxVisiblePages = 3;
    const startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    const endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    for (let i = startPage; i <= endPage; i++) {
        const pageButton = document.createElement('button');
        pageButton.textContent = i;
        pageButton.className = 'pagination-button';
        if (i === currentPage) pageButton.classList.add('active');

        pageButton.addEventListener('click', () => {
            currentPage = i;
            getFriendList(userId, currentPage);
        });

        paginationContainer.appendChild(pageButton);
    }

    // Next Button
    const nextButton = document.createElement('button');
    nextButton.className = 'pagination-button';
    nextButton.textContent = '>';
    nextButton.disabled = currentPage === totalPages;
    nextButton.addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            getFriendList(userId, currentPage);
        }
    });
    paginationContainer.appendChild(nextButton);

    // Last Button
    const lastButton = document.createElement('button');
    lastButton.className = 'pagination-button';
    lastButton.textContent = '>>';
    lastButton.disabled = currentPage === totalPages;
    lastButton.addEventListener('click', () => {
        currentPage = totalPages;
        getFriendList(userId, currentPage);
    });
    paginationContainer.appendChild(lastButton);
}

// Periodic updates for online status
setInterval(() => {
    getFriendList(userId, currentPage);
}, 30000); // Update every 30 seconds

document.addEventListener('DOMContentLoaded', function () {
    const toggleOnlineOnly = document.getElementById('toggleOnlineOnly');
    if (toggleOnlineOnly) {
        toggleOnlineOnly.checked = showOnlineOnly; 
        toggleOnlineOnly.addEventListener('change', () => {
            showOnlineOnly = toggleOnlineOnly.checked;
            localStorage.setItem('showOnlineFriends', showOnlineOnly); 
            currentPage = 1;
            getFriendList(userId, currentPage);
        });
    }
    getFriendList(userId, currentPage);
});
