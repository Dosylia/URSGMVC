const buttonAddBonusPicture = document.getElementById('opendialog_bonuspicture');
const favDialogBonusPicture = document.getElementById('favDialogBonusPicture');
const cancelButtonPictureBonus = favDialogBonusPicture.querySelector('#closeButton_user_picture_bonus');

buttonAddBonusPicture.addEventListener('click', () => {
  openDialogBonusPicture();
});

cancelButtonPictureBonus.addEventListener('click', () => {
  closeDialogBonusPicture();
});

function openDialogBonusPicture() {
  favDialogBonusPicture.style.display = 'flex';
  favDialogBonusPicture.showModal();
}

function closeDialogBonusPicture() {
  favDialogBonusPicture.style.display = 'none';
  favDialogBonusPicture.close();
}

document.addEventListener('DOMContentLoaded', function() {

  document.querySelectorAll(".delete-picture").forEach(button => {
    button.addEventListener("click", function () {
        let fileName = this.getAttribute("data-filename");

        fetch("/deleteBonusPicture", {
            method: "POST",
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'Authorization': `Bearer ${token}`,
          },
            body: `fileName=${encodeURIComponent(fileName)}&userId=${userIdHeader}`,
        })
        .then(response => response.json())
        .then(data => {
            if (data.message === "Success") {
                this.parentElement.remove();
            } else {
                console.log(data.message);
            }
        });
    });
});
});
