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
const requestBtn = document.getElementById('requests-btn');

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
        if (data.success && data.pendingRequests) {
            // Filter out existing pending notifications
            AllNotifications = AllNotifications.filter(request => request.type !== 'pending');
            const pendingWithType = data.pendingRequests
            .filter(notif => notif.fr_notifReadPending === 0)
            .map(notif => ({ ...notif, type: 'pending' }));
            AllNotifications.push(...pendingWithType);
            lastNotifCountPending = data.pendingRequests.length;
            lastNotifContentPending = data.pendingRequests;
            if (requestBtn) {
                let notificationBadgeProfile = document.querySelector('#requests-badge');
                if (!notificationBadgeProfile) {
                    notificationBadgeProfile = document.createElement('span');
                    notificationBadgeProfile.id = 'requests-badge';
                    notificationBadgeProfile.classList.add('notif-badge-profile');
                    notificationBadgeProfile.textContent = data.pendingRequests.length;
                    requestBtn.style.position = 'relative';
                    requestBtn.appendChild(notificationBadgeProfile);
                } else {
                    notificationBadgeProfile.textContent = lastNotifCountPending;
                }
            }
        } else {
            // Remove all pending notifications
            AllNotifications = AllNotifications.filter(notif => notif.type !== 'pending');
            lastNotifCountPending = 0;
            lastNotifContentPending = [];
        }
        fillNotificationCenter(); // Re-render all notifications
    })
    .catch(error => console.error('Fetch error:', error));
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
            // Filter out existing accepted notifications
            AllNotifications = AllNotifications.filter(notif => notif.type !== 'accepted');
            const acceptedWithType = data.acceptedFriendRequest.map(notif => ({ ...notif, type: 'accepted' }));
            AllNotifications.push(...acceptedWithType);
            lastNotifCount = data.acceptedFriendRequest.length;
            lastNotifContent = data.acceptedFriendRequest;
        } else {
            // Remove all accepted notifications
            AllNotifications = AllNotifications.filter(notif => notif.type !== 'accepted');
            lastNotifCount = 0;
            lastNotifContent = [];
        }
        fillNotificationCenter(); // Re-render all notifications
    })
    .catch(error => console.error('Fetch error:', error));
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

function fillNotificationCenter() {
    const modal = document.getElementById('notif-modal');

    // If the modal doesn't exist yet, create it once
    if (!modal) {
        createNotificationModal();
    }

    const modalContent = document.getElementById('notif-modal-content');
    modalContent.innerHTML = ''; // Clear existing notifications

    AllNotifications.forEach(notification => {
        const notifItem = document.createElement('div');
        notifItem.className = 'notif-item';
        notifItem.id = `notif-${notification.fr_id}`;

        let notifText = `${notification.user_username} `;
        notifText += notification.type === 'pending' 
            ? 'sent you a friend request' 
            : 'accepted your friend request';

        const notifTextElement = document.createElement('span');
        notifTextElement.textContent = notifText;

        const closeButton = document.createElement('i');
        closeButton.className = 'fa-solid fa-times close-btn';
        closeButton.dataset.frId = notification.fr_id;
        closeButton.dataset.userId = userIdHeader;
        closeButton.dataset.type = notification.type;

        notifItem.appendChild(notifTextElement);
        notifItem.appendChild(closeButton);
        modalContent.appendChild(notifItem);

        // Add click event listener for redirection
        notifTextElement.addEventListener('click', () => {
            if (notification.type === 'accepted') {
                document.getElementById(`notif-${notification.fr_id}`)?.remove();

                // Prepare data
                const dataDelete = {
                    dataset: {
                        frId: notification.fr_id,
                        userId: notification.fr_senderId,
                        type: "accepted"
                    }
                };
        
                // Handle the backend update
                handleNotificationClose(dataDelete);
                window.location.href = `/persoChat?friend_id=${notification.fr_receiverId}`;
            } else if (notification.type === 'pending') {
                window.location.href = `/userProfile`;
            }
        });
    });

    updateNotificationUI();
}

// Create modal once and append it to the container
function createNotificationModal() {
    const container = document.getElementById('notification-center-ctn');
    
    const modal = document.createElement('div');
    modal.className = 'notif-modal hidden';
    modal.id = 'notif-modal';

    const modalTitle = document.createElement('h3');
    modalTitle.className = 'notif-title';
    modalTitle.textContent = 'Notifications';

    const clearAllButton = document.createElement('button');
    clearAllButton.textContent = 'Clear All';
    clearAllButton.className = 'clear-all-btn';
    clearAllButton.addEventListener('click', clearAllNotifications);
    
    modalTitle.appendChild(clearAllButton);
    modal.appendChild(modalTitle);

    const modalContent = document.createElement('div');
    modalContent.id = 'notif-modal-content';
    modal.appendChild(modalContent);

    container.appendChild(modal);

    // Attach event listener once for close buttons
    modal.addEventListener('click', function(event) {
        if (event.target.classList.contains('close-btn')) {
            handleNotificationClose(event.target);
        }
    });
}

