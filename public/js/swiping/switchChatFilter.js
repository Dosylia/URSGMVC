// DOM Elements
const toggleChatFilter = document.querySelector('.toggle-container-filter')
const btnChatFilter = document.getElementById('toggleChatFilter')
const userIdHeaderDiv = document.getElementById('userInfo')
let initialChatFilterState = 0
let chatFilterActive = initialChatFilterState

// Function to switch chat filter
function switchChatFilter(userId, status) {
    console.log(
        `Switching chat filter for user ID: ${userId}, status: ${status}`
    )
    const token = localStorage.getItem('masterTokenWebsite')

    const dataToSend = {
        userId: userId,
        status: status,
    }

    const jsonData = JSON.stringify(dataToSend)

    fetch('/chatFilterSwitchWebsite', {
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
                chatFilterActive = status // Update the active state

                // Update UI based on new status
                if (status == 1) {
                    toggleChatFilter.classList.add('active')
                    btnChatFilter.classList.add('active')
                } else {
                    toggleChatFilter.classList.remove('active')
                    btnChatFilter.classList.remove('active')
                }
            } else {
                console.log('Could not update chat filter')
            }
        })
        .catch((error) => {
            console.error('Error:', error)
        })
}

document.addEventListener('DOMContentLoaded', function () {
    initialChatFilterState = userIdHeaderDiv
        ? Number(userIdHeaderDiv.getAttribute('data-chat-filter'))
        : 0
    chatFilterActive = initialChatFilterState

    if (chatFilterActive === 1) {
        toggleChatFilter.classList.add('active')
        btnChatFilter.classList.add('active')
    } else {
        toggleChatFilter.classList.remove('active')
        btnChatFilter.classList.remove('active')
    }

    toggleChatFilter.addEventListener('click', function () {
        const newStatus = chatFilterActive ? 0 : 1
        switchChatFilter(userIdHeader, newStatus)
    })
})
