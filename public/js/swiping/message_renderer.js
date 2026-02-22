import { renderEmotes, chatfilter } from './message_emotes.js'
import { replyToMessage, deleteMessage } from './message_events.js'
import { handleSendMessage } from './message_sender.js'
import { userId, messageContainer } from './message_utils.js'

// Render random chat control elements (UI only)
export function renderRandomChatControls() {
    const messagesContainer = document.getElementById('messages')
    if (!messagesContainer) return

    // Check if controls already exist to avoid duplicates
    if (document.getElementById('random-chat-header')) {
        return
    }

    // Create a persistent container for random chat controls (outside message container)
    let controlsContainer = document.getElementById(
        'random-chat-controls-container'
    )
    if (!controlsContainer) {
        controlsContainer = document.createElement('div')
        controlsContainer.id = 'random-chat-controls-container'

        // Insert before the messages container
        if (messagesContainer && messagesContainer.parentNode) {
            messagesContainer.parentNode.insertBefore(
                controlsContainer,
                messagesContainer
            )
        }
    }

    // Create random chat header
    const randomChatHeader = document.createElement('div')
    randomChatHeader.id = 'random-chat-header'
    randomChatHeader.style.cssText = `
        background: #007bff;
        color: white;
        padding: 10px;
        text-align: center;
        margin-bottom: 10px;
        border-radius: 5px;
    `
    randomChatHeader.innerHTML = `
        <p>🎲 Random Chat Session</p>
        <p style="font-size: 12px;">Chat with a random player!</p>
    `

    // Create action buttons
    const actionButtons = document.createElement('div')
    actionButtons.id = 'random-chat-buttons'
    actionButtons.style.cssText = `
        display: flex;
        gap: 10px;
        justify-content: center;
        margin: 10px 0;
    `

    actionButtons.innerHTML = `
        <button id="add-friend-btn" style="
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        ">Add Friend</button>
        <button id="skip-user-btn" style="
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        ">Skip to Next</button>
        <button id="close-chat-btn" style="
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        ">Close Chat</button>
    `

    // Append to persistent container
    controlsContainer.appendChild(randomChatHeader)
    controlsContainer.appendChild(actionButtons)
}

export function removeRandomChatControls() {
    const controlsContainer = document.getElementById(
        'random-chat-controls-container'
    )
    if (controlsContainer) {
        controlsContainer.remove()
    }
}

export function showLoadingIndicator() {
    let messagesContainer = document.getElementById('messages')
    messagesContainer.innerHTML = '<p>Loading messages...</p>'
}

