const showButtonSocialLinks = document.getElementById('opendialog_add_social_links');
const favDialogSocialLinks = document.getElementById('favDialogSocialLinks');
const cancelButtonSocialLinks = favDialogSocialLinks.querySelector('#closeButton_social_links');
const fileInput = document.getElementById('file');
const fileName = document.getElementById('file-name');
const placeholderMessage = document.getElementById('placeholder-message');

showButtonSocialLinks.addEventListener('click', () => {
  openDialogSocialLinks();
});

cancelButtonSocialLinks.addEventListener('click', () => {
  closeDialogSocialLinks();
});

function openDialogSocialLinks() {
  favDialogSocialLinks.style.display = 'flex';
  favDialogSocialLinks.showModal();
}

function closeDialogSocialLinks() {
  favDialogSocialLinks.style.display = 'none';
  favDialogSocialLinks.close();
}

const showButtonPicture = document.getElementById('opendialog_update_picture');
const favDialogPicture = document.getElementById('favDialogPicture');
const cancelButtonPicture = favDialogPicture.querySelector('#closeButton_user_picture');

showButtonPicture.addEventListener('click', () => {
  openDialogPicture();
});

cancelButtonPicture.addEventListener('click', () => {
  closeDialogPicture();
});

function openDialogPicture() {
  favDialogPicture.style.display = 'flex';
  favDialogPicture.showModal();
}

function closeDialogPicture() {
  favDialogPicture.style.display = 'none';
  favDialogPicture.close();
}

const hiddenP = document.getElementById('hidden_p');
const imgDiscord = document.getElementById('discord_picture');

if (imgDiscord !== null && imgDiscord !== undefined) {
  imgDiscord.addEventListener('click', () => {
    if (hiddenP.style.display === "none" || hiddenP.style.display === "") {
      hiddenP.style.display = "block";
    } else {
      hiddenP.style.display = "none";
    }
  });
}


fileInput.addEventListener('change', (event) => {
  const input = event.target;
  if (input.files.length > 0) {
    fileName.textContent = input.files[0].name;
  } else {
    fileName.textContent = 'No file selected';
  }
});

function usePictureFrame(itemId, userId) {
  console.log(`Adding frame item ID: ${itemId}, userId: ${userId}`);

  const dataToSend = {
      itemId,
      userId,
  };

  const jsonData = JSON.stringify(dataToSend);
  
  fetch('index.php?action=usePictureFrameWebsite', {
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
      placeholderMessage.innerHTML = '';
      console.log('Success:', data);
      if (data.success) {
        location.reload();
      } else {
          placeholderMessage.innerHTML = data.message;
      }
  })
  .catch(error => {
      console.error('Error:', error);
  });
}

function RemovePictureFrame(itemId, userId) {
  console.log(`Removing frame item ID: ${itemId}, userId: ${userId}`);

  const dataToSend = {
      itemId,
      userId,
  };

  const jsonData = JSON.stringify(dataToSend);
  
  fetch('index.php?action=removePictureFrameWebsite', {
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
      placeholderMessage.innerHTML = '';
      console.log('Success:', data);
      if (data.success) {
        location.reload();
      } else {
          placeholderMessage.innerHTML = data.message;
      }
  })
  .catch(error => {
      console.error('Error:', error);
  });
}

document.addEventListener('DOMContentLoaded', function() {

  const pictureFrameButtons = document.querySelectorAll('.btn_picture_frame');
  const pictureFrameButtonsRemove = document.querySelectorAll('.btn_picture_frame_remove');

  pictureFrameButtons.forEach(button => {
    button.addEventListener('click', function() {
      const itemId = this.getAttribute('data-item-id');
      usePictureFrame(itemId, userIdHeader);
    });
  });

  pictureFrameButtonsRemove.forEach(button => {
    button.addEventListener('click', function() {
      const itemId = this.getAttribute('data-item-id');
      RemovePictureFrame(itemId, userIdHeader);
    });
  });

  const lolUserText = document.getElementById('lolUserText');
  const copyIcon = document.querySelector('#lolUserText .fa-copy');

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
