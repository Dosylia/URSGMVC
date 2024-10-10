document.addEventListener("DOMContentLoaded", function() {
    // DOM elements
    let userId = document.getElementById('userId').value;
    let imageUser = document.getElementById('image_users');
    let username = document.getElementById('user_page_username');
    let userAge = document.getElementById('age_user');
    let sProfileIcon = document.getElementById('profilepicture_lol');
    let sUsername = document.getElementById('lolsUsername');
    let sRank = document.getElementById('lolsRank');
    let lolAccount = document.getElementById('lolAccount');
    let gender = document.getElementById('gender');
    let kindOfGamer = document.getElementById('kindOfGamer');
    let shortBio = document.getElementById('shortBio');
    let receiverId = document.getElementById('receiverId');
    let lolMain1Pic = document.getElementById('lolMain1Pic');
    let lolMain1P = document.getElementById('lolMain1P');
    let lolMain2Pic = document.getElementById('lolMain2Pic');
    let lolMain2P = document.getElementById('lolMain2P');
    let lolMain3Pic = document.getElementById('lolMain3Pic');
    let lolMain3P = document.getElementById('lolMain3P');
    let lolRankPic = document.getElementById('lolRankPic');
    let lolRankP = document.getElementById('lolRankP');
    let lolRolePic = document.getElementById('lolRolePic');
    let lolRoleP = document.getElementById('lolRoleP');
    const btnSwipeYes = document.getElementById('swipe_yes');
    const btnSwipeNo = document.getElementById('swipe_no');
    const swipeArea = document.getElementById('swipe-area');
    const frameSwiping = document.querySelector('.frame-swiping')
    const isVip = document.querySelector('.vip-badge');
    let profileFrames = null;

    function getOwnedItems(userId) {
        fetch('/getOwnedItems', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `userId=${encodeURIComponent(userId)}`
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Fetch error:', response.status, text);
                    throw new Error(`HTTP error! Status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.message === "Success") {
                const items = data.items;
                profileFrames = items.filter(item => item.items_category === 'profile Picture' && item.userItems_isUsed === 1);
                console.log('Profile frames:', profileFrames);
                if (profileFrames.length > 0) {
                    const frame = profileFrames[0];
                    frameSwiping.style.opacity = '1';
                    frameSwiping.src = `public/images/store/${frame.items_picture.replace('.jpg', '.png')}`;
                }
            } else {
                console.log(data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
    }

    // Function to fetch matching user data
    function fetchMatchingUser(userId) {
        fetch('/getUserMatching', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `userId=${encodeURIComponent(userId)}`
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Fetch error:', response.status, text);
                    throw new Error(`HTTP error! Status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.user) {
                getOwnedItems(data.user.user_id);
                fillData(data.user);
            } else {
                showNoMoreProfiles();
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
    }

    // Function to fill the data
    function fillData(data) {

        clearData();
        document.querySelector('.user_page').style.display = 'flex';
        // Fill the user image
        imageUser.src = data.user_picture ? `public/upload/${data.user_picture}` : "public/images/defaultprofilepicture.jpg";

        // Fill the league of legends data if available
        console.log(data.lol_sUsername)
        if (data.lol_sUsername && data.lol_sUsername.trim()) { // replacing if (data.lol_sUsername?.trim()) not working on mobile
            sProfileIcon.src = `public/images/profileicon/${data.lol_sProfileIcon}`;
            sUsername.innerText = data.lol_sUsername;
            sRank.innerText = data.lol_sRank;
        } else {
            document.querySelector('.box_league_account').style.display = 'none';
        }

        // Fill other user data
        btnSwipeYes.disabled = false;
        btnSwipeNo.disabled = false;
        username.innerText = data.user_username;
        userAge.innerText = data.user_age;
        lolAccount.innerText = data.valorant_account;
        gender.innerHTML += ` ${sanitizeHtlm(data.user_gender)}`;
        kindOfGamer.innerHTML += ` ${sanitizeHtlm(data.user_kindOfGamer)}`;
        shortBio.innerHTML += ` ${sanitizeHtlm(decodeHtmlEntities(data.user_shortBio))}`;
        receiverId.value = data.user_id;
            if (data.user_game === "League of Legends") {
                lolMain1P.innerText = data.lol_main1;
                lolMain2P.innerText = data.lol_main2;
                lolMain3P.innerText = data.lol_main3;
                lolRankP.innerText = data.lol_rank;
                lolRoleP.innerText = data.lol_role;
                lolMain1Pic.src = `public/images/champions/${sanitize(data.lol_main1)}.png`;
                lolMain1Pic.alt = data.lol_main1;
                lolMain2Pic.src = `public/images/champions/${sanitize(data.lol_main2)}.png`;
                lolMain2Pic.alt = data.lol_main2;
                lolMain3Pic.src = `public/images/champions/${sanitize(data.lol_main3)}.png`;
                lolMain3Pic.alt = data.lol_main3;
                lolRankPic.src = `public/images/ranks/${sanitize(data.lol_rank)}.png`;
                lolRankPic.alt = data.lol_rank;
                lolRolePic.src = `public/images/roles/${sanitize(data.lol_role)}.png`;
                lolRolePic.alt = data.lol_role;
            } else {
                lolMain1P.innerText = data.valorant_main1;
                lolMain2P.innerText = data.valorant_main2;
                lolMain3P.innerText = data.valorant_main3;
                lolRankP.innerText = data.valorant_rank;
                lolRoleP.innerText = data.valorant_role;
                lolMain1Pic.src = `public/images/valorant_champions/${sanitize(data.valorant_main1)}_icon.webp`;
                lolMain1Pic.alt = data.valorant_main1;
                lolMain2Pic.src = `public/images/valorant_champions/${sanitize(data.valorant_main2)}_icon.webp`;
                lolMain2Pic.alt = data.valorant_main2;
                lolMain3Pic.src = `public/images/valorant_champions/${sanitize(data.valorant_main3)}_icon.webp`;
                lolMain3Pic.alt = data.valorant_main3;
                lolRankPic.src = `public/images/valorant_ranks/${sanitize(data.valorant_rank)}.png`;
                lolRankPic.alt = data.valorant_rank;
                lolRolePic.src = `public/images/valorant_roles/${sanitize(data.valorant_role)}.webp`;
                lolRolePic.alt = data.valorant_role;
            }

            if (data.user_isVip === 1) {
                const vipBadge = document.createElement('img');
                vipBadge.src = '/public/images/premium-badge.png';   
                vipBadge.alt = 'Premium Badge';
                isVip.appendChild(vipBadge);
            }
    }

    function clearData() {
        // Hide elements that might be shown based on conditions
        document.querySelector('.box_league_account').style.display = 'none';
    
        // Clear image sources and alt text
        imageUser.src = "public/images/defaultprofilepicture.jpg";
        imageUser.alt = "Default profile picture";
        sProfileIcon.src = "";
        sProfileIcon.alt = "";
        frameSwiping.src = "";
        
        // Clear text content
        sUsername.innerText = "";
        sRank.innerText = "";
        username.innerText = "";
        userAge.innerText = "";
        lolAccount.innerText = "";
        gender.innerHTML = "<strong>Gender:</strong> "; 
        kindOfGamer.innerHTML = "<strong>Kind of Gamer:</strong> "; 
        shortBio.innerHTML = "<strong>ShortBio:</strong>"; 
        receiverId.value = "";
    
        // Clear the League of Legends data
        lolMain1P.innerText = "";
        lolMain2P.innerText = "";
        lolMain3P.innerText = "";
        lolRankP.innerText = "";
        lolRoleP.innerText = "";
        lolMain1Pic.src = "";
        lolMain1Pic.alt = "";
        lolMain2Pic.src = "";
        lolMain2Pic.alt = "";
        lolMain3Pic.src = "";
        lolMain3Pic.alt = "";
        lolRankPic.src = "";
        lolRankPic.alt = "";
        lolRolePic.src = "";
        lolRolePic.alt = "";
    }

    // Function to show the no more profiles message
    function showNoMoreProfiles() {
        document.querySelector('.user_page').style.display = 'none';
        document.querySelector('.noUserToSee').style.display = 'flex';
    }

    // Sanitize function
    function sanitize(input) {
        return input.trim().replace(/\s+/g, '');
    }

    function sanitizeHtlm(input) {
        const element = document.createElement('div');
        element.innerText = input;
        return element.innerHTML;
    }

    function decodeHtmlEntities(encodedString) {
        const element = document.createElement('div');
        element.innerHTML = encodedString;
        return element.innerText;
    }

    // Function swipping
    function handleSwipeGesture() {
        if (Math.abs(touchendX - touchstartX) > threshold) {
            if (touchendX < touchstartX) {
                swipeYes(userId, receiverId.value);
            } else if (touchendX > touchstartX) {
                swipeNo(userId, receiverId.value);
            }
        }
    }

    // Swipe functions
    function swipeYes(userId, receiverId) {
        fetch('index.php?action=swipeDone', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `swipe_yes=1&senderId=${encodeURIComponent(userId)}&receiverId=${encodeURIComponent(receiverId)}`
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Fetch error:', response.status, text);
                    throw new Error(`HTTP error! Status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Swipe yes response data:', data);
            if (data.success) {
                buttonSuccess();
                setTimeout(() => {
                    fetchMatchingUser(userId);
                }, 1000);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
    }

    function swipeNo(userId, receiverId) {
        fetch('index.php?action=swipeDone', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `swipe_no=1&senderId=${encodeURIComponent(userId)}&receiverId=${encodeURIComponent(receiverId)}`
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Fetch error:', response.status, text);
                    throw new Error(`HTTP error! Status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Swipe no response data:', data);
            if (data.success) {
                buttonFailure();
                setTimeout(() => {
                    fetchMatchingUser(userId);
                }, 1000);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
    }

    function buttonSuccess() {
        // Create checkmark icon element
        const iElement = document.createElement('i');
        iElement.classList.add('fa-solid', 'fa-heart', 'buttonSwipe');
        iElement.style.color = "green";
        swipeArea.appendChild(iElement);
    
        // Remove the icon after 1 second
        setTimeout(() => {
            swipeArea.removeChild(iElement);
        }, 2000);
    }
    
    function buttonFailure() {
        // Create cross icon element
        const iElement = document.createElement('i');
        iElement.classList.add('fa-solid', 'fa-xmark', 'buttonSwipe');
        iElement.style.color = "red";
        swipeArea.appendChild(iElement);
    
        // Remove the icon after 1 second
        setTimeout(() => {
            swipeArea.removeChild(iElement);
        }, 2000);
    }

    // Initial call to fetch matching user data
    fetchMatchingUser(userId);

    // Event listeners

    btnSwipeYes.addEventListener('click', (event) => {
        event.preventDefault();
        btnSwipeYes.disabled = true;
        btnSwipeNo.disabled = true;
        swipeYes(userId, receiverId.value);
    });

    btnSwipeNo.addEventListener('click', (event) => {
        event.preventDefault();
        btnSwipeNo.disabled = true;
        btnSwipeYes.disabled = true;
        swipeNo(userId, receiverId.value);
    });

    let touchstartX = 0;
    let touchendX = 0;
    const threshold = 100; 
    const screenWidth = window.innerWidth;
    
    function handleSwipeGesture() {
        const swipeDistance = touchendX - touchstartX;
        const center = screenWidth / 2;
        console.log('Handling swipe gesture');
        
        // Only proceed if the swipe distance is greater than the threshold
        if (Math.abs(swipeDistance) > threshold) {
            // Swipe right detection
            if (touchstartX < center && touchendX > touchstartX) {
                swipeYes(userId, receiverId.value);
            }
            // Swipe left detection
            else if (touchstartX > center && touchendX < touchstartX) {
                swipeNo(userId, receiverId.value);
            }
        }
    }
    
    swipeArea.addEventListener('touchstart', function(event) {
        touchstartX = event.changedTouches[0].screenX;
        console.log('Touch start:', touchstartX);
    }, false);
    
    swipeArea.addEventListener('touchend', function(event) {
        touchendX = event.changedTouches[0].screenX;
        console.log('Touch end:', touchendX);
        handleSwipeGesture();
    }, false);
});