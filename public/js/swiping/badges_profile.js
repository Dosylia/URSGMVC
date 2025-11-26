"use strict";
import apiFetch from "../Functions/api_fetch.js";

const badgesList = document.getElementById('badges-list')
const additionalContainer = document.getElementById(
    'additional-badges-container'
)

// Keep track of active badges
let activeBadges = [] // { id, items_picture, items_name }

// On page load, populate activeBadges from existing PHP-rendered badges (if any)
document.addEventListener('DOMContentLoaded', () => {
    const existing = additionalContainer.querySelectorAll(
        '.additional-badge img'
    )
    existing.forEach((img) => {
        activeBadges.push({
            id: img.dataset.id || '', // optional if you output data-id in PHP
            items_picture: img
                .getAttribute('src')
                .replace('public/images/store/', ''),
            items_name: img.getAttribute('title'),
        })
    })
})

// Listen for clicks on badge list
badgesList.addEventListener('click', function (e) {
    const btn = e.target
    const badgeItem = btn.closest('.badge-item')
    if (!badgeItem) return

    const badgeId = String(badgeItem.dataset.badgeId)
    const badgeName = badgeItem.dataset.badgeName
    const badgePicture = badgeItem.dataset.badgePicture

    // Use badge
    if (btn.classList.contains('use-badge-btn')) {
        if (activeBadges.length >= 3) return
        console.log("test")
        const jsonData = JSON.stringify({ badgeId, userId })

        apiFetch({
            url: '/useBadgeWebsite',
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'param=' + encodeURIComponent(jsonData),
        })
            .then((data) => {
                if (data.success) {
                    badgeItem.classList.add('active-badge')
                    btn.textContent = 'Remove Badge'
                    btn.classList.remove('use-badge-btn')
                    btn.classList.add('remove-badge-btn')

                    activeBadges.push({
                        id: badgeId,
                        items_picture: badgePicture,
                        items_name: badgeName,
                    })

                    refreshAdditionalBadges()
                    updateBadgeButtons()
                }
            })
            .catch((error) => {
                // General error happened. Probably not user related and more on the dev side.
                console.log("Error when adding badge: ", error)
            })
    }

    // Remove badge
    if (btn.classList.contains('remove-badge-btn')) {
        const jsonData = JSON.stringify({ badgeId, userId })

        apiFetch({
            url: '/removeBadgeWebsite',
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
            body: 'param=' + encodeURIComponent(jsonData),
        })
            .then((data) => {
                if (data.success) {
                    badgeItem.classList.remove('active-badge')
                    btn.textContent = 'Use Badge'
                    btn.classList.remove('remove-badge-btn')
                    btn.classList.add('use-badge-btn')

                    // Remove from local state (strict ID match)
                    activeBadges = activeBadges.filter((b) => b.id !== badgeId)

                    refreshAdditionalBadges()
                    updateBadgeButtons()
                }
            })
            .catch((error) => {
                // General error happened. Probably not user related and more on the dev side.
                console.log("Error when removing badge: ", error)
            })
    }
})

function refreshAdditionalBadges() {
    additionalContainer.innerHTML = ''

    if (activeBadges.length > 0) {
        activeBadges.slice(0, 3).forEach((badge) => {
            const div = document.createElement('div')
            div.classList.add('additional-badge')
            div.innerHTML = `
                <img src="public/images/store/${badge.items_picture}" 
                        alt="${badge.items_name}" 
                        title="${badge.items_name}">
            `
            additionalContainer.appendChild(div)
        })
    } else {
        additionalContainer.innerHTML = `<p>No badges</p>`
    }
}

function updateBadgeButtons() {
    const useButtons = badgesList.querySelectorAll('.use-badge-btn')
    if (activeBadges.length >= 3) {
        useButtons.forEach((btn) => {
            btn.disabled = true
            btn.title = 'Maximum 3 badges active'
        })
    } else {
        useButtons.forEach((btn) => {
            btn.disabled = false
            btn.title = ''
        })
    }
}
