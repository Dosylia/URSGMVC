// CONST
const buyButtons = document.querySelectorAll(".buy-button");
const categoryFilter = document.getElementById('category-filter');
const itemCards = document.querySelectorAll('.item-card');
const token = localStorage.getItem('masterTokenWebsite');

// FUNCTIONS
function buyItem(itemId, userId) {
    console.log(`Buying item ID: ${itemId}, userId: ${userId}`);

    const dataToSend = {
        itemId,
        userId,
    };

    const jsonData = JSON.stringify(dataToSend);
    
    fetch('index.php?action=buyItemWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
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
        const placeholderMessage = document.getElementById(`placeholder-message-${itemId}`);
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
    
    fetch('index.php?action=buyRoleWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
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
        const placeholderMessage = document.getElementById(`placeholder-message-${itemId}`);
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

    categoryFilter.addEventListener('change', function() {
        const selectedCategory = categoryFilter.value;

        itemCards.forEach(function(itemCard) {
            const itemCategory = itemCard.getAttribute('data-category');


            if (selectedCategory === 'all' || itemCategory === selectedCategory) {
                itemCard.style.display = 'block';
            } else {
                itemCard.style.display = 'none';
            }
        });
    });
});
