document.addEventListener('DOMContentLoaded', function () {
    // DOM elements
    console.log('userId : ', userId)
    let imageUser = document.getElementById('image_users')
    let username = document.getElementById('user_page_username')
    let userAge = document.getElementById('age_user')
    let sUsername = document.getElementById('lolsUsername')
    let lolAccount = document.getElementById('lolsUsername')
    let gender = document.getElementById('swiping_gender')
    let server = document.getElementById('swiping_server')
    let kindOfGamer = document.getElementById('swiping_kindOfGamer')
    let shortBio = document.getElementById('shortBio')
    let receiverId = document.getElementById('receiverId')
    let lolMain1Pic = document.getElementById('lolMain1Pic')
    let lolMain2Pic = document.getElementById('lolMain2Pic')
    let lolMain3Pic = document.getElementById('lolMain3Pic')
    let lolRankP = document.getElementById('lolRankP')
    let lolRoleP = document.getElementById('lolRoleP')
    const btnSwipeYes = document.getElementById('swipe_yes')
    const btnSwipeNo = document.getElementById('swipe_no')
    const swipeArea = document.getElementById('swipe-area')
    const frameSwiping = document.querySelector('.frame-swiping')
    const championContainer = document.querySelector('.swiping_champions')
    let profileFrames = null
    const token = localStorage.getItem('masterTokenWebsite')
    const ErrorSpan = document.querySelector('.report-feedback')
    const badgeContainer = document.querySelector('.badge-container-swiping')
    const reportPicture = document.getElementById('image_users_modal')
    const reportUsername = document.getElementById('report-username')
    const reportDescription = document.getElementById('report-description')
    const submitReportButton = document.getElementById('submit-report')
    const picturesRow = document.querySelector('.pictures-row')
    const bonusPictureContainer = document.getElementById(
        'bonus-picture-container'
    )
    let hasBindedAccount = false
    const championNameFixes = {
        'Dr.Mundo': 'DrMundo',
        LeBlanc: 'Leblanc',
        KhaZix: 'Khazix',
        KaiSa: 'Kaisa',
        VelKoz: 'Velkoz',
        ChoGath: 'Chogath',
        RekSai: 'RekSai',
        Ksante: 'KSante',
    }

    document
        .getElementById('open-filter-modal-no-users')
        ?.addEventListener('click', function () {
            document.getElementById('filter-modal-no-users').style.display =
                'flex'
            const overlay = document.getElementById('overlay')
            overlay.style.display = 'block'
        })

    // Add event listener for the new modal's close button
    document
        .getElementById('close-modal-filter-no-users')
        ?.addEventListener('click', function () {
            document.getElementById('filter-modal-no-users').style.display =
                'none'
            const overlay = document.getElementById('overlay')
            overlay.style.display = 'none'
        })

    document
        .getElementById('open-filter-modal')
        .addEventListener('click', function () {
            document.getElementById('filter-modal').style.display = 'flex'
            const overlay = document.getElementById('overlay')
            overlay.style.display = 'block'
        })

    document
        .getElementById('close-modal-filter')
        .addEventListener('click', function () {
            document.getElementById('filter-modal').style.display = 'none'
            const overlay = document.getElementById('overlay')
            overlay.style.display = 'none'
        })

    document.querySelectorAll('.filter-btn').forEach((button) => {
        button.addEventListener('click', function () {
            const category = this.getAttribute('data-filter')
            const value = this.getAttribute('data-value')

            // Toggle the class on all matching buttons in both modals
            document
                .querySelectorAll(
                    `.filter-btn[data-filter="${category}"][data-value="${value}"]`
                )
                .forEach((btn) => btn.classList.toggle(`${category}-active`))

            // Get all active buttons across both modals, ensure no duplicates
            const activeButtons = document.querySelectorAll(
                `.filter-btn.${category}-active`
            )
            const selectedValues = Array.from(
                new Set(
                    Array.from(activeButtons).map((btn) =>
                        btn.getAttribute('data-value')
                    )
                )
            )

            // Store in localStorage
            localStorage.setItem(category, JSON.stringify(selectedValues))
        })
    })

    // Update for both modals
    document
        .querySelectorAll('#update-filter, #update-filter-no-users')
        .forEach((btn) => {
            btn.addEventListener('click', function () {
                this.closest('.modal').style.display = 'none'
                fetchMatchingUser(userId)
                const overlay = document.getElementById('overlay')
                overlay.style.display = 'none'
            })
        })

    restoreFilters()

    // Restore saved filters from localStorage
    function restoreFilters() {
        const modals = [
            document.getElementById('filter-modal'),
            document.getElementById('filter-modal-no-users'),
        ]

        modals.forEach((modal) => {
            if (!modal) return

            // Server filters
            const serverFilters =
                JSON.parse(localStorage.getItem('server')) || []
            serverFilters.forEach((value) => {
                const button = modal.querySelector(
                    `.filter-btn[data-filter='server'][data-value='${value}']`
                )
                if (button) button.classList.add('server-active')
            })

            // Gamemode filters
            const gamemodeFilters =
                JSON.parse(localStorage.getItem('gamemode')) || []
            gamemodeFilters.forEach((value) => {
                const button = modal.querySelector(
                    `.filter-btn[data-filter='gamemode'][data-value='${value}']`
                )
                if (button) button.classList.add('gamemode-active')
            })

            // Gender filter
            let genderFilters
            try {
                const stored = JSON.parse(localStorage.getItem('gender'))
                genderFilters = Array.isArray(stored) ? stored : []
            } catch (e) {
                genderFilters = []
            }
            genderFilters.forEach((value) => {
                const button = modal.querySelector(
                    `.filter-btn[data-filter='gender'][data-value='${value}']`
                )
                if (button) button.classList.add('gender-active')
            })
        })
    }

    function getOwnedItems(userId) {
        fetch('/getOwnedItems', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `userId=${encodeURIComponent(userId)}`,
        })
            .then((response) => {
                if (!response.ok) {
                    return response.text().then((text) => {
                        console.error('Fetch error:', response.status, text)
                        throw new Error(
                            `HTTP error! Status: ${response.status}`
                        )
                        z
                    })
                }
                return response.json()
            })
            .then((data) => {
                if (data.message === 'Success') {
                    const items = data.items
                    profileFrames = items.filter(
                        (item) =>
                            item.items_category === 'profile Picture' &&
                            item.userItems_isUsed === 1
                    )
                    console.log('Profile frames:', profileFrames)
                    if (profileFrames.length > 0) {
                        const frame = profileFrames[0]
                        frameSwiping.style.opacity = '1'
                        frameSwiping.src = `public/images/store/${frame.items_picture.replace(
                            '.jpg',
                            '.png'
                        )}`
                    } else {
                        frameSwiping.style.opacity = '0'
                    }
                } else {
                    console.log(data.message)
                }
            })
            .catch((error) => {
                console.error('Fetch error:', error)
            })
    }

    // Function to fetch matching user data
    function fetchMatchingUser(userId) {
        const server = localStorage.getItem('server') || ''
        const gender = localStorage.getItem('gender') || ''
        const gamemode = localStorage.getItem('gamemode') || ''
        fetch('/getUserMatching', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                Authorization: `Bearer ${token}`,
            },
            body: `userId=${encodeURIComponent(
                userId
            )}&isNotReactNative=1&server=${server}&gender=${gender}&gamemode=${gamemode}`,
        })
            .then((response) => {
                if (!response.ok) {
                    return response.text().then((text) => {
                        console.error('Fetch error:', response.status, text)
                        throw new Error(
                            `HTTP error! Status: ${response.status}`
                        )
                    })
                }
                return response.json()
            })
            .then((data) => {
                if (data.success && data.user) {
                    document.querySelector('.noUserToSee').style.display =
                        'none'
                    getOwnedItems(data.user.user_id)
                    fillData(data.user)
                } else {
                    showNoMoreProfiles()
                }
            })
            .catch((error) => {
                console.error('Fetch error:', error)
            })
    }

    // Function to fill the data
    async function fillData(data) {
        clearData()
        document.querySelector('.swiping-ctn').style.display = 'flex'
        // Fill the user image
        imageUser.src = data.user_picture
            ? `public/upload/${data.user_picture}`
            : 'public/images/defaultprofilepicture.jpg'

        if (data.lol_sUsername && data.lol_sUsername.trim()) {
            // Ensure it's not empty
            hasBindedAccount = true
            sUsername.innerText = data.lol_account

            const ratingContainer = document.getElementById('user-rating-stars')
            ratingContainer.innerHTML = ''

            if (data.user_rating !== undefined) {
                ratingContainer.innerHTML = generateStars(data.user_rating)
            }
        } else {
            sUsername.innerText = 'UNKNOWN'
            hasBindedAccount = false
        }

        const baseRank = hasBindedAccount
            ? data.lol_sRank.split(' ')[0].charAt(0).toUpperCase() +
              data.lol_sRank.split(' ')[0].slice(1).toLowerCase()
            : data.lol_rank || 'default'

        if (data.user_bonusPicture && data.user_bonusPicture !== '[]') {
            let pictures

            try {
                pictures = JSON.parse(data.user_bonusPicture)
            } catch (error) {
                console.error('Error parsing user_bonusPicture:', error)
                pictures = [] // Force it to be an empty array so it behaves like the else case
            }

            // Ensure it's an array and has valid pictures
            if (!Array.isArray(pictures) || pictures.length === 0) {
                bonusPictureContainer.style.display = 'none'
            } else {
                console.log('Bonus pictures:', pictures)
                bonusPictureContainer.style.display = 'flex'

                // Limit to 2 pictures only
                pictures.slice(0, 2).forEach((picture) => {
                    const picturePath = `public/upload/${picture}`

                    // Create the wrapper div
                    const pictureWrapper = document.createElement('div')
                    pictureWrapper.classList.add('picture-wrapper-swiping')

                    // Create the image element
                    const img = document.createElement('img')
                    img.src = picturePath
                    img.classList.add('user-picture-swiping')
                    img.alt = 'User Picture'

                    // Append image and button to wrapper
                    pictureWrapper.appendChild(img)

                    // Append wrapper to the pictures row
                    picturesRow.appendChild(pictureWrapper)
                })
            }
        } else {
            bonusPictureContainer.style.display = 'none'
        }

        reportPicture.src = `${
            data.user_picture
                ? `public/upload/${data.user_picture}`
                : 'public/images/defaultprofilepicture.jpg'
        }`
        reportPicture.alt = data.user_username
        reportUsername.innerText = data.user_username

        // Fill other user data
        btnSwipeYes.disabled = false
        btnSwipeNo.disabled = false
        username.innerText = data.user_username
        userAge.innerText = data.user_age

        let genderOption = ''

        switch (data.user_gender) {
            case 'Male':
                genderOption = `<i class="fa-solid fa-mars"></i> Male`
                break
            case 'Female':
                genderOption = `<i class="fa-solid fa-venus"></i> Female`
                break
            case 'Non Binary':
                genderOption = `<i class="fa-solid fa-genderless"></i> Non binary`
                break
            case 'Trans Male':
                genderOption = `<i class="fa-solid fa-transgender"></i> Trans  (FtM)`
                break
            case 'Trans Female':
                genderOption = `<i class="fa-solid fa-transgender"></i> Trans  (MtF)`
                break
            case 'Trans':
                genderOption = `<i class="fa-solid fa-transgender"></i> Trans`
                break
            default:
                genderOption = `<i class="fa-solid fa-genderless"></i> Unknown`
                break
        }

        gender.innerHTML = genderOption

        const newWrapper = document.createElement('div')
        newWrapper.id = 'swiping_kindOfGamer'
        newWrapper.style.display = 'flex'
        newWrapper.style.gap = '10px'
        newWrapper.style.flexWrap = 'wrap'

        // Generate new spans
        switch (data.user_kindOfGamer) {
            case 'Chill':
                if (data.user_game !== 'Valorant') {
                    newWrapper.innerHTML = `
                        <span class="swiping_filters_others swiping_filters_row"><img src="public/images/league-icon.png" alt="League of legends game mode"> Aram</span>
                        <span class="swiping_filters_others swiping_filters_row"><img src="public/images/league-icon.png" alt="League of legends game mode"> Normal Draft</span>
                    `
                } else {
                    newWrapper.innerHTML = `
                        <span class="swiping_filters_others swiping_filters_row"><img src="public/images/valorant-icon.png" alt="Valorant game mode"> Normal Draft</span>
                    `
                }
                break
            case 'Competition':
                if (data.user_game !== 'Valorant') {
                    newWrapper.innerHTML = `
                    <span class="swiping_filters_others swiping_filters_row"><img src="public/images/league-icon.png" alt="League of legends game mode"> Ranked</span>
                `
                } else {
                    newWrapper.innerHTML = `
                    <span class="swiping_filters_others swiping_filters_row"><img src="public/images/valorant-icon.png" alt="Valorant game mode"> Ranked</span>
                `
                }
                break
            default:
                if (data.user_game !== 'Valorant') {
                    newWrapper.innerHTML = `
                        <span class="swiping_filters_others swiping_filters_row"><img src="public/images/league-icon.png" alt="League of legends game mode"> Aram</span>
                        <span class="swiping_filters_others swiping_filters_row"><img src="public/images/league-icon.png" alt="League of legends game mode"> Normal Draft</span>
                        <span class="swiping_filters_others swiping_filters_row"><img src="public/images/league-icon.png" alt="League of legends game mode"> Ranked</span>
                    `
                } else {
                    newWrapper.innerHTML = `
                        <span class="swiping_filters_others swiping_filters_row"><img src="public/images/valorant-icon.png" alt="Valorant game mode"> Normal Draft</span>
                        <span class="swiping_filters_others swiping_filters_row"><img src="public/images/valorant-icon.png" alt="Valorant game mode"> Ranked</span>
                    `
                }
                break
        }

        if (data.user_game === 'Valorant') {
            // Remove the Aram button
            const aramButton = document.querySelector(
                'button[data-value="Aram"]'
            )
            if (aramButton) {
                aramButton.remove()
            }
        }

        const oldWrapper = document.getElementById('swiping_kindOfGamer')
        if (oldWrapper) {
            oldWrapper.replaceWith(newWrapper) // Replace existing wrapper
        } else {
            kindOfGamer.appendChild(newWrapper) // If first time, just append
        }

        shortBio.innerHTML =
            sanitizeHtlm(decodeHtmlEntities(data.user_shortBio)) ||
            'No description available'

        receiverId.value = data.user_id
        if (data.user_game === 'League of Legends' && data.lol_role) {
            lolRankP.innerText = hasBindedAccount
                ? data.lol_sRank
                : data.lol_rank || 'Unranked '
            lolRoleP.innerText = data.lol_role
            lolRoleP.innerText = data.lol_role || 'Unknown'
            server.innerText = data.lol_server
            if (data.lol_noChamp === 1) {
                championContainer.style.display = 'none'
            } else {
                lolMain1Pic.src = data.lol_main1
                    ? `https://ddragon.leagueoflegends.com/cdn/img/champion/loading/${sanitizeChampionName(
                          sanitize(data.lol_main1)
                      )}_0.jpg`
                    : ''
                lolMain1Pic.alt = data.lol_main1 || ''

                lolMain2Pic.src = data.lol_main2
                    ? `https://ddragon.leagueoflegends.com/cdn/img/champion/loading/${sanitizeChampionName(
                          sanitize(data.lol_main2)
                      )}_0.jpg`
                    : ''
                lolMain2Pic.alt = data.lol_main2 || ''

                lolMain3Pic.src = data.lol_main3
                    ? `https://ddragon.leagueoflegends.com/cdn/img/champion/loading/${sanitizeChampionName(
                          sanitize(data.lol_main3)
                      )}_0.jpg`
                    : ''
                lolMain3Pic.alt = data.lol_main3 || ''
                championContainer.style.display = 'flex'
            }
        } else if (data.user_game === 'Valorant' && data.valorant_role) {
            // lolAccount.innerText = data.valorant_account || "Unknown Account";
            lolRankP.innerText = data.valorant_rank
            lolRoleP.innerText = data.valorant_role
            server.innerText = data.valorant_server
            if (data.valorant_noChamp === 1) {
                championContainer.style.display = 'none'
            } else {
                lolMain1Pic.src = data.valorant_main1
                    ? `public/images/valorant_champions/${sanitize(
                          data.valorant_main1
                      )}_icon.webp`
                    : '' // Empty src if no main
                lolMain1Pic.alt = data.valorant_main1 || ''
                lolMain2Pic.src = data.valorant_main2
                    ? `public/images/valorant_champions/${sanitize(
                          data.valorant_main2
                      )}_icon.webp`
                    : '' // Empty src if no main
                lolMain2Pic.alt = data.valorant_main2 || ''
                lolMain3Pic.src = data.valorant_main3
                    ? `public/images/valorant_champions/${sanitize(
                          data.valorant_main3
                      )}_icon.webp`
                    : '' // Empty src if no main
                lolMain3Pic.alt = data.valorant_main3 || ''
                championContainer.style.display = 'flex'
            }
        }

        const hasPictures = bonusPictureContainer.style.display === 'flex'
        const hasChampions = championContainer.style.display === 'flex'
        const viewToggle = document.getElementById('view-toggle')
        const toggleButtons = document.querySelectorAll('.view-toggle-btn')

        // Reset toggle state
        viewToggle.style.display = 'none'
        toggleButtons.forEach((btn) => btn.classList.remove('active'))

        if (hasPictures && hasChampions) {
            viewToggle.style.display = 'flex'
            // Set initial view to pictures
            bonusPictureContainer.style.display = 'none'
            championContainer.style.display = 'flex'
            toggleButtons[0].classList.add('active')
            // Add event listener
            viewToggle.removeEventListener('click', handleViewToggle)
            viewToggle.addEventListener('click', handleViewToggle)
        }

        if (data.user_game === 'League of Legends') {
            // Ensure the rank container has relative positioning
            lolRankP.style.position = 'relative'

            // Check if rankIcon already exists to avoid duplicates
            let existingIcon = lolRankP.querySelector('.rank-icon')
            if (!existingIcon) {
                const rankIcon = document.createElement('i')
                rankIcon.classList.add(
                    'fa-solid',
                    'rank-icon',
                    hasBindedAccount ? 'fa-check' : 'fa-xmark'
                )
                rankIcon.style.position = 'absolute'
                rankIcon.style.top = '-10px'
                rankIcon.style.right = '-10px'
                rankIcon.style.fontSize = '14px'
                rankIcon.style.color = hasBindedAccount ? 'green' : 'red'

                lolRankP.appendChild(rankIcon)
            } else {
                // Update the existing icon if already present
                existingIcon.className = `fa-solid rank-icon ${
                    hasBindedAccount ? 'fa-check' : 'fa-xmark'
                }`
                existingIcon.style.color = hasBindedAccount ? 'green' : 'red'
            }
        }

        if (data.user_isVip === 1) {
            addBadge(
                badgeContainer,
                'Premium Badge',
                '/public/images/premium-badge.png',
                'Premium',
                '#e84056'
            )
        }

        if (data.user_isPartner === 1) {
            addBadge(
                badgeContainer,
                'Partner Badge',
                '/public/images/partner-badge.png',
                'Partner',
                '#c89b3e'
            )
        }

        if (data.user_isCertified === 1) {
            addBadge(
                badgeContainer,
                'Certified Badge',
                '/public/images/certified-badge.png',
                'Certified',
                '#6BBEEB'
            )
        }
    }

    function handleViewToggle(event) {
        const button = event.target.closest('.view-toggle-btn')
        if (!button) return

        const view = button.dataset.view
        const isPictures = view === 'pictures'
        const toggleButtons = document.querySelectorAll('.view-toggle-btn')
        const bonusPictureContainer = document.getElementById(
            'bonus-picture-container'
        )
        const championContainer = document.querySelector('.swiping_champions')

        toggleButtons.forEach((btn) => btn.classList.remove('active'))
        button.classList.add('active')

        bonusPictureContainer.style.display = isPictures ? 'flex' : 'none'
        championContainer.style.display = isPictures ? 'none' : 'flex'
    }

    function addBadge(container, title, imgSrc, text, color) {
        const spanBadge = document.createElement('span')
        spanBadge.classList.add('badge')
        spanBadge.title = title
        spanBadge.style.border = `1px solid ${color}`
        spanBadge.style.color = color
        spanBadge.style.padding = '4px 8px'
        spanBadge.style.borderRadius = '4px'
        spanBadge.style.display = 'flex'
        spanBadge.style.alignItems = 'center'
        spanBadge.style.gap = '5px'

        const badgeIcon = document.createElement('img')
        badgeIcon.src = imgSrc
        badgeIcon.alt = title
        badgeIcon.style.width = '16px'
        badgeIcon.style.height = '16px'

        const badgeText = document.createElement('span')
        badgeText.textContent = text
        badgeText.style.color = color

        spanBadge.appendChild(badgeIcon)
        spanBadge.appendChild(badgeText)
        container.appendChild(spanBadge)
    }

    function clearData() {
        // Hide elements that might be shown based on conditions
        document.querySelector('.swiping_champions').style.display = 'none'

        // Clear image sources and alt text
        imageUser.src = 'public/images/defaultprofilepicture.jpg'
        imageUser.alt = 'Default profile picture'
        frameSwiping.src = ''
        badgeContainer.innerHTML = ''
        frameSwiping.style.opacity = '0'
        ErrorSpan.innerText = ''
        reportPicture.src = ''
        reportPicture.alt = ''
        reportUsername.innerText = ''
        reportDescription.value = ''
        submitReportButton.disabled = false
        picturesRow.innerHTML = ''
        hasBindedAccount = false
        championContainer.style.display = 'flex'

        // Clear text content
        sUsername.innerText = ''
        username.innerText = ''
        userAge.innerText = ''
        lolAccount.innerText = ''
        gender.innerHTML = ''
        server.innerHTML = ''
        kindOfGamer.innerHTML = ' '
        shortBio.innerHTML = ''
        receiverId.value = ''

        // Clear the League of Legends data
        lolMain1Pic.src = ''
        lolMain1Pic.alt = ''
        lolMain2Pic.src = ''
        lolMain2Pic.alt = ''
        lolMain3Pic.src = ''
        lolMain3Pic.alt = ''

        const viewToggle = document.getElementById('view-toggle')
        if (viewToggle) {
            viewToggle.style.display = 'none'
        }
        document.querySelectorAll('.view-toggle-btn').forEach((btn) => {
            btn.classList.remove('active')
        })
        bonusPictureContainer.style.display = 'none'
        championContainer.style.display = 'none'
    }

    // Function to show the no more profiles message
    function showNoMoreProfiles() {
        document.querySelector('.swiping-ctn').style.display = 'none'
        document.querySelector('.noUserToSee').style.display = 'flex'
    }

    function sanitizeChampionName(championName) {
        return championNameFixes[championName] || championName // Return the fixed name if exists, else return the original
    }

    // Sanitize function
    function sanitize(input) {
        return input.trim().replace(/\s+/g, '')
    }

    function sanitizeHtlm(input) {
        const element = document.createElement('div')
        element.innerText = input
        return element.innerHTML
    }

    function decodeHtmlEntities(encodedString) {
        const element = document.createElement('div')
        element.innerHTML = encodedString
        return element.innerText
    }

    // Function swipping
    function handleSwipeGesture() {
        if (Math.abs(touchendX - touchstartX) > threshold) {
            if (touchendX < touchstartX) {
                swipeYes(userId, receiverId.value)
            } else if (touchendX > touchstartX) {
                swipeNo(userId, receiverId.value)
            }
        }
    }

    // Swipe functions
    function swipeYes(userId, receiverId) {
        fetch('index.php?action=swipeDoneWebsite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                Authorization: `Bearer ${token}`,
            },
            body: `swipe_yes=1&senderId=${encodeURIComponent(
                userId
            )}&receiverId=${encodeURIComponent(receiverId)}`,
        })
            .then((response) => {
                if (!response.ok) {
                    return response.text().then((text) => {
                        console.error('Fetch error:', response.status, text)
                        throw new Error(
                            `HTTP error! Status: ${response.status}`
                        )
                    })
                }
                return response.json()
            })
            .then((data) => {
                console.log('Swipe yes response data:', data)
                if (data.success) {
                    buttonSuccess()
                    setTimeout(() => {
                        fetchMatchingUser(userId)
                    }, 1000)
                }
            })
            .catch((error) => {
                console.error('Fetch error:', error)
            })
    }

    function swipeNo(userId, receiverId) {
        fetch('index.php?action=swipeDoneWebsite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                Authorization: `Bearer ${token}`,
            },
            body: `swipe_no=1&senderId=${encodeURIComponent(
                userId
            )}&receiverId=${encodeURIComponent(receiverId)}`,
        })
            .then((response) => {
                if (!response.ok) {
                    return response.text().then((text) => {
                        console.error('Fetch error:', response.status, text)
                        throw new Error(
                            `HTTP error! Status: ${response.status}`
                        )
                    })
                }
                return response.json()
            })
            .then((data) => {
                console.log('Swipe no response data:', data)
                if (data.success) {
                    buttonFailure()
                    setTimeout(() => {
                        fetchMatchingUser(userId)
                    }, 1000)
                }
            })
            .catch((error) => {
                console.error('Fetch error:', error)
            })
    }

    function buttonSuccess() {
        // Create checkmark icon element
        const iElement = document.createElement('i')
        iElement.classList.add('fa-solid', 'fa-heart', 'buttonSwipe')
        iElement.style.color = 'green'
        swipeArea.appendChild(iElement)

        // Remove the icon after 1 second
        setTimeout(() => {
            swipeArea.removeChild(iElement)
        }, 2000)
    }

    function buttonFailure() {
        // Create cross icon element
        const iElement = document.createElement('i')
        iElement.classList.add('fa-solid', 'fa-xmark', 'buttonSwipe')
        iElement.style.color = 'red'
        swipeArea.appendChild(iElement)

        // Remove the icon after 1 second
        setTimeout(() => {
            swipeArea.removeChild(iElement)
        }, 2000)
    }

    // Initial call to fetch matching user data
    fetchMatchingUser(userId)

    // Event listeners

    btnSwipeYes.addEventListener('click', (event) => {
        event.preventDefault()
        btnSwipeYes.disabled = true
        btnSwipeNo.disabled = true
        swipeYes(userId, receiverId.value)
    })

    btnSwipeNo.addEventListener('click', (event) => {
        event.preventDefault()
        btnSwipeNo.disabled = true
        btnSwipeYes.disabled = true
        swipeNo(userId, receiverId.value)
    })

    let touchstartX = 0
    let touchendX = 0
    const threshold = 100
    const screenWidth = window.innerWidth

    function handleSwipeGesture() {
        const swipeDistanceX = touchendX - touchstartX // Horizontal distance
        const swipeDistanceY = touchendY - touchstartY // Vertical distance
        const center = screenWidth / 2
        console.log('Handling swipe gesture')

        if (
            Math.abs(swipeDistanceX) > threshold &&
            Math.abs(swipeDistanceX) > Math.abs(swipeDistanceY)
        ) {
            // Swipe right detection
            if (touchstartX < center && touchendX > touchstartX) {
                swipeYes(userId, receiverId.value)
            }
            // Swipe left detection
            else if (touchstartX > center && touchendX < touchstartX) {
                swipeNo(userId, receiverId.value)
            }
        }
    }

    swipeArea.addEventListener(
        'touchstart',
        function (event) {
            touchstartX = event.changedTouches[0].screenX
            touchstartY = event.changedTouches[0].screenY // Track vertical start position
            console.log('Touch start:', touchstartX, touchstartY)
        },
        false
    )

    swipeArea.addEventListener(
        'touchend',
        function (event) {
            touchendX = event.changedTouches[0].screenX
            touchendY = event.changedTouches[0].screenY // Track vertical end position
            console.log('Touch end:', touchendX, touchendY)
            handleSwipeGesture()
        },
        false
    )
})

const fetchDdragonVersion = async () => {
    const response = await fetch(
        'https://ddragon.leagueoflegends.com/api/versions.json'
    )
    const versions = await response.json()
    return versions[0]
}

function generateStars(rating, maxStars = 5) {
    let starsHtml = ''
    const fullStars = Math.floor(rating)
    const emptyStars = maxStars - fullStars

    // Add full stars
    for (let i = 0; i < fullStars; i++) {
        starsHtml += '<span class="star full-star">&#9733;</span>' // filled star
    }
    // Add empty stars
    for (let i = 0; i < emptyStars; i++) {
        starsHtml += '<span class="star empty-star">&#9733;</span>' // empty star (grey)
    }

    return starsHtml
}