// Function to update message container
export function updateMessageContainer(messages, friend, user) {
    let messagesContainer = document.getElementById('messages')

    // Check if this is a random chat session
    const randomSession = localStorage.getItem('randomChatSession')
    const isRandomChat = randomSession !== null

    // Skip re-render if random chat openers are already showing and there are still no messages
    if (
        isRandomChat &&
        (!messages || messages.length === 0) &&
        messagesContainer.querySelector('#random-chat-openers')
    ) {
        return
    }

    messagesContainer.innerHTML = '' // Clear current messages

    // Check if this is a random chat session (for the target user)
    const isTargetOfRandomChat = friend?.isRandomChatTarget

    if (isTargetOfRandomChat) {
        // Add special header for users who were randomly found
        const randomTargetHeader = document.createElement('div')
        randomTargetHeader.style.cssText = `
            background: #ff6b35;
            color: white;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 10px;
        `
        randomTargetHeader.innerHTML = `
            <p>🎯 This user randomly found you!</p>
            <p style="font-size: 12px;">You can chat until they close the session.</p>
        `
        messagesContainer.appendChild(randomTargetHeader)
    }

    // Add conversation starters if this is a random chat with no messages
    if (isRandomChat && (!messages || messages.length === 0)) {
        // Get the game from playerfinder filters (set before random chat starts)
        const filters = JSON.parse(
            localStorage.getItem('playerfinder_filters') || '{}'
        )
        addRandomChatOpeners(
            messagesContainer,
            filters.game || 'League of Legends'
        )
        return // Exit early since there are no messages to display
    }

    let previousMessage = null // Variable to store the previous message
    let unreadSectionStarted = false

    messages.forEach((message) => {
        let isCurrentUser = message.chat_senderId == userId
        let messageClass = isCurrentUser
            ? 'message-from-user'
            : 'message-to-user'
        let messagePosition = isCurrentUser ? 'left' : 'left'
        let userPosition = isCurrentUser ? 'left' : 'left' // right - left to go back to chat on sides
        let lastMessagePosition = isCurrentUser ? 'flex-start' : 'flex-start' // flex-end - flex-start to go back to chat on sides
        let messageUser = isCurrentUser ? user : friend
        let messageLink = isCurrentUser ? 'userProfile' : 'anotherUser'
        let timestampPosition = isCurrentUser ? 'inverted' : 'inverted' // normal - inverted to go back to chat on sides
        let replyContainerClass = isCurrentUser ? 'normal' : 'inverted'
        let backgroundColor = isCurrentUser ? '#e84056' : ''
        let pictureLink
        let senderOwnsGoldEmotes =
            message.chat_senderId == user.user_id
                ? user.ownGoldEmotes
                : friend.ownGoldEmotes

        if (
            messageUser.user_picture === null ||
            messageUser.user_picture === undefined
        ) {
            pictureLink = 'images/defaultprofilepicture.jpg'
        } else {
            pictureLink = `upload/${messageUser.user_picture}`
        }

        if (
            !unreadSectionStarted &&
            !isCurrentUser &&
            message.chat_status === 'unread'
        ) {
            // Create separator for unread messages
            let separator = document.createElement('div')
            separator.className = 'unread-separator'
            separator.innerHTML = `
                    <span>New message</span>
                    <hr>
                `
            messagesContainer.appendChild(separator)
            unreadSectionStarted = true
        }

        let messageDiv = document.createElement('div')
        messageDiv.classList.add('message', messageClass)
        messageDiv.id = `message-${message.chat_id}`

        // Message Status (for sent messages only)
        let messageStatus = ''
        if (isCurrentUser) {
            messageStatus =
                message.chat_status === 'read'
                    ? '<i style="color:rgb(196, 220, 17);" class="fa-solid fa-envelope-circle-check"></i>'
                    : '<i class="fa-solid fa-envelope"></i>'
        }

        let utcDate = new Date(message.chat_date)
        let localOffset = utcDate.getTimezoneOffset()
        let localDate = new Date(utcDate.getTime() - localOffset * 60000)

        // Format for today
        const isToday =
            new Date(message.chat_date).toDateString() ===
            new Date().toDateString()

        // Format time
        let formattedTime = localDate.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
        })

        // Format full date for other days
        let formattedDate
        if (!isToday) {
            formattedDate = new Date(message.chat_date).toLocaleDateString(
                'fr-FR'
            )
        } else {
            formattedDate = `Today ${formattedTime}`
        }

        // Check if previous message exists and is from the same sender within 5 minutes
        let messageContent

        if (message.chat_replyTo) {
            let originalMessage = messages.find(
                (m) => m.chat_id == message.chat_replyTo
            )
            if (originalMessage) {
                let replacedMessage = originalMessage.chat_message.replace(
                    /\[img\](.*?)\[\/img\]/g,
                    'Contains a 📷'
                )

                const truncatedMessage =
                    replacedMessage.length > 50
                        ? replacedMessage.substring(0, 50) + '...'
                        : replacedMessage

                let finalMessage = truncatedMessage
                if (
                    previousMessage &&
                    previousMessage.chat_senderId === message.chat_senderId
                ) {
                    let timeDifference =
                        new Date(message.chat_date) -
                        new Date(previousMessage.chat_date)
                    if (timeDifference <= 5 * 60 * 1000) {
                        messageContent = `
                            <p class="last-message" style="text-align: ${messagePosition}; padding-top: 20px; padding-bottom: 5px; position: relative; z-index: 950;">
                                <span class="timestamp-hover">${formattedTime}</span>
                                <span class="message-text" style="text-align: ${messagePosition}; background-color: ${backgroundColor};">${
                                    user.user_hasChatFilter
                                        ? renderEmotes(
                                              chatfilter(
                                                  message.chat_message,
                                                  senderOwnsGoldEmotes
                                              )
                                          )
                                        : renderEmotes(
                                              message.chat_message,
                                              senderOwnsGoldEmotes
                                          )
                                }
                                ${
                                    isCurrentUser
                                        ? `<span class="message-status">${messageStatus}</span>`
                                        : ''
                                }
                                </span>
                                <span class="replied-message ${replyContainerClass}" style="position: absolute; 
                                        top: 5px; 
                                        ${
                                            isCurrentUser
                                                ? 'left: 0'
                                                : 'left: 0'
                                        }; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${
                                            isCurrentUser
                                                ? 'text-align: left'
                                                : 'text-align: left'
                                        }; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;"
                                    data-reply-id="${message.chat_replyTo}">
                                        ${renderEmotes(finalMessage)}
                                </span>
                            </p>
                            `
                    } else {
                        messageContent = `
                                <p id="username_message" style="text-align: ${userPosition}; padding-bottom: 20px;">
                                    <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(
                                        messageUser.user_username
                                    )}"><strong class="strong_text">${
                                        messageUser.user_username
                                    }</strong></a>
                                    <span class="timestamp ${messagePosition}">${formattedDate}</span>
                                </p>
                                <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px; position: relative; z-index: 950;">
                                    <span class="timestamp-hover">${formattedTime}</span>
                                    <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${
                                        user.user_hasChatFilter
                                            ? renderEmotes(
                                                  chatfilter(
                                                      message.chat_message,
                                                      senderOwnsGoldEmotes
                                                  )
                                              )
                                            : renderEmotes(
                                                  message.chat_message,
                                                  senderOwnsGoldEmotes
                                              )
                                    }
                                    ${
                                        isCurrentUser
                                            ? `<span class="message-status">${messageStatus}</span>`
                                            : ''
                                    }
                                    </span>
                                    <span class="replied-message ${replyContainerClass}" style="position: absolute; 
                                        top: -10px; 
                                        ${
                                            isCurrentUser
                                                ? 'left: 0'
                                                : 'left: 0'
                                        }; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${
                                            isCurrentUser
                                                ? 'text-align: left'
                                                : 'text-align: left'
                                        }; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;"
                                    data-reply-id="${message.chat_replyTo}">
                                        ${renderEmotes(finalMessage)}
                                    </span>
                                </p>
                            `
                    }
                } else {
                    messageContent = `
                                <p id="username_message" style="text-align: ${userPosition}; padding-bottom: 20px;">
                                    <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(
                                        messageUser.user_username
                                    )}">
                                        <strong class="strong_text">${
                                            messageUser.user_username
                                        }</strong>
                                    </a>
                                    <span class="timestamp ${messagePosition}">${formattedDate}</span>
                                </p>
                                <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px; position: relative; z-index: 950;">
                                    <span class="timestamp-hover">${formattedTime}</span>
                                    <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">
                                        ${
                                            user.user_hasChatFilter
                                                ? renderEmotes(
                                                      chatfilter(
                                                          message.chat_message,
                                                          senderOwnsGoldEmotes
                                                      )
                                                  )
                                                : renderEmotes(
                                                      message.chat_message,
                                                      senderOwnsGoldEmotes
                                                  )
                                        }
                                        ${
                                            isCurrentUser
                                                ? `<span class="message-status">${messageStatus}</span>`
                                                : ''
                                        }
                                    </span>
                                    <span class="replied-message ${replyContainerClass}" style="position: absolute; 
                                        top: -10px; 
                                        ${
                                            isCurrentUser
                                                ? 'left: 0'
                                                : 'left: 0'
                                        }; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${
                                            isCurrentUser
                                                ? 'text-align: left'
                                                : 'text-align: left'
                                        }; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;"
                                    data-reply-id="${message.chat_replyTo}">
                                        ${renderEmotes(
                                            finalMessage,
                                            senderOwnsGoldEmotes
                                        )}
                                    </span>
                                </p>

                        `
                }
            } else {
                if (
                    previousMessage &&
                    previousMessage.chat_senderId === message.chat_senderId
                ) {
                    let timeDifference =
                        new Date(message.chat_date) -
                        new Date(previousMessage.chat_date)
                    if (timeDifference <= 5 * 60 * 1000) {
                        messageContent = `
                            <p class="last-message" style="text-align: ${messagePosition}; position: relative;">
                                <span class="timestamp-hover">${formattedTime}</span>
                                <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${
                                    user.user_hasChatFilter
                                        ? renderEmotes(
                                              chatfilter(
                                                  message.chat_message,
                                                  senderOwnsGoldEmotes
                                              )
                                          )
                                        : renderEmotes(
                                              message.chat_message,
                                              senderOwnsGoldEmotes
                                          )
                                }
                                ${
                                    isCurrentUser
                                        ? `<span class="message-status">${messageStatus}</span>`
                                        : ''
                                }
                                </span>
                                <span class="replied-message ${replyContainerClass}" style="position: absolute; 
                                        top: -10px; 
                                        ${
                                            isCurrentUser
                                                ? 'left: 0'
                                                : 'left: 0'
                                        }; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${
                                            isCurrentUser
                                                ? 'text-align: left'
                                                : 'text-align: left'
                                        }; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;">
                                    [Message unavailable]
                                </span>
                            </p>
                            `
                    } else {
                        // Build message with sender info and timestamp
                        messageContent = `
                                <p id="username_message" style="text-align: ${userPosition}; padding-bottom: 20px;">
                                    <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(
                                        messageUser.user_username
                                    )}"><strong class="strong_text">${
                                        messageUser.user_username
                                    }</strong></a>
                                    <span class="timestamp ${messagePosition}">${formattedDate}</span>
                                </p>
                                <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px; position: relative;">
                                    <span class="timestamp-hover">${formattedTime}</span>
                                    <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${
                                        user.user_hasChatFilter
                                            ? renderEmotes(
                                                  chatfilter(
                                                      message.chat_message,
                                                      senderOwnsGoldEmotes
                                                  )
                                              )
                                            : renderEmotes(
                                                  message.chat_message,
                                                  senderOwnsGoldEmotes
                                              )
                                    }
                                    ${
                                        isCurrentUser
                                            ? `<span class="message-status">${messageStatus}</span>`
                                            : ''
                                    }
                                    </span>
                                    <span class="replied-message ${replyContainerClass}" style="position: absolute; 
                                        top: -10px; 
                                        ${
                                            isCurrentUser
                                                ? 'left: 0'
                                                : 'left: 0'
                                        }; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${
                                            isCurrentUser
                                                ? 'text-align: left'
                                                : 'text-align: left'
                                        }; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;">
                                        [Message unavailable]
                                    </span>
                                </p>
                            `
                    }
                } else {
                    // Build message with sender info
                    messageContent = `
                            <p id="username_message" style="text-align: ${userPosition}; padding-bottom: 20px;">
                                <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(
                                    messageUser.user_username
                                )}"><strong class="strong_text">${
                                    messageUser.user_username
                                }</strong></a>
                                <span class="timestamp ${messagePosition}">${formattedDate}</span>
                            </p>
                            <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px; position: relative;">
                                <span class="timestamp-hover">${formattedTime}</span>
                                <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${
                                    user.user_hasChatFilter
                                        ? renderEmotes(
                                              chatfilter(
                                                  message.chat_message,
                                                  senderOwnsGoldEmotes
                                              )
                                          )
                                        : renderEmotes(
                                              message.chat_message,
                                              senderOwnsGoldEmotes
                                          )
                                }
                                ${
                                    isCurrentUser
                                        ? `<span class="message-status">${messageStatus}</span>`
                                        : ''
                                }
                                </span>
                                <span class="replied-message ${replyContainerClass}" style="position: absolute; 
                                        top: -10px; 
                                        ${
                                            isCurrentUser
                                                ? 'left: 0'
                                                : 'left: 0'
                                        }; 
                                        font-size: 0.9em; 
                                        z-index: 999; 
                                        ${
                                            isCurrentUser
                                                ? 'text-align: left'
                                                : 'text-align: left'
                                        }; 
                                        word-wrap: break-word; 
                                        max-width: 100%;
                                        padding: 0 10px;">
                                    [Message unavailable]
                                </span>
                            </p>
                        `
                }
            }
        } else {
            if (
                previousMessage &&
                previousMessage.chat_senderId === message.chat_senderId
            ) {
                let timeDifference =
                    new Date(message.chat_date) -
                    new Date(previousMessage.chat_date)
                if (timeDifference <= 5 * 60 * 1000) {
                    messageContent = `
                        <p class="last-message" style="text-align: ${messagePosition};">
                            <span class="timestamp-hover">${formattedTime}</span>
                            <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${
                                user.user_hasChatFilter
                                    ? renderEmotes(
                                          chatfilter(
                                              message.chat_message,
                                              senderOwnsGoldEmotes
                                          )
                                      )
                                    : renderEmotes(
                                          message.chat_message,
                                          senderOwnsGoldEmotes
                                      )
                            }
                            ${
                                isCurrentUser
                                    ? `<span class="message-status">${messageStatus}</span>`
                                    : ''
                            }
                            </span>
                        </p>
                        `
                } else {
                    messageContent = `
                            <p id="username_message" style="text-align: ${userPosition};">
                                <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(
                                    messageUser.user_username
                                )}"><strong class="strong_text">${
                                    messageUser.user_username
                                }</strong></a>
                                <span class="timestamp ${messagePosition}">${formattedDate}</span>
                            </p>
                            <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px;">
                                <span class="timestamp-hover">${formattedTime}</span>
                                <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${
                                    user.user_hasChatFilter
                                        ? renderEmotes(
                                              chatfilter(
                                                  message.chat_message,
                                                  senderOwnsGoldEmotes
                                              )
                                          )
                                        : renderEmotes(
                                              message.chat_message,
                                              senderOwnsGoldEmotes
                                          )
                                }
                                ${
                                    isCurrentUser
                                        ? `<span class="message-status">${messageStatus}</span>`
                                        : ''
                                }
                                </span>
                            </p>
                        `
                }
            } else {
                messageContent = `
                        <p id="username_message" style="text-align: ${userPosition};">
                            <a class="username_chat_friend clickable" target="_blank" href="/${messageLink}&username=${encodeURIComponent(
                                messageUser.user_username
                            )}"><strong class="strong_text">${
                                messageUser.user_username
                            }</strong></a>
                            <span class="timestamp ${messagePosition}">${formattedDate}</span>
                        </p>
                        <p class="last-message" style="text-align: ${messagePosition}; padding-top: 3px;">
                            <span class="timestamp-hover">${formattedTime}</span>
                            <span class="message-text" style="text-align: ${messagePosition};  background-color: ${backgroundColor};">${
                                user.user_hasChatFilter
                                    ? renderEmotes(
                                          chatfilter(
                                              message.chat_message,
                                              senderOwnsGoldEmotes
                                          )
                                      )
                                    : renderEmotes(
                                          message.chat_message,
                                          senderOwnsGoldEmotes
                                      )
                            }
                            ${
                                isCurrentUser
                                    ? `<span class="message-status">${messageStatus}</span>`
                                    : ''
                            }
                            </span>
                        </p>
                    `
            }
        }

        messageContent = messageContent.replace(
            /https:\/\/discord\.gg\/[a-zA-Z0-9]+/g,
            function (url) {
                return `<a href="${url}" target="_blank" class="discord-link">Click to join</a>`
            }
        )

        messageContent = processMessageContent(messageContent)

        messageDiv.innerHTML = messageContent

        // **Create Hover Menu**
        let hoverMenu = document.createElement('div')
        hoverMenu.classList.add('hover-menu')
        hoverMenu.innerHTML = `<span class="menu-button">...</span>`

        let options = document.createElement('div')
        options.classList.add('hover-options')
        options.style.display = 'none' // Initially hidden

        // Toggle menu when clicking the three dots
        hoverMenu
            .querySelector('.menu-button')
            .addEventListener('click', (event) => {
                event.stopPropagation() // Prevent the click from closing immediately
                options.style.display =
                    options.style.display === 'none' ? 'block' : 'none'
            })

        // Close menu when clicking anywhere outside
        document.addEventListener('click', () => {
            options.style.display = 'none'
        })

        // Reply Button
        let replyButton = document.createElement('button')
        replyButton.textContent = 'Reply'
        replyButton.type = 'button'
        replyButton.addEventListener('click', () =>
            replyToMessage(
                message.chat_id,
                message.chat_message,
                messageUser.user_username
            )
        )
        options.appendChild(replyButton)

        // Delete Button (Only for Current User)
        if (isCurrentUser) {
            let deleteButton = document.createElement('button')
            deleteButton.textContent = 'Delete'
            deleteButton.addEventListener('click', () =>
                deleteMessage(message.chat_id, messageUser.user_id)
            )
            options.appendChild(deleteButton)
        }

        hoverMenu.appendChild(options)

        // **Show/Hide on Hover**
        hoverMenu.style.display = 'none'
        messageDiv.addEventListener(
            'mouseenter',
            () => (hoverMenu.style.display = 'block')
        )
        messageDiv.addEventListener(
            'mouseleave',
            () => (hoverMenu.style.display = 'none')
        )

        messagesContainer.appendChild(messageDiv)

        // Store the current message as previousMessage for the next iteration
        previousMessage = message

        const lastMessage = messageDiv.querySelector('.last-message')
        if (lastMessage) {
            lastMessage.classList.add(timestampPosition)
            lastMessage.style.justifyContent = lastMessagePosition
        }

        // Add hover behavior for timestamp
        let timestampSpan = messageDiv.querySelector('.timestamp-hover')
        if (timestampSpan) {
            timestampSpan.parentNode.insertBefore(hoverMenu, timestampSpan)
            timestampSpan.style.display = 'none'
            messageDiv.addEventListener('mouseenter', function () {
                timestampSpan.style.display = 'inline-block'
            })

            messageDiv.addEventListener('mouseleave', function () {
                timestampSpan.style.display = 'none'
            })
        }
    })

    // Add click handlers to replied message previews
    document.querySelectorAll('.replied-message').forEach((element) => {
        element.addEventListener('click', function (e) {
            console.log('Clicked on replied message preview!')
            e.stopPropagation()
            const replyId = this.dataset.replyId
            if (replyId) {
                const originalMessage = document.getElementById(
                    `message-${replyId}`
                )
                if (originalMessage) {
                    // Smooth scroll to original message
                    originalMessage.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    })

                    // Visual feedback (optional)
                    const originalMessageSpan =
                        originalMessage.querySelector('span.message-text')
                    originalMessageSpan.style.backgroundColor = '#ffeb3b40'
                    setTimeout(() => {
                        originalMessageSpan.style.backgroundColor = ''
                    }, 3000)
                }
            }
        })
    })

    setTimeout(scrollToBottom, 100)
}

