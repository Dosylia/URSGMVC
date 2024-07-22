const openMenuProfile = document.getElementById('open_menu_profile');
let username = document.getElementById('userUsername').value;
// Function to toggle the menu visibility and rotate the image
function toggleMenu() {
  const menu = document.getElementById('menu_profile');

  // On ajoute ou on supprime la classe "visible" en fonction de l'état actuel du menu
  if (menu.classList.contains('visible')) {
    menu.classList.remove('visible');
  } else {
    menu.classList.add('visible');
  }

  // Toggle the 'rotate' class to the image element
  openMenuProfile.classList.toggle('rotate');
}

// Add an event listener to the image for the click event
openMenuProfile.addEventListener('click', toggleMenu);
// Création du menu déroulant
const menuProfile = document.createElement('div');
menuProfile.id = 'menu_profile';

// Option 1: Profile
const optionProfile = document.createElement('a');
optionProfile.href = '/userProfile&username='+username;
optionProfile.innerText = 'Profile';
optionProfile.classList.add('menu-option');
menuProfile.appendChild(optionProfile);

// Séparateur (ligne noire)
const separator = document.createElement('hr');
separator.classList.add('menu-separator');
menuProfile.appendChild(separator);

// Option 2: Blocklist
const optionBlocklist = document.createElement('a');
optionBlocklist.href = '/friendlistPage';
optionBlocklist.innerText = 'Friendlist';
optionBlocklist.classList.add('menu-option');
menuProfile.appendChild(optionBlocklist);

// Séparateur (ligne noire)
const separator2 = document.createElement('hr');
separator2.classList.add('menu-separator');
menuProfile.appendChild(separator2);

// Option 3: Logout
const optionLogout = document.createElement('a');
optionLogout.href = '/logout';
optionLogout.innerText = 'Logout';
optionLogout.classList.add('menu-option');
menuProfile.appendChild(optionLogout);

// Insertion du menu déroulant after the nav element
const navElement = document.querySelector('nav');
navElement.insertAdjacentElement('afterend', menuProfile);