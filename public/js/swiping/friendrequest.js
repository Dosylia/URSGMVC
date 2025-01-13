let friendRequests = []; // Array to hold all friend requests
const usersPerPage = 3; // Number of friend requests to display per page
let currentPage = 1; // Start with the first page

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
            // Re-select the span after DOM updates
            const friendRequestSpan = document.getElementById('friendrequest-backend');
            friendRequestSpan.style.display = 'block';
            friendRequestSpan.innerText = '';
            if (data.success) {
                friendRequestSpan.innerText = data.message;

                // Update the friendRequests array and re-render     
                frId = Number(frId); 
                friendRequests = friendRequests.filter(request => request.fr_id !== frId);
                renderFriendRequests();
            } else {
                friendRequestSpan.innerText = data.message;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Render friend requests for the current page
function renderFriendRequests() {
    const friendRequestBox = document.querySelector('.friendrequest_box');

    if (friendRequests.length === 0) {
        friendRequestBox.innerHTML = '';
        friendRequestBox.style.display = 'none';
        return;
    }

    const startIndex = (currentPage - 1) * usersPerPage;
    const endIndex = startIndex + usersPerPage;
    const requestsToDisplay = friendRequests.slice(startIndex, endIndex);

    // Render the friend requests with buttons
    friendRequestBox.innerHTML = `
        <h2 class="title_generalbox">Friend requests</h2><br>
        <span id="friendrequest-backend"></span>
        ${requestsToDisplay
            .map(
                request => `
            <div class="friend_request_ctn" data-fr-id="${request.fr_id}">
                <p>
                    <a target="_blank" href="/anotherUser&username=${encodeURIComponent(request.user_username)}">
                        <img id="image_users_small" src="${request.user_picture ? `public/upload/${request.user_picture}` : 'public/images/defaultprofilepicture.jpg'}" alt="Picture of ${request.user_username}" />
                        <span class="clickable">${request.user_username}, ${request.user_age}, ${request.user_gender}</span>
                    </a>
                </p><br>
                <div class="friend_request_ctn_btn">
                    <a href="#" class="accept_friend_button" data-fr-id="${request.fr_id}" data-user-id="${request.user_id}" data-status="accepted">
                        <button>Accept</button>
                    </a>
                    <a href="#" class="refuse_friend_button" data-fr-id="${request.fr_id}" data-user-id="${request.user_id}" data-status="rejected">
                        <button>Refuse</button>
                    </a>
                </div>
            </div>`
            )
            .join('')}
        <div class="pagination">
            ${currentPage > 1 ? `<button id="prevPage">&laquo; Previous</button>` : ''}
            ${currentPage < Math.ceil(friendRequests.length / usersPerPage) ? `<button id="nextPage">Next &raquo;</button>` : ''}
        </div>
    `;

    // Re-attach listeners after rendering
    addPaginationListeners();
    addActionListeners();
}

// Add click listeners for pagination buttons
function addPaginationListeners() {
    const prevButton = document.getElementById('prevPage');
    const nextButton = document.getElementById('nextPage');

    if (prevButton) {
        prevButton.addEventListener('click', () => {
            currentPage--;
            renderFriendRequests();
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', () => {
            currentPage++;
            renderFriendRequests();
        });
    }
}

// Add click listeners for accept/refuse buttons
function addActionListeners() {
    const acceptButtons = document.querySelectorAll('.accept_friend_button');
    const refuseButtons = document.querySelectorAll('.refuse_friend_button');

    acceptButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const frId = button.getAttribute('data-fr-id');
            let userId = document.getElementById('userId').value;
            const status = button.getAttribute('data-status');
            updateFriend(frId, userId, status);
        });
    });

    refuseButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const frId = button.getAttribute('data-fr-id');
            let userId = document.getElementById('userId').value;
            const status = button.getAttribute('data-status');
            updateFriend(frId, userId, status);
        });
    });
}

// Initialize friend requests and render the first page
document.addEventListener("DOMContentLoaded", function () {
    // Populate friendRequests from the server (e.g., as a JSON array)
    const friendRequestElement = document.getElementById('friendRequestData');

    if (friendRequestElement) {
        const requestData = friendRequestElement.textContent;
        friendRequests = JSON.parse(requestData);
        renderFriendRequests();
    } 
});
