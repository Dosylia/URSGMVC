const buttonclose = document.getElementById('closeButton');

buttonclose.addEventListener('click', (event) => {
    event.preventDefault();
    window.location.href = 'index.php?action=chat';
});