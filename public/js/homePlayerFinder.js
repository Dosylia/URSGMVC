savePreferencesBtn?.addEventListener('click', () => {
    const game = filterGame.value;
    const role = filterRole.value;
    const rank = filterRank.value;
    const voice = filterVoiceChat.value;

    const prefs = {
        game: game || null,
        role: role || null,
        rank: rank || null,
        voice: voice !== "" ? voice === "1" : undefined
    };

    // Save to localStorage
    localStorage.setItem('playerfinder_filters', JSON.stringify(prefs));

    // Build query string
    const params = new URLSearchParams();
    if (game) params.set('game', game);
    if (role) params.set('role', role);
    if (rank) params.set('rank', rank);
    if (voice !== "") params.set('voice', voice);

    // Redirect to /playerfinder with filters in GET
    window.location.href = `/playerfinder?${params.toString()}`;
});

document.addEventListener("DOMContentLoaded", function () {

    savePreferencesBtn?.addEventListener('click', () => {
    const game = filterGame.value;
    const role = filterRole.value;
    const rank = filterRank.value;
    const voice = filterVoiceChat.value;

    const prefs = {
        game: game || null,
        role: role || null,
        rank: rank || null,
        voice: voice !== "" ? voice === "1" : undefined
    };

    // Save to localStorage
    localStorage.setItem('playerfinder_filters', JSON.stringify(prefs));

    // Build query string
    const params = new URLSearchParams();
    if (game) params.set('game', game);
    if (role) params.set('role', role);
    if (rank) params.set('rank', rank);
    if (voice !== "") params.set('voice', voice);

    // Redirect to /playerfinder with filters in GET
    window.location.href = `/playerfinder?${params.toString()}`;
});

    document.getElementById('filterGame').addEventListener('change', function() {
      const game = this.value;
      const roles = document.querySelectorAll('#filterRole option');
      const ranks = document.querySelectorAll('#filterRank option');
      roles.forEach(opt => {
        if (opt.value === "") {
          opt.style.display = '';
        } else if (!game) {
          opt.style.display = '';
        } else if (opt.dataset.game === 'lol' && game === 'League of Legends') {
          opt.style.display = '';
        } else if (opt.dataset.game === 'valorant' && game === 'Valorant') {
          opt.style.display = '';
        } else {
          opt.style.display = 'none';
        }
      });
      // Repeat similar logic for rank filter

      ranks.forEach(opt => {
        if (opt.value === "") {
          opt.style.display = '';
        } else if (!game) {
          opt.style.display = '';
        } else if (opt.dataset.game === 'lol' && game === 'League of Legends') {
          opt.style.display = '';
        } else if (opt.dataset.game === 'valorant' && game === 'Valorant') {
          opt.style.display = '';
        } else {
          opt.style.display = 'none';
        }
      });
    });

});