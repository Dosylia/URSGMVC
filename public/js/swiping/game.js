let selectedUser = null

function shakeMinigameWindow() {
    const minigameWindow = document.querySelector('.minigame-inner')
    minigameWindow.classList.add('shake')
    setTimeout(() => {
        minigameWindow.classList.remove('shake')
    }, 500)
}

function getGameUser(userId, game, tryCount) {
    const token = localStorage.getItem('masterTokenWebsite')

    fetch('/getGameUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(userId)}&game=${encodeURIComponent(
            game
        )}&tryCount=${encodeURIComponent(tryCount)}`,
    })
        .then((response) => response.json())
        .then((data) => {
            const elements = getElements() // Assuming this retrieves UI element references
            const ignore = localStorage.getItem('ignoreGame')
            const ignorePermanently = localStorage.getItem(
                'ignoreGamePermanently'
            )

            if (ignore === '1' || ignorePermanently === '1') {
                elements.minigameWindow.style.display = 'none'
                return
            } else {
                const overlay = document.getElementById('overlay')
                overlay.style.display = 'block'
                elements.minigameWindow.classList.remove('hidden')
            }

            if (data.message === 'Success') {
                elements.minigameWindow.style.display = 'flex'

                switch (tryCount) {
                    case 1:
                        setCharacterImage(data.hints.game_main)
                        elements.affiliationHint.textContent =
                            data.hints.hint_affiliation
                        break
                    case 2:
                        setCharacterImage(data.hints.game_main)
                        elements.affiliationHint.textContent =
                            data.hints.hint_affiliation
                        elements.genderHint.textContent = data.hints.hint_gender
                        break
                    case 3:
                        setCharacterImage(data.hints.game_main)
                        elements.affiliationHint.textContent =
                            data.hints.hint_affiliation
                        elements.genderHint.textContent = data.hints.hint_gender
                        elements.guessHint.textContent = data.hints.hint_guess
                        break
                    default:
                        setCharacterImage(data.hints.game_main)
                        break
                }
            } else if (data.message === 'Already played') {
                const overlay = document.getElementById('overlay')
                overlay.style.display = 'none'
                elements.minigameWindow.style.display = 'none'
                console.log(data.message)
            } else {
                const overlay = document.getElementById('overlay')
                overlay.style.display = 'none'
                elements.minigameWindow.style.display = 'none'
                console.log(data.message)
            }
        })
        .catch((error) => {
            const overlay = document.getElementById('overlay')
            overlay.style.display = 'none'
            elements.minigameWindow.style.display = 'none'
            console.error('Fetch error:', error)
        })
}

function getHintsToShow(hints, tryCount) {
    const hintKeys = [
        'hint_affiliation',
        'hint_secondary',
        'hint_tertiary',
        'hint_final',
    ]
    return hintKeys
        .slice(0, tryCount + 1)
        .map((key) => hints[key])
        .filter(Boolean)
}

function updateHint(elements, hints) {
    if (hints[0])
        elements.affiliationHint.textContent = `Affiliation: ${hints[0]}`
    if (hints[1]) elements.genderHint.textContent = `Gender: ${hints[1]}`
    if (hints[2]) elements.guessHint.textContent = `Guess: ${hints[2]}`
}

async function setCharacterImage(championName) {
    try {
        const sanitizedChampionName = sanitizeChampionName(championName)
        const version = await fetchDdragonVersion()
        const ddragonBaseUrl = `https://ddragon.leagueoflegends.com/cdn/${version}/img/champion/`
        const characterImg = document.querySelector('.character-img')
        characterImg.src = `${ddragonBaseUrl}${sanitizedChampionName}.png`
        characterImg.alt = `${championName} Image`
    } catch (error) {
        console.error(
            'Failed to fetch the ddragon version or set the character image:',
            error
        )
    }
}

const sanitizeChampionName = (championName) =>
    championName.replace(/[^a-zA-Z0-9]/g, '')

const fetchDdragonVersion = async () => {
    const response = await fetch(
        'https://ddragon.leagueoflegends.com/api/versions.json'
    )
    const versions = await response.json()
    return versions[0]
}

function getElements() {
    const minigameWindow = document.getElementById('minigameWindow')
    const query = (selector) => minigameWindow.querySelector(selector)

    return {
        minigameWindow,
        exitButton: query('.exit-button'),
        submitButton: query('.submit-button'),
        nameInput: query('.name-input'),
        affiliationHint: query('.affiliation-hint'),
        genderHint: query('.gender-hint'),
        guessHint: query('.guess-hint'),
        characterImg: query('.character-img'),
        playerImg: query('.player-img'),
        hintContainer: query('.hints-container'),
        resultContainer: query('.result-container'),
        resultTitle: query('.result-title'),
        resultText: query('.result-text'),
        rulesBtn: query('.rules-button'),
        rulesText: query('.rules-text'),
    }
}

function toggleSubmitButton(elements) {
    const isDisabled = !elements.nameInput.value.trim()
    elements.submitButton.disabled = isDisabled
    elements.submitButton.style.backgroundColor = isDisabled
        ? '#8E8E8E'
        : '#AE7B32'
}

