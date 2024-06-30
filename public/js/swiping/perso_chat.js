    // let messageInput;
    // let btnSubmit;

    // messageInput = document.getElementById("message");
    // btnSubmit = document.getElementById("submit_chat");
    // // Event listener for Enter key
    // messageInput.addEventListener("keyup", function(event) {

    // });

    function scrollToLastMessage() {
        var lastMessage = document.getElementById('last-message');
        var messageContainer = document.querySelector('.messages-container');
        if (lastMessage && messageContainer) {
          lastMessage.scrollIntoView();
          messageContainer.scrollTop = messageContainer.scrollHeight;
        }
      }

      window.addEventListener('DOMContentLoaded', function() {
        var messageInput = document.getElementById('message_text');
        messageInput.focus();
      });