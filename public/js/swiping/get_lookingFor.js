let userId = document.getElementById('userId').value;

function isLookingFor(userId) {
    const token = localStorage.getItem('masterTokenWebsite');
    fetch('/userIsLookingForGameWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(parseInt(userId))}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNotification("Looking for a game for next 5 min!");
                const lookingForButton = document.getElementById('looking-for-button');
                lookingForButton.style.background = "linear-gradient(45deg, #4CAF50, #66bb6a)";
                sendMessageDiscord(userId);
            } else {
                console.error('Error setting status:', data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
}

function sendMessageDiscord(userId) {
    const token = localStorage.getItem('masterTokenWebsite');
    fetch('/sendMessageDiscord', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(parseInt(userId))}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Message sent to Discord successfully or user does not have League data!');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
}

document.addEventListener('DOMContentLoaded', function () {
    const lookingForButton = document.getElementById('looking-for-button');

    lookingForButton.addEventListener('click', function () {
        isLookingFor(userId);
    });

});