const dialogCookie = document.getElementById('dialogCookie');
const optionsCookies = document.getElementById('optionsCookies');
const aboutCookies = document.getElementById('aboutCookies');
const darkOverlay = document.getElementById('darkOverlay');
const savedCookies = localStorage.getItem('cookies');
const header = document.querySelector('header');
let stateOptions = 1;
let stateAbout = 0;

// Functions
function openModalCookie() {
    dialogCookie.style.display = 'flex';
    darkOverlay.style.display = 'block'; // Show the overlay
    header.style.opacity = '0.5'; // Optional, dims only the header
    dialogCookie.showModal();
}

function closeModalCookie() {
    dialogCookie.style.display = 'none';
    darkOverlay.style.display = 'none'; // Hide the overlay
    header.style.opacity = '1'; // Restore header opacity
    dialogCookie.close();
}

function switchAboutCookies() {
    if (stateAbout === 0) {
        aboutCookies.style.display = "flex";
        optionsCookies.style.display = "none";
        stateAbout = 1;
        stateOptions = 0;
    }
}

function switchOptionsCookies() {
    if (stateOptions === 0) {
        optionsCookies.style.display = "flex";
        aboutCookies.style.display = "none";
        stateAbout = 0;
        stateOptions = 1;
    }
}

function saveCookiesLocalStorage() {
    localStorage.setItem('cookies', 'acceptedAll');
}

document.addEventListener("DOMContentLoaded", function() {
    const btnOptionsCookies = document.getElementById('btn_optionsCookie');
    const btnAboutCookies = document.getElementById('btn_aboutCookie');
    const btnSave = document.getElementById('saveCookies');

    const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('deleted')) {
            const email = urlParams.get('message').split('Email: ')[1];
            trackDeleteAccount(email);
        }

    btnOptionsCookies.addEventListener('click', () => {
        switchOptionsCookies();
    });

    btnAboutCookies.addEventListener('click', () => {
        switchAboutCookies();
    });

    btnSave.addEventListener('click', () => {
        closeModalCookie();
        saveCookiesLocalStorage();
    });

    if (!savedCookies) {
        openModalCookie();
        switchOptionsCookies();
    }
});
