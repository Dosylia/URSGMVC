const hourlyData = JSON.parse(document.getElementById('hourlyData').value);

// Get the current hour (for proper ordering)
const currentHour = new Date().getHours();

// Create an array for the last 24 hours, initializing with 0 activity count
const activityData = Array(24).fill(0);
const labels = Array(24).fill("").map((_, i) => {
    let hour = (currentHour - 23 + i + 24) % 24; // Ensures the rolling effect
    return `${hour}:00`; 
});

// Populate activityData array with activity counts from hourlyData
hourlyData.forEach(entry => {
    let adjustedHour = (entry.hour - currentHour + 24) % 24; // Adjust hour index based on current hour
    activityData[adjustedHour] = entry.activity_count;
});

// Setup the chart
const ctx = document.getElementById('userActivityGraph').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels, // Now showing a rolling 24-hour window
        datasets: [{
            label: 'Active Users (Last 24 Hours)',
            data: activityData,
            borderColor: 'rgba(231, 64, 87, 1)',
            backgroundColor: 'rgba(152, 12, 31, 0.2)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: {
                title: { display: true, text: 'Hour (Last 24 Hours)' }
            },
            y: {
                title: { display: true, text: 'Number of Users' }
            }
        }
    }
});
