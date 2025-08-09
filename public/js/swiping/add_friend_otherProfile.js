// Variables
const addFriendButton = document.getElementById('add-friend-button-user');
const offlineModal = document.getElementById('offlineModal');
const addFriendNoUser = document.getElementById('add-friend-button-no-user');

// Functions to handle friend requests
function showOfflineModal() {
    if (offlineModal) {
        offlineModal.classList.remove("hidden");
    }
}

function sendFriendRequest(userId, otherUserId) {
    const token = localStorage.getItem('masterTokenWebsite');
    fetch('/addAsFriendWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: `senderId=${encodeURIComponent(userId)}&receiverId=${encodeURIComponent(otherUserId)}`
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Fetch error:', response.status, text);
                throw new Error(`HTTP error! Status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Delete the image and keep only modified text
            if (addFriendButton) {
                // Remove the image inside the button
                const img = addFriendButton.querySelector('img');
                if (img) img.remove();

                // Change the text
                addFriendButton.textContent = "Successfully Added";
                addFriendButton.style.backgroundColor = "#93c47d";
                // Optional: Disable the button to prevent clicking again
                addFriendButton.disabled = true;
            }
        } else {
            addFriendButton.textContent = "Failed to Add";
            addFriendButton.style.backgroundColor = "grey";
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });    
}

// Dom content loaded event
document.addEventListener('DOMContentLoaded', function() {
    addFriendButton?.addEventListener('click', function() {
        sendFriendRequest(addFriendButton.dataset.userId, addFriendButton.dataset.otherUserId);
    });

    addFriendNoUser?.addEventListener('click', function() {
        showOfflineModal();
    });

    document.querySelectorAll('.close-modal-btn').forEach(button => {
      button.addEventListener('click', function () {
        const modalId = this.getAttribute('data-modal');
        document.getElementById(modalId).classList.add('hidden');
      });
    });
});