export function displayError(message) {
    console.log('Displaying error:', message)
    const errorElement = document.getElementById('front-end-error')
    errorElement.style.backgroundColor = 'rgba(255, 100, 100, 1)'
    errorElement.style.padding = '1em'
    errorElement.textContent = message
    errorElement.style.display = 'block'
}
