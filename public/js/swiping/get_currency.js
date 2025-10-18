"use strict";
import apiFetch from "./api_fetch.js";

let userIdElementHeaderCurrency = document.getElementById('userId');
let userIdHeaderCurrency = userIdElementHeaderCurrency ? userIdElementHeaderCurrency.value : null;
let numberofFailCurrency = 0;
const userId = userIdHeaderCurrency;

function getCurrency() {
    if (numberofFailCurrency >= 5) {
        console.error('Too many failed attempts to fetch accepted friend requests. Stopping further attempts.');
        return;
    }

    apiFetch({
        url: 'index.php?action=getCurrencyWebsite',
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `userId=${encodeURIComponent(userId)}`
    })
        .then((data) => {
            if (data.message == "Success") {
            numberofFailCurrency = 0;
            console.log('Currency fetched successfully');
            fillCurrency(data.currency);
        }
        })
        .catch((error) => {
            numberofFailCurrency++;
            console.error('Fetch error:', error);
        })
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



document.addEventListener("DOMContentLoaded", function() {
    getCurrency(); 
    setInterval(function() {
        getCurrency();
    }, 20000);
});