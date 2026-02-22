import { deleteMessageApi, markMessageAsReadApi } from './message_api.js'
import { fetchMessages } from './message_fetcher.js'
import { closeRatingModal, sendRating } from './message_friends.js'
import { removeRandomChatControls } from './message_renderer.js'
import {
    userId,
    friendId,
    replyPreviewContainer,
    chatInput,
    closeRatingModalBtn,
    submitRating,
    messageInput,
    btnSubmit,
    btnDesign,
    senderId,
    receiverId,
    setFriendId,
    setActualFriendId,
    getActualFriendId,
    chatInterface,
    messageContainer,
    clearImageTrue,
} from './message_utils.js'
import { addRandomChatControls } from './message_random_chat.js'

export function replyToMessage(chatId, messageText, senderName) {
    replyPreviewContainer.style.display = 'block'
    const truncatedMessage =
        messageText.length > 50
            ? messageText.substring(0, 50) + '...'
            : messageText

    let finalMessage = messageText
    if (messageText.includes('[img]') && messageText.includes('[/img]')) {
        finalMessage = messageText.replace(
            /\[img\](.*?)\[\/img\]/g,
            'Replying to a picture 📷'
        )
    }

    replyPreviewContainer.innerHTML = `
            <div class="reply-preview-content">
                <strong>${senderName}:</strong> ${finalMessage}
                <button type="button" id="cancel-reply-btn">✖</button>
            </div>
        `

    // Set a hidden field or state variable to track reply context
    chatInput.dataset.replyTo = chatId

    // Attach event listener to cancel button
    let cancelReplyBtn = document.getElementById('cancel-reply-btn')
    if (cancelReplyBtn) {
        cancelReplyBtn.addEventListener('click', cancelReply)
    }

    // Set focus on the chat input field
    if (chatInput) {
        chatInput.focus()
    }
}

export async function markMessageAsRead(senderId, receiverId) {
    try {
        const data = await markMessageAsReadApi(senderId, receiverId)
        console.log('Success:', data)
    } catch (error) {
        console.error('Error:', error)
    }
}

export function cancelReply() {
    replyPreviewContainer.style.display = 'none'
    chatInput.dataset.replyTo = '' // Clear reply context
}

export function deleteMessage(chatId, userId) {
    try {
        const data = deleteMessageApi(chatId, userId)

        if (data.success) {
            fetchMessages(userId, friendId) // Reload messages after deletion
        } else {
            console.error('Error deleting message:', data.message)
        }
    } catch (error) {
        console.error('Fetch error (delete message):', error)
    }
}

// Initialize random chat UI
export async function initRandomChatUI(randomUserId) {
    // Hide friend list on mobile for random chat
    const isMax1018px = window.matchMedia('(max-width: 1018px)').matches
    if (isMax1018px && chatInterface) {
        chatInterface.style.display = 'none'
        messageContainer.style.display = 'block'
    }

    // Add random chat controls
    await addRandomChatControls()
}

export function initChatEvents() {
    closeRatingModalBtn.addEventListener('click', () => {
        closeRatingModal()
    })

    submitRating.addEventListener('click', (event) => {
        sendRating()
    })

    // Add friend switching functionality - exact copy of original logic
    document.addEventListener('click', async function (event) {
        let link = event.target.closest('.username_chat_friend')

        // If the clicked element is a link with a valid href (not just '#'), allow navigation
        if (
            link &&
            link.getAttribute('href') &&
            link.getAttribute('href') !== '#'
        ) {
            return
        }

        // Otherwise, handle custom logic (e.g., changing the chat)
        if (!link) return

        event.preventDefault() // Prevent the default behavior for non-navigational links

        let isRandomChat = false

        let newFriendId = link.getAttribute('data-friend-id')

        if (newFriendId !== getActualFriendId()) {
            const modalDiscord = document.getElementById(
                'confirmationModalDiscord'
            )
            if (modalDiscord) {
                modalDiscord.style.display = 'none'
            }

            setActualFriendId(newFriendId)
            setFriendId(newFriendId)

            if (link.dataset.randomChat === 'true') {
                // It's a random chat, we want to show random chat UI
                isRandomChat = true

                localStorage.setItem(
                    'randomChatSession',
                    JSON.stringify({
                        targetUserId: newFriendId,
                        initiatedAt: Date.now(),
                    })
                )

                await initRandomChatUI(newFriendId)
            } else {
                // If last chat was random, reset to normal chat UI
                removeRandomChatControls()
            }

            replyPreviewContainer.style.display = 'none'
            closeRatingModal()
            chatInput.dataset.replyTo = ''

            let messageInput = document.getElementById('message_text')
            if (messageInput) {
                const username = messageInput.dataset.username
                const previewContainer = document.getElementById(
                    'imagePreviewContainer'
                )
                if (previewContainer) {
                    previewContainer.innerHTML = ''
                }
                messageInput.value = ''
                messageInput.placeholder = 'Talk to @' + username
            }

            await fetchMessages(userId, newFriendId, isRandomChat)

            clearImageTrue()

            const isMax1018px = window.matchMedia('(max-width: 1018px)').matches

            // Always check screen size after updating messages
            if (isMax1018px) {
                if (
                    chatInterface &&
                    window.getComputedStyle(messageContainer).display === 'none'
                ) {
                    chatInterface.style.display = 'none'
                    messageContainer.style.display = 'block'
                }
            }
        }
    })
}

export function initMessageInputEvents() {
    if (messageInput) {
        messageInput.addEventListener('focus', function () {
            markMessageAsRead(senderId, receiverId)
            setTimeout(function () {
                messageInput.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                })
            }, 300)
        })
    }
}

export function initSendButtonEvents() {
    // Button event listeners for sending messages
    if (btnDesign) {
        btnDesign.addEventListener('click', handleFormSubmit)
        btnDesign.addEventListener('touchstart', handleFormSubmit)
    }

    if (btnSubmit) {
        btnSubmit.addEventListener('touchstart', handleFormSubmit)
        btnSubmit.addEventListener('click', handleFormSubmit)
    }
}

function handleFormSubmit(event) {
    event.preventDefault()

    // Import and call handleSendMessage directly to avoid event issues
    import('./message_sender.js').then((module) => {
        module.handleSendMessage(event)
    })
}
