const dialogCookie = document.getElementById('dialogCookie');
const optionsCookies = document.getElementById('optionsCookies');
const aboutCookies = document.getElementById('aboutCookies');
const savedCookies = localStorage.getItem('cookies');
let stateOptions = 1;
let stateAbout = 0;

// Functions
function openModalCookie() {
    dialogCookie.style.display = 'flex';
    dialogCookie.showModal();
}

function closeModalCookie() {
    dialogCookie.style.display = 'none';
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
