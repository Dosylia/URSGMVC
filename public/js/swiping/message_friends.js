import {
    getGameStatusLoL,
    checkIfUsersPlayedTogether,
    rateFriendWebsite,
} from './message_api.js'
import {
    userId,
    friendIdElement,
    RatingModal,
    currentFriendUsername,
    submitRating,
    lastFriendStatus,
    friendData,
    setCurrentFriendUsername,
    setLastFriendStatus,
} from './message_utils.js'

export async function showFriendInfo(friend) {
    if (!friend) return

    const isSameFriend = friend.user_username === currentFriendUsername
    const hasSameStatus = friend.user_isOnline === lastFriendStatus

    if (isSameFriend && hasSameStatus) return

    if (isSameFriend && !hasSameStatus) {
        const statusSpan = document.querySelector(
            '#friendTop .online-status, #friendTop .offline-status, #friendTop .looking-game-status'
        )
        if (statusSpan) {
            statusSpan.className =
                friend.user_isOnline === 1 ? 'online-status' : 'offline-status'
        }
        if (friend.user_isOnline === 0) {
            const statusGame = document.querySelector('.ingame-status')
            if (statusGame) {
                statusGame.remove()
            }
        }
        setLastFriendStatus(friend.user_isOnline)
        return
    }

    friendIdElement.value = friend.user_id
    const usernameFriend = document.getElementById('message_text')
    usernameFriend.placeholder = `Talk to @${friend.user_username}`
    setCurrentFriendUsername(friend.user_username)

    const pictureLink = friend.user_picture
        ? `upload/${friend.user_picture}`
        : 'images/defaultprofilepicture.jpg'

    let friendGameStatus = false
    let friendLeagueStatus = null
    let gamemode = ''

    if (friend.lol_verified === 1) {
        checkIfUsersPlayedTogether(friend.user_id, userId)
        friendLeagueStatus = await getGameStatusLoL(friend.user_id)
        if (friendLeagueStatus) {
            friendGameStatus = true
            gamemode =
                friendLeagueStatus.gameMode === 'CHERRY'
                    ? 'ARENA'
                    : friendLeagueStatus.gameMode
        }
    }

    // Build the entire friend card as a DOM element for easier referencing
    if (!friendData) {
        friendData = document.createElement('div')
        friendData.id = 'friendData'
        document.body.appendChild(friendData)
    }

    const container = document.createElement('div')
    container.id = 'friendTop'

    const userInfo = document.createElement('span')
    userInfo.style.width = '80%'
    userInfo.style.display = 'flex'
    userInfo.style.gap = '5px'
    userInfo.innerHTML = `
            <img class="avatar" src="public/${pictureLink}" alt="Avatar ${
                friend.user_username
            }">
            <a class="username_chat_friend" target="_blank" href="/anotherUser&username=${encodeURIComponent(
                friend.user_username
            )}">
                <strong class="strong_text">${friend.user_username}</strong>
            </a>
        `
    container.appendChild(userInfo)

    // League of Legends section with copy interaction
    if (friend.lol_verified === 1) {
        const lolSection = document.createElement('span')
        lolSection.className = 'friend-details-top'

        const lolLogo = document.createElement('img')
        lolLogo.src = 'public/images/lol-logo.png'
        lolLogo.alt = 'League of Legends'

        const lolUsername = document.createElement('p')
        lolUsername.className = 'friends-lol-username'
        lolUsername.dataset.username = friend.lol_account
        lolUsername.innerHTML = `${friend.lol_account} <i class="fa-solid fa-copy"></i>`

        // 👇 Attach the copy functionality here
        lolUsername.addEventListener('click', () => {
            const username = friend.lol_account
            const temp = document.createElement('textarea')
            temp.value = username
            document.body.appendChild(temp)
            temp.select()
            document.execCommand('copy')
            document.body.removeChild(temp)

            const copyIcon = lolUsername.querySelector('.fa-copy')
            if (copyIcon) {
                copyIcon.classList.add('visible')
                setTimeout(() => copyIcon.classList.remove('visible'), 1000)
            }
        })

        lolSection.appendChild(lolLogo)
        lolSection.appendChild(lolUsername)
        container.appendChild(lolSection)
    }

    // Game status
    if (friendGameStatus) {
        const status = document.createElement('span')
        status.className = 'ingame-status'
        status.innerText = `🎮 Playing ${friendLeagueStatus.champion} (${gamemode})`
        container.appendChild(status)
    }

    // Online/Offline status
    const statusSpan = document.createElement('span')
    if (friend.user_isOnline === 1) {
        statusSpan.className =
            friend.user_isLooking === 1
                ? 'looking-game-status'
                : 'online-status'
    } else {
        statusSpan.className = 'offline-status'
    }
    container.appendChild(statusSpan)

    // Render
    friendData.innerHTML = ''
    friendData.appendChild(container)

    // Message container setup
    let messagesContainer = document.getElementById('messages')
    if (!messagesContainer) {
        messagesContainer = document.createElement('div')
        messagesContainer.id = 'messages'
        document.body.appendChild(messagesContainer)
    }

    // Don't clear here - let the renderer handle message clearing
    messagesContainer.style.minHeight = 'calc(var(--vh, 1vh) * 65)'
}

