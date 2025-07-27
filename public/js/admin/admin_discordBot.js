document.getElementById('startBotBtn').onclick = function() {
    botControl('start');
};
document.getElementById('stopBotBtn').onclick = function() {
    botControl('stop');
};
document.getElementById('restartBotBtn').onclick = function() {
    botControl('restart');
};
document.getElementById('sendCommandBtn').onclick = function() {
    const token = localStorage.getItem('adminToken');
    const command = document.getElementById('discordCommandInput').value;
    fetch('/discordBotCommand', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`, // optional
        },
        body: 'command=' + encodeURIComponent(command)
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('discordBotResponse').textContent = data.message || JSON.stringify(data);
        fetchBotStatus(); // Optionally refresh status after command
    })
    .catch(err => {
        console.error('Fetch error:', err);
        document.getElementById('discordBotResponse').textContent = 'Error while contacting server.';
    });
};

window.onload = function () {
    fetchBotStatus(); 
    setInterval(fetchBotStatus, 60000); 
};

function botControl(action) {
    const token = localStorage.getItem('adminToken');
    fetch('/discordBotControl', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`, // optional if you decide to use token later
        },
        body: `action=${encodeURIComponent(action)}`
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('discordBotResponse').textContent = data.message || JSON.stringify(data);
        fetchBotStatus(); // Refresh bot status after any action
    })
    .catch(err => {
        console.error('Fetch error:', err);
        document.getElementById('discordBotResponse').textContent = 'Error while contacting server.';
    });
}


function fetchBotStatus() {
    const token = localStorage.getItem('adminToken');
    fetch('/discordBotStatus', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`, // optional
        }
    })
    .then(res => res.json())
    .then(data => {
        const responseEl = document.getElementById('discordBotResponse');
        if (data.success) {
            responseEl.textContent = `Bot status: ${data.status}`;
        } else {
            responseEl.textContent = 'Unable to determine bot status.';
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        document.getElementById('discordBotResponse').textContent = 'Error while contacting server.';
    });
}