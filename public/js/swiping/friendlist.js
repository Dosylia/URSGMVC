function openConfirmationPopup() {
    var confirmationPopup = document.getElementById("confirmationPopup");
    if (typeof confirmationPopup.showModal === "function") {
      confirmationPopup.showModal();
    } else {
      // Affichage de secours pour les navigateurs ne prenant pas en charge <dialog>
      confirmationPopup.style.display = "block";
    }
  }
  
  function closeConfirmationPopup() {
    var confirmationPopup = document.getElementById("confirmationPopup");
    if (typeof confirmationPopup.close === "function") {
      confirmationPopup.close();
    } else {
      // Fermeture de secours pour les navigateurs ne prenant pas en charge <dialog>
      confirmationPopup.style.display = "none";
    }
  }


  function openConfirmationPopup2() {
    var confirmationPopup = document.getElementById("confirmationPopup2");
    if (typeof confirmationPopup.showModal === "function") {
      confirmationPopup.showModal();
    } else {
      // Affichage de secours pour les navigateurs ne prenant pas en charge <dialog>
      confirmationPopup.style.display = "block";
    }
  }
  
  function closeConfirmationPopup2() {
    var confirmationPopup = document.getElementById("confirmationPopup2");
    if (typeof confirmationPopup.close === "function") {
      confirmationPopup.close();
    } else {
      // Fermeture de secours pour les navigateurs ne prenant pas en charge <dialog>
      confirmationPopup.style.display = "none";
    }
  }