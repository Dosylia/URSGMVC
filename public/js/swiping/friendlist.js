function openConfirmationPopup(dialogId, friendUsername) {
  var confirmationPopup = document.getElementById(dialogId);
  if (typeof confirmationPopup.showModal === "function") {
      confirmationPopup.showModal();
  } else {
      confirmationPopup.style.display = "block";
  }
}

function closeConfirmationPopup(dialogId) {
  var confirmationPopup = document.getElementById(dialogId);
  if (typeof confirmationPopup.close === "function") {
      confirmationPopup.close();
  } else {
      confirmationPopup.style.display = "none";
  }
}

function openConfirmationPopup2(dialogId, friendUsername) {
  var confirmationPopup2 = document.getElementById(dialogId);
  if (typeof confirmationPopup2.showModal === "function") {
      confirmationPopup2.showModal();
  } else {
      confirmationPopup2.style.display = "block";
  }
}

function closeConfirmationPopup2(dialogId) {
  var confirmationPopup2 = document.getElementById(dialogId);
  if (typeof confirmationPopup2.close === "function") {
      confirmationPopup2.close();
  } else {
      confirmationPopup2.style.display = "none";
  }
}

function openConfirmationPopupUnfriend(dialogId, friendUsername) {
  var confirmationPopupUnfriend = document.getElementById(dialogId);
  if (typeof confirmationPopupUnfriend.showModal === "function") {
      confirmationPopupUnfriend.showModal();
  } else {
      confirmationPopupUnfriend.style.display = "block";
  }
}

function closeConfirmationPopupUnfriend(dialogId) {
  var confirmationPopupUnfriend = document.getElementById(dialogId);
  if (typeof confirmationPopupUnfriend.close === "function") {
      confirmationPopupUnfriend.close();
  } else {
      confirmationPopupUnfriend.style.display = "none";
  }
}

function switchTab(tabId) {
  const tabs = document.querySelectorAll('.friendlist-section');
  const buttons = document.querySelectorAll('.tab-button');
  tabs.forEach(tab => tab.classList.remove('active'));
  buttons.forEach(btn => btn.classList.remove('active'));
  document.getElementById(tabId).classList.add('active');
  event.target.classList.add('active');
}
