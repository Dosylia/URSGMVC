// DOM elements
const toggleRandomChatPermission = document.querySelector(
    '.toggle-container-random-chat'
)
const btnRandomChatFilter = document.getElementById(
    'toggleRandomChatPermission'
)
const token = localStorage.getItem('masterTokenWebsite')
let initialPermissionState = 0
let randomChatPermissionActive = initialPermissionState

function switchRandomChatPermission(token, userId, status) {
    const dataToSend = {
        userId: userId,
        status: status,
    }

    const jsonData = JSON.stringify(dataToSend)

    fetch('/switchRandomChatPermission', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok')
            }
            return response.json()
        })
        .then((data) => {
            if (data.message === 'Success') {
                randomChatPermissionActive = status // Update the active state

                // Update UI based on new status
                if (status == 1) {
                    toggleRandomChatPermission.classList.add('active')
                    btnRandomChatFilter.classList.add('active')
                } else {
                    toggleRandomChatPermission.classList.remove('active')
                    btnRandomChatFilter.classList.remove('active')
                }
            } else {
                console.log('Could not update random chat permission')
            }
        })
        .catch((error) => {
            console.error('Error:', error)
        })
}

// Switch random chat permission
document.addEventListener('DOMContentLoaded', function () {
    initialPermissionState = userIdHeaderDiv
        ? Number(userIdHeaderDiv.getAttribute('data-random-chat-permission'))
        : 0
    randomChatPermissionActive = initialPermissionState

    if (randomChatPermissionActive === 1) {
        toggleRandomChatPermission.classList.add('active')
        btnRandomChatFilter.classList.add('active')
    } else {
        toggleRandomChatPermission.classList.remove('active')
        btnRandomChatFilter.classList.remove('active')
    }

    toggleRandomChatPermission.addEventListener('click', function () {
        const newStatus = randomChatPermissionActive ? 0 : 1
        switchRandomChatPermission(
            token,
            userIdHeaderDiv.getAttribute('data-user-id'),
            newStatus
        )
    })
})
