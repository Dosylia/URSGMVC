let userIdElementHeaderCurrency = document.getElementById('userId');
let userIdHeaderCurrency = userIdElementHeaderCurrency ? userIdElementHeaderCurrency.value : null;

function getCurrency(userId) {
    fetch('index.php?action=getCurrency', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `userId=${encodeURIComponent(userId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.message == "Success") {
            console.log('Currency fetched successfully');
            fillCurrency(data.currency);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });
}

function fillCurrency(currency) {
    let currencyElement = document.getElementById('currency');
    const currencyFinal = currency.user_currency;
    if (currencyElement) {
        currencyElement.innerHTML = currencyFinal;
    }
}
const userId = userIdHeaderCurrency;

document.addEventListener("DOMContentLoaded", function() {
    getCurrency(userId); 
    setInterval(function() {
        getCurrency(userId);
    }, 20000);
});