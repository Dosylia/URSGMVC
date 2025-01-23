document.addEventListener("DOMContentLoaded", function () {
    banUserNo = document.getElementById("banUserNo");
    censorBioNo = document.getElementById("censorBioNo");
    censorPictureNo = document.getElementById("censorPictureNo");

    banUserNo.addEventListener("click", function () {
        const banUserModal = document.getElementById("banUserModal");
        banUserModal.style.display = "none";
    });

    censorBioNo.addEventListener("click", function () {
        const censorBioModal = document.getElementById("censorBioModal");
        censorBioModal.style.display = "none";
    });

    censorPictureNo.addEventListener("click", function () {
        const censorPictureModal = document.getElementById("censorPictureModal");
        censorPictureModal.style.display = "none";
    });
});

function openConfirmationPopupBanUser(userId, username) {
    const banUserModal = document.getElementById("banUserModal");

    const banUserTitle = document.getElementById("banUserTitle");
    banUserTitle.textContent = `Are you sure you want to ban ${username}`;
    const banUserInput = document.getElementById("userIdBan");
    banUserInput.value = userId;

    banUserModal.style.display = "flex";
}

function openConfirmationPopupCensorBio(userId, username) {
    event.preventDefault();
    const censorBioModal = document.getElementById("censorBioModal");

    const censorBioTitle = document.getElementById("censorBioTitle");
    censorBioTitle.textContent = `Are you sure you want to censor the bio of ${username}`;
    const censorBioInput = document.getElementById("userIdCensorBio");
    censorBioInput.value = userId;

    censorBioModal.style.display = "flex";
}

function openConfirmationPopupCensorPicture(userId, username) {
    event.preventDefault();
    const censorPictureModal = document.getElementById("censorPictureModal");

    const censorPictureTitle = document.getElementById("censorPictureTitle");
    censorPictureTitle.textContent = `Are you sure you want to censor the picture of ${username}`;
    const censorPictureInput = document.getElementById("userIdCensorPicture");
    censorPictureInput.value = userId;

    censorPictureModal.style.display = "flex";
}