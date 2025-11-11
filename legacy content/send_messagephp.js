'use strict'

import {
    fetchMessages,
    clearImageVar,
    clearImageFalse,
} from './get_message_utils.js'

let senderId
let receiverId
let messageInput
let btnSubmit
let btnDesign
let isActionAllowed = true
let attachedImages = []
let messageBurstCount = 0
let lastMessageTimestamp = 0
let isCoolingDown = false
const BURST_LIMIT = 5
const COOLDOWN_TIME = 2000
const spamWarning = document.getElementById('spamWarning')
let dropZoneOverlay

window.sendMessageToPhp = function (senderId, message, replyToChatId) {
    let friendIdElement = document.getElementById('receiverId')
    const receiverId = friendIdElement ? friendIdElement.value : null
    const token = localStorage.getItem('masterTokenWebsite')

    const cleanedMessage = message.replace('📷', '').trim()
    const imageTags = attachedImages.map((url) => `[img]${url}[/img]`).join('')
    const fullMessage = cleanedMessage + imageTags

    const dataToSend = {
        senderId,
        receiverId,
        message: fullMessage,
        replyToChatId,
    }

    const jsonData = JSON.stringify(dataToSend)

    fetch('/sendMessageDataWebsite', {
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
            console.log('Success:', data)
            messageInput.value = ''
            const previewContainer = document.getElementById(
                'imagePreviewContainer'
            )
            previewContainer.innerHTML = ''
            attachedImages = []
            let replyPreviewContainer = document.getElementById('reply-preview')
            replyPreviewContainer.innerHTML = ''
            replyPreviewContainer.style.display = 'none'
            messageInput.removeAttribute('data-reply-to')
            fetchMessages(userId, receiverId)
        })
        .catch((error) => {
            console.error('Error:', error)
        })
}

function handleImageUpload(file) {
    if (!file || !file.type.startsWith('image/')) {
        console.log('Not an image file')
        return
    }

    const token = localStorage.getItem('masterTokenWebsite')
    const formData = new FormData()
    formData.append('image', file)
    formData.append('senderId', senderId)
    messageInput.value += 'Picture is being uploaded...'

    fetch('/uploadChatImage', {
        method: 'POST',
        headers: {
            Authorization: `Bearer ${token}`,
        },
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success && data.imageUrl) {
                attachedImages = []
                clearImageFalse()
                attachedImages.push(data.imageUrl)
                messageInput.value = '📷'

                // Create and insert preview image
                const previewContainer = document.getElementById(
                    'imagePreviewContainer'
                )
                const imgPreview = document.createElement('img')
                imgPreview.src = data.imageUrl
                imgPreview.classList.add('img-preview')

                // Create wrapper div for positioning
                const wrapperDiv = document.createElement('div')
                wrapperDiv.style.position = 'relative'
                wrapperDiv.style.display = 'inline-block'
                wrapperDiv.style.marginTop = '5px'

                // Create a close button
                const closeButton = document.createElement('button')
                closeButton.innerHTML = '×'
                closeButton.style.position = 'absolute'
                closeButton.style.top = '-7px'
                closeButton.style.right = '-5px'
                closeButton.style.background = 'rgba(0,0,0,0.6)'
                closeButton.style.border = 'none'
                closeButton.style.color = '#fff'
                closeButton.style.fontSize = '14px'
                closeButton.style.cursor = 'pointer'
                closeButton.style.borderRadius = '50%'
                closeButton.style.width = '20px'
                closeButton.style.height = '20px'
                closeButton.title = 'Remove Image'
                closeButton.type = 'button' // Prevent form submission

                // Append image and close button to wrapper
                wrapperDiv.appendChild(imgPreview)
                wrapperDiv.appendChild(closeButton)

                // Add to preview container
                previewContainer.innerHTML = ''
                previewContainer.appendChild(wrapperDiv)

                // Handle delete
                closeButton.addEventListener('click', function () {
                    previewContainer.innerHTML = ''
                    console.log('Image removed from preview')

                    // Remove [img] tag from message input
                    const inputValue = messageInput.value
                    const regex = new RegExp(
                        `\\[img\\]${data.imageUrl.replace(
                            /[.*+?^${}()|[\]\\]/g,
                            '\\$&'
                        )}\\[\\/img\\]`
                    )
                    messageInput.value = inputValue.replace(regex, '')

                    // Optionally delete the image from server
                    fetch('/deleteChatImage', {
                        method: 'POST',
                        headers: {
                            Authorization: `Bearer ${token}`,
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ imageUrl: data.imageUrl }),
                    })
                        .then((response) => response.json())
                        .then((deleteData) => {
                            const username = messageInput.dataset.username
                            messageInput.value = ''
                            messageInput.placeholder = 'Talk to @' + username
                            if (!deleteData.success) {
                                console.log(
                                    'Failed to delete image from server'
                                )
                            }
                        })
                        .catch((error) => {
                            console.error('Error deleting image:', error)
                        })
                })
            } else {
                alert('Failed to upload image')
                const username = messageInput.dataset.username
                messageInput.value = ''
                messageInput.placeholder = 'Talk to @' + username
            }
        })
        .catch((error) => {
            console.error('Upload error:', error)
        })
}

