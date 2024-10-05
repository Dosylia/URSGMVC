document.addEventListener('DOMContentLoaded', function() {
  var toggleButton = document.getElementById('toggle-button');
  var body = document.getElementsByTagName('body')[0];
  let container = document.querySelector('.toggle-container');

  // Charger la préférence du mode depuis le localStorage
  const savedMode = localStorage.getItem('mode');
  if (savedMode) {
      body.classList.add(savedMode);
      if (savedMode === 'dark-mode') {
          toggleButton.classList.add('dark-mode');
          container.classList.add('active');
      }
  }

  container.addEventListener('click', function() {
      if (body.classList.contains('dark-mode')) {
          // Mode actuel : sombre, on passe au mode clair
          body.classList.remove('dark-mode');
          body.classList.add('light-mode'); // Ajout de la classe "light-mode"
          toggleButton.classList.remove('dark-mode');
          container.classList.remove('active');
          saveModePreference('light-mode');
      } else {
          // Mode actuel : clair, on passe au mode sombre
          body.classList.remove('light-mode'); // Suppression de la classe "light-mode"
          body.classList.add('dark-mode');
          toggleButton.classList.add('dark-mode');
          container.classList.add('active');
          saveModePreference('dark-mode');
      }
  });

  // Fonction pour enregistrer le choix du mode dans le localStorage
  function saveModePreference(mode) {
      localStorage.setItem('mode', mode);
      console.log("Préférence du mode enregistrée : " + mode);
  }
});
