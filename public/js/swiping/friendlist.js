function openConfirmationPopup() {
    var confirmationPopup = document.getElementById("confirmationPopup");
    if (typeof confirmationPopup.showModal === "function") {
      confirmationPopup.showModal();
    } else {
      confirmationPopup.style.display = "block";
    }
  }
  
  function closeConfirmationPopup() {
    var confirmationPopup = document.getElementById("confirmationPopup");
    if (typeof confirmationPopup.close === "function") {
      confirmationPopup.close();
    } else {
      confirmationPopup.style.display = "none";
    }
  }


  function openConfirmationPopup2() {
    var confirmationPopup = document.getElementById("confirmationPopup2");
    if (typeof confirmationPopup.showModal === "function") {
      confirmationPopup.showModal();
    } else {
      confirmationPopup.style.display = "block";
    }
  }
  
  function closeConfirmationPopup2() {
    var confirmationPopup = document.getElementById("confirmationPopup2");
    if (typeof confirmationPopup.close === "function") {
      confirmationPopup.close();
    } else {
      confirmationPopup.style.display = "none";
    }
  }