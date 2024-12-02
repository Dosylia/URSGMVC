friendRequestSpan = document.getElementById('friendrequest-backend');

function updateFriend(frId, userId, status) {
    
    const dataToSend = {
        frId: frId,
        userId: userId,
        status: status
    };

    const jsonData = JSON.stringify(dataToSend);
    
    fetch('index.php?action=updateFriendWebsite', {
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
        friendRequestSpan.style.display = 'block';
        friendRequestSpan.innerText = '';
        if (data.success) {
            friendRequestSpan.innerText = data.message;
            const requestContainer = document.querySelector(
                `.friend_request_ctn [data-fr-id="${frId}"]`
            ).closest('.friend_request_ctn');
            if (requestContainer) {
                requestContainer.remove();
            }
        } else {
            friendRequestSpan.innerText = data.message;
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// EVENTS
document.addEventListener("DOMContentLoaded", function () {
    const acceptButtons = document.querySelectorAll('.accept_friend_button');
    const refuseButtons = document.querySelectorAll('.refuse_friend_button');

    acceptButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const frId = button.getAttribute('data-fr-id');
            const userId = button.getAttribute('data-user-id');
            const status = button.getAttribute('data-status');
            updateFriend(frId, userId, status);
        });
    });

    refuseButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const frId = button.getAttribute('data-fr-id');
            const userId = button.getAttribute('data-user-id');
            const status = button.getAttribute('data-status');
            updateFriend(frId, userId, status);
        });
    });
});