import { fetchMessagesApi, fetchRandomChatMessagesApi } from './message_api.js'
import { showFriendInfo } from './message_friends.js'
import {
    updateMessageContainer,
    showLoadingIndicator,
} from './message_renderer.js'
import {
    userId,
    isFirstFetch,
    numberofFail,
    currentMessages,
    firstFriendId,
    setFirstFetch,
    setNumberOfFail,
    incrementNumberOfFail,
    setCurrentMessages,
} from './message_utils.js'

export async function fetchMessages(userId, friendId, isRandomChat = false) {
    if (numberofFail >= 5) {
        console.error('Too many failed attempts. Stopping fetch loop.')
        return
    }

    // Check if this is a random chat session
    // This check might seem too light
    const randomSession = localStorage.getItem('randomChatSession')

    if (!isRandomChat) {
        isRandomChat = randomSession !== null
    }

    const firstFriendInput = document.getElementById('firstFriend')
    let firstFriend = firstFriendInput ? firstFriendInput.value : null

    if (firstFriend && friendId !== firstFriendId) {
        firstFriendInput.value = 'no'
    }

    const hasFocus = document.hasFocus()
    if (!hasFocus) {
        firstFriend = 'yes'
    }

    if (isFirstFetch) {
        showLoadingIndicator()
        setFirstFetch(false) // Reset the flag after the first fetch
    }

    try {
        // Use different API endpoint for random chat
        const data = isRandomChat
            ? await fetchRandomChatMessagesApi(userId, friendId, firstFriend)
            : await fetchMessagesApi(userId, friendId, firstFriend)

        if (data.success) {
            setNumberOfFail(0)
            if (data.messages !== null && data.messages !== undefined) {
                if (
                    JSON.stringify(currentMessages) !==
                    JSON.stringify(data.messages)
                ) {
                    setCurrentMessages(data.messages)
                    await showFriendInfo(data.friend)
                    updateMessageContainer(
                        data.messages,
                        data.friend,
                        data.user
                    )
                } else {
                    await showFriendInfo(data.friend)
                    // Still call updateMessageContainer for random chat even if messages haven't changed
                    if (isRandomChat) {
                        updateMessageContainer(
                            data.messages,
                            data.friend,
                            data.user
                        )
                    }
                }
            } else {
                setCurrentMessages([])
                await showFriendInfo(data.friend)
                updateMessageContainer([], data.friend, data.user)
            }
        } else {
            incrementNumberOfFail()
            console.error('Error fetching messages:', data.message)

            // Handle expired random chat session
            if (
                isRandomChat &&
                data.message.includes('No active random chat session')
            ) {
                console.log(
                    'Random chat session expired - clearing localStorage and redirecting'
                )
                localStorage.removeItem('randomChatSession')
                // Redirect to normal chat flow
                window.location.href = '/persoChat'
                return
            }

            if (
                data.message.includes('Friend not found') ||
                data.message.includes('User not found')
            ) {
                console.warn(
                    'Stopping message fetch loop due to missing friend/user.'
                )
                return
            }

            // Optional: retry for other types of logical errors
            setTimeout(() => fetchMessages(userId, friendId), 5000)
        }
    } catch (error) {
        incrementNumberOfFail()
        console.error('Fetch or JSON parse error:', error)

        // Retry only for temporary issues (not "Friend not found", etc.)
        if (
            !error.message.includes('Friend not found') &&
            !error.message.includes('User not found')
        ) {
            setTimeout(() => fetchMessages(userId, friendId), 5000)
        } else {
            console.warn('Not retrying due to invalid user/friend.')
        }
    }
}
