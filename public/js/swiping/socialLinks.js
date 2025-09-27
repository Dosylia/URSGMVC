document.addEventListener('DOMContentLoaded', () => {
    const socialInputs = {} // To store updated social input values
    const socialsContainer = document.getElementById('socials-container')
    const validateButton = document.createElement('button')

    validateButton.textContent = 'Validate Actions'
    validateButton.id = 'validate-actions'
    validateButton.style.display = 'none' // Initially hidden
    validateButton.style.marginTop = '20px'
    socialsContainer.appendChild(validateButton)

    // Capture the edit button clicks and turn usernames into input fields
    document.querySelectorAll('.fa-pen-to-square').forEach((editIcon) => {
        editIcon.addEventListener('click', (event) => {
            const container = event.target.closest('.social-container-column')
            const link = container.querySelector('a')
            const username = container.querySelector('p')

            if (!username || !link) return

            const platform = link.href.split('/')[2].split('.')[0]

            // Create input field with current value
            const input = document.createElement('input')
            input.type = 'text'
            input.value = username.innerText
            input.classList.add('edit-social-input')

            // Store the input reference in socialInputs
            socialInputs[platform] = input

            // Replace username with input field
            username.replaceWith(input)
            input.focus()
            showValidateButton()
        })
    })

    // Capture the link icon clicks and turn into input for unbinded accounts
    document.addEventListener('click', (event) => {
        if (event.target.classList.contains('fa-link')) {
            const container = event.target.closest('.social-container-column')
            const username = container.querySelector('p')

            if (!username) return

            const platform = container.dataset.platform // Assuming each platform has a data-platform attribute

            if (platform === 'discord') {
                window.open(
                    'https://discord.com/oauth2/authorize?client_id=1354386306746159235&response_type=code&redirect_uri=https%3A%2F%2Fur-sg.com%2FdiscordBind&scope=identify+guilds+email+connections',
                    '_blank'
                )
            }

            // Create input field and replace the username with the input
            const input = document.createElement('input')
            input.type = 'text'
            input.classList.add('edit-social-input')

            // Store the input reference in socialInputs
            socialInputs[platform] = input

            // Replace the username with the input field
            username.replaceWith(input)
            input.focus()

            // Show the validate button
            showValidateButton()
        }
    })

    // Capture the unlink icon clicks to remove social link values
    document.querySelectorAll('.fa-link-slash').forEach((unlinkIcon) => {
        unlinkIcon.addEventListener('click', (event) => {
            const container = event.target.closest('.social-container-column')
            const username = container.querySelector('p')

            // Remove unlink icon
            const unlinkIconElement = container.querySelector('.fa-link-slash')
            if (unlinkIconElement) unlinkIconElement.remove()

            // Remove edit icon
            const editIconElement = container.querySelector('.fa-edit')
            if (editIconElement) editIconElement.remove()

            const openIcon = container.querySelector('.fa-up-right-from-square')
            if (openIcon) openIcon.remove()

            const editIcon = container.querySelector('.fa-pen-to-square')
            if (editIcon) editIcon.remove()

            if (!username) return

            // Set username text to "UNKNOWN"
            username.innerText = 'UNKNOWN'
            username.classList.add('shadow')
            username.style.fontSize = '1.5rem'

            // Get the platform name from the data attribute
            const platform = container.dataset.platform

            // Set the input value to empty in socialInputs
            socialInputs[platform] = ''
            const type = 'unlink'

            saveAllSocials(type)

            // Add the "link" icon back to allow relinking
            const linkIcon = document.createElement('i')
            linkIcon.classList.add('fa-solid', 'fa-link')
            container.appendChild(linkIcon)
        })
    })

    // Button to trigger the save action
    document
        .getElementById('validate-actions')
        .addEventListener('click', () => {
            saveAllSocials()
        })

    function showValidateButton() {
        validateButton.style.display = 'block'
    }

    // Function to save all social links at once
    function saveAllSocials(type) {
        // Function to check if a value is "UNKNOWN" and return an empty string
        function sanitizeValue(value) {
            return value === 'UNKNOWN' || value === '' ? '' : value
        }

        // Send the current data for all social platforms
        const socialData = {
            userId: userIdHeader, // Assuming userIdHeader is defined elsewhere
            discord: sanitizeValue(
                socialInputs.discord
                    ? socialInputs.discord.value
                    : getCurrentSocialData('discord')
            ),
            twitter: sanitizeValue(
                socialInputs.twitter
                    ? socialInputs.twitter.value
                    : getCurrentSocialData('twitter')
            ),
            instagram: sanitizeValue(
                socialInputs.instagram
                    ? socialInputs.instagram.value
                    : getCurrentSocialData('instagram')
            ),
            twitch: sanitizeValue(
                socialInputs.twitch
                    ? socialInputs.twitch.value
                    : getCurrentSocialData('twitch')
            ),
            bluesky: sanitizeValue(
                socialInputs.bluesky
                    ? socialInputs.bluesky.value
                    : getCurrentSocialData('bluesky')
            ),
        }

        const token = localStorage.getItem('masterTokenWebsite')

        console.log('Sending social data:', socialData)

        fetch('index.php?action=updateSocialsWebsite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                Authorization: `Bearer ${token}`,
            },
            body: 'param=' + encodeURIComponent(JSON.stringify(socialData)),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.message === 'Success') {
                    console.log('Updated successfully!', data)
                    if (type !== 'unlink') {
                        window.location.reload()
                    }
                } else {
                    console.error('Update failed:', data.message)
                }
            })
            .catch((error) => {
                console.error('Request failed:', error)
            })
    }

    // Helper function to get the current value for social media platforms
    function getCurrentSocialData(platform) {
        const socialElement = document.querySelector(
            `.social-container-column[data-platform="${platform}"] p`
        )
        return socialElement ? socialElement.innerText : '' // Default to empty string if no data
    }
})
