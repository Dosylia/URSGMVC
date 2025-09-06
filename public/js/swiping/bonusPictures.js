const buttonAddBonusPicture = document.getElementById('opendialog_bonuspicture')
const favDialogBonusPicture = document.getElementById('favDialogBonusPicture')
const cancelButtonPictureBonus = favDialogBonusPicture.querySelector(
    '#closeButton_user_picture_bonus'
)
const fileInput = document.getElementById('file')
const fileName = document.getElementById('file-name')

fileInput.addEventListener('change', (event) => {
    const input = event.target
    if (input.files.length > 0) {
        fileName.textContent = input.files[0].name
    } else {
        fileName.textContent = 'No file selected'
    }
})

buttonAddBonusPicture.addEventListener('click', () => {
    openDialogBonusPicture()
})

cancelButtonPictureBonus.addEventListener('click', () => {
    closeDialogBonusPicture()
})

function openDialogBonusPicture() {
    favDialogBonusPicture.style.display = 'flex'
    favDialogBonusPicture.showModal()
}

function closeDialogBonusPicture() {
    favDialogBonusPicture.style.display = 'none'
    favDialogBonusPicture.close()
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.bonusPicture_delete').forEach((button) => {
        button.addEventListener('click', function () {
            const token = localStorage.getItem('masterTokenWebsite')
            let fileName = this.getAttribute('data-filename')

            fetch('/deleteBonusPicture', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    Authorization: `Bearer ${token}`,
                },
                body: `fileName=${encodeURIComponent(
                    fileName
                )}&userId=${userIdHeader}`,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.message === 'Success') {
                        this.parentElement.remove()
                    } else {
                        console.log('Error deleting picture :', data.message)
                    }
                })
        })
    })
})
