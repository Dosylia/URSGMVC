import {
    deleteMessageApi,
    markMessageAsReadApi,
    sendFriendRequestApi,
    closeRandomChatApi,
    findRandomPlayerApi,
} from './message_api.js'
import { fetchMessages } from './message_fetcher.js'
import { closeRatingModal, sendRating } from './message_friends.js'
import { showLoadingIndicator } from './message_renderer.js'
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

            await fetchMessages(userId, newFriendId)

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

// Initialize random chat specific events
export function initRandomChatEvents() {
    // Add friend button
    document
        .getElementById('add-friend-btn')
        ?.addEventListener('click', async () => {
            const randomSession = JSON.parse(
                localStorage.getItem('randomChatSession')
            )
            if (randomSession) {
                await sendFriendRequest(userId, randomSession.targetUserId)
            }
        })

    // Skip to next user button
    document
        .getElementById('skip-user-btn')
        ?.addEventListener('click', async () => {
            await skipToNextRandomUser()
        })

    // Close chat button
    document
        .getElementById('close-chat-btn')
        ?.addEventListener('click', async () => {
            await closeRandomChat()
        })
}

// Send friend request to random user
async function sendFriendRequest(senderId, receiverId) {
    try {
        const data = await sendFriendRequestApi(senderId, receiverId)
        if (data.success) {
            // Update the button to show success
            const addFriendBtn = document.getElementById('add-friend-btn')
            if (addFriendBtn) {
                addFriendBtn.textContent = 'Successfully Added'
                addFriendBtn.style.backgroundColor = '#93c47d'
                addFriendBtn.disabled = true
            }
        } else {
            const addFriendBtn = document.getElementById('add-friend-btn')
            if (addFriendBtn) {
                addFriendBtn.textContent = 'Failed to Add'
                addFriendBtn.style.backgroundColor = 'grey'
            }
        }
    } catch (error) {
        console.error('Error sending friend request:', error)
        const addFriendBtn = document.getElementById('add-friend-btn')
        if (addFriendBtn) {
            addFriendBtn.textContent = 'Error'
            addFriendBtn.style.backgroundColor = 'grey'
        }
    }
}

// Skip to next random user
async function skipToNextRandomUser() {
    // Close current session
    await closeRandomChat()

    // Get stored preferences and find new random player
    const prefs = {
        voice: localStorage.getItem('playerFinder_voice'),
        role: localStorage.getItem('playerFinder_role'),
        rank: localStorage.getItem('playerFinder_rank'),
        description: localStorage.getItem('playerFinder_description'),
    }

    findRandomPlayer(prefs)
}

// Close random chat session
async function closeRandomChat() {
    const randomSession = JSON.parse(localStorage.getItem('randomChatSession'))
    if (!randomSession) return

    try {
        // Notify server that chat is closed
        await closeRandomChatApi(randomSession.targetUserId)

        // Clear local storage
        localStorage.removeItem('randomChatSession')

        // Redirect back to main chat or player finder
        console.log('Random chat closed successfully')
        window.location.href = '/persoChat?message=Chat closed successfully'
    } catch (error) {
        console.error('Error closing random chat:', error)
        alert('Error closing chat. Please try again.')
    }
}

// Find random player function (moved from player finder)
function findRandomPlayer(prefs) {
    findRandomPlayerApi(prefs, userId)
        .then((data) => {
            if (data.success) {
                const randomUserId = data.randomUserId
                window.location.href = `persoChat&random_user_id=${randomUserId}`
            } else {
                alert(
                    'No random players found. Try adjusting your preferences.'
                )
            }
        })
        .catch((error) => {
            console.error('Request failed', error)
            alert('Error finding random player. Please try again.')
        })
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
                // Close current session and find new player
                const randomSession = JSON.parse(
                    localStorage.getItem('randomChatSession')
                )
                if (randomSession) {
                    await closeRandomChatApi(randomSession.targetUserId)
                    localStorage.removeItem('randomChatSession')
                    // Redirect to player finder or main chat
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
