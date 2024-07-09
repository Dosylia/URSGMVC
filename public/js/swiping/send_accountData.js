"use strict";

let lolAccount, btnSubmit;

// Function to send data
function sendAccountToPhp(lolAccount) {
    const jsonData = JSON.stringify({ lolAccount: lolAccount });

    fetch('index.php?action=sendLeagueAccountData', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
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
    btnSubmit = document.getElementById("submit_chat");
    lolAccount = document.getElementById('account_lol');

    btnSubmit.addEventListener("click", function(event) {
        event.preventDefault();
        
        const accountValue = lolAccount.value;
        sendAccountToPhp(accountValue);
    });
});





