import { fetchMessages } from './message_fetcher.js'
import {
    initChatEvents,
    initMessageInputEvents,
    initSendButtonEvents,
} from './message_events.js'
import { initImageHandlers } from './message_images.js'
import { initEmoteHandlers } from './message_emotes.js'
import {
    userId,
    friendId,
    actualFriendId,
    chatInterface,
    messageContainer,
} from './message_utils.js'

document.addEventListener('DOMContentLoaded', function () {
    if (typeof userId !== 'undefined' && userId !== null) {
        fetchMessages(userId, actualFriendId)
        setInterval(() => fetchMessages(userId, actualFriendId), 5000)
    }

    setVhVariable()
    checkScreenSize()

    // Initialize all message handling modules
    initChatEvents()
    initMessageInputEvents()
    initSendButtonEvents()
    initImageHandlers()
    initEmoteHandlers()

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
