document.addEventListener('DOMContentLoaded', function() {
    const ValorantAgents = [
        {name: 'Brimstone', image: 'public/images/valorant_champions/Brimstone_icon.webp'},
        {name: 'Viper', image: 'public/images/valorant_champions/Viper_icon.webp'},
        {name: 'Phoenix', image: 'public/images/valorant_champions/Phoenix_icon.webp'},
        {name: 'Jett', image: 'public/images/valorant_champions/Jett_icon.webp'},
        {name: 'Sova', image: 'public/images/valorant_champions/Sova_icon.webp'},
        {name: 'Raze', image: 'public/images/valorant_champions/Raze_icon.webp'},
        {name: 'Breach', image: 'public/images/valorant_champions/Breach_icon.webp'},
        {name: 'Omen', image: 'public/images/valorant_champions/Omen_icon.webp'},
        {name: 'Sage', image: 'public/images/valorant_champions/Sage_icon.webp'},
        {name: 'Killjoy', image: 'public/images/valorant_champions/Killjoy_icon.webp'},
        {name: 'Astra', image: 'public/images/valorant_champions/Astra_icon.webp'},
        {name: 'Yoru', image: 'public/images/valorant_champions/Yoru_icon.webp'},
        {name: 'Kayo', image: 'public/images/valorant_champions/KAYO_icon.webp'},
        {name: 'Skye', image: 'public/images/valorant_champions/Skye_icon.webp'},
        {name: 'Astra', image: 'public/images/valorant_champions/Astra_icon.webp'},
        {name: 'Chamber', image: 'public/images/valorant_champions/Chamber_icon.webp'},
        {name: 'Neon', image: 'public/images/valorant_champions/Neon_icon.webp'},
        {name: 'Fade', image: 'public/images/valorant_champions/Fade_icon.webp'},
        {name: 'Harbor', image: 'public/images/valorant_champions/Harbor_icon.webp'},
        {name: 'Breach', image: 'public/images/valorant_champions/Breach_icon.webp'},
        {name: 'Reyna', image: 'public/images/valorant_champions/Reyna_icon.webp'},
        {name: 'Cypher', image: 'public/images/valorant_champions/Cypher_icon.webp'},
        {name: 'Sova', image: 'public/images/valorant_champions/Sova_icon.webp'},
        {name: 'Viper', image: 'public/images/valorant_champions/Viper_icon.webp'},
        {name: 'Raze', image: 'public/images/valorant_champions/Raze_icon.webp'},
        {name: 'Phoenix', image: 'public/images/valorant_champions/Phoenix_icon.webp'},
        {name: 'Jett', image: 'public/images/valorant_champions/Jett_icon.webp'}
    ];
    

// Get main images and modals
const pickerImages = document.querySelectorAll('.champion-picker-img');
const selectedChampions = new Set();
const modals = {
    'picker-main1': 'modal-main1',
    'picker-main2': 'modal-main2',
    'picker-main3': 'modal-main3'
};

// Populate modals with champions
Object.keys(modals).forEach(pickerClass => {
    const modalId = modals[pickerClass];
    const modalList = document.getElementById(`champion-list-${modalId.split('-')[1]}`);

    ValorantAgents.forEach(champ => {
        const img = document.createElement('img');
        img.src = champ.image;
        img.alt = champ.name;
        img.dataset.name = champ.name;
        img.addEventListener('click', () => selectChampion(pickerClass, champ.name, champ.image));
        modalList.appendChild(img);
    });
});

// Open modal when clicking on an image
pickerImages.forEach(img => {
    img.addEventListener('click', function() {
        const modalId = modals[this.classList[1]];
        document.getElementById(modalId).style.display = 'flex';
    });
});

// Close modal function
window.closeModal = function(modalId) {
    event.preventDefault();
    document.getElementById(modalId).style.display = 'none';
};

// Select champion and close modal
function selectChampion(pickerClass, champName, champImage) {
    if (selectedChampions.has(champName)) {
        return;
    }

    const inputElement = document.getElementById(pickerClass.replace('picker-', ''));
    const previousChampion = inputElement.value;

    // Update the <p> text to the selected champion's name
    const pickerId = pickerClass.replace('picker-', '') + '-name';  // 'main1-name', 'main2-name', 'main3-name'
    document.getElementById(pickerId).textContent = champName;

    if (previousChampion) {
        selectedChampions.delete(previousChampion);
    }

    document.querySelector(`.${pickerClass}`).src = champImage;
    document.querySelector(`.${pickerClass}`).style.border = "3px solid #5cd34f";
    inputElement.value = champName;
    selectedChampions.add(champName);

    closeModal(modals[pickerClass]);
}

// Search function
window.filterChampions = function(input, listId) {
    const filter = input.value.toLowerCase();
    const images = document.querySelectorAll(`#${listId} img`);

    images.forEach(img => {
        img.style.display = img.dataset.name.toLowerCase().includes(filter) ? 'inline-block' : 'none';
    });
};

// Skip selection functionality (reset selected champions)
function handleSkipSelection() {
    const skipInput = document.getElementById('skipSelection');
    const pickerImages = document.querySelectorAll('.champion-picker-img');
    const pickerInputs = document.querySelectorAll('.champion-picker-input');

    if (skipInput.value === '0') {
        // Activate "Skip Mode": Clear champions and darken images
        pickerImages.forEach(img => img.classList.add('shadow'));
        pickerImages.forEach(img => img.style.border = "3px solid #e74057");
        pickerInputs.forEach(input => {
            if (input.value) {
                selectedChampions.delete(input.value);
            }
            input.value = '';
        });
        skipInput.value = '1';
    } else {
        // Restore selection mode: Remove shadow and restore previous selections
        pickerImages.forEach(img => img.classList.remove('shadow'));
        pickerInputs.forEach(input => {
            const champName = input.value;
            if (champName) {
                selectedChampions.add(champName);
            }
        });
        skipInput.value = '0';
    }
}

// Set the correct display state based on skipSelection value on page load
const skipInput = document.getElementById('skipSelection'); // Get the hidden input

if (skipInput.value == '1') {  // Check if skipSelection is active
    console.log('Skip mode is active');
    pickerImages.forEach(img => img.classList.add('shadow'));
    pickerImages.forEach(img => img.style.border = "3px solid #e74057");
} else if(skipSelection?.dataset.page === "create" && skipSelection?.value === "0") {
    pickerImages.forEach(img => img.style.border = "3px solid #e74057");
} else {
    pickerImages.forEach(img => img.style.border = "3px solid #5cd34f");
}

// Skip selection button event listener
document.getElementById('skipSelection-btn').addEventListener('click', function(event) {
    event.preventDefault();
    handleSkipSelection(); // Toggle skip selection mode when clicked
});

// Add event listener for the search input fields
const searchInputs = document.querySelectorAll('.champion-search');
searchInputs.forEach(input => {
    input.addEventListener('keyup', function() {
        const listId = input.nextElementSibling.id;
        filterChampions(input, listId);
    });
});

const closeButtons = document.querySelectorAll('.close-btn');
closeButtons.forEach(button => {
    button.addEventListener('click', function(event) {
        event.preventDefault();
        const modalId = button.closest('.champion-modal').id;
        closeModal(modalId);
    });
});
});