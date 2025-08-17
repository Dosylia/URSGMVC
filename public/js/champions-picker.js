document.addEventListener('DOMContentLoaded', function() {
    const LoLChampions = [
        {name: 'Aatrox', image: 'public/images/champions/Aatrox.png'},
        {name: 'Ahri', image: 'public/images/champions/Ahri.png'},
        {name: 'Akali', image: 'public/images/champions/Akali.png'},
        {name: 'Akshan', image: 'public/images/champions/Akshan.png'},
        {name: 'Alistar', image: 'public/images/champions/Alistar.png'},
        {name: 'Ambessa', image: 'public/images/champions/Ambessa.png'},
        {name: 'Amumu', image: 'public/images/champions/Amumu.png'},
        {name: 'Anivia', image: 'public/images/champions/Anivia.png'},
        {name: 'Annie', image: 'public/images/champions/Annie.png'},
        {name: 'Aphelios', image: 'public/images/champions/Aphelios.png'},
        {name: 'Ashe', image: 'public/images/champions/Ashe.png'},
        {name: 'Aurelion Sol', image: 'public/images/champions/AurelionSol.png'},
        {name: 'Aurora', image: 'public/images/champions/Aurora.png'},
        {name: 'Azir', image: 'public/images/champions/Azir.png'},
        {name: 'Bard', image: 'public/images/champions/Bard.png'},
        {name: 'Belveth', image: 'public/images/champions/Belveth.png'},
        {name: 'Blitzcrank', image: 'public/images/champions/Blitzcrank.png'},
        {name: 'Brand', image: 'public/images/champions/Brand.png'},
        {name: 'Braum', image: 'public/images/champions/Braum.png'},
        {name: 'Briar', image: 'public/images/champions/Briar.png'},
        {name: 'Caitlyn', image: 'public/images/champions/Caitlyn.png'},
        {name: 'Camille', image: 'public/images/champions/Camille.png'},
        {name: 'Cassiopeia', image: 'public/images/champions/Cassiopeia.png'},
        {name: 'ChoGath', image: 'public/images/champions/ChoGath.png'},
        {name: 'Corki', image: 'public/images/champions/Corki.png'},
        {name: 'Darius', image: 'public/images/champions/Darius.png'},
        {name: 'Diana', image: 'public/images/champions/Diana.png'},
        {name: 'DrMundo', image: 'public/images/champions/DrMundo.png'},
        {name: 'Draven', image: 'public/images/champions/Draven.png'},
        {name: 'Ekko', image: 'public/images/champions/Ekko.png'},
        {name: 'Elise', image: 'public/images/champions/Elise.png'},
        {name: 'Evelynn', image: 'public/images/champions/Evelynn.png'},
        {name: 'Ezreal', image: 'public/images/champions/Ezreal.png'},
        {name: 'Fiddlesticks', image: 'public/images/champions/Fiddlesticks.png'},
        {name: 'Fiora', image: 'public/images/champions/Fiora.png'},
        {name: 'Fizz', image: 'public/images/champions/Fizz.png'},
        {name: 'Galio', image: 'public/images/champions/Galio.png'},
        {name: 'Gangplank', image: 'public/images/champions/Gangplank.png'},
        {name: 'Garen', image: 'public/images/champions/Garen.png'},
        {name: 'Gnar', image: 'public/images/champions/Gnar.png'},
        {name: 'Gragas', image: 'public/images/champions/Gragas.png'},
        {name: 'Graves', image: 'public/images/champions/Graves.png'},
        {name: 'Gwen', image: 'public/images/champions/Gwen.png'},
        {name: 'Hecarim', image: 'public/images/champions/Hecarim.png'},
        {name: 'Heimerdinger', image: 'public/images/champions/Heimerdinger.png'},
        {name: 'Hwei', image: 'public/images/champions/Hwei.png'},
        {name: 'Illaoi', image: 'public/images/champions/Illaoi.png'},
        {name: 'Irelia', image: 'public/images/champions/Irelia.png'},
        {name: 'Ivern', image: 'public/images/champions/Ivern.png'},
        {name: 'Janna', image: 'public/images/champions/Janna.png'},
        {name: 'JarvanIV', image: 'public/images/champions/JarvanIV.png'},
        {name: 'Jax', image: 'public/images/champions/Jax.png'},
        {name: 'Jayce', image: 'public/images/champions/Jayce.png'},
        {name: 'Jhin', image: 'public/images/champions/Jhin.png'},
        {name: 'Jinx', image: 'public/images/champions/Jinx.png'},
        {name: 'Ksante', image: 'public/images/champions/Ksante.png'},
        {name: 'KaiSa', image: 'public/images/champions/KaiSa.png'},
        {name: 'Kalista', image: 'public/images/champions/Kalista.png'},
        {name: 'Karma', image: 'public/images/champions/Karma.png'},
        {name: 'Karthus', image: 'public/images/champions/Karthus.png'},
        {name: 'Kassadin', image: 'public/images/champions/Kassadin.png'},
        {name: 'Katarina', image: 'public/images/champions/Katarina.png'},
        {name: 'Kayle', image: 'public/images/champions/Kayle.png'},
        {name: 'Kayn', image: 'public/images/champions/Kayn.png'},
        {name: 'Kennen', image: 'public/images/champions/Kennen.png'},
        {name: 'KhaZix', image: 'public/images/champions/Khazix.png'},
        {name: 'Kindred', image: 'public/images/champions/Kindred.png'},
        {name: 'Kled', image: 'public/images/champions/Kled.png'},
        {name: 'KogMaw', image: 'public/images/champions/KogMaw.png'},
        {name: 'Ksante', image: 'public/images/champions/Ksante.png'},
        {name: 'LeBlanc', image: 'public/images/champions/LeBlanc.png'},
        {name: 'Lee Sin', image: 'public/images/champions/LeeSin.png'},
        {name: 'Leona', image: 'public/images/champions/Leona.png'},
        {name: 'Lillia', image: 'public/images/champions/Lillia.png'},
        {name: 'Lissandra', image: 'public/images/champions/Lissandra.png'},
        {name: 'Lucian', image: 'public/images/champions/Lucian.png'},
        {name: 'Lulu', image: 'public/images/champions/Lulu.png'},
        {name: 'Lux', image: 'public/images/champions/Lux.png'},
        {name: 'Malphite', image: 'public/images/champions/Malphite.png'},
        {name: 'Malzahar', image: 'public/images/champions/Malzahar.png'},
        {name: 'Maokai', image: 'public/images/champions/Maokai.png'},
        {name: 'MasterYi', image: 'public/images/champions/MasterYi.png'},
        {name: 'Mel', image: 'public/images/champions/Mel.png'},
        {name: 'Milio', image: 'public/images/champions/Milio.png'},
        {name: 'Miss Fortune', image: 'public/images/champions/MissFortune.png'},
        {name: 'Mordekaiser', image: 'public/images/champions/Mordekaiser.png'},
        {name: 'Morgana', image: 'public/images/champions/Morgana.png'},
        {name: 'Naafiri', image: 'public/images/champions/Naafiri.png'},
        {name: 'Nami', image: 'public/images/champions/Nami.png'},
        {name: 'Nasus', image: 'public/images/champions/Nasus.png'},
        {name: 'Nautilus', image: 'public/images/champions/Nautilus.png'},
        {name: 'Neeko', image: 'public/images/champions/Neeko.png'},
        {name: 'Nidalee', image: 'public/images/champions/Nidalee.png'},
        {name: 'Nilah', image: 'public/images/champions/Nilah.png'},
        {name: 'Nocturne', image: 'public/images/champions/Nocturne.png'},
        {name: 'Nunu', image: 'public/images/champions/Nunu.png'},
        {name: 'Olaf', image: 'public/images/champions/Olaf.png'},
        {name: 'Orianna', image: 'public/images/champions/Orianna.png'},
        {name: 'Ornn', image: 'public/images/champions/Ornn.png'},
        {name: 'Pantheon', image: 'public/images/champions/Pantheon.png'},
        {name: 'Poppy', image: 'public/images/champions/Poppy.png'},
        {name: 'Pyke', image: 'public/images/champions/Pyke.png'},
        {name: 'Qiyana', image: 'public/images/champions/Qiyana.png'},
        {name: 'Quinn', image: 'public/images/champions/Quinn.png'},
        {name: 'Rakan', image: 'public/images/champions/Rakan.png'},
        {name: 'Rammus', image: 'public/images/champions/Rammus.png'},
        {name: 'RekSai', image: 'public/images/champions/RekSai.png'},
        {name: 'Rell', image: 'public/images/champions/Rell.png'},
        {name: 'Renata Glasc', image: 'public/images/champions/Renata.png'},
        {name: 'Renekton', image: 'public/images/champions/Renekton.png'},
        {name: 'Rengar', image: 'public/images/champions/Rengar.png'},
        {name: 'Riven', image: 'public/images/champions/Riven.png'},
        {name: 'Rumble', image: 'public/images/champions/Rumble.png'},
        {name: 'Ryze', image: 'public/images/champions/Ryze.png'},
        {name: 'Samira', image: 'public/images/champions/Samira.png'},
        {name: 'Sejuani', image: 'public/images/champions/Sejuani.png'},
        {name: 'Senna', image: 'public/images/champions/Senna.png'},
        {name: 'Seraphine', image: 'public/images/champions/Seraphine.png'},
        {name: 'Sett', image: 'public/images/champions/Sett.png'},
        {name: 'Shaco', image: 'public/images/champions/Shaco.png'},
        {name: 'Shen', image: 'public/images/champions/Shen.png'},
        {name: 'Shyvana', image: 'public/images/champions/Shyvana.png'},
        {name: 'Singed', image: 'public/images/champions/Singed.png'},
        {name: 'Sion', image: 'public/images/champions/Sion.png'},
        {name: 'Sivir', image: 'public/images/champions/Sivir.png'},
        {name: 'Skarner', image: 'public/images/champions/Skarner.png'},
        {name: 'Smolder', image: 'public/images/champions/Smolder.png'},
        {name: 'Sona', image: 'public/images/champions/Sona.png'},
        {name: 'Soraka', image: 'public/images/champions/Soraka.png'},
        {name: 'Swain', image: 'public/images/champions/Swain.png'},
        {name: 'Sylas', image: 'public/images/champions/Sylas.png'},
        {name: 'Syndra', image: 'public/images/champions/Syndra.png'},
        {name: 'Tahm Kench', image: 'public/images/champions/TahmKench.png'},
        {name: 'Taliyah', image: 'public/images/champions/Taliyah.png'},
        {name: 'Talon', image: 'public/images/champions/Talon.png'},
        {name: 'Taric', image: 'public/images/champions/Taric.png'},
        {name: 'Teemo', image: 'public/images/champions/Teemo.png'},
        {name: 'Thresh', image: 'public/images/champions/Thresh.png'},
        {name: 'Tristana', image: 'public/images/champions/Tristana.png'},
        {name: 'Trundle', image: 'public/images/champions/Trundle.png'},
        {name: 'Tryndamere', image: 'public/images/champions/Tryndamere.png'},
        {name: 'Twisted Fate', image: 'public/images/champions/TwistedFate.png'},
        {name: 'Twitch', image: 'public/images/champions/Twitch.png'},
        {name: 'Udyr', image: 'public/images/champions/Udyr.png'},
        {name: 'Urgot', image: 'public/images/champions/Urgot.png'},
        {name: 'Varus', image: 'public/images/champions/Varus.png'},
        {name: 'Vayne', image: 'public/images/champions/Vayne.png'},
        {name: 'Veigar', image: 'public/images/champions/Veigar.png'},
        {name: 'VelKoz', image: 'public/images/champions/VelKoz.png'},
        {name: 'Vex', image: 'public/images/champions/Vex.png'},
        {name: 'Vi', image: 'public/images/champions/Vi.png'},
        {name: 'Viego', image: 'public/images/champions/Viego.png'},
        {name: 'Viktor', image: 'public/images/champions/Viktor.png'},
        {name: 'Vladimir', image: 'public/images/champions/Vladimir.png'},
        {name: 'Volibear', image: 'public/images/champions/Volibear.png'},
        {name: 'Warwick', image: 'public/images/champions/Warwick.png'},
        {name: 'Wukong', image: 'public/images/champions/Wukong.png'},
        {name: 'Xayah', image: 'public/images/champions/Xayah.png'},
        {name: 'Xerath', image: 'public/images/champions/Xerath.png'},
        {name: 'Xin Zhao', image: 'public/images/champions/XinZhao.png'},
        {name: 'Yasuo', image: 'public/images/champions/Yasuo.png'},
        {name: 'Yone', image: 'public/images/champions/Yone.png'},
        {name: 'Yorick', image: 'public/images/champions/Yorick.png'},
        {name: 'Yuumi', image: 'public/images/champions/Yuumi.png'},
        {name: 'Zac', image: 'public/images/champions/Zac.png'},
        {name: 'Zed', image: 'public/images/champions/Zed.png'},
        {name: 'Zeri', image: 'public/images/champions/Zeri.png'},
        {name: 'Ziggs', image: 'public/images/champions/Ziggs.png'},
        {name: 'Zilean', image: 'public/images/champions/Zilean.png'},
        {name: 'Zoe', image: 'public/images/champions/Zoe.png'},
        {name: 'Zyra', image: 'public/images/champions/Zyra.png'},
    ]

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

    LoLChampions.forEach(champ => {
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