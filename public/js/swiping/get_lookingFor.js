"use strict";
import apiFetch from "./api_fetch.js";

let userId = document.getElementById('userId').value;

function isLookingFor(userId, account, message) {

    apiFetch({
    url: '/userIsLookingForGameWebsite',
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `userId=${encodeURIComponent(parseInt(userId))}`
})
    .then((data) => {
        if (data.success) {
                displayNotification("Looking for a game for the next 5 min!");
                const lookingForButton = document.getElementById('looking-for-button');
                lookingForButton.style.background = "linear-gradient(45deg, #4CAF50, #66bb6a)";
                const oldTime = data.oldTime;
                sendMessageDiscord(userId, account, message, oldTime);
            } else {
                console.error('Error setting status:', data.error);
            }
    })
    .catch((error) => {
        // General error happened. Probably not user related and more on the dev side.
	console.error('Fetch error:', error)
    })

    
}

function sendMessageDiscord(userId, account, message, oldTime) {
    const token = localStorage.getItem('masterTokenWebsite');

    const formData = new URLSearchParams();
    formData.append('userId', parseInt(userId));
    if (account) formData.append('account', account);
    if (message) formData.append('extraMessage', message);
    formData.append('oldTime', oldTime);
    const overlay = document.getElementById("overlay");
    overlay.style.display = "none";

    fetch('/sendMessageDiscord', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: formData.toString()
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Message sent to Discord successfully!');
                const message = document.getElementById('lookingfor-message');
                message.value = "";
                message.placeholder = "Type your message here...";
            } else {
                console.error('Message error:', data.error);
                const message = document.getElementById('lookingfor-message');
                message.value = "";
                message.placeholder = "Type your message here...";
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

document.addEventListener('DOMContentLoaded', function () {
    const lookingForModal = document.getElementById('lookingfor-modal');
    const closeModalBtn = document.getElementById('close-modal');
    const lookingForButton = document.getElementById('looking-for-button');
    const submitLookingFor = document.getElementById('submit-lookingfor');

    const userId = document.getElementById('userId').value;

    lookingForButton.addEventListener('click', () => {
        lookingForModal.classList.remove('lookingfor-modal-hidden');
        const overlay = document.getElementById("overlay");
        overlay.style.display = "block";
    });

    closeModalBtn.addEventListener('click', () => {
        lookingForModal.classList.add('lookingfor-modal-hidden');
        const overlay = document.getElementById("overlay");
        overlay.style.display = "none";
    });

    submitLookingFor.addEventListener('click', () => {
        const message = document.getElementById('lookingfor-message').value.trim();
        const account = document.getElementById('lookingfor-account')?.value.trim(); // May not exist if verified

        isLookingFor(userId, account, message);
        lookingForModal.classList.add('lookingfor-modal-hidden'); // Close modal
    });
});
