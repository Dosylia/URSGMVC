let userId = document.getElementById('userId').value;
let currentPage = 1;
const itemsPerPage = 10; 
let totalFriends = 0;
let showOnlineOnly = localStorage.getItem('showOnlineFriends') === 'true';
let refreshMode = false;

// Fetch and render friend list
function getFriendList(userId, page = 1) {
    const token = localStorage.getItem('masterTokenWebsite');

    if (page !== currentPage || showOnlineOnly) {
        refreshMode = false;
        currentPage = page;
    }
    
    fetch('/getFriendlistWebsite', {
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
                if (refreshMode) {
                    updateOnlineStatus(data.friendlist);
                } else {
                    refreshMode = true;
                    totalFriends = data.friendlist.length;
                    let filteredFriends = data.friendlist;

                    if (showOnlineOnly) {
                        filteredFriends.sort((a, b) => b.friend_online - a.friend_online);
                    }

                    renderFriendList(filteredFriends, page);
                    setupPagination(filteredFriends.length, itemsPerPage);
                }
            } else {
                console.error('Error fetching friends:', data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
}

function getGameStatusLoL(friendId) {
    fetch('/getGameStatusLoL', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `friendId=${encodeURIComponent(friendId)}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Game status:', data.status);
                return data;
            } else {
                console.error('Error fetching game status:', data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
}

function updateOnlineStatus(friendList) {
    friendList.forEach(friend => {
        const friendElement = document.querySelector(`[data-friend-id="${friend.friend_id}"]`);
        if (!friendElement) return;
        if (friendElement) {
            const onlineStatus = friendElement.querySelector('.online-status');
            const lookingForGame = friendElement.querySelector('.looking-game-status');

            // Clear old status
            if (onlineStatus) onlineStatus.remove();
            if (lookingForGame) lookingForGame.remove();

            // Update new status
            if (friend.friend_online === 1 && friend.friend_isLookingGame === 1) {
                const newLookingForGame = document.createElement('span');
                newLookingForGame.className = 'looking-game-status';
                friendElement.querySelector('.chat-name').appendChild(newLookingForGame);
            } else if (friend.friend_online === 1) {
                const newOnlineStatus = document.createElement('span');
                newOnlineStatus.className = 'online-status';
                friendElement.querySelector('.chat-name').appendChild(newOnlineStatus);
            }
        }
    });
}

// Render the friend list dynamically
async function renderFriendList(friendList, page) {
    const loadingIndicator = document.getElementById('loading-indicator');
    const friendListContainer = document.getElementById('friendList');

    // Show loading indicator
    if (loadingIndicator) {
        loadingIndicator.style.display = 'block';
    }

    try {
        // Simulating a delay (remove this in actual code)
        await new Promise(resolve => setTimeout(resolve, 500));

        // Clear friend list
        friendListContainer.innerHTML = '';

        const startIndex = (page - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, friendList.length);
        const paginatedFriends = friendList.slice(startIndex, endIndex);

        if (paginatedFriends.length > 0) {
            const firstFriend = paginatedFriends[0];
            const lookingForButton = document.getElementById('looking-for-button');

            if (firstFriend.friend_isLookingGameUser === 1) {
                lookingForButton.style.background = "linear-gradient(45deg, #4CAF50, #66bb6a)";
            } else {
                lookingForButton.style.background = "linear-gradient(135deg, #722084, #b026cf)";
            }
        }

        paginatedFriends.forEach(friend => {
            const friendLeagueStatus = getGameStatusLoL(friend.friend_id);

            if (friendLeagueStatus) {
                console.log('Friend League Status:', friendLeagueStatus);
            }

            const friendElement = document.createElement('a');
            friendElement.className = "username_chat_friend clickable";
            friendElement.href = "#";
            friendElement.dataset.friendId = friend.friend_id;

            const friendDiv = document.createElement('div');
            friendDiv.className = 'friend';
            friendDiv.dataset.senderId = friend.friend_id;

            const avatarDiv = document.createElement('div');
            avatarDiv.className = 'friend-avatar';

            const img = document.createElement('img');
            img.src = friend.friend_picture ? `public/upload/${friend.friend_picture}` : 'public/images/defaultprofilepicture.jpg';
            img.alt = `Avatar ${friend.friend_username}`;
            img.loading = 'lazy';
            avatarDiv.appendChild(img);

            const detailsDiv = document.createElement('div');
            detailsDiv.className = 'friend-details';

            const chatNameSpan = document.createElement('span');
            chatNameSpan.className = 'chat-name clickable';
            chatNameSpan.innerHTML = `${friend.friend_username} <span id="unread_messages_for_friend_container_${friend.friend_id}"></span>`;

            if (friend.friend_online === 1 && friend.friend_isLookingGame === 1) {
                const lookingForGame = document.createElement('span');
                lookingForGame.className = 'looking-game-status';
                chatNameSpan.appendChild(lookingForGame);
            } else if (friend.friend_online === 1) {
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
            friendListContainer.style.display = 'block';

            // Update unread counts for this friend
            const unreadCount = globalUnreadCounts[friend.friend_id] || 0;
            const friendContainer = document.getElementById(`unread_messages_for_friend_container_${friend.friend_id}`);
            if (unreadCount > 0 && friendContainer) {
                const span = document.createElement('span');
                span.className = 'unread-count';
                span.style.marginLeft = '10px';

                const button = document.createElement('button');
                button.className = 'unread_message';
                button.textContent = unreadCount;

                span.appendChild(button);
                friendContainer.appendChild(span);
            }
        });

        // Hide loading indicator after successful rendering
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
            loadingIndicator.remove();
        }
    } catch (error) {
        console.error("Error loading friend list:", error);

        // If something went wrong, keep the loading indicator visible
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
            friendListContainer.style.display = 'block';
        }
    }
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
        refreshMode = false;
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
            refreshMode = false;
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
            refreshMode = false;
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
            refreshMode = false;
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
        refreshMode = false;
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
            refreshMode = false;
            getFriendList(userId, currentPage); 
        });
    }

    getFriendList(userId, currentPage);
});
