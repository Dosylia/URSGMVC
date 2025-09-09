"use strict";
import apiFetch from "./api_fetch.js";

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
            let fileName = this.getAttribute('data-filename')
            apiFetch({
                url: '/deleteBonusPicture',
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
                body: `fileName=${encodeURIComponent(
                    fileName
                )}&userId=${userIdHeader}`,
            })
                .then((data) => {
                    if (data.message === 'Success') {
                        this.parentElement.remove()
                    } else {
                        console.log('Error deleting picture :', data.message)
                    }
                })
                .catch((error) => {
                    // General error happened. Probably not user related and more on the dev side.
                     console.log('Error deleting picture: ', error)
                })
        })
    })
})
