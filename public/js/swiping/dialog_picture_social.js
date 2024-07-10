const showButtonSocialLinks = document.getElementById('opendialog_add_social_links');
const favDialogSocialLinks = document.getElementById('favDialogSocialLinks');
const cancelButtonSocialLinks = favDialogSocialLinks.querySelector('#closeButton_social_links');
const fileInput = document.getElementById('file');
const fileName = document.getElementById('file-name');

showButtonSocialLinks.addEventListener('click', () => {
  openDialogSocialLinks();
});

cancelButtonSocialLinks.addEventListener('click', () => {
  closeDialogSocialLinks();
});

function openDialogSocialLinks() {
  favDialogSocialLinks.style.display = 'flex';
  favDialogSocialLinks.showModal();
}

function closeDialogSocialLinks() {
  favDialogSocialLinks.style.display = 'none';
  favDialogSocialLinks.close();
}

const showButtonPicture = document.getElementById('opendialog_update_picture');
const favDialogPicture = document.getElementById('favDialogPicture');
const cancelButtonPicture = favDialogPicture.querySelector('#closeButton_user_picture');

showButtonPicture.addEventListener('click', () => {
  openDialogPicture();
});

cancelButtonPicture.addEventListener('click', () => {
  closeDialogPicture();
});

function openDialogPicture() {
  favDialogPicture.style.display = 'flex';
  favDialogPicture.showModal();
}

function closeDialogPicture() {
  favDialogPicture.style.display = 'none';
  favDialogPicture.close();
}

const hiddenP = document.getElementById('hidden_p');
const imgDiscord = document.getElementById('discord_picture');

if (imgDiscord !== null && imgDiscord !== undefined) {
  imgDiscord.addEventListener('click', () => {
    if (hiddenP.style.display === "none" || hiddenP.style.display === "") {
      hiddenP.style.display = "block";
    } else {
      hiddenP.style.display = "none";
    }
  });
}


fileInput.addEventListener('change', (event) => {
  const input = event.target;
  if (input.files.length > 0) {
    fileName.textContent = input.files[0].name;
  } else {
    fileName.textContent = 'No file selected';
  }
});
