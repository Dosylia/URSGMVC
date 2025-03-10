// Variables
let userIdElementHeader = document.getElementById('userId');
let userIdHeader = userIdElementHeader ? userIdElementHeader.value : null;
let originalTitle = document.title;
let originalTitleNoChange = document.title;
const token = localStorage.getItem('masterTokenWebsite');
let globalUnreadCounts = {};
let lastNotifCount = 0;
let lastNotifContent = [];
let lastNotifCountPending = 0;
let lastNotifContentPending = [];
let AllNotifications = [];

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
        if (data.success && data.pendingRequests && data.pendingRequests.length > 0) {
            const pendingRequests = data.pendingRequests;
            const newNotifCount = pendingRequests.length;
            if (newNotifCount !== lastNotifCountPending || !arraysEqualPending(pendingRequests, lastNotifContentPending)) {
                fillNotificationCenter(pendingRequests, 'pending', userId);
                lastNotifCountPending = newNotifCount; // Update last count
                lastNotifContentPending = pendingRequests; // Update last content
            }
        } else {
            console.log('No friend requests found');
            fillNotificationCenter([], 'pending'); // **Clear UI when no pending requests exist**
            lastNotifCountPending = 0;
            lastNotifContentPending = []; // Clear last content
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });
}

function fetchAcceptedFriendRequest(userId) {
    fetch('/getAcceptedFriendRequestWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(userId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.acceptedFriendRequest) {
            const acceptedRequests = data.acceptedFriendRequest;
            const newNotifCount = acceptedRequests.length;

            // Only update UI if the count or content has changed
            if (newNotifCount !== lastNotifCount || !arraysEqual(acceptedRequests, lastNotifContent)) {
                fillNotificationCenter(acceptedRequests, 'accepted', userId);
                lastNotifCount = newNotifCount; // Update last count
                lastNotifContent = acceptedRequests; // Update last content
            }
        } else {
            if (lastNotifCount !== 0) {
                fillNotificationCenter([], 'accepted'); // Clear UI if no accepted friend requests
                lastNotifCount = 0;
                lastNotifContent = []; // Clear last content
            }
        }
    })
    .catch(error => {
        console.error('Fetch error (accepted requests):', error);
    });
}

// Helper function to compare two arrays of objects (deep comparison)
function arraysEqual(arr1, arr2) {
    if (arr1.length !== arr2.length) {
        return false;
    }
    for (let i = 0; i < arr1.length; i++) {
        if (arr1[i].fr_id !== arr2[i].fr_id || arr1[i].user_username !== arr2[i].user_username) {
            return false;
        }
    }
    return true;
}

function arraysEqualPending(arr1, arr2) {
    if (arr1.length !== arr2.length) {
        return false;
    }
    for (let i = 0; i < arr1.length; i++) {
        if (arr1[i].fr_id !== arr2[i].fr_id || arr1[i].user_username !== arr2[i].user_username) {
            return false;
        }
    }
    return true;
}

