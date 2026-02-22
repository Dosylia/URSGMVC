import { fetchMessages } from './message_fetcher.js'
import {
    initChatEvents,
    initMessageInputEvents,
    initSendButtonEvents,
    initRandomChatUI,
} from './message_events.js'
import { initImageHandlers } from './message_images.js'
import { initEmoteHandlers } from './message_emotes.js'
import {
    userId,
    chatInterface,
    messageContainer,
    setActualFriendId,
    getActualFriendId,
} from './message_utils.js'
import {
    checkForIncomingRandomChats,
    isRandomChatSession,
    validateRandomChatSession,
} from './message_random_chat.js'

// Check for random user chat
function getRandomUserId() {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get('random_user_id')
}

document.addEventListener('DOMContentLoaded', async function () {
    // Detect if we are on randomChat
    // Check closest username_chat_friend link for data-random-chat attribute
    const firstChatLink = document.querySelector('.username_chat_friend')
    const isRandomChat =
        firstChatLink && firstChatLink.dataset.randomChat === 'true'
    let randomUserId = getRandomUserId()

    if (randomUserId || isRandomChat) {
        if (isRandomChat) {
            // Get user_id using data-friend-id
            randomUserId = firstChatLink.dataset.friendId
        }
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
        const targetFriendId = randomUserId || getActualFriendId()
        fetchMessages(userId, targetFriendId)
        setInterval(() => {
            const currentFriendId = randomUserId || getActualFriendId()
            fetchMessages(userId, currentFriendId)
        }, 5000)
    }

    setVhVariable()
    checkScreenSize()

    // Initialize all message handling modules
    initChatEvents()
    initMessageInputEvents()
    initSendButtonEvents()
    initImageHandlers()
    initEmoteHandlers()

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
