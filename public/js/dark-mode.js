document.addEventListener('DOMContentLoaded', function() {
    var toggleButton = document.getElementById('toggle-button');
    var body = document.getElementsByTagName('body')[0];
    let container = document.querySelector('.toggle-container');
  
    container.addEventListener('click', function() {
      if (body.classList.contains('dark-mode')) {
        // Mode actuel : sombre, on passe au mode clair
        body.classList.remove('dark-mode');
        body.classList.add('light-mode'); // Ajout de la classe "light-mode"
        toggleButton.classList.remove('dark-mode');
        // Enregistrez le choix du mode dans la session
        saveModePreference('light');
      } else {
        // Mode actuel : clair, on passe au mode sombre
        body.classList.remove('light-mode'); // Suppression de la classe "light-mode"
        body.classList.add('dark-mode');
        toggleButton.classList.add('dark-mode');
        // Enregistrez le choix du mode dans la session
        saveModePreference('dark');
      }
    });
  
    // Fonction pour enregistrer le choix du mode dans la session
    function saveModePreference(mode) {
      fetch('index.php?action=saveDarkMode', {
        method: 'POST',
        body: JSON.stringify({ mode: mode })
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          // La préférence du mode a été enregistrée avec succès
          console.log("Préférence du mode enregistrée : " + mode);
        } else {
          // Gérer les erreurs de réponse du serveur
          console.log("Une erreur s'est produite lors de l'enregistrement de la préférence du mode : " + data.message);
        }
      })
      .catch(error => {
        // Gérer les erreurs de requête
        console.log("Une erreur s'est produite lors de la requête : " + error.message);
      });
    }
  });