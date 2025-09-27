let isChannelCreationInProgress = false // Flag to prevent duplicate execution

function createChannel() {
    if (isChannelCreationInProgress) return // Prevent re-entry if the process is already in progress

    isChannelCreationInProgress = true // Set flag to indicate the process is in progress

    const token = localStorage.getItem('masterTokenWebsite')
    const senderId = document.getElementById('senderId').value // Fetch the sender's ID
    const receiverId = document.getElementById('receiverId').value // Fetch the receiver's ID

    fetch('/createChannel', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(senderId)}`,
    })
        .then((response) => response.json())
        .then((data) => {
            isChannelCreationInProgress = false // Reset flag after process completes

            if (data.success) {
                const link = data.link // Link to the newly created channel

                // After channel creation, send an invitation message
                const message = `You have been invited to a new discord channel! Use this link to join: ${link}`
                const replyToChatId = null // Optional: If you have a specific message to reply to, set it here.

                // Call the sendMessageToPhp function from send_messagephp.js
                sendMessageToPhp(senderId, message, replyToChatId)

                // Open the link to the newly created channel
                window.open(link, '_blank')
            } else {
                console.error('Error creating temporary channel:', data.error)
            }
        })
        .catch((error) => {
            isChannelCreationInProgress = false // Reset flag on error
            console.error('Fetch error:', error)
        })
}

document.addEventListener('DOMContentLoaded', function () {
    const createChannelBtn = document.getElementById('discord-create')
    const confirmationModal = document.getElementById(
        'confirmationModalDiscord'
    )
    const cancelBtn = document.getElementById('cancelBtn')
    const confirmBtn = document.getElementById('confirmBtn')
    const bindDiscordBtn = document.getElementById('bind-discord-looking')

    // Handle the bind discord button to redirect to the binding page
    bindDiscordBtn.addEventListener('click', function (event) {
        event.preventDefault()
        window.open(
            'https://discord.com/oauth2/authorize?client_id=1354386306746159235&response_type=code&redirect_uri=https%3A%2F%2Fur-sg.com%2FdiscordBind&scope=identify+guilds+email+connections',
            '_blank'
        )
    })

    // Show the modal when the create channel button is clicked
    createChannelBtn.addEventListener('click', function (event) {
        event.preventDefault()
        confirmationModal.style.display = 'flex' // Show modal
    })

    // Handle the cancel button to close the modal
    cancelBtn.addEventListener('click', function (event) {
        event.preventDefault()
        confirmationModal.style.display = 'none' // Close modal
    })

    // Handle the confirm button to trigger the createChannel function
    confirmBtn.addEventListener('click', function (event) {
        event.preventDefault()
        confirmationModal.style.display = 'none' // Close modal
        createChannel() // Call the function to create the channel
    })
})