// Add conversation openers for random chat when no messages exist
function addRandomChatOpeners(messagesContainer, userGame) {
    const openersContainer = document.createElement('div')
    openersContainer.id = 'random-chat-openers'

    openersContainer.innerHTML = `
        <h3 style="margin-bottom: 15px; color: white;">🎮 Start the conversation!</h3>
        <p style="margin-bottom: 20px; opacity: 0.9;">Choose a conversation starter or type your own message:</p>
    `

    const buttonContainer = document.createElement('div')
    buttonContainer.style.cssText = `
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
        margin-top: 15px;
    `

    openersContainer.appendChild(buttonContainer)
    messagesContainer.appendChild(openersContainer)

    // Fetch openers and create buttons once data arrives
    fetch('public/js/swiping/chat_openers.json')
        .then((response) => response.json())
        .then((data) => {
            const gameOpeners = data[userGame] || []

            //Use only 3 random openers if there are more than 3
            if (gameOpeners.length > 3) {
                gameOpeners.sort(() => 0.5 - Math.random())
                gameOpeners.length = 3
            }

            gameOpeners.forEach((opener) => {
                const button = document.createElement('button')
                button.textContent = opener
                button.style.cssText = `
                    background: rgba(255, 255, 255, 0.2);
                    border: 1px solid rgba(255, 255, 255, 0.3);
                    color: white;
                    padding: 8px 12px;
                    border-radius: 20px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    font-size: 14px;
                    backdrop-filter: blur(10px);
                `

                button.addEventListener('mouseenter', () => {
                    button.style.background = 'rgba(255, 255, 255, 0.3)'
                    button.style.transform = 'translateY(-2px)'
                })

                button.addEventListener('mouseleave', () => {
                    button.style.background = 'rgba(255, 255, 255, 0.2)'
                    button.style.transform = 'translateY(0)'
                })

                button.addEventListener('click', () => {
                    const messageInput = document.getElementById('message_text')
                    if (messageInput) {
                        messageInput.value = opener
                        messageInput.focus()
                        messageInput.dispatchEvent(
                            new Event('input', { bubbles: true })
                        )

                        // Automatically send the message
                        setTimeout(() => {
                            const fakeEvent = new Event('submit', {
                                bubbles: true,
                                cancelable: true,
                            })
                            handleSendMessage(fakeEvent)
                        }, 100)
                    }
                })

                buttonContainer.appendChild(button)
            })
        })
}