function fillNotificationCenter(notifications, type, userId) {
    console.log('Notifications:', notifications);

    const container = document.getElementById('notification-center-ctn');
    
    // Check if the bell icon already exists
    let bellIcon = document.getElementById('notification-bell');
    let notifBadge = document.getElementById('notif-badge');
    
    // If bell icon does not exist, create it and the notification badge
    if (!bellIcon) {
        bellIcon = document.createElement('i');
        bellIcon.className = 'fa-solid fa-bell';
        bellIcon.id = 'notification-bell';

        notifBadge = document.createElement('span');
        notifBadge.className = 'notif-badge';
        notifBadge.id = 'notif-badge';

        container.appendChild(bellIcon);
        container.appendChild(notifBadge);
    }

    // Check if the modal already exists, if not, create it
    let modal = document.getElementById('notif-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'notif-modal hidden';
        modal.id = 'notif-modal';

        // **Add a title to the modal**
        const modalTitle = document.createElement('h3');
        modalTitle.className = 'notif-title';
        modalTitle.textContent = 'Notifications Center'; // You can change this title
        modal.appendChild(modalTitle);

        container.appendChild(modal);
    } else {
        // Ensure the title is present (prevents duplication)
        let modalTitle = modal.querySelector('.notif-title');
        if (!modalTitle) {
            modalTitle = document.createElement('h3');
            modalTitle.className = 'notif-title';
            modalTitle.textContent = 'Notifications Center';
            modal.prepend(modalTitle);
        }
    }

    // Add notifications that are not already in the AllNotifications array
    notifications.forEach(notification => {
        // Check if notification already exists in AllNotifications based on fr_id
        const exists = AllNotifications.some(existingNotif => existingNotif.fr_id === notification.fr_id);

        if (!exists) {
            // Add new notification to the array
            AllNotifications.push(notification);

            // Create notification item
            const notifItem = document.createElement('div');
            notifItem.className = 'notif-item';
            notifItem.id = `notif-${notification.fr_id}`; // Unique ID

            // Determine the notification text based on type
            let notifText;
            if (type === 'pending') {
                notifText = `${notification.user_username} sent you a friend request`;
            } else if (type === 'accepted') {
                notifText = `${notification.user_username} accepted your friend request`;
            } else {
                notifText = `You have a new notification`;
            }

            const notifTextElement = document.createElement('span');
            notifTextElement.textContent = notifText;

            // Close button ❌ for notifications
            const closeButton = document.createElement('i');
            closeButton.className = 'fa-solid fa-times close-btn';
            closeButton.dataset.frId = notification.fr_id;
            closeButton.dataset.userId = userId;
            closeButton.dataset.type = type;

            // Append text and close button to the notification item
            notifItem.appendChild(notifTextElement);
            notifItem.appendChild(closeButton);

            modal.appendChild(notifItem);
        }
    });

    // Update the badge count
    notifBadge.textContent = AllNotifications.length;
    notifBadge.style.display = AllNotifications.length > 0 ? 'inline-block' : 'none';

    // Add event listener to toggle modal visibility
    if (!bellIcon.dataset.listenerAttached) {
        bellIcon.addEventListener('click', function () {
            console.log('Opening modal');
            modal.classList.toggle('hidden');
        });
        bellIcon.dataset.listenerAttached = 'true'; // Mark as having a listener
    }


    // Add event listener for all close buttons (using event delegation)
    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('close-btn')) {
            const frId = event.target.dataset.frId;
            const userId = event.target.dataset.userId;
            const typeBtn = event.target.dataset.type;
    
            // Prevent multiple calls for the same notification
            event.stopImmediatePropagation(); // Prevents further propagation of the event
    
            if (typeBtn === 'pending') {
                console.log("Type", typeBtn);
                updateNotificationFriendRequestPending(frId, userId); 
            } else if (typeBtn === 'accepted') {
                console.log("Type", typeBtn);
                updateNotificationFriendRequestAccepted(frId, userId); 
            } else {
                console.log('Unknown notification type');
            }
    
            // Remove the notification from the array after dismissal
            const notifIndex = AllNotifications.findIndex(existingNotif => existingNotif.fr_id === frId);
            if (notifIndex !== -1) {
                AllNotifications.splice(notifIndex, 1); 
            }
    
            // Update the notification badge count
            notifBadge.textContent = AllNotifications.length;
            notifBadge.style.display = AllNotifications.length > 0 ? 'inline-block' : 'none';
        }
    });
}

function updateNotificationFriendRequestAccepted(frId, userId, type) {
    fetch('/updateNotificationFriendRequestAcceptedWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: `frId=${encodeURIComponent(frId)}&userId=${encodeURIComponent(userId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Notification updated successfully');

            // Remove only the dismissed notification row
            const notifItem = document.getElementById(`notif-${frId}`);
            if (notifItem) notifItem.remove();

            // Update the notification count dynamically
            const notifBadge = document.getElementById('notif-badge');
            let currentCount = parseInt(notifBadge.textContent, 10) || 0;

            if (currentCount > 1) {
                notifBadge.textContent = currentCount - 1; // Decrease count
            } else {
                notifBadge.style.display = 'none'; // Hide if no notifications left

                // Hide the bell icon if no notifications are left
                const notifBell = document.getElementById('notification-bell');
                if (notifBell) {
                    notifBell.style.display = 'none'; // Hide bell icon
                }

                // Hide the modal if no notifications remain
                const modal = document.getElementById('notif-modal');
                if (modal) {
                    modal.classList.add('hidden'); // Hide the modal
                }
            }
        } else {
            console.log('Failed to update notification');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });
}

