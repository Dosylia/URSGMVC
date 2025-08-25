const token = localStorage.getItem('masterTokenWebsite')
const badgesList = document.getElementById('badges-list')

badgesList.addEventListener('click', function (e) {
    const btn = e.target
    const badgeItem = btn.closest('.badge-item')
    if (!badgeItem) return

    const badgeId = badgeItem.dataset.badgeId

    // Use Badge
    if (btn.classList.contains('use-badge-btn')) {
        const jsonData = JSON.stringify({ badgeId, userId })
        fetch('index.php?action=useBadgeWebsite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                Authorization: `Bearer ${token}`,
            },
            body: 'param=' + encodeURIComponent(jsonData),
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.success) {
                    badgeItem.classList.add('active-badge')
                    btn.textContent = 'Remove Badge'
                    btn.classList.remove('use-badge-btn')
                    btn.classList.add('remove-badge-btn')
                }
            })
    }

    // Remove Badge
    if (btn.classList.contains('remove-badge-btn')) {
        const jsonData = JSON.stringify({ badgeId, userId })
        fetch('index.php?action=removeBadgeWebsite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                Authorization: `Bearer ${token}`,
            },
            body: 'param=' + encodeURIComponent(jsonData),
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.success) {
                    badgeItem.classList.remove('active-badge')
                    btn.textContent = 'Use Badge'
                    btn.classList.remove('remove-badge-btn')
                    btn.classList.add('use-badge-btn')
                }
            })
    }
})
