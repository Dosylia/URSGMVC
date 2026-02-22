import { sendMessageApi } from './message_api.js'
import { fetchMessages } from './message_fetcher.js'
import {
    clearImageVar,
    clearImageFalse,
    messageInput,
    senderId,
    receiverId,
    userId,
    attachedImages,
    spamWarning,
    isActionAllowed,
    messageBurstCount,
    lastMessageTimestamp,
    isCoolingDown,
    BURST_LIMIT,
    COOLDOWN_TIME,
    resetActionAllowed,
    setActionAllowed,
    incrementMessageBurst,
    resetMessageBurst,
    setCoolingDown,
    setLastMessageTimestamp,
    clearAttachedImages,
} from './message_utils.js'

export function handleSendMessage(event) {
    event.preventDefault()

    const now = Date.now()

    if (isCoolingDown) {
        console.warn('Cooldown active. Please wait.')
        return
    }

    if (!isActionAllowed) {
        return
    }

    setActionAllowed(false)

    const message = messageInput.value.trim()

    if (message === '' && attachedImages.length === 0) {
        resetActionAllowed()
        return
    }

    if (clearImageVar === true) {
        clearAttachedImages()
        clearImageFalse()
        if (message === '' && attachedImages.length === 0) {
            resetActionAllowed()
            return
        }
    }

    const replyToChatId = messageInput.dataset.replyTo || null

    sendMessageToPhp(senderId, message, replyToChatId)

    if (now - lastMessageTimestamp > COOLDOWN_TIME) {
        resetMessageBurst()
    }

    incrementMessageBurst()
    setLastMessageTimestamp(now)

    if (messageBurstCount >= BURST_LIMIT) {
        setCoolingDown(true)
        spamWarning.style.display = 'block'

        setTimeout(() => {
            setCoolingDown(false)
            resetMessageBurst()
            spamWarning.style.display = 'none'
        }, COOLDOWN_TIME)
    }

    setTimeout(() => {
        resetActionAllowed()
    }, 50)
}

export async function sendMessageToPhp(senderId, message, replyToChatId) {
    let friendIdElement = document.getElementById('receiverId')
    const receiverId = friendIdElement ? friendIdElement.value : null

    const cleanedMessage = message.replace('📷', '').trim()
    const imageTags = attachedImages.map((url) => `[img]${url}[/img]`).join('')
    const fullMessage = cleanedMessage + imageTags

    try {
        const data = await sendMessageApi(
            senderId,
            receiverId,
            fullMessage,
            replyToChatId
        )

        if (data.success) {
            clearMessageInput()
            clearImagePreview()
            clearReplyPreview()
            fetchMessages(userId, receiverId)
        } else {
            console.error('Error sending message:', data.message)
        }
    } catch (error) {
        console.error('Exception in sendMessageToPhp:', error)
    }
}

function clearMessageInput() {
    messageInput.value = ''
}

function clearImagePreview() {
    const previewContainer = document.getElementById('imagePreviewContainer')
    if (previewContainer) {
        previewContainer.innerHTML = ''
    }
    clearAttachedImages()
}

function clearReplyPreview() {
    let replyPreviewContainer = document.getElementById('reply-preview')
    if (replyPreviewContainer) {
        replyPreviewContainer.innerHTML = ''
        replyPreviewContainer.style.display = 'none'
    }
    messageInput.removeAttribute('data-reply-to')
}
