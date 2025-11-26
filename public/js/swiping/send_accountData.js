"use strict";
import apiFetch from "../Functions/api_fetch.js";

let lolAccount, btnSubmit, lolserver, verificationDiv;

// Function to send data
function sendAccountToPhp(lolAccount, lolServer) {

    const dataToSend = {
        lolAccount,
        lolServer
    };

    const jsonData = JSON.stringify(dataToSend);


    fetch('index.php?action=sendAccountToPhp', {
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
        if (data.status === 'success') {
            displayVerificationCode();
        } else {
            console.error('Error:', data.message);
            verificationDiv.innerHTML = `<p style="color: red;">Error: ${data.message}</p>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        verificationDiv.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
    });
}

// Function to display the verification code and instructions
function displayVerificationCode() {
    verificationDiv.innerHTML = `
        <img id="picture_verify" src="public/images/profileicon/7.png">
        <p>Change your picture in game to this one to verify your account.</p>
    `;

    // Create and add a button for verification
    const verifyButton = document.createElement('button');
    verifyButton.textContent = 'Verify';
    verifyButton.id = 'verify-button';
    verificationDiv.appendChild(verifyButton);

    // Add event listener for the verify button
    verifyButton.addEventListener('click', function(event) {
        event.preventDefault();
        verifyAccount();
    });
}

// Function to verify the account after the user enters the verification code in their profile
function verifyAccount() {

    const action = "verifyLeagueAccount";

    const dataToSend = {
        action
    };

    const jsonData = JSON.stringify(dataToSend);

    fetch('index.php?action=verifyLeagueAccount', {
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
        if (data.status === 'success') {
            verificationDiv.innerHTML = `<p style="color: green;">Account verified and binded successfully!</p>`;
        } else {
            verificationDiv.innerHTML = `<p style="color: red;">${data.message}</p>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        verificationDiv.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
    });
}

document.addEventListener("DOMContentLoaded", function() {
    btnSubmit = document.getElementById("send-button");
    lolAccount = document.getElementById('account_lol');
    lolserver = document.getElementById('server');
    verificationDiv = document.getElementById('verification_code');

    btnSubmit.addEventListener("click", function(event) {
        event.preventDefault();

        const accountValue = lolAccount.value;
        const serverValue = lolserver.value;
        sendAccountToPhp(accountValue, serverValue);
    });
});


