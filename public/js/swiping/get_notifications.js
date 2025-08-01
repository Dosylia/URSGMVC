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
const messageSound = new Audio('public/sounds/notification.mp3');
let numberOfFailsUnred = 0;
let numberOfFailsAccepted = 0;
let numberOfFailsPending = 0;
let numberOfFailsInterested = 0;

function fetchFriendRequest(userId) {
    if (numberOfFailsPending >= 5) {
        console.error('Too many failed attempts to fetch accepted friend requests. Stopping further attempts.');
        return; 
    }
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
            numberOfFailsPending = 0;   
            // Filter out existing pending notifications
            if (data.givenDailyReward || data.givenRequestReward) {
                if (data.givenDailyReward) {
                displayNotification(
                    `You just won 500 credits for connecting today!`,
                    userId
                );
                    if (displayMoneyWon) {
                        displayMoneyWon.textContent = `500`;
                        displayMoneyWon.style.display = 'block';

                        displayMoneyWon.style.animation = 'none';
                        displayMoneyWon.offsetHeight;
                        displayMoneyWon.style.animation = 'rewardBounce 5s ease-out forwards';

                        setTimeout(() => {
                            displayMoneyWon.style.display = 'none';
                        }, 7000);
                    }
                } else if (data.givenRequestReward) {
                    const displayMoneyWon = document.getElementById('displayMoneyWon');
                    if (displayMoneyWon) {
                        console.log('Money won:', data.amountGiven);
                        displayMoneyWon.textContent = `+ ${data.amountGiven}`;
                        displayMoneyWon.style.display = 'block';

                        displayMoneyWon.style.animation = 'none';
                        displayMoneyWon.offsetHeight;
                        displayMoneyWon.style.animation = 'rewardBounce 5s ease-out forwards';

                        setTimeout(() => {
                            displayMoneyWon.style.display = 'none';
                        }, 7000);
                    }
                }               
            }
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
            numberOfFailsPending = 0;
            // Remove all pending notifications
            if (data.givenDailyReward || data.givenRequestReward) {
                if (data.givenDailyReward) {
                    displayNotification(
                        `You just won 500 credits for connecting today!`,
                        userId
                    );
                    if (displayMoneyWon) {
                        displayMoneyWon.textContent = `500`;
                        displayMoneyWon.style.display = 'block';

                        displayMoneyWon.style.animation = 'none';
                        displayMoneyWon.offsetHeight;
                        displayMoneyWon.style.animation = 'rewardBounce 5s ease-out forwards';

                        setTimeout(() => {
                            displayMoneyWon.style.display = 'none';
                        }, 7000);
                    }
                } else if (data.givenRequestReward) {
                    const displayMoneyWon = document.getElementById('displayMoneyWon');
                    if (displayMoneyWon) {
                        console.log('Money won:', data.amountGiven);
                        displayMoneyWon.textContent = `+ ${data.amountGiven}`;
                        displayMoneyWon.style.display = 'block';

                        displayMoneyWon.style.animation = 'none';
                        displayMoneyWon.offsetHeight;
                        displayMoneyWon.style.animation = 'rewardBounce 5s ease-out forwards';

                        setTimeout(() => {
                            displayMoneyWon.style.display = 'none';
                        }, 7000);
                    }
                }               
            }
            AllNotifications = AllNotifications.filter(notif => notif.type !== 'pending');
            lastNotifCountPending = 0;
            lastNotifContentPending = [];
        }
        fillNotificationCenter(); // Re-render all notifications
    })
    .catch(error => {
        numberOfFailsPending++;
        console.error('Fetch error:', error);
    });
}

function fetchInterestedUsers(userId) {
    if (numberOfFailsInterested >= 5) {
        console.error('Too many failed attempts to fetch interested users. Stopping further attempts.');
        return; 
    }
    fetch('/getInterestedPeople', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(userId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.interestedUsers) {
            numberOfFailsInterested = 0;
            // Filter out existing interested notifications
            AllNotifications = AllNotifications.filter(request => request.type !== 'interested');
            const interestedWithType = data.interestedUsers.map(notif => ({ ...notif, type: 'interested' }));
            AllNotifications.push(...interestedWithType);
            lastNotifCount = data.interestedUsers.length;
            lastNotifContent = data.interestedUsers;
        } else {
            numberOfFailsInterested = 0;
            // Remove all interested notifications
            AllNotifications = AllNotifications.filter(notif => notif.type !== 'interested');
            lastNotifCount = 0;
            lastNotifContent = [];
        }
        fillNotificationCenter(); // Re-render all notifications
    })
    .catch(error => {
        numberOfFailsInterested++;
        console.error('Fetch error:', error);
    });
}

