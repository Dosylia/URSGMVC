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
