// Variables
let userIdElementHeader = document.getElementById('userId');
let userIdHeader = userIdElementHeader ? userIdElementHeader.value : null;
let originalTitle = document.title;
let originalTitleNoChange = document.title;
const token = localStorage.getItem('masterTokenWebsite');
let globalUnreadCounts = {};

// Fonction pour récupérer les demandes d'ami en attente
function fetchFriendRequest(userId) {
    fetch('index.php?action=getFriendRequestWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(userId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.pendingCount) {
            console.log('Friend Request fetched successfully');
            fillPendingFriendRequest(data.pendingCount);
        } else {
            console.log('No friend requests found');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });
}

// Function to fetch unread messages for the main user
function fetchUnreadMessage(userId) {
    const servedSenderIds = JSON.parse(localStorage.getItem('servedSenderIds')) || []; // Track sender IDs

    fetch('index.php?action=getUnreadMessageWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(userId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.unreadCount) {
            console.log('Unread messages fetched successfully');

            const newSenderIds = [];
            const updatedServedSenderIds = [...servedSenderIds];

            data.unreadCount.forEach((message) => {
                if (!servedSenderIds.includes(message.chat_senderId)) {
                    // Serve the notification
                    const type = 'message';
                    displayNotification(
                        `New message from ${message.user_username}: ${message.chat_message}`,
                        type,
                        message.chat_senderId,
                        message.user_picture
                    );

                    // Track sender as served
                    newSenderIds.push(message.chat_senderId);
                    updatedServedSenderIds.push(message.chat_senderId);
                }
            });

            // Update local storage with new served sender IDs
            localStorage.setItem('servedSenderIds', JSON.stringify(updatedServedSenderIds));

            // Update UI or perform other actions
            fillUnread(data.unreadCount);
            updateUnreadMessagesForFriends(data.unreadCount);

            // Clean up old sender IDs
            cleanupServedSenders(data.unreadCount.map(m => m.chat_senderId));
        } else {
            globalUnreadCounts = {};
            clearContainer();
            document.title = originalTitleNoChange;
            localStorage.removeItem('servedSenderIds');
            console.log('No unread messages or success flag not set');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });
}

function cleanupServedSenders(currentUnreadSenderIds) {
    const servedSenderIds = JSON.parse(localStorage.getItem('servedSenderIds')) || [];
    const validServedSenderIds = servedSenderIds.filter(id => currentUnreadSenderIds.includes(id));
    localStorage.setItem('servedSenderIds', JSON.stringify(validServedSenderIds));
}

function clearContainer() {
    const container = document.getElementById('unread_messages_container');
    const containerFriend = document.querySelectorAll('.unread_message');
    container.innerHTML = '';


    containerFriend.forEach(function(element) {
        element.remove();
    });
}

function fillUnread(unreadCounts) {
    const container = document.getElementById('unread_messages_container');
    if (!container) {
        console.error('Container element not found');
        return;
    }

    container.innerHTML = ''; // Clear previous content
    let count = 0;

    // Calculate the total unread message count
    unreadCounts.forEach(unreadCount => {
        if (unreadCount.unread_count > 0) {
            count += unreadCount.unread_count;
        }
    });

    if (count > 0) {
        // Create and append unread message notification
        const anchor = document.createElement('a');
        anchor.href = '/persoChat';

        const span = document.createElement('span');
        span.className = 'pending-count-header';

        const button = document.createElement('button');
        button.title = 'Unread Message';
        button.id = 'unread_message';
        button.textContent = count;

        span.appendChild(button);
        anchor.appendChild(span);
        container.appendChild(anchor);

        // Update the title with unread messages count
        document.title = count === 1
            ? `1 New message - ${originalTitle}`
            : `${count} New messages - ${originalTitle}`;
    } else {
        // No unread messages: reset title to original
        document.title = originalTitleNoChange;
    }
}


// Fonction pour mettre à jour les notifications non lues pour chaque ami
function updateUnreadMessagesForFriends(unreadCounts) {
    // Update global unread counts
    unreadCounts.forEach(unreadCount => {
        globalUnreadCounts[unreadCount.chat_senderId] = unreadCount.unread_count;
    });

    // Update currently visible friends
    const friendElements = document.querySelectorAll('.friend');

    friendElements.forEach(friendElement => {
        const friendId = friendElement.getAttribute('data-sender-id');
        const friendContainer = document.getElementById(`unread_messages_for_friend_container_${friendId}`);
        if (!friendContainer) {
            console.log(`friendContainer with ID ${friendId} not found`);
            return;
        }

        friendContainer.innerHTML = '';

        const unreadCount = globalUnreadCounts[friendId] || 0;
        if (unreadCount > 0) {
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
}



// Fonction pour remplir les demandes d'ami en attente
function fillPendingFriendRequest(pendingCount) {
    const container = document.getElementById('pending_friend_requests_container');
    container.innerHTML = ''; // Efface le contenu précédent

    if (pendingCount.pendingFriendRequest > 0) {
        const anchor = document.createElement('a');
        anchor.href = '/userProfile';

        const span = document.createElement('span');
        span.className = 'pending-count';

        const button = document.createElement('button');
        button.title = 'Friend requests';
        button.id = 'unread_message_header';
        button.textContent = pendingCount.pendingFriendRequest;

        span.appendChild(button);
        anchor.appendChild(span);
        container.appendChild(anchor);
    }
}

// Fonction pour récupérer les mises à jour périodiques
function fetchUpdates() {
    fetchFriendRequest(userIdHeader);
    fetchUnreadMessage(userIdHeader);
}

// Démarrer les mises à jour périodiques au chargement de la page
document.addEventListener("DOMContentLoaded", function() {
    fetchUpdates();
    setTimeout(fetchUpdates, 1000)
    setInterval(fetchUpdates, 20000); // Rafraîchir toutes les 20 secondes (20000 ms)
});