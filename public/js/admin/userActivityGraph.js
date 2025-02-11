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

const weeklyData = JSON.parse(document.getElementById('weeklyData').value);
console.log("Weekly data", weeklyData);

// Create an array for the last 7 days, initializing with 0 activity count
const activityDataWeekly = Array(7).fill(0);
const labelsWeekly = [];

// Get the current date
const now = new Date();

// Generate labels for the past 7 days
for (let i = 6; i >= 0; i--) {
    let date = new Date();
    date.setDate(now.getDate() - i);
    labelsWeekly.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
}

// Populate activityData array with activity counts from weeklyData
weeklyData.forEach(entry => {
    let entryDate = new Date(entry.date); // Ensure entry.date is a full date
    let dayIndex = (entryDate.getDay() + 6) % 7; // Align to labels
    activityDataWeekly[dayIndex] = entry.activity_count;
});

// Setup the chart
const ctxWeekly = document.getElementById('weeklyUserActivityGraph').getContext('2d');
new Chart(ctxWeekly, {
    type: 'line',
    data: {
        labels: labelsWeekly,
        datasets: [{
            label: 'Active Users (Last 7 Days)',
            data: activityDataWeekly,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: {
                title: { display: true, text: 'Day' }
            },
            y: {
                title: { display: true, text: 'Number of Users' }
            }
        }
    }
});
