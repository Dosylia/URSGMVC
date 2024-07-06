import { chatInterface, messageContainer } from './get_message.js';
const buttonclose = document.getElementById('buttonSwitchChat');

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