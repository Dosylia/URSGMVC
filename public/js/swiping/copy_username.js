document.addEventListener('DOMContentLoaded', function() {
    const lolUserText = document.getElementById('lolUserText');
    const copyIcon = document.querySelector('#lolUserText .fa-copy');

    const inviteFriendbtn = document.getElementById('inviteFriend_btn')


    if (inviteFriendbtn) {
      inviteFriendbtn.addEventListener('click', function() {
          const username = inviteFriendbtn.dataset.userUsername;
          const inviteText = `You've been invited to URSG by your friend ${username}! 🎮 Come join the community, chat, and find teammates to play with. Click the link to get started: https://ur-sg.com/?invitedBy=${username}`;

          // Copy text to clipboard
          const tempTextArea = document.createElement('textarea');
          tempTextArea.value = inviteText;
          document.body.appendChild(tempTextArea);
          tempTextArea.select();
          document.execCommand('copy');
          document.body.removeChild(tempTextArea);

          // Remove existing "Copied!" message if it exists
          const existingCopiedText = inviteFriendbtn.querySelector('.copied-text');
          if (existingCopiedText) {
              existingCopiedText.remove();
          }

          // Create "Copied!" message
          const copiedText = document.createElement("i");
          copiedText.classList.add('copied-text'); // Class for styling if needed
          copiedText.style.marginLeft = "8px"; // Add space after "INVITE A FRIEND"
          copiedText.textContent = "Copied!";

          // Find the text node inside the button
          const buttonText = inviteFriendbtn.lastChild;

          // Insert "Copied!" after the text inside the button
          inviteFriendbtn.insertBefore(copiedText, buttonText.nextSibling);

          console.log("Successfully copied the text to the clipboard");

          // Remove "Copied!" text after 3 seconds
          setTimeout(() => {
              copiedText.remove();
          }, 500);
      });
  }
    
  
    if (lolUserText) {
      lolUserText.addEventListener('click', function() {
        const tempTextArea = document.createElement('textarea');
        tempTextArea.value = this.innerText.trim(); 
        document.body.appendChild(tempTextArea); 
        tempTextArea.select(); 
        document.execCommand('copy'); 
        document.body.removeChild(tempTextArea); 
  
        copyIcon.classList.add('visible');
        setTimeout(() => {
          copyIcon.classList.remove('visible');
        }, 1000); 
      });
    }
  });
  