function fetchAcceptedFriendRequest(userId) {
    if (numberOfFailsAccepted >= 5) {
        console.error('Too many failed attempts to fetch accepted friend requests. Stopping further attempts.');
        return; 
    }
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
            numberOfFailsAccepted = 0;
            // Filter out existing accepted notifications
            AllNotifications = AllNotifications.filter(notif => notif.type !== 'accepted');
            const acceptedWithType = data.acceptedFriendRequest.map(notif => ({ ...notif, type: 'accepted' }));
            AllNotifications.push(...acceptedWithType);
            lastNotifCount = data.acceptedFriendRequest.length;
            lastNotifContent = data.acceptedFriendRequest;
        } else {
            numberOfFailsAccepted = 0;
            // Remove all accepted notifications
            AllNotifications = AllNotifications.filter(notif => notif.type !== 'accepted');
            lastNotifCount = 0;
            lastNotifContent = [];
        }
        fillNotificationCenter(); // Re-render all notifications
    })
    .catch(error => {
        numberOfFailsAccepted++;
        console.error('Fetch error:', error);
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
        switch (notification.type) {
            case 'pending':
                notifText += 'sent you a friend request';
                break;
            case 'accepted':
                notifText += 'accepted your friend request';
                break;
            case 'interested':
                notifText += 'is interested to play with you';
                break;
            default:
                notifText += 'sent you a notification';
        }

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
        notifTextElement.addEventListener('click', async () => {
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
                await handleNotificationClose(dataDelete);
                window.location.href = `/persoChat?friend_id=${notification.fr_receiverId}`;
            } else if (notification.type === 'pending') {
                window.location.href = `/userProfile`;
            } else if (notification.type === 'interested') {
                document.getElementById(`notif-${notification.fr_id}`)?.remove();

                const dataDelete = {
                    dataset: {
                        frId: notification.fr_id, // pf_id
                        userId: notification.userId,
                        type: "interested"
                    }
                };

                await handleNotificationClose(dataDelete);
                window.location.href = `/persoChat?friend_id=${notification.friendId}`;
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
async function handleNotificationClose(target) {
    const frId = target.dataset.frId;
    const userId = target.dataset.userId;
    const typeBtn = target.dataset.type;

    if (typeBtn === 'pending') {
        updateNotificationFriendRequestPending(frId, userId);
        return Promise.resolve();
    } else if (typeBtn === 'accepted') {
        return await updateNotificationFriendRequestAccepted(frId, userId);
    } else if (typeBtn === 'interested') {
        return await updateNotificationPlayerFinder(frId, userId);
    } else {
        console.log('Unknown notification type');
        return Promise.resolve(); // nothing to wait for
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
        } else if (type === 'interested') {
            updateNotificationPlayerFinder(fr_id, userId, type);
        }
    });
}

async function updateNotificationPlayerFinder(frId, userId) {
    try {
        const response = await fetch('/markInterestAsSeen', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Authorization': `Bearer ${token}`,
            },
            body: `postId=${encodeURIComponent(frId)}&userId=${encodeURIComponent(userId)}`
        });

        const data = await response.json();

        if (data.success) {
            console.log('Notification updated successfully');

            const notifItem = document.getElementById(`notif-${frId}`);
            if (notifItem) notifItem.remove();

            const notifBadge = document.getElementById('notif-badge');
            let currentCount = parseInt(notifBadge.textContent, 10) || 0;

            if (currentCount > 1) {
                notifBadge.textContent = currentCount - 1;
            } else {
                notifBadge.style.display = 'none';
                const notifBell = document.getElementById('notification-bell');
                if (notifBell) notifBell.style.display = 'none';

                const modal = document.getElementById('notif-modal');
                if (modal) modal.classList.add('hidden');
            }
        } else {
            console.log('Failed to update notification');
        }
    } catch (error) {
        console.error('Fetch error:', error);
    }
}

