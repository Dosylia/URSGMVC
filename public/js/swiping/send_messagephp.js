"use strict";

import { friendId, userId, fetchMessages } from './get_message.js';

let senderId;
let receiverId;
let messageInput;
let btnSubmit;

function sendMessageToPhp(senderId, receiverId, message) {
    const dataToSend = {
        senderId,
        receiverId,
        message
    };

    const jsonData = JSON.stringify(dataToSend);

    fetch('index.php?action=sendMessageData', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
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

document.addEventListener("DOMContentLoaded", function() {
    let senderIdElement = document.getElementById("senderId");
    let receiverIdElement = document.getElementById("receiverId");
    messageInput = document.getElementById("message_text");
    btnSubmit = document.getElementById("submit_chat");

    senderId = senderIdElement ? senderIdElement.value : null;
    receiverId = receiverIdElement ? receiverIdElement.value : null;

    if (!senderIdElement || !receiverIdElement || !messageInput || !btnSubmit) {
        return;
    }

    btnSubmit.addEventListener("click", function(event) {
        event.preventDefault();

        const message = messageInput.value.trim();

        if (message === "") {
            return;
        }

        sendMessageToPhp(senderId, receiverId, message);
    });
});