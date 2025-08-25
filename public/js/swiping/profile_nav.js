document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('#user-bottom-nav button')
    const sections = document.querySelectorAll(
        '#aboutme-container, #pictures-container, #socials-container, #requests-container, #badges-container'
    )
    const loadingIndicator = document.getElementById('loading-indicator')
    let secretInput = ''

    // Initially hide all sections
    sections.forEach((section) => (section.style.display = 'none'))

    // Show loading indicator
    loadingIndicator.style.display = 'block'

    // Simulate content loading (replace with actual loading logic if needed)
    setTimeout(() => {
        loadingIndicator.style.display = 'none' // Hide loading indicator
        document.getElementById('aboutme-container').style.display = 'flex' // Show default section
    }, 1000) // Adjust timing if necessary

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            // Remove 'focused' class from all buttons
            buttons.forEach((btn) => btn.classList.remove('focused'))

            // Add 'focused' class to the clicked button
            button.classList.add('focused')

            // Hide all sections
            sections.forEach((section) => (section.style.display = 'none'))

            // Show the corresponding section
            const sectionId = button.id.replace('-btn', '-container')
            document.getElementById(sectionId).style.display = 'flex'
        })
    })

    document.addEventListener('keydown', (e) => {
        if (e.key.length === 1) {
            secretInput += e.key.toLowerCase()
            if (secretInput.length > 20) secretInput = secretInput.slice(-20)

            if (secretInput.includes('hat')) {
                const pictureContainer = document.getElementById(
                    'profile-picture-container'
                )

                // Prevent multiple hats/messages
                if (document.querySelector('.hat-easter-egg')) return

                // Create hat image
                const hat = document.createElement('img')
                hat.className = 'hat-easter-egg'
                hat.src = '/public/images/hat-egg.png'
                hat.alt = '🎩'

                // Create message
                const message = document.createElement('div')
                message.className = 'hat-message'
                message.textContent =
                    'You unlocked the URSG Hat! Easter egg for our partner HATTY! 🎉'

                pictureContainer.appendChild(hat)
                pictureContainer.parentNode.insertBefore(
                    message,
                    pictureContainer.nextSibling
                )

                console.log(
                    '%c🎩 You found the URSG Hat, easter egg for our partner HATTY!',
                    'color: magenta; font-size: 16px;'
                )
            }
        }
    })
})
