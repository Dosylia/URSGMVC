const skipSelectionCheckbox = document.getElementById('skipSelection');
const championSelection = document.getElementById('championSelection');

document.addEventListener("DOMContentLoaded", function() {

    if (skipSelectionCheckbox.value == 1) {
        skipSelectionCheckbox.checked = true; 
        championSelection.style.display = 'none';
    } else {
        skipSelectionCheckbox.checked = false;
        championSelection.style.display = 'flex';
    }

    // Handle checkbox state change
    skipSelectionCheckbox.addEventListener('change', (e) => {
        console.log("Changing champion selection visibility");
        if (e.target.checked) {
            e.target.value = 1; 
            championSelection.style.display = 'none';
        } else {
            e.target.value = 0; 
            championSelection.style.display = 'flex';
        }
    });
});