export function handleRatingPrompt(friendId, commonMatches) {
    const ratingData = JSON.parse(localStorage.getItem('ratingData') || '{}')
    const friendKey = `friendId_${friendId}`
    const friendData = ratingData[friendKey] || {
        ratedMatches: {},
        lastRatingTime: 0,
    }
    const oneWeek = 7 * 24 * 60 * 60 * 1000
    const now = Date.now()

    // Don't ask if rated in the last week
    if (now - friendData.lastRatingTime < oneWeek) return

    // Find a matchId that hasn't been rated yet
    const newMatchId = commonMatches.find((id) => !friendData.ratedMatches[id])
    if (!newMatchId) return

    showRatingModal(friendId, newMatchId)
}

function showRatingModal(friendId, matchId) {
    RatingModal.classList.remove('rating-modal-hidden')
    document.getElementById('overlay').style.display = 'block'
    submitRating.setAttribute('data-friend-id', friendId)
    submitRating.setAttribute('data-match-id', matchId)
}

export function closeRatingModal(type) {
    RatingModal.classList.add('rating-modal-hidden')
    const overlay = document.getElementById('overlay')
    overlay.style.display = 'none'

    const friendId = submitRating.getAttribute('data-friend-id')
    const friendKey = `friendId_${friendId}`
    let ratingData = JSON.parse(localStorage.getItem('ratingData') || '{}')
    const friendData = ratingData[friendKey] || {
        ratedMatches: {},
        lastRatingTime: 0,
    }

    // Only update if not a successful rating
    if (type !== 'success') {
        friendData.lastRatingTime = Date.now()
        ratingData[friendKey] = friendData
        localStorage.setItem('ratingData', JSON.stringify(ratingData))
    }
}

export function sendRating() {
    const friendId = submitRating.getAttribute('data-friend-id')
    const matchId = submitRating.getAttribute('data-match-id')
    const rating = document.getElementById('rating-score').value
    const token = localStorage.getItem('masterTokenWebsite')

    // Use api function to send the rating
    rateFriendWebsite(friendId, matchId, rating, userId)
        .then((data) => {
            if (data.success) {
                let ratingData = JSON.parse(
                    localStorage.getItem('ratingData') || '{}'
                )
                const friendKey = `friendId_${friendId}`
                const friendData = ratingData[friendKey] || {
                    ratedMatches: {},
                    lastRatingTime: 0,
                }

                friendData.ratedMatches[matchId] = rating
                friendData.lastRatingTime = Date.now()

                ratingData[friendKey] = friendData
                localStorage.setItem('ratingData', JSON.stringify(ratingData))
            } else {
                console.error('Rating failed:', data.message)
            }
        })
        .catch((error) => console.error('Fetch error:', error))
}
