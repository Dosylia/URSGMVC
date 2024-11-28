// Variables
let userIdElementHeader = document.getElementById('userId');
let userIdHeader = userIdElementHeader ? userIdElementHeader.value : null;
let originalTitle = document.title;

// Fonction pour récupérer les demandes d'ami en attente
function fetchFriendRequest(userId) {
    fetch('index.php?action=getFriendRequestWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
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

// Fonction pour récupérer les messages non lus pour l'utilisateur principal
function fetchUnreadMessage(userId) {
    
    fetch('index.php?action=getUnreadMessageWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `userId=${encodeURIComponent(userId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.unreadCount) {
            console.log('Message Count fetched successfully');
            fillUnread(data.unreadCount);
            updateUnreadMessagesForFriends(data.unreadCount);
        } else {
            clearContainer();
            console.log('No unread messages or success flag not set');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });
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

    unreadCounts.forEach(unreadCount => {
        if (unreadCount.unread_count > 0) {
            count += unreadCount.unread_count;
        }
    });

    if (count > 0) {
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

        if (count = 1) {
            document.title = `${count} New message - ${originalTitle}`;
        } else {
            document.title = `${count} New messages - ${originalTitle}`;
        }
    }
}

// Fonction pour mettre à jour les notifications non lues pour chaque ami
function updateUnreadMessagesForFriends(unreadCounts) {
    // Convertir unreadCounts en un objet pour un accès plus rapide
    const unreadCountsMap = {};
    unreadCounts.forEach(unreadCount => {
        unreadCountsMap[unreadCount.chat_senderId] = unreadCount.unread_count;
    });

    const friendElements = document.querySelectorAll('.friend');

    friendElements.forEach(friendElement => {
        const friendId = friendElement.getAttribute('data-sender-id');
        
        const friendContainer = document.getElementById(`unread_messages_for_friend_container_${friendId}`);
        if (!friendContainer) {
            console.log(`friendContainer avec ID ${friendId} non trouvé`);
            return;
        }

        friendContainer.innerHTML = '';

        const unreadCount = unreadCountsMap[friendId];
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