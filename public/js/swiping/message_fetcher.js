import { fetchMessagesApi } from './message_api.js'
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

export async function fetchMessages(userId, friendId) {
    if (numberofFail >= 5) {
        console.error('Too many failed attempts. Stopping fetch loop.')
        return
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
        const data = await fetchMessagesApi(userId, friendId, firstFriend)

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
                }
            } else {
                setCurrentMessages([])
                await showFriendInfo(data.friend)
                updateMessageContainer([], data.friend, data.user)
            }
        } else {
            incrementNumberOfFail()
            console.error('Error fetching messages:', data.error)

            if (
                data.error.includes('Friend not found') ||
                data.error.includes('User not found')
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
