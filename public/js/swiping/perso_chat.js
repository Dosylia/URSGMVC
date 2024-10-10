import { chatInterface, messageContainer } from './get_message.js';
const buttonclose = document.getElementById('buttonSwitchChat');
const searchBar = document.getElementById('friendSearch');

if (buttonclose !== null && buttonclose !== undefined) {
    buttonclose.addEventListener('click', (event) => {
        event.preventDefault();
        if (chatInterface !== null) {
            chatInterface.style.display = 'flex';
        }
        if (messageContainer !== null) {
            messageContainer.style.display = 'none';
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {

    searchBar.addEventListener('input', function() {
        console.log('searching');
        const query = this.value.toLowerCase();
        const friends = document.querySelectorAll('.friend-list .friend');
        
        friends.forEach(friend => {
            const username = friend.querySelector('.chat-name').textContent.toLowerCase();
            if (username.includes(query)) {
                friend.parentElement.style.display = ''; 
            } else {
                friend.parentElement.style.display = 'none'; 
            }
        });
    });
});