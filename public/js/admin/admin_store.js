// Read users JSON that PHP printed in the PHTML
const allUsersEl = document.getElementById('all-users-json')
const allUsers = allUsersEl ? JSON.parse(allUsersEl.textContent || '[]') : []

let selectedUserId = null

// DOM
const searchInput = document.getElementById('user-search')
const resultsContainer = document.getElementById('user-results')
const selectedUserBox = document.getElementById('selected-user')
const selectedUserPicture = document.getElementById('selected-user-picture')
const selectedUserName = document.getElementById('selected-user-name')
const clearSelectedBtn = document.getElementById('clear-selected-user')

const grantUserId = document.getElementById('grant-user-id')
const removeUserId = document.getElementById('remove-user-id')
const grantBtn = document.getElementById('grant-item-btn')
const removeBtn = document.getElementById('remove-item-btn')

function renderResults(users) {
    resultsContainer.innerHTML = ''
    users.forEach((user) => {
        const div = document.createElement('div')
        div.className = 'user-result'
        div.innerHTML = `
      <img src="public/upload/${
          user.user_picture || 'defaultprofilepicture.jpg'
      }" alt="${user.user_username}">
      <span>${user.user_username}</span>
    `
        div.addEventListener('click', () => selectUser(user))
        resultsContainer.appendChild(div)
    })
}

function selectUser(user) {
    selectedUserId = user.user_id
    selectedUserPicture.src = user.user_picture
        ? `public/upload/${user.user_picture}`
        : 'public/images/defaultprofilepicture.jpg'
    selectedUserName.textContent = user.user_username
    selectedUserBox.style.display = 'block'
    grantUserId.value = selectedUserId
    removeUserId.value = selectedUserId
    grantBtn.disabled = false
    removeBtn.disabled = false
    resultsContainer.innerHTML = ''
    searchInput.value = user.user_username
}

function clearSelectedUser() {
    selectedUserId = null
    selectedUserBox.style.display = 'none'
    grantUserId.value = ''
    removeUserId.value = ''
    grantBtn.disabled = true
    removeBtn.disabled = true
}

searchInput.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase()
    resultsContainer.innerHTML = ''
    if (q.length < 2) return

    // simple local filter
    const filtered = allUsers
        .filter((u) => (u.user_username || '').toLowerCase().includes(q))
        .slice(0, 20) // cap results
    renderResults(filtered)
})

if (clearSelectedBtn) {
    clearSelectedBtn.addEventListener('click', clearSelectedUser)
}

// Hard stop: ensure a user is selected before submit (extra safety)
document.getElementById('grant-item-form').addEventListener('submit', (e) => {
    if (!selectedUserId) {
        e.preventDefault()
        alert('Please select a user first.')
    }
})

document.getElementById('remove-item-form').addEventListener('submit', (e) => {
    if (!selectedUserId) {
        e.preventDefault()
        alert('Please select a user first.')
    }
})
