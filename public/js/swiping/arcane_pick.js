let userId = document.getElementById('userId').value;
let joinZaun = document.getElementById('join_zaun');
let joinPiltover = document.getElementById('join_piltover');
let ignoreArcane = document.getElementById('ignore_arcane');
let arcanePicker = document.getElementById('arcane_picker');

function joinSide(userId, side) {
    fetch('index.php?action=arcaneSide', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `pick=1&userId=${encodeURIComponent(userId)}&side=${encodeURIComponent(side)}`
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Fetch error:', response.status, text);
                throw new Error(`HTTP error! Status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // close pop up
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });
}

document.addEventListener("DOMContentLoaded", function() {

ignoreArcane.addEventListener('click', function() {
    joinSide(userId, 'none');
});

joinPiltover.addEventListener('click', function() {
    joinSide(userId, 'Piltover');
});

joinZaun.addEventListener('click', function() {
    joinSide(userId, 'Zaun');
    arcanePicker.style.display = 'none';
});

});