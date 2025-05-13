// const showButtonSocialLinks = document.getElementById('opendialog_add_social_links');
// const favDialogSocialLinks = document.getElementById('favDialogSocialLinks');
// let cancelButtonSocialLinks;
// if (favDialogSocialLinks !== null && favDialogSocialLinks !== undefined) {
//   cancelButtonSocialLinks = favDialogSocialLinks.querySelector('#closeButton_social_links');
// }
const fileInputProfile = document.getElementById('fileProfile');
const fileNameProfile = document.getElementById('file-nameProfile');
const placeholderMessage = document.getElementById('placeholder-message');

// if (showButtonSocialLinks !== null && showButtonSocialLinks !== undefined) {
//   showButtonSocialLinks.addEventListener('click', () => {
//     openDialogSocialLinks();
//   });
  
//   cancelButtonSocialLinks.addEventListener('click', () => {
//     closeDialogSocialLinks();
//   });
// }

// function openDialogSocialLinks() {
//   favDialogSocialLinks.style.display = 'flex';
//   favDialogSocialLinks.showModal();
// }

// function closeDialogSocialLinks() {
//   favDialogSocialLinks.style.display = 'none';
//   favDialogSocialLinks.close();
// }

const showButtonPicture = document.getElementById('opendialog_update_picture');
const favDialogPicture = document.getElementById('favDialogPicture');
const cancelButtonPicture = favDialogPicture.querySelector('#closeButton_user_picture');

showButtonPicture.addEventListener('click', () => {
    const overlay = document.getElementById("overlay");
  overlay.style.display = "block";
  favDialogPicture.style.display = 'block';
});

cancelButtonPicture.addEventListener('click', () => {
    const overlay = document.getElementById("overlay");
  overlay.style.display = "none";
  favDialogPicture.style.display = 'none';
});


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


fileInputProfile.addEventListener('change', (event) => {
  const input = event.target;
  if (input.files.length > 0) {
    fileNameProfile.textContent = input.files[0].name;
  } else {
    fileNameProfile.textContent = 'No file selected';
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
          'Content-Type': 'application/x-www-form-urlencoded',
          'Authorization': `Bearer ${token}`,
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
          'Content-Type': 'application/x-www-form-urlencoded',
          'Authorization': `Bearer ${token}`,
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
});
