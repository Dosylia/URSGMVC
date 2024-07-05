const buttonclose = document.getElementById('buttonSwitchChat');

if (buttonclose !== null && buttonclose !== undefined) {
    buttonclose.addEventListener('click', (event) => {
        event.preventDefault();
        chatInterface.style.display = 'flex';
        messageContainer.style.display = 'none';
    });
}