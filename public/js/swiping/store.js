// CONST
const buyButtons = document.querySelectorAll('.buy-button')
const categoryFilter = document.getElementById('category-filter')
const itemCards = document.querySelectorAll('.item-card')

// FUNCTIONS
function buyItem(itemId, userId) {
    const token = localStorage.getItem('masterTokenWebsite')
    console.log(`Buying item ID: ${itemId}, userId: ${userId}`)

    const dataToSend = {
        itemId,
        userId,
    }

    const jsonData = JSON.stringify(dataToSend)

    fetch('index.php?action=buyItemWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok')
            }
            return response.json()
        })
        .then((data) => {
            const placeholderMessage = document.getElementById(
                `placeholder-message-${itemId}`
            )
            placeholderMessage.innerHTML = ''
            console.log('Success:', data)
            if (data.success) {
                placeholderMessage.innerHTML = data.message
            } else {
                placeholderMessage.innerHTML = data.message
            }
        })
        .catch((error) => {
            console.error('Error:', error)
        })
}

function buySoulHard(itemId, userId) {
    const token = localStorage.getItem('masterTokenWebsite')
    console.log(`Buying soul hard ID: ${itemId}, userId: ${userId}`)

    const dataToSend = {
        itemId,
        userId,
    }

    const jsonData = JSON.stringify(dataToSend)

    fetch('index.php?action=buyCurrencyWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok')
            }
            return response.json()
        })
        .then((data) => {
            const placeholderMessage = document.getElementById(
                `placeholder-message-${itemId}`
            )
            placeholderMessage.innerHTML = ''
            console.log('Success:', data)
            if (data.success) {
                placeholderMessage.innerHTML = data.message
            } else {
                placeholderMessage.innerHTML = data.message
            }
        })
        .catch((error) => {
            console.error('Error:', error)
        })
}

function buyPremiumBoost(itemId, userId) {
    const token = localStorage.getItem('masterTokenWebsite')
    console.log(`Buying premium boost ID: ${itemId}, userId: ${userId}`)

    const dataToSend = {
        itemId,
        userId,
    }

    const jsonData = JSON.stringify(dataToSend)

    fetch('index.php?action=buyPremiumBoostWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok')
            }
            return response.json()
        })
        .then((data) => {
            const placeholderMessage = document.getElementById(
                `placeholder-message-${itemId}`
            )
            placeholderMessage.innerHTML = ''
            console.log('Success:', data)
            if (data.success) {
                placeholderMessage.innerHTML = data.message
            } else {
                placeholderMessage.innerHTML = data.message
            }
        })
        .catch((error) => {
            console.error('Error:', error)
        })
}

function buyRole(itemId, userId) {
    const token = localStorage.getItem('masterTokenWebsite')
    console.log(`Buying role ID: ${itemId}, userId: ${userId}`)

    const dataToSend = {
        itemId,
        userId,
    }

    const jsonData = JSON.stringify(dataToSend)

    fetch('index.php?action=buyRoleWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok')
            }
            return response.json()
        })
        .then((data) => {
            const placeholderMessage = document.getElementById(
                `placeholder-message-${itemId}`
            )
            placeholderMessage.innerHTML = ''
            console.log('Success:', data)
            if (data.success) {
                placeholderMessage.innerHTML = `
                ${data.message}<br/>
                <a href="https://discord.gg/Bfpkws74V3" target="_blank" class="claimPremium">Join Discord before claiming</a>
                <button onclick="claimDiscordRole()" class="claimPremium">Claim Premium Role on Discord</button>
            `
            } else {
                placeholderMessage.innerHTML = data.message
            }
        })
        .catch((error) => {
            console.error('Error:', error)
        })
}

function getDiscordRole(itemId) {
    console.log(`Getting Discord role for item ID: ${itemId}`)
    const placeholderMessage = document.getElementById(
        `placeholder-message-${itemId}`
    )
    placeholderMessage.innerHTML = `
                Already got role but not on discord?<br/>
                <a href="https://discord.gg/Bfpkws74V3" target="_blank" class="claimPremium">Join Discord before claiming</a>
                <button onclick="claimDiscordRole()" class="claimPremium">Claim Premium Role on Discord</button>
            `
}

function claimDiscordRole() {
    window.open(
        'https://discord.com/oauth2/authorize?client_id=1354386306746159235&response_type=code&redirect_uri=https%3A%2F%2Fur-sg.com%2FdiscordClaim&scope=identify',
        '_blank'
    )
}

// EVENTS
document.addEventListener('DOMContentLoaded', function () {
    let kittyClicks = 0
    const kittyCard = document.getElementById('kitty-frame-card')
    const discordRoleButton = document.getElementById('getRoleDiscord')

    if (kittyCard) {
        kittyCard.addEventListener('click', () => {
            kittyClicks++
            if (kittyClicks === 3) {
                document.getElementById('ahris-easter-egg').style.display =
                    'block'
                kittyClicks = 0 // reset for replay
            }
        })
    }

    discordRoleButton?.addEventListener('click', function () {
        const itemId = this.getAttribute('data-item-id')
        getDiscordRole(itemId)
    })

    buyButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const itemId = this.getAttribute('data-item-id')
            const itemCategory =
                this.closest('.item-card').getAttribute('data-category')

            if (itemCategory === 'role') {
                buyRole(itemId, userIdHeader)
            } else if (itemCategory === 'currency') {
                buySoulHard(itemId, userIdHeader)
            } else if (itemCategory === 'Boost') {
                buyPremiumBoost(itemId, userIdHeader)
            } else {
                buyItem(itemId, userIdHeader)
            }
        })
    })

    categoryFilter.addEventListener('change', function () {
        const selectedCategory = categoryFilter.value

        itemCards.forEach(function (itemCard) {
            const itemCategory = itemCard.getAttribute('data-category')

            if (
                selectedCategory === 'all' ||
                itemCategory === selectedCategory
            ) {
                itemCard.style.display = 'block'
            } else {
                itemCard.style.display = 'none'
            }
        })
    })
})
