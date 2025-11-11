import { renderEmotes, chatfilter } from './message_emotes.js'
import { replyToMessage, deleteMessage } from './message_events.js'
import { userId, messageContainer } from './message_utils.js'

export function showLoadingIndicator() {
    let messagesContainer = document.getElementById('messages')
    messagesContainer.innerHTML = '<p>Loading messages...</p>'
}

// Function to update message container
export function updateMessageContainer(messages, friend, user) {
    let messagesContainer = document.getElementById('messages')
    messagesContainer.innerHTML = '' // Clear current messages

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