async function updateNotificationFriendRequestAccepted(frId, userId) {
    try {
        const response = await fetch('/updateNotificationFriendRequestAcceptedWebsite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Authorization': `Bearer ${token}`,
            },
            body: `frId=${encodeURIComponent(frId)}&userId=${encodeURIComponent(userId)}`
        });

        const data = await response.json();

        if (data.success) {
            console.log('Notification updated successfully');

            // Remove only the dismissed notification row
            const notifItem = document.getElementById(`notif-${frId}`);
            if (notifItem) notifItem.remove();

            // Update the notification count dynamically
            const notifBadge = document.getElementById('notif-badge');
            let currentCount = parseInt(notifBadge.textContent, 10) || 0;

            if (currentCount > 1) {
                notifBadge.textContent = currentCount - 1;
            } else {
                notifBadge.style.display = 'none';

                const notifBell = document.getElementById('notification-bell');
                if (notifBell) notifBell.style.display = 'none';

                const modal = document.getElementById('notif-modal');
                if (modal) modal.classList.add('hidden');
            }

            return true; // Optional: indicate success
        } else {
            console.log('Failed to update notification');
            return false;
        }
    } catch (error) {
        console.error('Fetch error:', error);
        return false;
    }
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

    if (numberOfFailsUnred >= 5) {
        console.error('Too many failed attempts to fetch unread messages. Stopping further attempts.');
        return; 
    }
    const servedSenderIds = JSON.parse(localStorage.getItem('servedSenderIds')) || [];

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
            numberOfFailsUnred = 0;
            console.log('Unread messages fetched successfully');

            const newSenderIds = [];
            const updatedServedSenderIds = [...servedSenderIds];

            let shouldPlaySound = false;

            data.unreadCount.forEach((message) => {
                if (!servedSenderIds.includes(message.chat_senderId)) {
                    displayNotification(
                        `New message from ${message.user_username}: ${message.chat_message}`,
                        'message',
                        message.chat_senderId,
                        message.user_picture
                    );

                    newSenderIds.push(message.chat_senderId);
                    updatedServedSenderIds.push(message.chat_senderId);

                    shouldPlaySound = true; // Mark for sound play
                }
            });

            // 🔊 Play sound if any new sender triggered a notification
            const soundSetting = localStorage.getItem('soundNotifications');

            if (shouldPlaySound && soundSetting != "off") {
                console.log('Playing notification sound');
                messageSound.play().catch(err => console.warn("Sound play error:", err));
            }

            localStorage.setItem('servedSenderIds', JSON.stringify(updatedServedSenderIds));
            fillUnread(data.unreadCount);
            updateUnreadMessagesForFriends(data.unreadCount);
            cleanupServedSenders(data.unreadCount.map(m => m.chat_senderId));
        } else {
            numberOfFailsUnred = 0;
            globalUnreadCounts = {};
            clearContainer();
            document.title = originalTitleNoChange;
            localStorage.removeItem('servedSenderIds');
            console.log('No unread messages or success flag not set');
        }
    })
    .catch(error => {
        numberOfFailsUnred++;
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

function refreshPushSubscription(userId) {
    navigator.serviceWorker.ready.then(registration => {
        // Step 1: Unsubscribe the old subscription if it exists
        registration.pushManager.getSubscription().then(subscription => {
            if (subscription) {
                subscription.unsubscribe().then(() => {
                    console.log('Old subscription removed.');
                    // Step 2: Register again
                    registerServiceWorker(userId);
                });
            } else {
                console.log('No previous subscription found, registering fresh...');
                registerServiceWorker(userId);
            }
        });
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
    fetchInterestedUsers(userIdHeader);
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
        } else if (browserPermission === 'granted' && localPermission !== 'granted'){
            addNotificationPermission(userId);
        } else if (browserPermission !== 'granted' && localPermission === 'granted'){
            addNotificationPermission(userId);
        } else {
            console.log('Notification permission already granted.');
             refreshPushSubscription(userId);
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

window.addEventListener('load', async function () {
    if (Notification.permission !== 'granted') return;

    const lastRefresh = parseInt(localStorage.getItem('notification_last_refresh'), 10);
    const lastBrowserPrefix = localStorage.getItem('notification_browser_prefix');
    const now = Date.now();
    const sevenDays = 7 * 24 * 60 * 60 * 1000;

    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();

    if (!subscription) {
        refreshPushSubscription(userId);
        return;
    }

    const currentEndpoint = subscription.endpoint;
    let currentPrefix = 'unknown';

    if (currentEndpoint.startsWith('https://fcm.googleapis.com')) {
        currentPrefix = 'fcm';
    } else if (currentEndpoint.startsWith('https://updates.push.services.mozilla.com')) {
        currentPrefix = 'mozilla';
    } else if (currentEndpoint.startsWith('https://push.apple.com')) {
        currentPrefix = 'apple';
    }

    try {
        const res = await fetch('/fetchNotificationEndpoint', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Authorization': `Bearer ${token}`,
            },
            body: `userId=${encodeURIComponent(parseInt(userId))}`
        });

        const data = await res.json();

        if (!data.success) {
            console.error('Error fetching server endpoint:', data.error);
            return;
        }

        const serverEndpoint = data.endpoint;
        const endpointChanged = serverEndpoint !== currentEndpoint;
        const timeExpired = isNaN(lastRefresh) || (now - lastRefresh > sevenDays);
        const browserChanged = lastBrowserPrefix !== currentPrefix;

        const shouldRefresh = endpointChanged || timeExpired || browserChanged;

        if (shouldRefresh) {
            console.log('🔄 Refreshing subscription because:');
            if (endpointChanged) console.log('- Endpoint changed');
            if (timeExpired) console.log('- Last refresh too old');
            if (browserChanged) console.log('- Browser changed');

            refreshPushSubscription(userId);
            localStorage.setItem('notification_last_refresh', now.toString());
            localStorage.setItem('notification_browser_prefix', currentPrefix);
        } else {
            console.log('✅ Subscription is up-to-date');
        }

    } catch (error) {
        console.error('Network error while fetching endpoint:', error);
    }
});




