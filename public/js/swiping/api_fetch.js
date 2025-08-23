'use strict'

async function apiFetch({ url, method = 'GET', headers = {}, body = null }) {
    const token = localStorage.getItem('masterTokenWebsite')
    console.log('New Api fetch works!')
    try {
        const options = {
            method,
            headers: {
                ...headers,
                Authorization: token ? `Bearer ${token}` : undefined,
            },
        }

        if (body) {
            if (
                headers['Content-Type'] === 'application/x-www-form-urlencoded'
            ) {
                options.body = new URLSearchParams(body).toString()
            } else if (headers['Content-Type'] === 'application/json') {
                options.body = JSON.stringify(body)
            } else {
                options.body = body
            }
        }

        const response = await fetch(url, options)

        if (!response.ok) {
            const errorText = await response.text()
            console.error('Fetch error:', response.status, errorText)
            throw new Error(`HTTP error! Status: ${response.status}`)
        }

        return await response.json()
    } catch (error) {
        console.error('Fetch error:', error)
        throw error
    }
}

export default apiFetch

// Example how to use

// Add this to the JS script
// "use strict";
// import apiFetch from "./api_fetch.js";

// Add this to the Script call in the phtml file so it looks like that: 
// Add type="module"
// <script type="module" src="public/js/swiping/bonusPictures.js?<?= time(); ?>"></script>

// apiFetch({
//     url: '/TheFunnyLink',
//     method: 'POST/GET',
//     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
//     body: "Just use the old body",
// })
//     .then((data) => {
//         if (data.success) {
//         //    All went well so just do your thing with the data now
//         } else {
//             // Things went bad so somewhere but we got data. User Error potantially
//         }
//     })
//     .catch(() => {
//         // General error happened. Probably not user related and more on the dev side.
//     })
