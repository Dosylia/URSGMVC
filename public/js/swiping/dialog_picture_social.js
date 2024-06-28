const showButtonSocialLinks = document.getElementById('opendialog_add_social_links');
const favDialogSocialLinks = document.getElementById('favDialogSocialLinks');
const cancelButtonSocialLinks = favDialogSocialLinks.querySelector('#closeButton_social_links');

showButtonSocialLinks.addEventListener('click', () => {
  openDialogSocialLinks();
});

cancelButtonSocialLinks.addEventListener('click', () => {
  closeDialogSocialLinks();
});

function openDialogSocialLinks() {
  favDialogSocialLinks.showModal();
}

function closeDialogSocialLinks() {
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
  favDialogPicture.showModal();
}

function closeDialogPicture() {
  favDialogPicture.close();
}