export function processMessageContent(messageContent) {
    // Replace [img][/img] with a sanitized image URL
    messageContent = messageContent.replace(
        /\[img\](.*?)\[\/img\]/g,
        function (match, url) {
            // Sanitize the URL
            const sanitizedUrl = sanitizeUrl(url)

            if (sanitizedUrl) {
                // Return a safe, clickable image if the URL is valid
                return `<a href="${sanitizedUrl}" target="_blank"><img src="${sanitizedUrl}" class="chat-image" alt="Sent image"></a>`
            } else {
                // If the URL is invalid or harmful, return an empty string or a warning placeholder
                return '<span class="invalid-url-warning">Invalid image URL</span>'
            }
        }
    )

    return messageContent
}

export function sanitizeUrl(url) {
    const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp']
    const urlLower = url.toLowerCase()

    // Check if the URL contains a valid image extension
    const isValidImage = imageExtensions.some((ext) => urlLower.endsWith(ext))

    // If the URL is relative (i.e., doesn't start with 'http://', 'https://', or '/'), allow it
    if (isValidImage) {
        if (
            url.startsWith('/') ||
            urlLower.startsWith('http://') ||
            urlLower.startsWith('https://')
        ) {
            return url // Valid relative or absolute image URL
        } else {
            // Allow relative paths (no protocol)
            return `/${url}` // Prepend a '/' to make it a valid relative URL
        }
    }

    return null // If not a valid image, return null
}

// Function to scroll to the bottom of the messages container
export function scrollToBottom() {
    const messagesContainer = document.getElementById('messages')
    if (!messagesContainer) return

    // Smooth scroll to bottom
    messagesContainer.scrollTo({
        top: messagesContainer.scrollHeight,
        behavior: 'auto',
    })
}
