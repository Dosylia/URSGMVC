import { fetchMessages } from './message_fetcher.js'
import {
    initChatEvents,
    initMessageInputEvents,
    initSendButtonEvents,
    initRandomChatEvents,
} from './message_events.js'
import { initImageHandlers } from './message_images.js'
import { initEmoteHandlers } from './message_emotes.js'
import {
    checkIncomingRandomChatsApi,
    closeRandomChatApi,
    validateRandomChatSessionApi,
} from './message_api.js'
import {
    userId,
    friendId,
    actualFriendId,
    chatInterface,
    messageContainer,
    setActualFriendId,
} from './message_utils.js'

// Check for random user chat
function getRandomUserId() {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get('random_user_id')
}

// Session validation cache to avoid repeated API calls
let sessionValidationCache = {
    isValid: null,
    lastChecked: 0,
    cacheDuration: 10000, // 10 seconds cache
}

// Check if current chat is a random chat session
function isRandomChatSession() {
    return localStorage.getItem('randomChatSession') !== null
}

// Validate if the random chat session is still active (with caching)
async function validateRandomChatSession() {
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

document.addEventListener('DOMContentLoaded', async function () {
    const randomUserId = getRandomUserId()

    if (randomUserId) {
        // Store random chat session
        localStorage.setItem(
            'randomChatSession',
            JSON.stringify({
                targetUserId: randomUserId,
                initiatedAt: Date.now(),
            })
        )

        // Set the random user as the active friend
        setActualFriendId(randomUserId)

        // Initialize random chat UI
        await initRandomChatUI(randomUserId)
    }
    if (typeof userId !== 'undefined' && userId !== null) {
        const targetFriendId = randomUserId || actualFriendId
        fetchMessages(userId, targetFriendId)
        setInterval(() => fetchMessages(userId, targetFriendId), 5000)
    }

    setVhVariable()
    checkScreenSize()

    // Initialize all message handling modules
    initChatEvents()
    initMessageInputEvents()
    initSendButtonEvents()
    initImageHandlers()
    initEmoteHandlers()

    if (randomUserId) {
        initRandomChatEvents()
    }

    // Check for incoming random chat sessions (for target users)
    checkForIncomingRandomChats()
    setInterval(checkForIncomingRandomChats, 10000) // Check every 10 seconds

    // Periodic validation of random chat session (clear localStorage if expired)
    if (randomUserId || isRandomChatSession()) {
        setInterval(async () => {
            if (isRandomChatSession()) {
                const isValid = await validateRandomChatSession()
                if (!isValid) {
                    console.log(
                        'Periodic check: Random chat session expired, redirecting to main chat'
                    )
                    window.location.href = '/persoChat'
                }
            }
        }, 15000) // Check every 15 seconds
    }

    window.addEventListener('resize', () => {
        setVhVariable()
        checkScreenSize()
    })
})

// Initialize random chat UI
async function initRandomChatUI(randomUserId) {
    // Hide friend list on mobile for random chat
    const isMax1018px = window.matchMedia('(max-width: 1018px)').matches
    if (isMax1018px && chatInterface) {
        chatInterface.style.display = 'none'
        messageContainer.style.display = 'block'
    }

    // Add random chat controls
    await addRandomChatControls()
}

// Initialize random chat controls (orchestration only)
async function addRandomChatControls() {
    // Import and use the proper modules
    const { renderRandomChatControls } = await import('./message_renderer.js')
    const { initRandomChatControlEvents } = await import('./message_events.js')
    
    // Render the controls
    renderRandomChatControls()
    
    // Initialize event handlers
    initRandomChatControlEvents(validateRandomChatSession)
}

// Check for incoming random chat sessions (when someone finds you)
async function checkForIncomingRandomChats() {
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

function setVhVariable() {
    let vh = window.innerHeight * 0.01 // 1vh
    document.documentElement.style.setProperty('--vh', `${vh}px`)
}

function checkScreenSize() {
    const isMax1018px = window.matchMedia('(max-width: 1018px)').matches

    if (isMax1018px) {
        if (
            chatInterface !== null &&
            window.getComputedStyle(messageContainer).display !== 'none'
        ) {
            chatInterface.style.display = 'none'
        }
    } else {
        if (chatInterface !== null && chatInterface !== undefined) {
            chatInterface.style.display = 'flex'
        }
        messageContainer.style.display = 'block'
    }
}
