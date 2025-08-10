function sendPageView(siteLocation) {
    console.log('Sent view page triggered')
}

function sendMatchCreated() {
    console.log('Sent match created triggered')
}

function trackNewUser() {
    console.log('Sent new user triggered')
}

function trackLogin() {
    console.log('Sent login triggered')
}

// Collect email, so we might contact the user later
function trackDeleteAccount(email) {
    console.log('Sent delete account triggered', email);
}

function sendReturningUserEvent() {
    console.log('Sent returning user triggered');
}

// Expose globally if needed
window.sendPageView = sendPageView;
window.sendMatchCreated = sendMatchCreated;
window.trackNewUser = trackNewUser;
window.sendReturningUserEvent = sendReturningUserEvent;
window.trackLogin = trackLogin;
window.trackDeleteAccount = trackDeleteAccount;
