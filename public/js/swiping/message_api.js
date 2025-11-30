// api.js
function getToken() {
    return localStorage.getItem('masterTokenWebsite')
}

export async function fetchMessagesApi(userId, friendId, firstFriend) {
    const token = getToken()
    const response = await fetch('/getMessageDataWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            Authorization: `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(
            userId
        )}&friendId=${encodeURIComponent(
            friendId
        )}&firstFriend=${encodeURIComponent(firstFriend)}`,
    })
    return response.json()
}

// Fetch messages for random chat session
export async function fetchRandomChatMessagesApi(
    userId,
    friendId,
    firstFriend
) {
    const token = getToken()
    const dataToSend = {
        userId: userId,
        friendId: friendId,
        firstFriend: firstFriend,
        isRandomChat: true,
    }
    const jsonData = JSON.stringify(dataToSend)

    const response = await fetch('/getRandomChatMessages', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return await response.json()
}

export async function getGameStatusLoL(friendId) {
    const response = await fetch('/getGameStatusLoL', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `friendId=${encodeURIComponent(friendId)}`,
    })
    return response.json()
}

export async function deleteMessageApi(chatId, userId) {
    const token = getToken()
    const response = await fetch('/deleteMessageWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(userId)}&chatId=${encodeURIComponent(
            chatId
        )}`,
    })
    return response.json()
}

export async function checkIfUsersPlayedTogether(friendId, userId) {
    const token = getToken()
    const response = await fetch('/checkIfUsersPlayedTogether', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(
            parseInt(userId)
        )}&friendId=${encodeURIComponent(parseInt(friendId))}`,
    })
    return response.json()
}

export async function rateFriendWebsite(friendId, matchId, rating, userId) {
    const token = getToken()
    const response = await fetch('/rateFriendWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: `friendId=${encodeURIComponent(
            friendId
        )}&matchId=${encodeURIComponent(matchId)}&rating=${encodeURIComponent(
            rating
        )}&userId=${encodeURIComponent(userId)}`,
    })
    return response.json()
}

export async function sendMessageApi(
    senderId,
    receiverId,
    fullMessage,
    replyToChatId
) {
    const token = getToken()

    const dataToSend = {
        senderId,
        receiverId,
        message: fullMessage,
        replyToChatId,
    }

    const jsonData = JSON.stringify(dataToSend)
    const response = await fetch('/sendMessageDataWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
    return response.json()
}

export async function markMessageAsReadApi(senderId, receiverId) {
    const token = getToken()
    const dataToSend = {
        senderId,
        receiverId,
    }

    const jsonData = JSON.stringify(dataToSend)

    const response = await fetch('/markMessageAsReadWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
    return response.json()
}

export async function deleteChatImageApi(imageUrl) {
    const token = getToken()
    const response = await fetch('/deleteChatImage', {
        method: 'POST',
        headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ imageUrl }),
    })
    return response.json()
}

export async function uploadImageApi(file, senderId) {
    const token = getToken()
    const formData = new FormData()
    formData.append('image', file)
    formData.append('senderId', senderId)

    const response = await fetch('/uploadChatImage', {
        method: 'POST',
        headers: {
            Authorization: `Bearer ${token}`,
        },
        body: formData,
    })
    return response.json()
}

export async function fetchFriendlistApi(userId) {
    const token = getToken()
    const response = await fetch('/getFriendlistWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(parseInt(userId))}`,
    })
    return response.json()
}

// Send friend request API (using existing endpoint)
export async function sendFriendRequestApi(senderId, receiverId) {
    const token = getToken()

    const response = await fetch('/addAsFriendWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: `senderId=${encodeURIComponent(
            senderId
        )}&receiverId=${encodeURIComponent(receiverId)}`,
    })

    if (!response.ok) {
        const text = await response.text()
        console.error('Fetch error:', response.status, text)
        throw new Error(`HTTP error! Status: ${response.status}`)
    }

    return response.json()
}

// Close random chat session API
export async function closeRandomChatApi(targetUserId) {
    const token = getToken()
    const dataToSend = { targetUserId }
    const jsonData = JSON.stringify(dataToSend)

    const response = await fetch('/closeRandomChat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
    return response.json()
}

// Find random player API
export async function findRandomPlayerApi(prefs, userId) {
    const token = getToken()
    const dataToSend = {
        voiceChat: prefs.voice !== undefined ? prefs.voice : null,
        roleLookingFor: prefs.role || null,
        rankLookingFor: prefs.rank || null,
        description: prefs.description || null,
        userId: userId,
    }
    const jsonData = JSON.stringify(dataToSend)

    const response = await fetch('/getRandomPlayerFinderChat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
    return response.json()
}

// Check for incoming random chat sessions API
export async function checkIncomingRandomChatsApi(userId) {
    const token = getToken()
    const dataToSend = { userId }
    const jsonData = JSON.stringify(dataToSend)

    const response = await fetch('/checkIncomingRandomChats', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
    return response.json()
}

// Validate if random chat session is still active
export async function validateRandomChatSessionApi(userId, targetUserId) {
    const token = getToken()
    const dataToSend = { userId, targetUserId }
    const jsonData = JSON.stringify(dataToSend)

    const response = await fetch('/validateRandomChatSession', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
    return response.json()
}
