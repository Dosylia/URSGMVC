  function addPlayerFinderPost({ voice, role, rank, desc, account }) {
    const token = localStorage.getItem('masterTokenWebsite');
    const bodyData = {
        voiceChat: voice,
        roleLookingFor: role,
        rankLookingFor: rank,
        description: desc,
        userId : userId
      };
    fetch('/addPlayerFinderPost', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify(bodyData),
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          console.log(data.oldTime)
          sendMessageDiscord(userId, account, desc, data.oldTime);
          location.reload();
        } else {
          console.log('Error: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Request failed', error);
      });
  }

  function addFriendAndChat(friendId, userId) {
    const token = localStorage.getItem('masterTokenWebsite');
    fetch('/addFriendAndChat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: `userId=${encodeURIComponent(parseInt(userId))}&friendId=${encodeURIComponent(parseInt(friendId))}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `persoChat&friend_id=${friendId}`;
            } else {
                console.error('Error adding as friend', data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
  }

  const applyFilters = () => {
    const savedPrefs = localStorage.getItem('playerfinder_filters');
    if (!savedPrefs) return;
  
    try {
      const prefs = JSON.parse(savedPrefs);
      const cards = document.querySelectorAll('.playerfinder-card');
  
      cards.forEach(card => {
        const role = card.dataset.role;
        const rank = card.dataset.rank;
        const voice = card.dataset.voice === 'true';
  
        const roleMatch = !prefs.role || role === prefs.role.toLowerCase().replace(/\s+/g, '');
        const rankMatch = !prefs.rank || rank === prefs.rank;
        const voiceMatch = prefs.voice === undefined || prefs.voice === voice;

        if (roleMatch && rankMatch && voiceMatch) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    } catch (e) {
      console.error('Error applying filters:', e);
    }
  };

  function sendMessageDiscord(userId, account, message, oldTime) {
    const token = localStorage.getItem('masterTokenWebsite');

    const formData = new URLSearchParams();
    formData.append('userId', parseInt(userId));
    if (account) formData.append('account', account);
    if (message) formData.append('extraMessage', message);
    formData.append('oldTime', oldTime);

    fetch('/sendMessageDiscord', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: formData.toString()
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Message sent to Discord successfully!');
            } else {
                console.error('Message error:', data.error);
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

  document.addEventListener("DOMContentLoaded", function () {
    // Get elements
    const createPostBtn = document.getElementById('createPostBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const submitPostBtn = document.getElementById('submitPostBtn');
    const savePreferencesBtn = document.getElementById('savePreferencesBtn');
    const playerfinderModal = document.getElementById('playerfinder-modal');
  
    const voiceChatInput = document.getElementById('voiceChat');
    const eloInput = document.getElementById('eloLookingFor');
    const rankInput = document.getElementById('rankLookingFor');
    const descInput = document.getElementById('description');
  
    const filterRole = document.getElementById('filterRole');
    const filterRank = document.getElementById('filterRank');
    const filterVoiceChat = document.getElementById('filterVoiceChat');
    const deletePostBtn = document.getElementById('delete-post');
    const playWithThemBtns = document.querySelectorAll('.playwith-btn');
    const chatButtons = document.querySelectorAll(".interested-modal .add-and-chat-btn");

    applyFilters();

    // Play with them button
    playWithThemBtns.forEach(button => {
      button.addEventListener('click', () => {
        const token = localStorage.getItem('masterTokenWebsite');
        const postId = button.dataset.postid;

        fetch('/playWithThem', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
          },
          body: JSON.stringify({ postId, userId }), // Make sure userId is defined
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            console.log(data.message);
            displayNotification(data.message, userId);
          } else {
            console.error('Error:', data.message);
          }
        })
        .catch(error => {
          console.error('Request failed', error);
        });
      });
    });

    if (chatButtons.length > 0) {
      chatButtons.forEach(button => {
        button.addEventListener("click", function () {
        const friendId = this.dataset.friendId;
        const userId = this.dataset.userId;
          if (friendId && userId) {
            addFriendAndChat(friendId, userId);
          }
        });
      });
    }

    deletePostBtn?.addEventListener('click', () => {
      const token = localStorage.getItem('masterTokenWebsite');
      const postId = deletePostBtn.dataset.postid;
      fetch('/deletePlayerFinderPost', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({ postId, userId }),
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          console.log(data.message);
          location.reload();
        } else {
          console.log('Error: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Request failed', error);
      });
    });

    document.querySelectorAll('.interested-btn').forEach(button => {
      button.addEventListener('click', function () {
        const postId = this.id.split('-')[1];
        document.getElementById('interestedModal-' + postId).classList.remove('hidden');
      });
    });

    document.querySelectorAll('.close-modal-btn').forEach(button => {
      button.addEventListener('click', function () {
        const modalId = this.getAttribute('data-modal');
        document.getElementById(modalId).classList.add('hidden');
      });
    });
  
    // Show modal
    createPostBtn?.addEventListener('click', () => {
      console.log('Create Post button clicked');
      playerfinderModal?.classList.remove('hidden');
    });
  
    // Hide modal
    closeModalBtn?.addEventListener('click', () => {
      playerfinderModal?.classList.add('hidden');
    });
  
    // Submit post
    submitPostBtn?.addEventListener('click', () => {
              
      const account = document.getElementById('lookingfor-account')?.value.trim();
        const postData = {
          voice: voiceChatInput?.checked,
          role: eloInput?.value,
          rank: rankInput?.value,
          desc: descInput?.value.trim(),
          account: account
        };
      
        const descError = document.getElementById('descError');
      
        if (!postData.desc) {
          descError.textContent = "Description cannot be empty.";
          descError?.classList.remove('hidden');
          return;
        } else if (postData.desc.length > 130) {
          descError.textContent = "Description must be 130 characters or less.";
          descError?.classList.remove('hidden');
          return;
        } else {
          descError?.classList.add('hidden');
        }
      
        console.log(postData); // Or call your backend function
        if (typeof addPlayerFinderPost === 'function') {
          addPlayerFinderPost(postData);
        }
      
        playerfinderModal?.classList.add('hidden');
      });
      
      
  
    // Save preferences
    savePreferencesBtn?.addEventListener('click', () => {
        const prefs = {
            role: filterRole?.value,
            rank: filterRank?.value,
            voice: filterVoiceChat?.value !== "" ? filterVoiceChat?.value === "1" : undefined
          };
  
      localStorage.setItem('playerfinder_filters', JSON.stringify(prefs));
      applyFilters();
    });
  
    // Load preferences
    const savedPrefs = localStorage.getItem('playerfinder_filters');
    if (savedPrefs) {
      try {
        const prefs = JSON.parse(savedPrefs);
        if (prefs) {
          if (filterRole) filterRole.value = prefs.role || '';
          if (filterRank) filterRank.value = prefs.rank || '';
          if (filterRank) filterRank.value = prefs.rank || '';
          if (typeof prefs.voice === 'boolean' && filterVoiceChat)
            filterVoiceChat.checked = prefs.voice;
        }
      } catch (e) {
        console.error('Failed to parse saved preferences:', e);
      }
    }
  });