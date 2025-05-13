function sendMessageDiscord(userId, account, message, oldTime, voice, role, rank) {
  const token = localStorage.getItem('masterTokenWebsite');

  const formData = new URLSearchParams();
  formData.append('userId', parseInt(userId));
  if (account) formData.append('account', account);
  if (message) formData.append('extraMessage', message);
  formData.append('playerfinder', true);
  formData.append('oldTime', oldTime);
  if (voice) formData.append('voiceChat', voice);
  if (role) formData.append('roleLookingFor', role);
  if (rank) formData.append('rankLookingFor', rank);

  return fetch('/sendMessageDiscord', {
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
        throw new Error(data.error || 'Unknown error');
      }
    })
    .catch(error => {
      console.error('Fetch error:', error);
      throw error;
    });
}

function addPlayerFinderPost({ voice, role, rank, desc, account }) {
  const token = localStorage.getItem('masterTokenWebsite');
  const bodyData = {
    voiceChat: voice,
    roleLookingFor: role,
    rankLookingFor: rank,
    description: desc,
    userId: userId,
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
        console.log(data.oldTime);
        sendMessageDiscord(userId, account, desc, data.oldTime, voice, role, rank)
          .then(() => {
            location.reload();
          })
          .catch(error => {
            console.error('Discord message failed:', error);
            location.reload();
          });
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
    const prefs = JSON.parse(localStorage.getItem('playerfinder_filters')) || {};
    
    document.querySelectorAll('.playerfinder-card').forEach(card => {
        const game = card.dataset.game;
        const role = card.dataset.role;
        const rank = card.dataset.rank;
        const voice = card.dataset.voice === 'true';

        // Convert filter values to lowercase for comparison
        const filterRole = prefs.role?.toLowerCase().replace(/\s+/g, '') || '';
        const filterRank = prefs.rank?.toLowerCase().replace(/\s+/g, '') || '';

        const gameMatch = !prefs.game || game === prefs.game;
        const roleMatch = !prefs.role || role === filterRole;
        const rankMatch = !prefs.rank || rank.toLowerCase() === filterRank;
        const voiceMatch = prefs.voice === undefined || prefs.voice === voice;

        card.style.display = (gameMatch && roleMatch && rankMatch && voiceMatch) ? '' : 'none';
    });
};

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
    const offlineButtons = document.querySelectorAll(".offline-btn");
    const toggleBtn = document.getElementById('toggleFilter');
    const filterPanel = document.getElementById('filterPanel');

    const filterElements = [
      document.getElementById('filterGame'),
      document.getElementById('filterRole'),
      document.getElementById('filterRank'),
      document.getElementById('filterVoiceChat')
    ];

    filterElements.forEach(el => {
      el.addEventListener('change', () => {
        const newPrefs = {
          game: document.getElementById('filterGame').value || null,
          role: document.getElementById('filterRole').value || null,
          rank: document.getElementById('filterRank').value || null,
          voice: document.getElementById('filterVoiceChat').value !== '' ? 
                  document.getElementById('filterVoiceChat').value === '1' : undefined
        };

        localStorage.setItem('playerfinder_filters', JSON.stringify(newPrefs));
        applyFilters();
      });
    });

    applyFilters();

    toggleBtn.addEventListener('click', () => {
      filterPanel.classList.toggle('active');
      filterPanel.classList.toggle('hidden-on-mobile');
    });

    document.getElementById('filterGame').addEventListener('change', function() {
      const game = this.value;
      const roles = document.querySelectorAll('#filterRole option');
      const ranks = document.querySelectorAll('#filterRank option');
      roles.forEach(opt => {
        if (opt.value === "") {
          opt.style.display = '';
        } else if (!game) {
          opt.style.display = '';
        } else if (opt.dataset.game === 'lol' && game === 'League of Legends') {
          opt.style.display = '';
        } else if (opt.dataset.game === 'valorant' && game === 'Valorant') {
          opt.style.display = '';
        } else {
          opt.style.display = 'none';
        }
      });
      // Repeat similar logic for rank filter

      ranks.forEach(opt => {
        if (opt.value === "") {
          opt.style.display = '';
        } else if (!game) {
          opt.style.display = '';
        } else if (opt.dataset.game === 'lol' && game === 'League of Legends') {
          opt.style.display = '';
        } else if (opt.dataset.game === 'valorant' && game === 'Valorant') {
          opt.style.display = '';
        } else {
          opt.style.display = 'none';
        }
      });
    });

    if (offlineButtons.length > 0) {
      offlineButtons.forEach(btn => {
        btn.addEventListener("click", () => {
          const modal = document.getElementById("offlineModal");
          if (modal) {
            modal.classList.remove("hidden");
          }
        });
      });
    }

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
            if (data.isFriend) {
                const friendId = data.friendId;
                window.location.href = `persoChat&friend_id=${friendId}`;             
            }
            console.log(data.message);
            displayNotification(data.message, userId);
            const likedIcon = document.querySelector(`.liked-post-${postId}`);
            if (likedIcon) {
              likedIcon.style.display = 'block';
            }
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
            game: filterGame.value === "Any" ? "" : filterGame.value,
            role: filterRole.value === "Any" ? "" : filterRole.value,
            rank: filterRank.value === "Any" ? "" : filterRank.value,
            voice: filterVoiceChat.value !== "" ? filterVoiceChat.value === "1" : undefined
        };
        localStorage.setItem('playerfinder_filters', JSON.stringify(prefs));
    });
  
  // Load preferences
  const savedPrefs = localStorage.getItem('playerfinder_filters');
  if (savedPrefs) {
    try {
      const prefs = JSON.parse(savedPrefs);
      const gameSelect = document.getElementById('filterGame');
      
      // Set all values
      if (prefs.game) gameSelect.value = prefs.game;
      if (prefs.role) filterRole.value = prefs.role;
      if (prefs.rank) filterRank.value = prefs.rank;
      
      // Trigger option filtering
      gameSelect.dispatchEvent(new Event('change'));
      
      // Apply filters using saved prefs
      applyFilters();
    } catch(e) {/* ... */}
  }
  });