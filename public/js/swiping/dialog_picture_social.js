// const showButtonSocialLinks = document.getElementById('opendialog_add_social_links');
// const favDialogSocialLinks = document.getElementById('favDialogSocialLinks');
// let cancelButtonSocialLinks;
// if (favDialogSocialLinks !== null && favDialogSocialLinks !== undefined) {
//   cancelButtonSocialLinks = favDialogSocialLinks.querySelector('#closeButton_social_links');
// }
const fileInputProfile = document.getElementById('fileProfile')
const fileNameProfile = document.getElementById('file-nameProfile')
const placeholderMessage = document.getElementById('placeholder-message')

function switchPersonalColorWebsite(selectedColor, userId, removeColor) {
    if (!selectedColor && !removeColor) {
        alert('Please select a color first!')
        return
    }

    const token = localStorage.getItem('masterTokenWebsite')

    fetch('/switchPersonalColorWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: `color=${encodeURIComponent(
            selectedColor
        )}&userId=${encodeURIComponent(userId)}`,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Update color visually
                const profileColor = document.querySelector(
                    '.profile-personal-color'
                )
                if (profileColor)
                    profileColor.style.backgroundColor = selectedColor

                const userImage = document.getElementById('image_users')
                if (userImage) userImage.style.borderColor = selectedColor

                // Update all menu-links btn_user_updates_profile design
                const menuButtons = document.querySelectorAll(
                    '.btn_user_updates_profile'
                )

                //Update fa-solid fa-image icon color
                const updatePictureButton = document.querySelector(
                    '.btn_updateProfilePicture i'
                )

                if (updatePictureButton) {
                    updatePictureButton.style.color = selectedColor
                }

                menuButtons.forEach((button) => {
                    button.style.backgroundColor = selectedColor
                    button.style.borderColor = selectedColor
                })

                const overlay = document.getElementById('overlay')
                overlay.style.display = 'none'
                favDialogPicture.style.display = 'none'
            } else {
                console.error('Error:', data.error)
            }
        })
        .catch((error) => {
            console.error('Fetch error:', error)
        })
}

// if (showButtonSocialLinks !== null && showButtonSocialLinks !== undefined) {
//   showButtonSocialLinks.addEventListener('click', () => {
//     openDialogSocialLinks();
//   });

//   cancelButtonSocialLinks.addEventListener('click', () => {
//     closeDialogSocialLinks();
//   });
// }

// function openDialogSocialLinks() {
//   favDialogSocialLinks.style.display = 'flex';
//   favDialogSocialLinks.showModal();
// }

// function closeDialogSocialLinks() {
//   favDialogSocialLinks.style.display = 'none';
//   favDialogSocialLinks.close();
// }

const showRiotModalbtn = document.getElementById('openRiotAccount-btn')
const riotModal = document.getElementById('riot-modal')
const closeRiotModal = document.getElementById('close-modal-riot')

showRiotModalbtn?.addEventListener('click', function () {
    riotModal.classList.remove('riot-modal-hidden')
    const overlay = document.getElementById('overlay')
    overlay.style.display = 'block'
})

closeRiotModal?.addEventListener('click', function () {
    riotModal.classList.add('riot-modal-hidden')
    const overlay = document.getElementById('overlay')
    overlay.style.display = 'none'
})

const showButtonPicture = document.getElementById('opendialog_update_picture')
const favDialogPicture = document.getElementById('favDialogPicture')
const cancelButtonPicture = favDialogPicture.querySelector(
    '#closeButton_user_picture'
)

showButtonPicture.addEventListener('click', () => {
    const overlay = document.getElementById('overlay')
    overlay.style.display = 'block'
    favDialogPicture.style.display = 'block'
})

cancelButtonPicture.addEventListener('click', () => {
    const overlay = document.getElementById('overlay')
    overlay.style.display = 'none'
    favDialogPicture.style.display = 'none'
})

const hiddenP = document.getElementById('hidden_p')
const imgDiscord = document.getElementById('discord_picture')

if (imgDiscord !== null && imgDiscord !== undefined) {
    imgDiscord.addEventListener('click', () => {
        if (hiddenP.style.display === 'none' || hiddenP.style.display === '') {
            hiddenP.style.display = 'block'
        } else {
            hiddenP.style.display = 'none'
        }
    })
}

fileInputProfile.addEventListener('change', (event) => {
    const input = event.target
    if (input.files.length > 0) {
        fileNameProfile.textContent = input.files[0].name
    } else {
        fileNameProfile.textContent = 'No file selected'
    }
})

function usePictureFrame(itemId, userId) {
    console.log(`Adding frame item ID: ${itemId}, userId: ${userId}`)
    const token = localStorage.getItem('masterTokenWebsite')

    const dataToSend = {
        itemId,
        userId,
    }

    const jsonData = JSON.stringify(dataToSend)

    fetch('index.php?action=usePictureFrameWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok')
            }
            return response.json()
        })
        .then((data) => {
            placeholderMessage.innerHTML = ''
            console.log('Success:', data)
            if (data.success) {
                location.reload()
            } else {
                placeholderMessage.innerHTML = data.message
            }
        })
        .catch((error) => {
            console.error('Error:', error)
        })
}

function RemovePictureFrame(itemId, userId) {
    const token = localStorage.getItem('masterTokenWebsite')
    console.log(`Removing frame item ID: ${itemId}, userId: ${userId}`)

    const dataToSend = {
        itemId,
        userId,
    }

    const jsonData = JSON.stringify(dataToSend)

    fetch('index.php?action=removePictureFrameWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok')
            }
            return response.json()
        })
        .then((data) => {
            placeholderMessage.innerHTML = ''
            console.log('Success:', data)
            if (data.success) {
                location.reload()
            } else {
                placeholderMessage.innerHTML = data.message
            }
        })
        .catch((error) => {
            console.error('Error:', error)
        })
}

function showPreview(event) {
    const preview = document.getElementById('preview')
    const fileName = document.getElementById('file-nameProfile')
    const file = event.target.files[0]
    if (file) {
        preview.src = URL.createObjectURL(file)
        fileName.textContent = file.name
    }
}

function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex'
}

document.addEventListener('DOMContentLoaded', function () {
    /**
     * Generic function to handle file previews for a given dialog
     * @param {HTMLElement} dialog
     */
    function setupFilePreview(dialog) {
        const fileInput = dialog.querySelector('.file-input')
        const preview = dialog.querySelector('.preview-img')
        const fileName = dialog.querySelector('.file-name')

        if (fileInput) {
            fileInput.addEventListener('change', function (event) {
                const file = event.target.files[0]
                if (file) {
                    preview.src = URL.createObjectURL(file)
                    fileName.textContent = file.name
                }
            })
        }
    }

    /**
     * Generic function to handle form submission loading overlay
     * @param {HTMLElement} dialog
     */
    function setupFormLoading(dialog) {
        const form = dialog.querySelector('form')
        const loadingOverlay = dialog.querySelector('#loadingOverlay')

        if (form) {
            form.addEventListener('submit', function () {
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'flex'
                }
            })
        }
    }

    /**
     * Close buttons for dialogs
     */
    const closeButtons = document.querySelectorAll('.btn-close')
    closeButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const dialog = this.closest('dialog')
            if (dialog) dialog.close()
        })
    })

    /**
     * Initialize dialogs
     */
    const dialogs = document.querySelectorAll('dialog')
    dialogs.forEach((dialog) => {
        setupFilePreview(dialog)
        setupFormLoading(dialog)
    })

    const pictureFrameButtons = document.querySelectorAll('.btn_picture_frame')
    const pictureFrameButtonsRemove = document.querySelectorAll(
        '.btn_picture_frame_remove'
    )

    pictureFrameButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const itemId = this.getAttribute('data-item-id')
            usePictureFrame(itemId, userIdHeader)
        })
    })

    pictureFrameButtonsRemove.forEach((button) => {
        button.addEventListener('click', function () {
            const itemId = this.getAttribute('data-item-id')
            RemovePictureFrame(itemId, userIdHeader)
        })
    })

    const colorCircles = document.querySelectorAll('.color-circle')
    const colorPicker = document.getElementById('custom-color-input')
    const colorPreview = document.querySelector('.color-preview')
    const saveColorBtn = document.getElementById('save-color-btn')
    const removeColorBtn = document.getElementById('remove-color-btn')
    let selectedColor = null

    // Handle preset color selection
    colorCircles.forEach((circle) => {
        circle.addEventListener('click', () => {
            colorCircles.forEach((c) => c.classList.remove('selected'))
            circle.classList.add('selected')
            colorPreview.classList.remove('active')
            selectedColor = circle.dataset.color
        })
    })

    // Handle custom color selection
    colorPicker.addEventListener('input', (e) => {
        colorCircles.forEach((c) => c.classList.remove('selected'))
        colorPreview.style.backgroundColor = e.target.value
        colorPreview.classList.add('active')
        selectedColor = e.target.value
    })

    // Save color
    saveColorBtn.addEventListener('click', function () {
        if (!selectedColor) {
            document.querySelector('.section-alert').textContent =
                'Please select or pick a color first!'
            return
        }

        switchPersonalColorWebsite(selectedColor, userId, false)
    })

    removeColorBtn.addEventListener('click', function () {
        selectedColor = null
        switchPersonalColorWebsite('', userId, true)
    })
})
