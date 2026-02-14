function sendMessageDiscord(
    userId,
    account,
    message,
    oldTime,
    voice,
    role,
    rank
) {
    const token = localStorage.getItem('masterTokenWebsite')

    const formData = new URLSearchParams()
    formData.append('userId', parseInt(userId))
    if (account) formData.append('account', account)
    if (message) formData.append('extraMessage', message)
    formData.append('playerfinder', true)
    formData.append('oldTime', oldTime)
    if (voice) formData.append('voiceChat', voice)
    if (role) formData.append('roleLookingFor', role)
    if (rank) formData.append('rankLookingFor', rank)

    return fetch('/sendMessageDiscord', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: formData.toString(),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                console.log('Message sent to Discord successfully!')
            } else {
                console.error('Message error:', data.message)
                throw new Error(data.message || 'Unknown error')
            }
        })
        .catch((error) => {
            console.error('Fetch error:', error)
            throw error
        })
}

function addPlayerFinderPost({ voice, role, rank, desc, account }) {
    const token = localStorage.getItem('masterTokenWebsite')
    const bodyData = {
        voiceChat: voice,
        roleLookingFor: role,
        rankLookingFor: rank,
        description: desc,
        userId: userId,
    }

    fetch('/addPlayerFinderPost', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(bodyData),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                console.log(data.oldTime)
                sendMessageDiscord(
                    userId,
                    account,
                    desc,
                    data.oldTime,
                    voice,
                    role,
                    rank
                )
                    .then(() => {
                        location.reload()
                    })
                    .catch((error) => {
                        console.error('Discord message failed:', error)
                        location.reload()
                    })
            } else {
                console.log('Error: ' + data.message)
            }
        })
        .catch((error) => {
            console.error('Request failed', error)
        })
}

