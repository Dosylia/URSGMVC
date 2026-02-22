import {
    renderRandomChatControls,
    showLoadingIndicator,
} from './message_renderer.js'
import {
    closeRandomChatApi,
    checkIncomingRandomChatsApi,
    validateRandomChatSessionApi,
    sendFriendRequestApi,
    findRandomPlayerApi,
} from './message_api.js'
import { userId } from './message_utils.js'

// Session validation cache to avoid repeated API calls
let sessionValidationCache = {
    isValid: null,
    lastChecked: 0,
    cacheDuration: 10000, // 10 seconds cache
}

// Initialize random chat controls (orchestration only)
export async function addRandomChatControls() {
    // Render the controls
    renderRandomChatControls()

    // Initialize event handlers
    initRandomChatControlEvents(validateRandomChatSession)
}

// Show notification when someone finds you randomly
async function showRandomChatNotification(session) {
    // Don't show if already showing or if user is currently in a valid random chat
    if (document.getElementById('random-chat-notification')) {
        return
    }

    // Check if user is currently in a valid random chat session
    if (isRandomChatSession() && (await validateRandomChatSession())) {
        return
    }

    const notification = document.createElement('div')
    notification.id = 'random-chat-notification'
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #ff6b35, #f7931e);
        color: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        z-index: 10000;
        max-width: 300px;
        animation: slideIn 0.5s ease-out;
    `

    notification.innerHTML = `
        <div style="text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: white;">🎲 Random Chat!</h3>
            <p style="margin: 0 0 15px 0; font-size: 14px;">
                <strong>${session.initiatorUsername}</strong> found you randomly and wants to chat!
            </p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button id="accept-random-chat" style="
                    background: #28a745;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-weight: bold;
                ">Accept</button>
                <button id="decline-random-chat" style="
                    background: #dc3545;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 5px;
                    cursor: pointer;
                ">Decline</button>
            </div>
        </div>
    `

    // Add slide-in animation
    const style = document.createElement('style')
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `
    document.head.appendChild(style)

    document.body.appendChild(notification)

    // Handle accept
    document
        .getElementById('accept-random-chat')
        .addEventListener('click', () => {
            // Redirect to chat with the random user
            window.location.href = `/persoChat?random_user_id=${session.initiatorUserId}`
            notification.remove()
        })

    // Handle decline
    document
        .getElementById('decline-random-chat')
        .addEventListener('click', async () => {
            try {
                // Close the session from target's side
                await closeRandomChatApi(session.initiatorUserId)
                notification.remove()
            } catch (error) {
                console.error('Error declining random chat:', error)
                notification.remove()
            }
        })

    // Auto-remove after 30 seconds
    setTimeout(() => {
        if (document.getElementById('random-chat-notification')) {
            notification.remove()
        }
    }, 30000)
}

// Check for incoming random chat sessions (when someone finds you)
export async function checkForIncomingRandomChats() {
    if (!userId) return

    try {
        const response = await checkIncomingRandomChatsApi(userId)

        if (
            response.success &&
            response.incomingRandomChats &&
            response.incomingRandomChats.length > 0
        ) {
            for (const session of response.incomingRandomChats) {
                await showRandomChatNotification(session)
            }
        }
    } catch (error) {
        console.error('Error checking for incoming random chats:', error)
    }
}

// Validate if the random chat session is still active (with caching)
export async function validateRandomChatSession() {
    const randomSession = localStorage.getItem('randomChatSession')
    if (!randomSession) {
        sessionValidationCache.isValid = false
        return false
    }

    const now = Date.now()

    // Return cached result if still valid
    if (
        sessionValidationCache.isValid !== null &&
        now - sessionValidationCache.lastChecked <
            sessionValidationCache.cacheDuration
    ) {
        return sessionValidationCache.isValid
    }

    try {
        const sessionData = JSON.parse(randomSession)
        const response = await validateRandomChatSessionApi(
            userId,
            sessionData.targetUserId
        )

        sessionValidationCache.isValid = response.success && response.isActive
        sessionValidationCache.lastChecked = now

        // If session is not active, clear localStorage
        if (!sessionValidationCache.isValid) {
            localStorage.removeItem('randomChatSession')
            console.log('Random chat session expired - localStorage cleared')
        }

        return sessionValidationCache.isValid
    } catch (error) {
        console.error('Error validating random chat session:', error)
        // On error, assume session is invalid and clear localStorage
        localStorage.removeItem('randomChatSession')
        sessionValidationCache.isValid = false
        sessionValidationCache.lastChecked = now
        return false
    }
}

// Check if current chat is a random chat session
export function isRandomChatSession() {
    return localStorage.getItem('randomChatSession') !== null
}

// Initialize random chat control event listeners
export function initRandomChatControlEvents(validateSessionFn) {
    const addFriendBtn = document.getElementById('add-friend-btn')
    const skipUserBtn = document.getElementById('skip-user-btn')
    const closeChatBtn = document.getElementById('close-chat-btn')

    if (addFriendBtn) {
        addFriendBtn.addEventListener('click', async () => {
            // Validate session before allowing friend request
            if (await validateSessionFn()) {
                const randomSession = JSON.parse(
                    localStorage.getItem('randomChatSession')
                )
                if (randomSession) {
                    try {
                        await sendFriendRequestApi(
                            userId,
                            randomSession.targetUserId
                        )
                        addFriendBtn.textContent = 'Request Sent!'
                        addFriendBtn.style.background = '#28a745'
                        addFriendBtn.disabled = true
                    } catch (error) {
                        console.error('Error sending friend request:', error)
                        addFriendBtn.textContent = 'Error'
                        addFriendBtn.style.background = '#dc3545'
                    }
                }
            } else {
                alert('This chat session has expired. Please start a new one.')
                window.location.href = '/persoChat'
            }
        })
    }

    if (skipUserBtn) {
        skipUserBtn.addEventListener('click', async () => {
            if (await validateSessionFn()) {
                const randomSession = JSON.parse(
                    localStorage.getItem('randomChatSession')
                )
                if (randomSession) {
                    try {
                        await closeRandomChatApi(randomSession.targetUserId)
                    } catch (error) {
                        console.error('Error closing random chat:', error)
                    }
                    localStorage.removeItem('randomChatSession')
                    // Add loading indicator while finding new player
                    showLoadingIndicator()
                    const prefs =
                        JSON.parse(
                            localStorage.getItem('playerfinder_filters')
                        ) || {}

                    findRandomPlayer(prefs)
                } else {
                    window.location.href = '/persoChat'
                }
            } else {
                window.location.href = '/persoChat'
            }
        })
    }

    if (closeChatBtn) {
        closeChatBtn.addEventListener('click', async () => {
            if (await validateSessionFn()) {
                const randomSession = JSON.parse(
                    localStorage.getItem('randomChatSession')
                )
                if (randomSession) {
                    await closeRandomChatApi(randomSession.targetUserId)
                }
            }
            localStorage.removeItem('randomChatSession')
            window.location.href = '/persoChat'
        })
    }
}

// Find random player function (moved from player finder)
function findRandomPlayer(prefs) {
    findRandomPlayerApi(prefs, userId)
        .then((data) => {
            if (data.success) {
                const randomUserId = data.randomUserId
                const sessionId = data.sessionId
                window.location.href = `persoChat?random_user_id=${randomUserId}&session_id=${sessionId}`
                return
            } else {
                alert(
                    'No random players found. Try adjusting your preferences.'
                )
            }
        })
        .catch((error) => {
            console.error('Request failed', error)
        })
}