function markMessageAsRead(senderId) {
    const token = localStorage.getItem('masterTokenWebsite')
    let friendIdElement = document.getElementById('receiverId')
    const receiverId = friendIdElement ? friendIdElement.value : null

    const dataToSend = {
        senderId,
        receiverId,
    }

    const jsonData = JSON.stringify(dataToSend)

    fetch('index.php?action=markMessageAsReadWebsite', {
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
            console.log('Success:', data)
        })
        .catch((error) => {
            console.error('Error:', error)
        })
}

function preventDefaults(e) {
    e.preventDefault()
    e.stopPropagation()
}

function highlight() {
    dropZoneOverlay.classList.add('active')
}

function unhighlight() {
    dropZoneOverlay.classList.remove('active')
}

function handleDrop(e) {
    const dt = e.dataTransfer
    const files = dt.files

    if (files.length) {
        handleImageUpload(files[0])
    }
}

function handlePaste(e) {
    // Get pasted items from clipboard
    const items = (e.clipboardData || e.originalEvent.clipboardData).items

    // Look for images in pasted items
    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') !== -1) {
            // Get the image as a file
            const blob = items[i].getAsFile()
            handleImageUpload(blob)
            e.preventDefault() // Prevent default paste behavior
            break
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    let senderIdElement = document.getElementById('senderId')
    let receiverIdElement = document.getElementById('receiverId')
    messageInput = document.getElementById('message_text')
    btnSubmit = document.getElementById('submit_chat')
    btnDesign = document.getElementById('btnDesign')
    dropZoneOverlay = document.getElementById('dropZoneOverlay')
    const replyBox = document.querySelector('.reply-box')
    senderId = senderIdElement ? senderIdElement.value : null
    receiverId = receiverIdElement ? receiverIdElement.value : null

    if (replyBox) {
        // Prevent default drag behaviors
        ;['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
            replyBox.addEventListener(eventName, preventDefaults, false)
        })

        // Highlight drop area when item is dragged over it
        ;['dragenter', 'dragover'].forEach((eventName) => {
            replyBox.addEventListener(eventName, highlight, false)
        })
        ;['dragleave', 'drop'].forEach((eventName) => {
            replyBox.addEventListener(eventName, unhighlight, false)
        })

        // Handle dropped files
        replyBox.addEventListener('drop', handleDrop, false)
    }

    if (messageInput) {
        messageInput.addEventListener('paste', handlePaste)
    }

    document
        .getElementById('imageInput')
        .addEventListener('change', function () {
            const file = this.files[0]
            if (!file) return
            handleImageUpload(file)
        })

    messageInput.addEventListener('focus', function () {
        markMessageAsRead(senderId, receiverId)
        setTimeout(function () {
            messageInput.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }, 300)
    })

    function handleSendMessage(event) {
        event.preventDefault()

        const now = Date.now()

        if (isCoolingDown) {
            console.warn('Cooldown active. Please wait.')
            return
        }

        if (!isActionAllowed) {
            return
        }

        isActionAllowed = false

        const message = messageInput.value.trim()

        if (message === '' && attachedImages.length === 0) {
            console.log('Message is empty')
            isActionAllowed = true
            return
        }

        if (clearImageVar === true) {
            attachedImages = []
            clearImageFalse()
            if (message === '' && attachedImages.length === 0) {
                isActionAllowed = true
                return
            }
        }

        const replyToChatId = messageInput.dataset.replyTo || null

        sendMessageToPhp(senderId, message, replyToChatId)

        if (now - lastMessageTimestamp > COOLDOWN_TIME) {
            messageBurstCount = 0
        }

        messageBurstCount++
        lastMessageTimestamp = now

        if (isCoolingDown) {
            spamWarning.style.display = 'block'
            return
        }

        if (messageBurstCount >= BURST_LIMIT) {
            isCoolingDown = true
            spamWarning.style.display = 'block'

            setTimeout(() => {
                isCoolingDown = false
                messageBurstCount = 0
                spamWarning.style.display = 'none'
            }, COOLDOWN_TIME)
        }

        setTimeout(() => {
            isActionAllowed = true
        }, 50)
    }

    if (
        !senderIdElement ||
        !receiverIdElement ||
        !messageInput ||
        !btnSubmit ||
        !btnDesign
    ) {
        return
    }

    btnDesign.addEventListener('click', handleSendMessage)
    btnDesign.addEventListener('touchstart', handleSendMessage)
    btnSubmit.addEventListener('touchstart', handleSendMessage)
    btnSubmit.addEventListener('click', handleSendMessage)

    // Emote picker functionality
    const toggleEmotePickerButton = document.getElementById('toggleEmotePicker')
    const emoteContainer = document.getElementById('emoteContainer')
    const emotes = document.querySelectorAll('.emote')

    toggleEmotePickerButton.addEventListener('click', function () {
        emoteContainer.style.display =
            emoteContainer.style.display === 'none' ? 'flex' : 'none'
    })

    emotes.forEach((emote) => {
        emote.addEventListener('click', function () {
            const emoteAlt = emote.alt
            messageInput.value += ` ${emoteAlt} `
            emoteContainer.style.display = 'none'
            messageInput.focus()
        })
    })
})
