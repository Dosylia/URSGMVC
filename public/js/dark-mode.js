document.addEventListener('DOMContentLoaded', function() {
  var toggleButton = document.getElementById('toggle-button');
  var body = document.getElementsByTagName('body')[0];
  let container = document.querySelector('.toggle-container');

  // Charger la préférence du mode depuis le localStorage
  const savedMode = localStorage.getItem('mode');
    const activeMode = savedMode === 'light-mode' || savedMode === 'dark-mode' ? savedMode : 'dark-mode';

    body.classList.add(activeMode);


    if (activeMode === 'dark-mode' && container !== null) {
        toggleButton.classList.add('dark-mode');
        container.classList.add('active');
    }

    if (container !== null) {
        container.addEventListener('click', function () {
            if (body.classList.contains('dark-mode')) {
                // Switch to light mode
                body.classList.remove('dark-mode');
                body.classList.add('light-mode');
                toggleButton.classList.remove('dark-mode');
                container.classList.remove('active');
                saveModePreference('light-mode');
            } else {
                // Switch to dark mode
                body.classList.remove('light-mode');
                body.classList.add('dark-mode');
                toggleButton.classList.add('dark-mode');
                container.classList.add('active');
                saveModePreference('dark-mode');
            }
        });
    }


  // Fonction pour enregistrer le choix du mode dans le localStorage
  function saveModePreference(mode) {
      localStorage.setItem('mode', mode);
  }
});
