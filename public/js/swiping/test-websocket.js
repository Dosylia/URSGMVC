const socket = new WebSocket('ws://ur-sg.com:8080');

socket.addEventListener('message', (event) => {
    const notification = JSON.parse(event.data);
    console.log('New notification:', notification.content);
});