// CONST
const buyButtons = document.querySelectorAll(".buy-button");
const placeholderMessage = document.getElementById('placeholder-message');

// FUNCTIONS
function buyItem(itemId, userId) {
    console.log(`Buying item ID: ${itemId}, userId: ${userId}`);

    const dataToSend = {
        itemId,
        userId,
    };

    const jsonData = JSON.stringify(dataToSend);
    
    fetch('index.php?action=buyItem', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: "param=" + encodeURIComponent(jsonData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        placeholderMessage.innerHTML = '';
        console.log('Success:', data);
        if (data.success) {
            placeholderMessage.innerHTML = data.message;
        } else {
            placeholderMessage.innerHTML = data.message;
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function buyRole(itemId, userId) {
    console.log(`Buying role ID: ${itemId}, userId: ${userId}`);
    
    const dataToSend = {
        itemId,
        userId,
    };

    const jsonData = JSON.stringify(dataToSend);
    
    fetch('index.php?action=buyRole', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: "param=" + encodeURIComponent(jsonData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        placeholderMessage.innerHTML = '';
        console.log('Success:', data);
        if (data.success) {
            placeholderMessage.innerHTML = data.message;
        } else {
            placeholderMessage.innerHTML = data.message;
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// EVENTS
document.addEventListener("DOMContentLoaded", function() {
    buyButtons.forEach(button => {
        button.addEventListener("click", function() {
            const itemId = this.getAttribute('data-item-id');
            const itemCategory = this.closest('.item-card').getAttribute('data-category');

            if (itemCategory === 'role') {
                buyRole(itemId, userIdHeader);
            } else {
                buyItem(itemId, userIdHeader);
            }
        });
    });
});
