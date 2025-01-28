const hourlyData = JSON.parse(document.getElementById('hourlyData').value);

// Create an array with 24 hours, each starting at 0 activity count
const activityData = Array(24).fill(0);

// Populate activityData array with activity counts from hourlyData
hourlyData.forEach(entry => {
    if (entry.hour >= 0 && entry.hour < 24) {
        activityData[entry.hour] = entry.activity_count;  // Map the correct activity count for each hour
    }
});

// Labels for the X-axis (hours of the day)
const labels = Array.from({ length: 24 }, (_, i) => `${i}:00`);

// Setup the chart
const ctx = document.getElementById('userActivityGraph').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Active Users Today',
            data: activityData, // Use the cleaned up activityData array
            borderColor: 'rgba(231, 64, 87, 1)',
            backgroundColor: 'rgba(152, 12, 31, 0.2)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: {
                title: { display: true, text: 'Hour of the Day' }
            },
            y: {
                title: { display: true, text: 'Number of Users' }
            }
        }
    }
});