function updateNotificationFriendRequestPending(frId, userId, type) {
    fetch('/updateNotificationFriendRequestPendingWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: `frId=${encodeURIComponent(frId)}&userId=${encodeURIComponent(userId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Notification updated successfully');

            // Remove only the dismissed notification row
            const notifItem = document.getElementById(`notif-${frId}`);
            if (notifItem) notifItem.remove();

            // Update the notification count dynamically
            const notifBadge = document.getElementById('notif-badge');
            let currentCount = parseInt(notifBadge.textContent, 10) || 0;

            if (currentCount > 1) {
                notifBadge.textContent = currentCount - 1; // Decrease count
            } else {
                notifBadge.style.display = 'none'; // Hide if no notifications left

                // Hide the bell icon if no notifications are left
                const notifBell = document.getElementById('notification-bell');
                if (notifBell) {
                    notifBell.style.display = 'none'; // Hide bell icon
                }

                // Hide the modal if no notifications remain
                const modal = document.getElementById('notif-modal');
                if (modal) {
                    modal.classList.add('hidden'); // Hide the modal
                }
            }
        } else {
            console.log('Failed to update notification');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });
}

function addNewNotification(newNotification) {
    const notifBadge = document.getElementById('notif-badge');
    const notifBell = document.getElementById('notification-bell');
    const container = document.getElementById('notification-center-ctn');

    // Add the new notification
    fillAcceptedFriendRequest([newNotification], userId);

    // Check if there are any notifications before showing the bell
    if (notifBadge && notifBell) {
        let currentCount = parseInt(notifBadge.textContent, 10) || 0;

        // Show the bell icon and update the count only if there are notifications
        if (currentCount > 0) {
            notifBell.style.display = 'inline-block'; // Show bell icon
            notifBadge.textContent = currentCount + 1; // Increment count
            notifBadge.style.display = 'inline-block'; // Ensure the badge is visible
        } else {
            notifBell.style.display = 'none'; // Hide bell icon if no notifications
        }
    }
}

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
            ? `(1) ${originalTitle}`
            : `(${count}) ${originalTitle}`;
    } else {
        // No unread messages: reset title to original
        document.title = originalTitleNoChange;
    }
}


// Fonction pour mettre à jour les notifications non lues pour chaque ami
function updateUnreadMessagesForFriends(unreadCounts) {

    console.log('unread counts:', unreadCounts);    
    const newGlobalUnreadCounts = {};

    // Update with new unread counts
    unreadCounts.forEach(unreadCount => {
        newGlobalUnreadCounts[unreadCount.chat_senderId] = unreadCount.unread_count;
    });

    // Replace globalUnreadCounts with the new data
    globalUnreadCounts = newGlobalUnreadCounts;

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
    fetchAcceptedFriendRequest(userIdHeader);
    fetchFriendRequest(userIdHeader);
    fetchUnreadMessage(userIdHeader);
}

// Démarrer les mises à jour périodiques au chargement de la page
document.addEventListener("DOMContentLoaded", function() {
    fetchUpdates();
    setTimeout(fetchUpdates, 1000)
    setInterval(fetchUpdates, 20000); // Rafraîchir toutes les 20 secondes (20000 ms)

    document.addEventListener('click', function(event) {
        const modal = document.getElementById('notif-modal');
        const bell = document.getElementById('notification-bell');
    
        if (!modal.contains(event.target) && !bell.contains(event.target)) {
            modal.classList.add('hidden');
        }
    });
});