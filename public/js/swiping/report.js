let receiverId = document.getElementById('receiverId');

function reportUser(userId, reportedId, content, status, reason) {
    const dataToSend = {
        userId,
        reportedId,
        content,
        status,
        reason
    };

    const jsonData = JSON.stringify(dataToSend);

    const token = localStorage.getItem('masterTokenWebsite');
    fetch('/reportUserWebsite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: "param=" + encodeURIComponent(jsonData)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Reported user:', data.message);
            } else {
                console.error('Error fetching messages:', data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
}

document.addEventListener("DOMContentLoaded", function() {
    const reportButton = document.getElementById('report-button');

    reportButton.addEventListener('click', function() {
    const content = "Profile";
    const status = "pending";
    const reason = "Reported";
    reportButton.disabled = true;
    reportUser(userId, receiverId.value, content, status, reason);
    });
});