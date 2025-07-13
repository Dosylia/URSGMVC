document.addEventListener("DOMContentLoaded", function() {
    const modalTournament = document.getElementById('tournament-modal');
    const closeModalTournament = document.getElementById('close-modal-tournament');
    const shownModalTournament = localStorage.getItem('tournamentModalShown');

    if (!shownModalTournament) {
        modalTournament?.classList.remove('tournament-modal-hidden');
        const overlay = document.getElementById("overlay");
        overlay.style.display = "block";
        localStorage.setItem('tournamentModalShown', 'true');
    }

    // Close modal when 'x' is clicked
    closeModalTournament?.addEventListener('click', function() {
        modalTournament.classList.add('tournament-modal-hidden');
        const overlay = document.getElementById("overlay");
        overlay.style.display = "none";
    });

    // Close modal if clicking outside the modal content
    window.addEventListener('click', function(event) {
        if (event.target === modalTournament) {
            modalTournament.classList.add('tournament-modal-hidden');
            const overlay = document.getElementById("overlay");
            overlay.style.display = "none";
        }
    });
});