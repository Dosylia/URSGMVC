import { uploadImageApi, deleteChatImageApi } from './message_api.js'
import {
    messageInput,
    senderId,
    attachedImages,
    clearImageFalse,
    preventDefaults,
    highlight,
    unhighlight,
    replyBox,
    clearAttachedImages,
    addAttachedImage,
} from './message_utils.js'

export async function handleImageUpload(file) {
    if (!file || !file.type.startsWith('image/')) {
        console.log('Not an image file')
        return
    }

    messageInput.value += 'Picture is being uploaded...'

    try {
        const data = await uploadImageApi(file, senderId)

        if (data.success && data.imageUrl) {
            clearAttachedImages()
            clearImageFalse()
            addAttachedImage(data.imageUrl)
            messageInput.value = '📷'

            createImagePreview(data.imageUrl)
        } else {
            showUploadError('Failed to upload image')
        }
    } catch (error) {
        console.error('Upload error:', error)
        showUploadError('Upload failed')
    }
}

function createImagePreview(imageUrl) {
    // Create and insert preview image
    const previewContainer = document.getElementById('imagePreviewContainer')
    const imgPreview = document.createElement('img')
    imgPreview.src = imageUrl
    imgPreview.classList.add('img-preview')

    // Create wrapper div for positioning
    const wrapperDiv = document.createElement('div')
    wrapperDiv.style.position = 'relative'
    wrapperDiv.style.display = 'inline-block'
    wrapperDiv.style.marginTop = '5px'

    // Create a close button
    const closeButton = createCloseButton(imageUrl)

    // Append image and close button to wrapper
    wrapperDiv.appendChild(imgPreview)
    wrapperDiv.appendChild(closeButton)

    // Add to preview container
    previewContainer.innerHTML = ''
    previewContainer.appendChild(wrapperDiv)
}

function createCloseButton(imageUrl) {
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
    closeButton.type = 'button'

    closeButton.addEventListener('click', async function () {
        await handleImageRemoval(imageUrl)
    })

    return closeButton
}

async function handleImageRemoval(imageUrl) {
    const previewContainer = document.getElementById('imagePreviewContainer')
    previewContainer.innerHTML = ''
    console.log('Image removed from preview')

    // Remove [img] tag from message input
    const inputValue = messageInput.value
    const regex = new RegExp(
        `\\[img\\]${imageUrl.replace(
            /[.*+?^${}()|[\]\\]/g,
            '\\$&'
        )}\\[\\/img\\]`
    )
    messageInput.value = inputValue.replace(regex, '')

    try {
        const deleteData = await deleteChatImageApi(imageUrl)
        resetMessageInput()
        if (!deleteData.success) {
            console.log('Failed to delete image from server')
        }
    } catch (error) {
        console.error('Error deleting image:', error)
    }
}

function showUploadError(message) {
    alert(message)
    resetMessageInput()
}

function resetMessageInput() {
    const username = messageInput.dataset.username
    messageInput.value = ''
    messageInput.placeholder = 'Talk to @' + username
}

export function handleDrop(e) {
    const dt = e.dataTransfer
    const files = dt.files

    if (files.length) {
        handleImageUpload(files[0])
    }
}

export function handlePaste(e) {
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

export function initImageHandlers() {
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

    // Image input handler
    const imageInput = document.getElementById('imageInput')
    if (imageInput) {
        imageInput.addEventListener('change', function () {
            const file = this.files[0]
            if (!file) return
            handleImageUpload(file)
        })
    }
}
