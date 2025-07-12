function generateVisitorId() {
    return 'visitor_' + Date.now() + '_' + Math.random().toString(36).substring(2, 10);
}

function getVisitorId() {
    let id = localStorage.getItem('landingPageVisitorId');
    if (!id) {
        id = generateVisitorId();
        localStorage.setItem('landingPageVisitorId', id);
    }
    return id;
}

function isReturningUser() {
    const now = Date.now();
    const userVisitData = JSON.parse(localStorage.getItem('visitData') || '{}');

    if (!userVisitData.firstVisit) {
        localStorage.setItem('visitData', JSON.stringify({
            firstVisit: now,
            lastVisit: now
        }));
        return false;
    }

    const daysSinceFirstVisit = (now - userVisitData.firstVisit) / (1000 * 60 * 60 * 24);
    const hoursSinceLastVisit = (now - userVisitData.lastVisit) / (1000 * 60 * 60);

    // Update last visit time
    userVisitData.lastVisit = now;
    localStorage.setItem('visitData', JSON.stringify(userVisitData));

    // Define "returning" as: user who first visited 1+ days ago AND last visited over 6 hours ago
    return daysSinceFirstVisit >= 1 && hoursSinceLastVisit >= 6;
}

function pushEvent(eventName, eventCategory, eventLabel, extraParams = {}) {
    const visitorId = getVisitorId();
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event: eventName,
        event_category: eventCategory,
        event_label: eventLabel,
        user_platform: 'desktop',
        visitor_id: visitorId,
        ...extraParams,
    });
    // console.log(`Event pushed: ${eventName}`, {
    //     event_category: eventCategory,
    //     event_label: eventLabel,
    //     visitor_id: visitorId,
    //     ...extraParams,
    // });
}

function sendPageView(siteLocation) {
    pushEvent('page_view', 'Page', document.title, {
        page_location: window.location.href,
        page_path: window.location.pathname,
        site_location: siteLocation || 'unknown',
    });
}

function sendMatchCreated() {
    pushEvent('match_created', 'Match', 'Match Created');
}

function sendNewUserEvent() {
    pushEvent('new_user', 'User', 'New User');
}

function sendLandingpageView() {
    pushEvent('landing_page_view', 'Page', 'Landing Page Viewed');
}

function sendReturningUserEvent() {
    if (isReturningUser()) {
        pushEvent('returning_user', 'User', 'Returning User');
    }
}

// Expose globally if needed
window.sendPageView = sendPageView;
window.sendMatchCreated = sendMatchCreated;
window.sendNewUserEvent = sendNewUserEvent;
window.sendLandingpageView = sendLandingpageView;
window.sendReturningUserEvent = sendReturningUserEvent;
