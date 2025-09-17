let notificationQueue = []
let isNotificationDisplayed = false

function displayNotification(message, type, userId, picture) {
    // Add the notification details to the queue
    notificationQueue.push({ message, type, userId, picture })

    // Process the queue if no notification is currently displayed
    if (!isNotificationDisplayed) {
        processNotificationQueue()
    }
}

function processNotificationQueue() {
    // If the queue is empty, stop processing
    if (notificationQueue.length === 0) {
        isNotificationDisplayed = false
        return
    }

    // Mark that a notification is being displayed
    isNotificationDisplayed = true

    // Get the next notification from the queue
    const { message, type, userId, picture } = notificationQueue.shift()

    // Display the notification
    const notificationSpan = document.querySelector('.notification-span')
    if (!notificationSpan) {
        console.error('Notification span not found!')
        return
    }

    // Reset the content of the notification span
    notificationSpan.innerHTML = '' // Clear existing content
    notificationSpan.style.display = 'block'

    if (type === 'message' && userId) {
        // Create a clickable link
        let finalMessage = message
        if (message.includes('[img]') && message.includes('[/img]')) {
            finalMessage = message.replace(/\[img\](.*?)\[\/img\]/g, '📷') // Replace [img]...[/img] with 📷 emoji
        }
        const link = document.createElement('a')
        link.href = `/persoChat&friend_id=${userId}&mark_as_read=true`
        link.style.textDecoration = 'none'
        link.style.color = 'inherit'

        // Add the user's picture
        const img = document.createElement('img')
        img.src = picture
            ? `public/upload/${picture}`
            : 'public/images/defaultprofilepicture.jpg'
        img.alt = 'User Avatar'
        img.className = 'avatar'
        img.style.marginRight = '10px' // Optional styling
        img.style.verticalAlign = 'middle' // Align with text

        // Add the message text
        const text = document.createElement('span')
        text.textContent = finalMessage

        // Append elements to the link
        link.appendChild(img)
        link.appendChild(text)

        // Append the link to the notification span
        notificationSpan.appendChild(link)
    } else {
        // Display the message text for non-message types
        notificationSpan.innerText = message
    }

    // Clear the notification after 5 seconds
    setTimeout(() => {
        notificationSpan.innerHTML = '' // Clear the content
        notificationSpan.style.display = 'none' // Hide the notification
        isNotificationDisplayed = false

        // Process the next notification in the queue
        processNotificationQueue()
    }, 5000)
}
