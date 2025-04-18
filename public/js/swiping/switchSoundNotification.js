document.addEventListener("DOMContentLoaded", function () {
    const toggleContainerSound = document.getElementById('toggle-container-sound');
    const toggleButtonSound = document.getElementById('toggle-button-sound');

    // Initialize toggle state based on localStorage
    const soundPref = localStorage.getItem('soundNotifications');

    if (soundPref !== 'off') {
        toggleContainerSound.classList.add('active');
        toggleButtonSound.classList.add('active');
    }

    toggleContainerSound.addEventListener('click', function () {
        const isActive = toggleContainerSound.classList.toggle('active');
        toggleButtonSound.classList.toggle('active');

        if (isActive) {
            localStorage.setItem('soundNotifications', 'on');
        } else {
            localStorage.setItem('soundNotifications', 'off');
        }
    });
});