"use strict";

import { fetchMessages, clearImageVar, clearImageFalse } from './get_message_utils.js';

let senderId;
let receiverId;
let messageInput;
let btnSubmit;
let btnDesign;
let isActionAllowed = true;
let attachedImages = [];

window.sendMessageToPhp = function(senderId, message, replyToChatId) {
    let friendIdElement = document.getElementById("receiverId");
    const receiverId = friendIdElement ? friendIdElement.value : null;
    const token = localStorage.getItem('masterTokenWebsite');

    const cleanedMessage = message.replace('📷', '').trim();
    const imageTags = attachedImages.map(url => `[img]${url}[/img]`).join('');
    const fullMessage = cleanedMessage + imageTags;

    const dataToSend = {
        senderId,
        receiverId,
        message: fullMessage,
        replyToChatId,
    };

    const jsonData = JSON.stringify(dataToSend);

    fetch('/sendMessageDataWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: "param=" + encodeURIComponent(jsonData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Success:', data);
        messageInput.value = '';
        const previewContainer = document.getElementById('imagePreviewContainer');
        previewContainer.innerHTML = ''; 
        attachedImages = []; 
        let replyPreviewContainer = document.getElementById("reply-preview");
        replyPreviewContainer.innerHTML = '';
        replyPreviewContainer.style.display = 'none';
        messageInput.removeAttribute('data-reply-to');
        fetchMessages(userId, receiverId);
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

document.getElementById('imageInput').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const token = localStorage.getItem('masterTokenWebsite');

    const formData = new FormData();
    formData.append('image', file);
    formData.append('senderId', senderId);
    messageInput.value += 'Picture is being uploaded...';

    fetch('/uploadChatImage', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.imageUrl) {
         // Add [img] tag to the message input (optional)
        attachedImages = [];
        clearImageFalse();
        attachedImages.push(data.imageUrl);
        messageInput.value = '📷';

        // Create and insert preview image
        const previewContainer = document.getElementById('imagePreviewContainer');
        const imgPreview = document.createElement('img');
        imgPreview.src = data.imageUrl;
        imgPreview.style.maxWidth = '150px';
        imgPreview.style.maxHeight = '150px';
        imgPreview.style.marginTop = '5px';
        imgPreview.style.borderRadius = '8px';
        imgPreview.classList.add('img-preview');

        // Optional: clear previous preview if needed
        // Create wrapper div for positioning
        const wrapperDiv = document.createElement('div');
        wrapperDiv.style.position = 'relative';
        wrapperDiv.style.display = 'inline-block';
        wrapperDiv.style.marginTop = '5px';

        // Create a close button
        const closeButton = document.createElement('button');
        closeButton.innerHTML = '×';
        closeButton.style.position = 'absolute';
        closeButton.style.top = '-7px';
        closeButton.style.right = '-5px';
        closeButton.style.background = 'rgba(0,0,0,0.6)';
        closeButton.style.border = 'none';
        closeButton.style.color = '#fff';
        closeButton.style.fontSize = '14px';
        closeButton.style.cursor = 'pointer';
        closeButton.style.borderRadius = '50%';
        closeButton.style.width = '20px';
        closeButton.style.height = '20px';
        closeButton.title = 'Remove Image';
        closeButton.type = 'button'; // Prevent form submission

        // Append image and close button to wrapper
        wrapperDiv.appendChild(imgPreview);
        wrapperDiv.appendChild(closeButton);

        // Add to preview container
        previewContainer.innerHTML = '';
        previewContainer.appendChild(wrapperDiv);

        // Handle delete
        closeButton.addEventListener('click', function() {
            previewContainer.innerHTML = '';
            console.log('Image removed from preview');

            // Remove [img] tag from message input
            const inputValue = messageInput.value;
            const regex = new RegExp(`\\[img\\]${data.imageUrl.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\[\\/img\\]`);
            messageInput.value = inputValue.replace(regex, '');

            // Optionally delete the image from server
            fetch('/deleteChatImage', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ imageUrl: data.imageUrl })
            })
            .then(response => response.json())
            .then(deleteData => {
                const username = messageInput.dataset.username;
                messageInput.value = '';
                messageInput.placeholder = 'Talk to @' + username;
                if (!deleteData.success) {
                    console.log('Failed to delete image from server');
                }
            })
            .catch(error => {
                console.error('Error deleting image:', error);
            });
        });

            } else {
            alert('Failed to upload image');
            const username = messageInput.dataset.username;
            messageInput.value = '';
            messageInput.placeholder = 'Talk to @' + username;
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
    });
});

function markMessageAsRead(senderId) {
    const token = localStorage.getItem('masterTokenWebsite');
    let friendIdElement = document.getElementById("receiverId");
    const receiverId = friendIdElement ? friendIdElement.value : null;

    const dataToSend = {
        senderId,
        receiverId,
    };

    const jsonData = JSON.stringify(dataToSend);

    fetch('index.php?action=markMessageAsReadWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: "param=" + encodeURIComponent(jsonData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Success:', data);
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

document.addEventListener("DOMContentLoaded", function() {
    let senderIdElement = document.getElementById("senderId");
    let receiverIdElement = document.getElementById("receiverId");
    messageInput = document.getElementById("message_text");
    btnSubmit = document.getElementById("submit_chat");
    btnDesign = document.getElementById("btnDesign");

    senderId = senderIdElement ? senderIdElement.value : null;
    receiverId = receiverIdElement ? receiverIdElement.value : null;

    messageInput.addEventListener("focus", function() {
        markMessageAsRead(senderId, receiverId);
        setTimeout(function() {
            messageInput.scrollIntoView({ behavior: "smooth", block: "center" });
        }, 300);
    });

    function handleSendMessage(event) {
        event.preventDefault();
    
        if (!isActionAllowed) {
            return;
        }
    
        isActionAllowed = false;
    
        const message = messageInput.value.trim();
    
        // Check if both message and attached images are empty
        if (message === "" && attachedImages.length === 0) {
            console.log('Message is empty');
            isActionAllowed = true; // Re-enable action
            return;
        }

        if (clearImageVar === true) {
            attachedImages = [];
            clearImageFalse();
            if (message === "" && attachedImages.length === 0) {
                isActionAllowed = true;
                return;
            }
        }
    
        const replyToChatId = messageInput.dataset.replyTo || null;
    
        sendMessageToPhp(senderId, message, replyToChatId);
    
        setTimeout(() => {
            isActionAllowed = true;
        }, 750);
    }

    if (!senderIdElement || !receiverIdElement || !messageInput || !btnSubmit || !btnDesign) {
        return;
    }

    btnDesign.addEventListener("click", handleSendMessage);
    btnDesign.addEventListener("touchstart", handleSendMessage);
    btnSubmit.addEventListener("touchstart", handleSendMessage);
    btnSubmit.addEventListener("click", handleSendMessage);


    // Emote picker functionality
    const toggleEmotePickerButton = document.getElementById('toggleEmotePicker');
    const emoteContainer = document.getElementById('emoteContainer');
    const emotes = document.querySelectorAll('.emote');

    toggleEmotePickerButton.addEventListener('click', function() {
        emoteContainer.style.display = emoteContainer.style.display === 'none' ? 'block' : 'none';
    });

    emotes.forEach(emote => {
        emote.addEventListener('click', function() {
            const emoteAlt = emote.alt;
            messageInput.value += ` ${emoteAlt} `;
            emoteContainer.style.display = 'none';
            messageInput.focus();
        });
    });
});