document.addEventListener('DOMContentLoaded', async () => {
    const elements = getElements()
    let userId = document.getElementById('userId').value
    let tryCount = parseInt(localStorage.getItem('tryCount'), 10) || 0
    const oldDate = localStorage.getItem('gameDate')
    const overlay = document.getElementById('overlay')
    const currentDate = new Date().toISOString().split('T')[0]
    if (oldDate != currentDate) {
        localStorage.setItem('gameWon', 0)
    }
    const storedDate = localStorage.getItem('gameDate')
    let restoreGame = document.getElementById('restore-game-container')
    let restoreGameBtn = document.getElementById('restore-game-container')
    const ignore = localStorage.getItem('ignoreGame')
    const ignorePermanentlyBtn = document.getElementById(
        'ignore-permanently-btn'
    )
    const ignorePermanently = localStorage.getItem('ignoreGamePermanently')

    ignorePermanentlyBtn?.addEventListener('click', () => {
        localStorage.setItem('ignoreGamePermanently', 1)
        overlay.style.display = 'none'
        elements.minigameWindow.style.display = 'none'
        restoreGame.style.display = 'block'
    })

    if (
        (ignore === '1' || ignorePermanently === '1') &&
        localStorage.getItem('gameWon') !== '1'
    ) {
        restoreGame.style.display = 'block'
    }

    restoreGameBtn?.addEventListener('click', () => {
        overlay.style.display = 'none'
        localStorage.setItem('ignoreGame', 0)
        localStorage.setItem('ignoreGamePermanently', 0)
        getGameUser(userId, 'League of Legends', tryCount)
        restoreGame.style.display = 'none'
    })

    if (storedDate !== currentDate) {
        localStorage.setItem('gameDate', currentDate)
        tryCount = 0
        localStorage.setItem('tryCount', tryCount)
        localStorage.setItem('ignoreGame', 0)
    }

    elements.rulesBtn.addEventListener('click', () => {
        if (elements.rulesText.style.display === 'none') {
            elements.rulesText.style.display = 'block'
        } else {
            elements.rulesText.style.display = 'none'
        }
    })

    elements.exitButton.addEventListener('click', () => {
        console.log('Exit button clicked')
        overlay.style.display = 'none'
        elements.minigameWindow.style.display = 'none'
        localStorage.setItem('ignoreGame', 1)
        const gameStatus = localStorage.getItem('gameWon')
        if (gameStatus != 1) {
            restoreGame.style.display = 'block'
        }
    })

    getGameUser(userId, 'League of Legends', tryCount)

    elements.nameInput.addEventListener('input', () =>
        toggleSubmitButton(elements)
    )
    elements.submitButton.addEventListener('click', () =>
        handleSubmit(elements)
    )
    elements.nameInput.addEventListener(
        'keydown',
        (event) => event.key === 'Enter' && handleSubmit(elements)
    )

    toggleSubmitButton(elements)

    const handleSubmit = (elements) => {
        const userGuess = elements.nameInput.value.trim()
        const token = localStorage.getItem('masterTokenWebsite')
        if (!userGuess) return

        tryCount++
        localStorage.setItem('tryCount', tryCount)
        const dataToSend = {
            userId: document.getElementById('userId').value,
            game: 'League of Legends',
            guess: userGuess,
            tryCount: tryCount,
        }

        const jsonData = JSON.stringify(dataToSend)

        fetch('/submitGuess', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                Authorization: `Bearer ${token}`,
            },
            body: 'param=' + encodeURIComponent(jsonData),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.message === 'Correct') {
                    displayNotification(
                        `Congratulations! You guessed the user correctly: ${data.gameUser.game_username}!`,
                        userId
                    )
                    // elements.minigameWindow.style.display = "none";
                    const inGameResultSpan =
                        document.getElementById('ingame-result')
                    inGameResultSpan.innerText = ''
                    elements.playerImg.src = `public/images/game/${data.gameUser.game_username}.jpg`

                    elements.hintContainer.innerHTML = ''
                    party.confetti(elements.hintContainer, {
                        count: party.variation.range(20, 40),
                    })
                    elements.resultContainer.style.display = 'flex'
                    localStorage.setItem('gameWon', 1)
                    elements.resultTitle.innerText = `Well done!`
                    elements.resultText.innerText = `The answer was ${data.gameUser.game_username}`
                } else if (data.message === 'Close') {
                    updateHint(elements, data.hint)
                    const inGameResultSpan =
                        document.getElementById('ingame-result')
                    inGameResultSpan.innerText = 'Close guess! Try again!'
                    shakeMinigameWindow()
                } else if (data.message === 'Game Over') {
                    displayNotification(
                        `Game Over! The correct user was ${data.gameUser.game_username}`,
                        userId
                    )
                    const inGameResultSpan =
                        document.getElementById('ingame-result')
                    inGameResultSpan.innerText = 'Wrong guess!'
                    elements.playerImg.src = `public/images/game/${data.gameUser.game_username}.jpg`
                    elements.hintContainer.innerHTML = ''
                    elements.resultContainer.style.display = 'flex'
                    elements.resultTitle.innerText = `Game Over!`
                    elements.resultText.innerText = `The answer was ${data.gameUser.game_username}`
                } else {
                    updateHint(elements, data.hint)
                    const inGameResultSpan =
                        document.getElementById('ingame-result')
                    inGameResultSpan.innerText = 'Wrong guess! Try again!'
                    shakeMinigameWindow()
                }
            })
            .catch((error) => {
                console.error('Error submitting guess:', error)
            })
    }

    const updateHint = (elements, hint) => {
        if (hint.affiliation)
            elements.affiliationHint.textContent = hint.affiliation
        if (hint.gender) elements.genderHint.textContent = hint.gender
        if (hint.guess) elements.guessHint.textContent = hint.guess
    }
})
