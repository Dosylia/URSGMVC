import { fetchMessages, friendId, chatInterface, messageContainer, replyPreviewContainer, chatInput, clearImageTrue, closeRatingModalBtn, closeRatingModal, submitRating, sendRating} from './get_message_utils.js';
let actualFriendId = friendId;

document.addEventListener("DOMContentLoaded", function () {
    
    document.addEventListener("click", function (event) {
        let link = event.target.closest(".username_chat_friend");
    
        // If the clicked element is a link with a valid href (not just '#'), allow navigation
        if (link && link.getAttribute("href") && link.getAttribute("href") !== "#") {
            return; 
        }
    
        // Otherwise, handle custom logic (e.g., changing the chat)
        if (!link) return;
    
        event.preventDefault(); // Prevent the default behavior for non-navigational links
    
        let newFriendId = link.getAttribute("data-friend-id");

        const isMax1018px = window.matchMedia("(max-width: 1018px)").matches;

        // Always check screen size after updating messages
        if (isMax1018px) {
            if (chatInterface && window.getComputedStyle(messageContainer).display === 'none') {
                chatInterface.style.display = 'none';
                messageContainer.style.display = 'block';
            }
        }
    
        if (newFriendId !== actualFriendId) {
            const modalDiscord = document.getElementById('confirmationModalDiscord');
            modalDiscord.style.display = 'none'; // Hide the modal
            actualFriendId = newFriendId; // Update the recipient ID
            replyPreviewContainer.style.display = "none"; // Hide the reply preview
            closeRatingModal();
            chatInput.dataset.replyTo = ""; // Clear the reply context
            let messageInput = document.getElementById("message_text");
            const username = messageInput.dataset.username;
            const previewContainer = document.getElementById('imagePreviewContainer');
            previewContainer.innerHTML = ''; // Clear the preview container
            messageInput.value = '';
            messageInput.placeholder = 'Talk to @' + username;
            fetchMessages(actualFriendId); // Load new messages
            clearImageTrue();
        }
    });
    

    if (typeof userId !== "undefined" && userId !== null) {
        fetchMessages(actualFriendId); // Chargement initial
        setInterval(() => fetchMessages(actualFriendId), 5000);
    }

    setVhVariable();
    checkScreenSize();

    closeRatingModalBtn.addEventListener('click', () => {
        closeRatingModal();
    });

    submitRating.addEventListener('click', (event) => {
        sendRating();
    });

    window.addEventListener("resize", () => {
        setVhVariable();
        checkScreenSize();
    });
})

    // Function to set the --vh variable
    function setVhVariable() {
        let vh = window.innerHeight * 0.01; // 1vh
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }

    function checkScreenSize() {
        const isMax1018px = window.matchMedia("(max-width: 1018px)").matches;

        if (isMax1018px) {
            if (chatInterface !== null && window.getComputedStyle(messageContainer).display !== 'none') {
                chatInterface.style.display = 'none';
            }
        } else {
            if (chatInterface !== null && chatInterface !== undefined) {
                chatInterface.style.display = 'flex';
            }
            messageContainer.style.display = 'block';
        }
    }

