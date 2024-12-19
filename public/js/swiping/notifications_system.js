
// Initialize the global notification system if it doesn't already exist
window.notificationSystem = window.notificationSystem || {
    notificationQueue: [],
    isNotificationDisplayed: false,
    displayNotification: function (message) {
        // Add the message to the queue
        this.notificationQueue.push(message);

        // Process the queue if no notification is currently displayed
        if (!this.isNotificationDisplayed) {
            this.processNotificationQueue();
        }
    },
    processNotificationQueue: function () {
        // If the queue is empty, stop processing
        if (this.notificationQueue.length === 0) {
            this.isNotificationDisplayed = false;
            return;
        }

        // Mark that a notification is being displayed
        this.isNotificationDisplayed = true;

        // Get the next message from the queue
        const message = this.notificationQueue.shift();

        // Display the notification
        const notificationSpan = document.querySelector('.notification-span');
        if (!notificationSpan) {
            console.error("Notification span not found!");
            return;
        }

        notificationSpan.style.display = 'block';
        notificationSpan.innerText = message;

        // Clear the notification after 5 seconds
        setTimeout(() => {
            notificationSpan.innerText = '';
            notificationSpan.style.display = 'none'; // Hide the notification
            this.isNotificationDisplayed = false;

            // Process the next notification in the queue
            this.processNotificationQueue();
        }, 5000);
    },
};
