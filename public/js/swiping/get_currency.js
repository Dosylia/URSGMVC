let userIdElementHeaderCurrency = document.getElementById('userId');
let userIdHeaderCurrency = userIdElementHeaderCurrency ? userIdElementHeaderCurrency.value : null;
let numberofFailCurrency = 0;

function getCurrency(userId) {
    if (numberofFailCurrency >= 5) {
        console.error('Too many failed attempts to fetch accepted friend requests. Stopping further attempts.');
        return;
    }
    const token = localStorage.getItem('masterTokenWebsite');
    fetch('index.php?action=getCurrencyWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(userId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.message == "Success") {
            numberofFailCurrency = 0;
            console.log('Currency fetched successfully');
            fillCurrency(data.currency);
        }
    })
    .catch(error => {
        numberofFailCurrency++;
        console.error('Fetch error:', error);
    });
}

function formatCurrency(value) {
    if (value >= 1000) {
        return Math.floor(value / 1000) + 'k';
    }
    return value;
}

function fillCurrency(currency) {
    let currencyElement = document.getElementById('currency');
    const currencyFinal = formatCurrency(currency.user_currency);
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