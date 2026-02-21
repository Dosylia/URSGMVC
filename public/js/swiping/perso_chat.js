import { chatInterface, messageContainer } from './message_utils.js'
import { fetchFriendlistApi } from './message_api.js'

const buttonclose = document.getElementById('buttonSwitchChat')
const searchBar = document.getElementById('friendSearch')
let allFriends = [] // Store the full friend list in memory

if (buttonclose !== null && buttonclose !== undefined) {
    buttonclose.addEventListener('click', (event) => {
        event.preventDefault()
        if (chatInterface !== null) {
            chatInterface.style.display = 'flex'
        }
        if (messageContainer !== null) {
            messageContainer.style.display = 'none'
        }
    })
}

// Fetch the full friend list once
async function fetchAllFriends(userId) {
    try {
        const data = await fetchFriendlistApi(userId)
        if (data.success) {
            allFriends = data.friendlist // Store the entire friend list
        } else {
            console.error('Error fetching friends:', data.message)
        }
    } catch (error) {
        console.error('Fetch error:', error)
    }
}

// Search and filter friends
function searchFriends(query) {
    const friendListContainer = document.getElementById('friendList')
    friendListContainer.innerHTML = '' // Clear the current list

    const filteredFriends = allFriends.filter((friend) =>
        friend.friend_username.toLowerCase().includes(query)
    )

    filteredFriends.forEach((friend) => {
        // Create anchor tag with required classes and data attributes
        const friendElement = document.createElement('a')
        friendElement.className = 'username_chat_friend clickable'
        friendElement.href = '#' // Updated to `#` to match your provided structure
        friendElement.dataset.friendId = friend.friend_id

        // Create the outer friend container
        const friendDiv = document.createElement('div')
        friendDiv.className = 'friend'
        friendDiv.dataset.senderId = friend.friend_id

        // Create the avatar container
        const avatarDiv = document.createElement('div')
        avatarDiv.className = 'friend-avatar'

        const img = document.createElement('img')
        img.src = friend.friend_picture
            ? `public/upload/${friend.friend_picture}`
            : 'public/images/defaultprofilepicture.jpg'
        img.alt = `Avatar ${friend.friend_username}`
        avatarDiv.appendChild(img)

        // Create the details container
        const detailsDiv = document.createElement('div')
        detailsDiv.className = 'friend-details'

        // Construct chatNameSpan with username, unread messages, and online status
        const chatNameSpan = document.createElement('span')
        chatNameSpan.className = 'chat-name clickable'
        chatNameSpan.innerHTML = `
        ${friend.friend_username}
        <span id="unread_messages_for_friend_container_${
            friend.friend_id
        }"></span>
        ${
            friend.friend_online === 1 && friend.friend_isLookingGame === 1
                ? '<span class="looking-game-status"></span>'
                : friend.friend_online === 1
                  ? '<span class="online-status"></span>'
                  : ''
        }
        `

        const gameLogo = document.createElement('img')
        gameLogo.src =
            friend.friend_game === 'League of Legends'
                ? 'public/images/lol-logo.png'
                : 'public/images/Valorant.png'
        gameLogo.alt = friend.friend_game

        // Update unread messages count if applicable
        const unreadCount = globalUnreadCounts[friend.friend_id] || 0
        if (unreadCount > 0) {
            const unreadMessagesContainer = chatNameSpan.querySelector(
                `#unread_messages_for_friend_container_${friend.friend_id}`
            )
            const span = document.createElement('span')
            span.className = 'unread-count'
            span.style.marginLeft = '10px'

            const button = document.createElement('button')
            button.className = 'unread_message'
            button.textContent = unreadCount

            span.appendChild(button)
            unreadMessagesContainer.appendChild(span)
        }

        detailsDiv.appendChild(chatNameSpan)
        detailsDiv.appendChild(gameLogo)

        friendDiv.appendChild(avatarDiv)
        friendDiv.appendChild(detailsDiv)
        friendElement.appendChild(friendDiv)

        friendListContainer.appendChild(friendElement)
    })
}

document.addEventListener('DOMContentLoaded', async function () {
    const userId = document.getElementById('senderId').value

    // Fetch all friends on load
    await fetchAllFriends(userId)

    // Attach search functionality
    searchBar.addEventListener('input', function () {
        const query = this.value.toLowerCase()
        searchFriends(query)
    })
})
