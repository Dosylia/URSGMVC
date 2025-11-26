"use strict";
import apiFetch from "../Functions/api_fetch.js";

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

    apiFetch({
        url: '/reportUserWebsite',
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: "param=" + encodeURIComponent(jsonData),
    })
    .then((data) => {
        if (data.success) {
                console.log('Reported user:', data.message);
                displayNotification("User reported successfully!");
                const overlay = document.getElementById("overlay");
                overlay.style.display = "none";
            } else {
                console.error('Error fetching messages:', data.error);
            }
    })
    .catch((error) => {
        console.error('Fetch error:', error);
    })
}

document.addEventListener("DOMContentLoaded", function() {
    const reportButton = document.getElementById('report-button');
    const modal = document.getElementById('report-modal');
    const closeModal = document.getElementById('close-modal');
    const submitReport = document.getElementById('submit-report');
    const reportDescription = document.getElementById('report-description');

    // Open the modal when the report button is clicked
    reportButton.addEventListener('click', function() {
        modal.classList.remove('report-modal-hidden');
        const overlay = document.getElementById("overlay");
        overlay.style.display = "block";
    });

    // Close modal when 'x' is clicked
    closeModal.addEventListener('click', function() {
        modal.classList.add('report-modal-hidden');
        const overlay = document.getElementById("overlay");
        overlay.style.display = "none";
    });

    // Close modal if clicking outside the modal content
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.classList.add('report-modal-hidden');
            const overlay = document.getElementById("overlay");
            overlay.style.display = "none";
        }
    });

    // Report User Logic
    submitReport.addEventListener('click', function() {
        const content = "Profile";
        const status = "pending";
        const reason = reportDescription.value || "No reason provided"; // Optional description

        submitReport.disabled = true; // Prevent duplicate clicks

        // Call your reportUser function
        reportUser(userId, receiverId.value, content, status, reason);

        // Close the modal
        modal.classList.add('report-modal-hidden');
    });
});