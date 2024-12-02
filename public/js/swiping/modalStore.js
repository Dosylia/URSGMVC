document.addEventListener("DOMContentLoaded", function () {
    const earnPointsTrigger = document.getElementById('earnpoints');
    const modalOverlay = document.getElementById('modalOverlay');
    const closeModalButton = document.getElementById('closeModalEarnPoints');

    // Open modal function
    function openModal() {
        modalOverlay.style.display = 'flex';
    }

    // Close modal function
    function closeModal() {
        modalOverlay.style.display = 'none';
    }

    // Event listeners
    earnPointsTrigger.addEventListener('click', openModal);
    closeModalButton.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', function (event) {
        if (event.target === modalOverlay) {
            closeModal();
        }
    });
});