let btnSubmit;
let senderId;
let receiverId;
let messageInput;

function sendMessageToPhp(senderId, receiverId, message) {
    const dataToSend = {
        senderId,
        receiverId,
        message
    };

    const jsonData = JSON.stringify(dataToSend);

    fetch('index.php?action=sendMessageData', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: "param=" + encodeURIComponent(jsonData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Success:', data);
        messageInput.value = '';
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

document.addEventListener("DOMContentLoaded", function() {
    senderId = document.getElementById("senderId").value;
    receiverId = document.getElementById("receiverId").value;
    messageInput = document.getElementById("message_text");
    btnSubmit = document.getElementById("submit_chat");



    btnSubmit.addEventListener("click", function(event) {
        event.preventDefault();
        const message = messageInput.value;
        sendMessageToPhp(senderId, receiverId, message);
    });



});