function editPlayerPost(postId, desc, newRole, newRank) {
    fetch('/editPlayerPost', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(
            parseInt(userId)
        )}&postId=${encodeURIComponent(
            parseInt(postId)
        )}&description=${encodeURIComponent(desc)}&role=${encodeURIComponent(
            newRole
        )}&rank=${encodeURIComponent(newRank)}`,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const newParagraph = document.createElement('p')
                newParagraph.className = `desc-${postId}`
                newParagraph.innerHTML = desc.replace(/\n/g, '<br>')
                document
                    .getElementById('descriptionField')
                    ?.replaceWith(newParagraph)

                const updateBtn = document.getElementById('update-post')
                updateBtn.remove()
                const deleteDiv = document.querySelector('.delete')
                const button = document.createElement('button')
                button.type = 'button'
                button.className = 'submit-button'
                button.id = 'delete-post'
                button.innerText = 'Delete'
                button.dataset.postid = postId
                deleteDiv.appendChild(button)
                const editPlayerFinderBtn =
                    document.getElementById('edit-playerfinder')
                editPlayerFinderBtn.style.display = 'block'
                // Restore span display and update images
                const card = document.querySelector(
                    `.playerfinder-card[data-postid="${postId}"]`
                )
                const game = card.dataset.game
                const isLoL = game === 'League of Legends'

                const roleFolder = isLoL ? 'roles' : 'valorant_roles'
                const rankFolder = isLoL ? 'ranks' : 'valorant_ranks'
                const roleExt = isLoL ? 'png' : 'webp'

                const roleImg = card.querySelector(
                    '.looking-for span:nth-child(2) img'
                )
                const rankImg = card.querySelector(
                    '.looking-for span:nth-child(3) img'
                )

                const newRoleSanitized = newRole.replace(/\s+/g, '')
                const newRolePath = `public/images/${roleFolder}/${newRoleSanitized}.${roleExt}`
                const newRankPath = `public/images/${rankFolder}/${newRank}.png`

                roleImg.src = newRolePath
                roleImg.alt = newRole
                rankImg.src = newRankPath
                rankImg.alt = newRank

                card.dataset.roleNameUser = newRole
                card.dataset.rankUser = newRank

                const roleSelect = document.getElementById('editRole')
                const rankSelect = document.getElementById('editRank')
                roleSelect?.remove()
                rankSelect?.remove()

                const spans = card.querySelectorAll('.looking-for span')
                spans.forEach((span) => (span.style.display = 'inline-block'))

                const deletePostBtn = document.getElementById('delete-post')
                deletePostBtn?.addEventListener('click', () => {
                    const token = localStorage.getItem('masterTokenWebsite')
                    const postId = deletePostBtn.dataset.postid
                    deletePost(postId, token)
                })
            } else {
                console.error('Error updating:', data.message)
            }
        })
        .catch((error) => {
            console.error('Request failed', error)
        })
}

function deletePost(postId, token) {
    fetch('/deletePlayerFinderPost', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ postId, userId }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                console.log(data.message)
                location.reload()
            } else {
                displayErrors(data.message)
            }
        })
        .catch((error) => {
            console.error('Request failed', error)
            displayErrors('Error connecting to server.')
        })
}

function displayErrors(errorMessage) {
    // Ensure error message element exists
    let errorDiv = document.getElementById('random-player-error')
    if (!errorDiv) {
        errorDiv = document.createElement('div')
        errorDiv.id = 'error-message'
        errorDiv.style.color = 'white'
        errorDiv.style.textAlign = 'center'
        errorDiv.style.display = 'none'
        const pfSection =
            document.querySelector('.playerfinder') ||
            document.querySelector('.playerfinder-container')
        if (pfSection) {
            pfSection.insertBefore(errorDiv, pfSection.firstChild)
        } else {
            document.body.prepend(errorDiv)
        }
    }

    errorDiv.textContent = errorMessage || 'Error finding random player.'
    errorDiv.style.display = 'block'
}

function findRandomPlayer(prefs) {
    const token = localStorage.getItem('masterTokenWebsite')
    const dataToSend = {
        voiceChat: prefs.voice !== undefined ? prefs.voice : null,
        roleLookingFor: prefs.role || null,
        rankLookingFor: prefs.rank || null,
        description: prefs.description || null,
        userId: userId,
    }

    const jsonData = JSON.stringify(dataToSend)

    fetch('/getRandomPlayerFinder', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: 'param=' + encodeURIComponent(jsonData),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // If we do have a random player, open chat with them without being friend so need to redirect with chat Page but with a special param to open chat with that user directly without being friends
                const randomUserId = data.randomUserId
                const sessionId = data.sessionId
                window.location.href = `persoChat?random_user_id=${randomUserId}&session_id=${sessionId}`
            } else {
                // Show error message on the page
                displayErrors(data.message)
            }
        })
        .catch((error) => {
            displayErrors('Error connecting to server.')
            console.error('Request failed', error)
        })
}

function addFriendAndChat(friendId, userId) {
    const token = localStorage.getItem('masterTokenWebsite')
    fetch('/addFriendAndChat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Authorization: `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(
            parseInt(userId)
        )}&friendId=${encodeURIComponent(parseInt(friendId))}`,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                window.location.href = `persoChat&friend_id=${friendId}`
            } else {
                displayErrors(data.message)
            }
        })
        .catch((error) => {
            displayErrors('Error connecting to server.')
        })
}

const applyFilters = () => {
    const prefs = JSON.parse(localStorage.getItem('playerfinder_filters')) || {}

    document.querySelectorAll('.playerfinder-card').forEach((card) => {
        const game = card.dataset.game
        const role = card.dataset.roleUser
        const rank = card.dataset.rankUser
        const voice = card.dataset.voice === 'true'

        // Convert filter values to lowercase for comparison
        const filterRole = prefs.role?.toLowerCase().replace(/\s+/g, '') || ''
        const filterRank = prefs.rank?.toLowerCase().replace(/\s+/g, '') || ''

        const gameMatch = !prefs.game || game === prefs.game
        const roleMatch = !prefs.role || role === filterRole
        const rankMatch = !prefs.rank || rank.toLowerCase() === filterRank
        const voiceMatch = prefs.voice === undefined || prefs.voice === voice

        card.style.display =
            gameMatch && roleMatch && rankMatch && voiceMatch ? '' : 'none'
    })
}

document.addEventListener('DOMContentLoaded', function () {
    // Get elements
    const createPostBtn = document.getElementById('createPostBtn')
    const findRandomPlayerBtn = document.getElementById('findRandomPlayer')
    const closeModalBtn = document.getElementById('closeModalBtn')
    const submitPostBtn = document.getElementById('submitPostBtn')
    const savePreferencesBtn = document.getElementById('savePreferencesBtn')
    const playerfinderModal = document.getElementById('playerfinder-modal')

    const voiceChatInput = document.getElementById('voiceChat')
    const eloInput = document.getElementById('eloLookingFor')
    const rankInput = document.getElementById('rankLookingFor')
    const descInput = document.getElementById('description')

    const filterRole = document.getElementById('filterRole')
    const filterRank = document.getElementById('filterRank')
    const filterVoiceChat = document.getElementById('filterVoiceChat')
    const deletePostBtn = document.getElementById('delete-post')
    const playWithThemBtns = document.querySelectorAll('.playwith-btn')
    const chatButtons = document.querySelectorAll(
        '.interested-modal .add-and-chat-btn'
    )
    const offlineButtons = document.querySelectorAll('.offline-btn')
    const toggleBtn = document.getElementById('toggleFilter')
    const filterPanel = document.getElementById('filterPanel')
    const editPlayerFinderBtn = document.getElementById('edit-playerfinder')

    const filterElements = [
        document.getElementById('filterGame'),
        document.getElementById('filterRole'),
        document.getElementById('filterRank'),
        document.getElementById('filterVoiceChat'),
    ]

    filterElements.forEach((el) => {
        el.addEventListener('change', () => {
            const newPrefs = {
                game: document.getElementById('filterGame').value || null,
                role: document.getElementById('filterRole').value || null,
                rank: document.getElementById('filterRank').value || null,
                voice:
                    document.getElementById('filterVoiceChat').value !== ''
                        ? document.getElementById('filterVoiceChat').value ===
                          '1'
                        : undefined,
            }

            localStorage.setItem(
                'playerfinder_filters',
                JSON.stringify(newPrefs)
            )
            applyFilters()
        })
    })

    applyFilters()

    toggleBtn.addEventListener('click', () => {
        filterPanel.classList.toggle('active')
        filterPanel.classList.toggle('hidden-on-mobile')
    })

    editPlayerFinderBtn?.addEventListener('click', () => {
        const deletePostBtnEdit = document.getElementById('delete-post')
        postId = editPlayerFinderBtn.dataset.postid

        const card = document.querySelector(
            `.playerfinder-card[data-postid="${postId}"]`
        )
        const currentRole = card.dataset.roleName
        const currentRank = card.dataset.rank
        const voiceChatEnabled = card.dataset.voice === 'true'

        const descParagraph = document.querySelector('.desc-' + postId)
        const currentDesc = descParagraph.innerText.trim()

        const textarea = document.createElement('textarea')
        textarea.value = currentDesc
        textarea.maxLength = 130
        textarea.rows = 3
        textarea.required = true
        textarea.className = descParagraph.className
        textarea.id = 'descriptionField'
        descParagraph.replaceWith(textarea)

        deletePostBtnEdit.remove()
        editPlayerFinderBtn.style.display = 'none'

        const deleteDiv = document.querySelector('.delete')
        const updateBtn = document.createElement('button')
        updateBtn.type = 'button'
        updateBtn.className = 'submit-button'
        updateBtn.id = 'update-post'
        updateBtn.innerText = 'Update'
        deleteDiv.appendChild(updateBtn)

        const game = card.dataset.game
        const isLoL = game === 'League of Legends'

        const lolRoles = [
            'Any',
            'Top laner',
            'Jungle',
            'Mid laner',
            'AD Carry',
            'Support',
        ]
        const valRoles = [
            'Any',
            'Duelist',
            'Initiator',
            'Controller',
            'Sentinel',
        ]
        const lolRanks = [
            'Any',
            'Iron',
            'Bronze',
            'Silver',
            'Gold',
            'Platinum',
            'Emerald',
            'Diamond',
            'Master',
            'Grandmaster',
            'Challenger',
        ]
        const valRanks = [
            'Any',
            'Iron',
            'Bronze',
            'Silver',
            'Gold',
            'Platinum',
            'Diamond',
            'Ascendant',
            'Immortal',
            'Radiant',
        ]
        const roles = isLoL ? lolRoles : valRoles
        const ranks = isLoL ? lolRanks : valRanks

        const roleSelect = document.createElement('select')
        roleSelect.id = 'editRole'
        roleSelect.className = 'input-style'
        roles.forEach((role) => {
            const opt = document.createElement('option')
            opt.value = role
            opt.text = role
            roleSelect.appendChild(opt)
        })
        roleSelect.value = currentRole

        const rankSelect = document.createElement('select')
        rankSelect.id = 'editRank'
        rankSelect.className = 'input-style'
        ranks.forEach((rank) => {
            const opt = document.createElement('option')
            opt.value = rank
            opt.text = rank
            rankSelect.appendChild(opt)
        })
        rankSelect.value = currentRank

        // Append all
        const lookingForDiv = card.querySelector('.looking-for')
        const spans = lookingForDiv.querySelectorAll('span')
        spans.forEach((span) => (span.style.display = 'none'))
        lookingForDiv.appendChild(roleSelect)
        lookingForDiv.appendChild(rankSelect)

        updateBtn.style.backgroundColor = '#e84056'

        updateBtn.addEventListener('click', () => {
            const newDesc = descriptionField.value.trim()
            if (!newDesc) {
                document.getElementById('descError').classList.remove('hidden')
                return
            }

            const newRole = document.getElementById('editRole').value
            const newRank = document.getElementById('editRank').value

            editPlayerPost(postId, newDesc, newRole, newRank)
        })
    })

    findRandomPlayerBtn.addEventListener('click', () => {
        // Goes to a function, that user activated filters of the person, and pick a random matching person from the profiles on playerfinder. Open a chat with them.
        const prefs =
            JSON.parse(localStorage.getItem('playerfinder_filters')) || {}

        findRandomPlayer(prefs)
    })

    document
        .getElementById('filterGame')
        .addEventListener('change', function () {
            const game = this.value
            const roles = document.querySelectorAll('#filterRole option')
            const ranks = document.querySelectorAll('#filterRank option')
            roles.forEach((opt) => {
                if (opt.value === '') {
                    opt.style.display = ''
                } else if (!game) {
                    opt.style.display = ''
                } else if (
                    opt.dataset.game === 'lol' &&
                    game === 'League of Legends'
                ) {
                    opt.style.display = ''
                } else if (
                    opt.dataset.game === 'valorant' &&
                    game === 'Valorant'
                ) {
                    opt.style.display = ''
                } else {
                    opt.style.display = 'none'
                }
            })
            // Repeat similar logic for rank filter

            ranks.forEach((opt) => {
                if (opt.value === '') {
                    opt.style.display = ''
                } else if (!game) {
                    opt.style.display = ''
                } else if (
                    opt.dataset.game === 'lol' &&
                    game === 'League of Legends'
                ) {
                    opt.style.display = ''
                } else if (
                    opt.dataset.game === 'valorant' &&
                    game === 'Valorant'
                ) {
                    opt.style.display = ''
                } else {
                    opt.style.display = 'none'
                }
            })
        })

    if (offlineButtons.length > 0) {
        offlineButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const modal = document.getElementById('offlineModal')
                if (modal) {
                    modal.classList.remove('hidden')
                }
            })
        })
    }

    // Play with them button
    playWithThemBtns.forEach((button) => {
        button.addEventListener('click', () => {
            const token = localStorage.getItem('masterTokenWebsite')
            const postId = button.dataset.postid

            fetch('/playWithThem', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Authorization: `Bearer ${token}`,
                },
                body: JSON.stringify({ postId, userId }), // Make sure userId is defined
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        if (data.isFriend) {
                            const friendId = data.friendId
                            window.location.href = `persoChat&friend_id=${friendId}`
                        }
                        console.log(data.message)
                        displayNotification(data.message, userId)
                        const likedIcon = document.querySelector(
                            `.liked-post-${postId}`
                        )
                        if (likedIcon) {
                            likedIcon.style.display = 'block'
                        }
                    } else {
                        console.error('Error:', data.message)
                    }
                })
                .catch((error) => {
                    console.error('Request failed', error)
                })
        })
    })

    if (chatButtons.length > 0) {
        chatButtons.forEach((button) => {
            button.addEventListener('click', function () {
                const friendId = this.dataset.friendId
                const userId = this.dataset.userId
                if (friendId && userId) {
                    addFriendAndChat(friendId, userId)
                }
            })
        })
    }

    deletePostBtn?.addEventListener('click', () => {
        const token = localStorage.getItem('masterTokenWebsite')
        const postId = deletePostBtn.dataset.postid
        deletePost(postId, token)
    })

    document.querySelectorAll('.interested-btn').forEach((button) => {
        button.addEventListener('click', function () {
            const postId = this.id.split('-')[1]
            document
                .getElementById('interestedModal-' + postId)
                .classList.remove('hidden')
        })
    })

    document.querySelectorAll('.close-modal-btn').forEach((button) => {
        button.addEventListener('click', function () {
            const modalId = this.getAttribute('data-modal')
            document.getElementById(modalId).classList.add('hidden')
        })
    })

    // Show modal
    createPostBtn?.addEventListener('click', () => {
        console.log('Create Post button clicked')
        playerfinderModal?.classList.remove('hidden')
    })

    // Hide modal
    closeModalBtn?.addEventListener('click', () => {
        playerfinderModal?.classList.add('hidden')
    })

    // Submit post
    submitPostBtn?.addEventListener('click', () => {
        const account = document
            .getElementById('lookingfor-account')
            ?.value.trim()
        const postData = {
            voice: voiceChatInput?.checked,
            role: eloInput?.value,
            rank: rankInput?.value,
            desc: descInput?.value.trim(),
            account: account,
        }

        const descError = document.getElementById('descError')

        if (!postData.desc) {
            descError.textContent = 'Description cannot be empty.'
            descError?.classList.remove('hidden')
            return
        } else if (postData.desc.length > 130) {
            descError.textContent =
                'Description must be 130 characters or less.'
            descError?.classList.remove('hidden')
            return
        } else {
            descError?.classList.add('hidden')
        }

        console.log(postData) // Or call your backend function
        if (typeof addPlayerFinderPost === 'function') {
            addPlayerFinderPost(postData)
        }

        playerfinderModal?.classList.add('hidden')
    })

    // Save preferences
    savePreferencesBtn?.addEventListener('click', () => {
        const prefs = {
            game: filterGame.value === 'Any' ? '' : filterGame.value,
            role: filterRole.value === 'Any' ? '' : filterRole.value,
            rank: filterRank.value === 'Any' ? '' : filterRank.value,
            voice:
                filterVoiceChat.value !== ''
                    ? filterVoiceChat.value === '1'
                    : undefined,
        }
        localStorage.setItem('playerfinder_filters', JSON.stringify(prefs))
    })

    // Load preferences
    const savedPrefs = localStorage.getItem('playerfinder_filters')
    if (savedPrefs) {
        try {
            const prefs = JSON.parse(savedPrefs)
            const gameSelect = document.getElementById('filterGame')

            // Set all values
            if (prefs.game) gameSelect.value = prefs.game
            if (prefs.role) filterRole.value = prefs.role
            if (prefs.rank) filterRank.value = prefs.rank

            // Trigger option filtering
            gameSelect.dispatchEvent(new Event('change'))

            // Apply filters using saved prefs
            applyFilters()
        } catch (e) {
            /* ... */
        }
    }
})
