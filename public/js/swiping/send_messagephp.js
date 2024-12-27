"use strict";

import { friendId, userId, fetchMessages } from './get_message.js';

let senderId;
let receiverId;
let messageInput;
let btnSubmit;
let btnDesign;
let isActionAllowed = true;

function sendMessageToPhp(senderId, receiverId, message) {
    // If wanna convert time directly before sending it
    // let utcDate = new Date();
    // let localOffset = utcDate.getTimezoneOffset() * 60000;
    // let localDate = new Date(utcDate.getTime() - localOffset);
    // let formattedTime = localDate.toLocaleTimeString();
    const token = localStorage.getItem('masterTokenWebsite');

    const dataToSend = {
        senderId,
        receiverId,
        message,
    };

    const jsonData = JSON.stringify(dataToSend);

    fetch('index.php?action=sendMessageDataWebsite', {
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
        // After successfully sending message, fetch updated messages
        fetchMessages(userId, friendId);
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function markMessageAsRead(senderId, receiverId) {
    const token = localStorage.getItem('masterTokenWebsite');

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

        if (message === "") {
            console.log('Message is empty');
            return;
        }

        sendMessageToPhp(senderId, receiverId, message);

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
