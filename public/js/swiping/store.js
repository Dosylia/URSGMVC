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

    fetch('/buyItemWebsite', {
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

    fetch('/buyCurrencyWebsite', {
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
            if (data.success && data.stripeUrl) {
                window.open(data.stripeUrl, '_blank')
            } else {
                placeholderMessage.innerHTML = data.message
            }
        })
        .catch((error) => {
            console.error('Error:', error)
        })
}

function buyAscend(itemId, userId) {
    const token = localStorage.getItem('masterTokenWebsite')
    console.log(`Buying gold Ascend ID: ${itemId}, userId: ${userId}`)

    const dataToSend = {
        itemId,
        userId,
    }

    const jsonData = JSON.stringify(dataToSend)

    fetch('/buyAscendWebsite', {
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
            if (data.success && data.stripeUrl) {
                window.open(data.stripeUrl, '_blank')
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

    fetch('/buyRoleWebsite', {
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
                const roleType = data.roleType || 'gold' // backend can send this if you want
                placeholderMessage.innerHTML = `
                    ${data.message}<br/>
                    <a href="https://discord.gg/Bfpkws74V3" target="_blank" class="claimGold">Join Discord before claiming</a>
                    <button onclick="claimDiscordRole('${roleType}')" class="claimGold">
                        Claim ${
                            roleType.charAt(0).toUpperCase() + roleType.slice(1)
                        } Role on Discord
                    </button>
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

    const button = document.querySelector(
        `.buy-button-owned.getRoleDiscord[data-item-id='${itemId}']`
    )

    if (!button) {
        console.error(`Button not found for item ID: ${itemId}`)
        return
    }

    const itemCard = button.closest('.item-card')
    if (!itemCard) {
        console.error('Item card not found')
        return
    }

    const itemName = itemCard.getAttribute('data-name').toLowerCase()

    placeholderMessage.innerHTML = `
        Already got role but not on discord?<br/>
        <a href="https://discord.gg/Bfpkws74V3" target="_blank" class="claimGold">Join Discord before claiming</a>
        <button onclick="claimDiscordRole('${itemName}')" class="claimGold">Claim ${
        itemName.charAt(0).toUpperCase() + itemName.slice(1)
    } Role on Discord</button>
    `
}

function claimDiscordRole(roleType) {
    let redirectUri = encodeURIComponent(
        'https://ur-sg.com/discordClaim?role=' + roleType
    )
    const clientId = '1354386306746159235'

    window.open(
        `https://discord.com/oauth2/authorize?client_id=${clientId}&response_type=code&redirect_uri=${redirectUri}&scope=identify`,
        '_blank'
    )
}

// EVENTS
document.addEventListener('DOMContentLoaded', function () {
    let kittyClicks = 0
    const kittyCard = document.getElementById('kitty-frame-card')

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

    document.querySelectorAll('.getRoleDiscord').forEach((button) => {
        button.addEventListener('click', function () {
            const itemId = this.getAttribute('data-item-id')
            getDiscordRole(itemId)
        })
    })

    buyButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const itemId = this.getAttribute('data-item-id')
            const itemCategory =
                this.closest('.item-card').getAttribute('data-category')
            const itemName = this.closest('.item-card')
                .getAttribute('data-name')
                .toLowerCase()

            if (itemCategory === 'role' && itemName.includes('ascend')) {
                buyAscend(itemId, userIdHeader)
            } else if (
                itemCategory === 'role' &&
                !itemName.includes('ascend')
            ) {
                buyRole(itemId, userIdHeader)
            } else if (itemCategory === 'currency') {
                buySoulHard(itemId, userIdHeader)
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
