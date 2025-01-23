document.addEventListener("DOMContentLoaded", function () {
    const banUserNo = document.getElementById("banUserNo");
    const censorBioNo = document.getElementById("censorBioNo");
    const censorPictureNo = document.getElementById("censorPictureNo");
    const requestBanNo = document.getElementById("requestBanNo");
    const censorBothNo = document.getElementById("censorBothNo");
    const dismissNo = document.getElementById("dismissNo");

    // Close modals
    dismissNo.addEventListener("click", function () {
        const dismissModal = document.getElementById("dismissModal");
        dismissModal.style.display = "none";
    });

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

    requestBanNo.addEventListener("click", function () {
        const requestBanModal = document.getElementById("requestBanModal");
        requestBanModal.style.display = "none";
    });

    censorBothNo.addEventListener("click", function () {
        const censorBothModal = document.getElementById("censorBothModal");
        censorBothModal.style.display = "none";
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

function openConfirmationPopupRequestBan(userId, username) {
    event.preventDefault();
    const requestBanModal = document.getElementById("requestBanModal");
    const requestBanTitle = document.getElementById("requestBanTitle");
    requestBanTitle.textContent = `Are you sure you want to request a ban for ${username}`;
    const requestBanInput = document.getElementById("userIdRequestBan");
    requestBanInput.value = userId;
    requestBanModal.style.display = "flex";
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

function openConfirmationPopupCensorBoth(userId, username) {
    event.preventDefault();
    const censorBothModal = document.getElementById("censorBothModal");
    const censorBothTitle = document.getElementById("censorBothTitle");
    censorBothTitle.textContent = `Are you sure you want to censor both the bio and picture of ${username}`;
    const censorBothInput = document.getElementById("userIdCensorBoth");
    censorBothInput.value = userId;
    censorBothModal.style.display = "flex";
}

function dismissReports(userId, username) {
    event.preventDefault();
    const dismissModal = document.getElementById("dismissModal");
    const dismissTitle = document.getElementById("dismissTitle");
    dismissTitle.textContent = `Are you sure you want to dismiss the report of ${username}`;
    const dismissInput = document.getElementById("userIdDismiss");
    dismissInput.value = userId;
    dismissModal.style.display = "flex";
}