// Function to handle notification close actions
function handleNotificationClose(target) {
    const frId = target.dataset.frId;
    const userId = target.dataset.userId;
    const typeBtn = target.dataset.type;

    if (typeBtn === 'pending') {
        console.log("Type", typeBtn);
        updateNotificationFriendRequestPending(frId, userId);
    } else if (typeBtn === 'accepted') {
        console.log("Type", typeBtn);
        updateNotificationFriendRequestAccepted(frId, userId);
    } else {
        console.log('Unknown notification type');
    }
}

function updateNotificationUI() {
    const notifBadge = document.getElementById('notif-badge');
    const notifBell = document.getElementById('notification-bell');

    if (AllNotifications.length > 0) {
        notifBadge.textContent = AllNotifications.length;
        notifBadge.style.display = 'inline-block';
        notifBell.style.display = 'inline-block';
    } else {
        notifBadge.style.display = 'none';
        notifBell.style.display = 'none';
        document.getElementById('notif-modal').classList.add('hidden');
    }
}


function clearAllNotifications() {
    const notificationsToClear = [...AllNotifications];
    notificationsToClear.forEach(notification => {
        const { fr_id, type } = notification;
        const userId = userIdHeader;
        if (type === 'pending') {
            updateNotificationFriendRequestPending(fr_id, userId, type);
        } else if (type === 'accepted') {
            updateNotificationFriendRequestAccepted(fr_id, userId, type);
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

function addNotificationPermission(userId) {
    console.log('Adding notification permission...');
    if ('Notification' in window && navigator.serviceWorker) {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                console.log('Notification permission granted.');
                // Optionally save this info
                localStorage.setItem('notification_permission', 'granted');
                const token = localStorage.getItem('masterTokenWebsite');

                // Step 1: Send notification permission update
                fetch('/updateNotificationPermission', {
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
                            console.log('Notification permission updated successfully.');

                            // Step 2: Register service worker and subscribe to push notifications
                            return registerServiceWorker(userId); // Pass userId to sendSubscriptionToBackend inside registerServiceWorker
                        } else {
                            console.error('Error updating status:', data.error);
                            throw new Error('Failed to update notification permission.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            } else {
                localStorage.setItem('notification_permission', 'denied');
            }
        });
    }
}

// Register service worker, subscribe to push notifications, and send subscription to backend
function registerServiceWorker(userId) {
    return navigator.serviceWorker.register('service-worker.js')
        .then(registration => {
            console.log('Service Worker registered:', registration);

            // Subscribe to push notifications
            return registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: 'BMFWjnjjjer6XUJjuY9RxF_TvRP0Ke6kNNH13oo9lUCRnEYP6ddbpdQ91RD50JexpI8E7THMftePX89bzssMj5Q' // Replace with your public VAPID key
            });
        })
        .then(subscription => {
            // Step 3: Call sendSubscriptionToBackend inside registerServiceWorker
            return sendSubscriptionToBackend(subscription, userId); 
        })
        .catch(error => {
            console.error('Service Worker registration or subscription failed:', error);
            throw error; // Ensure the error is propagated for handling in the main flow
        });
}

// Send the subscription object to the backend
function sendSubscriptionToBackend(subscription, userId) {
    const token = localStorage.getItem('masterTokenWebsite');
    // Stringify the subscription object
    const subscriptionJSON = JSON.stringify(subscription.toJSON());
    // Use URLSearchParams for proper encoding
    const body = new URLSearchParams();
    body.append('param', subscriptionJSON);
    body.append('userId', userId);

    return fetch('/saveNotificationSubscription', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`
        },
        body: body
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Subscription saved:', data);
        } else {
            console.log('Subscription failed:', data);
        }
    })
    .catch(error => {
        console.error('Error saving subscription:', error);
        throw error;
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

document.addEventListener("DOMContentLoaded", function() {
    fetchUpdates();
    setTimeout(fetchUpdates, 1000);
    setInterval(fetchUpdates, 20000); // Refresh every 20 seconds

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('https://ur-sg.com/service-worker.js', {
                scope: 'https://ur-sg.com'
            })
            .then(function(registration) {
                console.log('✅ Service Worker registered with scope:', registration.scope);
            }).catch(function(error) {
                console.error('❌ Service Worker registration failed:', error);
            });
    }

    document.getElementById('notification-bell').addEventListener('click', function(event) {
        const modal = document.getElementById('notif-modal');
        if (modal) {
            modal.classList.toggle('hidden');
        }

        // Check if permissions already granted, if yes ignore function
        const localPermission = localStorage.getItem('notification_permission');
        const browserPermission = Notification.permission;
        
        if (browserPermission !== 'granted' && localPermission !== 'granted') {
            addNotificationPermission(userId);
        } else {
            console.log('Notification permission already granted.');
            registerServiceWorker(userId);
        }

        event.stopPropagation(); // Prevent closing immediately
    });

    document.addEventListener('click', function(event) {
        const modal = document.getElementById('notif-modal');
        const bell = document.getElementById('notification-bell');

        if (modal && !modal.contains(event.target) && !bell.contains(event.target)) {
            modal.classList.add('hidden');
        }
    });
});

