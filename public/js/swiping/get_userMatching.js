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

    // Function to fetch matching user data
    function fetchMatchingUser(userId) {
        fetch('index.php?action=getUserMatching', {
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
        document.querySelector('.user_page').style.display = 'flex';
        // Fill the user image
        imageUser.src = data.user_picture ? `public/upload/${data.user_picture}` : "public/images/defaultprofilepicture.jpg";

        // Fill the league of legends data if available
        if (data.lol_sUsername?.trim()) {
            sProfileIcon.src = `public/images/profileicon/${data.lol_sProfileIcon}`;
            sUsername.innerText = data.lol_sUsername;
            sRank.innerText = data.lol_sRank;
        } else {
            document.querySelector('.box_league_account').style.display = 'none';
        }

        // Fill other user data
        username.innerText = data.user_username;
        userAge.innerText = data.user_age;
        lolAccount.innerText = data.lol_account;
        gender.innerHTML += ` ${sanitizeHtlm(data.user_gender)}`;
        kindOfGamer.innerHTML += ` ${sanitizeHtlm(data.user_kindOfGamer)}`;
        shortBio.innerHTML += ` ${sanitizeHtlm(data.user_shortBio)}`;
        receiverId.value = data.user_id;
        lolMain1P.innerText = data.lol_main1;
        lolMain2P.innerText = data.lol_main2;
        lolMain3P.innerText = data.lol_main3;
        lolRankP.innerText = data.lol_rank;
        lolRoleP.innerText = data.lol_role;
        lolMain1Pic.src = `public/images/champions/${sanitize(data.lol_main1)}.png`;
        lolMain2Pic.src = `public/images/champions/${sanitize(data.lol_main2)}.png`;
        lolMain3Pic.src = `public/images/champions/${sanitize(data.lol_main3)}.png`;
        lolRankPic.src = `public/images/ranks/${sanitize(data.lol_rank)}.png`;
        lolRolePic.src = `public/images/roles/${sanitize(data.lol_role)}.png`;
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
        swipeYes(userId, receiverId.value);
    });

    btnSwipeNo.addEventListener('click', (event) => {
        event.preventDefault();
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
                console.log('Swipe right detected');
                swipeYes(userId, receiverId.value);
            }
            // Swipe left detection
            else if (touchstartX > center && touchendX < touchstartX) {
                console.log('Swipe left detected');
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