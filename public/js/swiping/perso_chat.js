const buttonclose = document.getElementById('buttonSwitchChat');
const messageContainer = document.querySelector('.messages-container');

buttonclose.addEventListener('click', (event) => {
    event.preventDefault();
   chatInterface.style.display = 'flex';
   messageContainer.style.display = 'none';
});