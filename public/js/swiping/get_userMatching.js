document.addEventListener("DOMContentLoaded", function() {
    // DOM elements
    let userId = document.getElementById('userId').value;
    let imageUser = document.getElementById('image_users');
    let username = document.getElementById('user_page_username');
    let userAge = document.getElementById('age_user');
    let sProfileIcon = document.getElementById('profilepicture_lol');
    let sUsername = document.getElementById('lolsUsername');
    let sRank = document.getElementById('lolsRank');
    let sLevel = document.getElementById('lolsLevel');
    let sServer = document.getElementById('lolsServer');
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
    let profileFrames = null;
    const token = localStorage.getItem('masterTokenWebsite');
    const ErrorSpan = document.querySelector('.report-feedback');
    const badgeContainer = document.querySelector('.badge-container-swiping');
    const reportPicture = document.getElementById('image_users_modal');
    const reportUsername = document.getElementById('report-username');
    const reportDescription = document.getElementById('report-description');
    const submitReportButton = document.getElementById('submit-report');

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
                } else {
                    frameSwiping.style.opacity = '0';
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
                'Content-Type': 'application/x-www-form-urlencoded',
                'Authorization': `Bearer ${token}`,
            },
            body: `userId=${encodeURIComponent(userId)}&isNotReactNative=1`
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
        if (data.lol_sUsername && data.lol_sUsername.trim()) { // replacing if (data.lol_sUsername?.trim()) not working on mobile
            document.querySelector('.box_league_account').style.display = 'flex';
            sProfileIcon.src = `http://ddragon.leagueoflegends.com/cdn/14.23.1/img/profileicon/${data.lol_sProfileIcon}.png`;
            sUsername.innerText = data.lol_account;
            sRank.innerText = data.lol_sRank;
            sLevel.innerText += `Level: ${sanitizeHtlm(data.lol_sLevel)}`;
            sServer.innerText = data.lol_server;
        } else {
            document.querySelector('.box_league_account').style.display = 'none';
        }

        reportPicture.src = `${data.user_picture ? `public/upload/${data.user_picture}` : "public/images/defaultprofilepicture.jpg"}`;
        reportPicture.alt = data.user_username;
        reportUsername.innerText = data.user_username;

        // Fill other user data
        btnSwipeYes.disabled = false;
        btnSwipeNo.disabled = false;
        username.innerText = data.user_username;
        userAge.innerText = data.user_age;
        const game = data.user_game; // For example, "League of Legends" or "Valorant"
        const server = game === "League of Legends" ? data.lol_server : data.valorant_server;
        
        const aboutYouUsers = document.querySelector('.about_you_users'); // The container element
        
        // Map user gender to corresponding image classes
        const isDarkMode = document.body.classList.contains('dark-mode');

        // Define the image suffix based on dark mode
        const imageSuffix = isDarkMode ? "-white" : "";
        
        // Gender Section with images
        let genderHtml = `
            <div class="gender about-users-containers">
                <p class="about-users-title"><strong>Gender</strong></p>
                <p class="about-users-box">
        `;
        
        // Map user gender to corresponding image classes
        switch (data.user_gender) {
            case 'Female':
                genderHtml += `
                    <img src="public/images/male${imageSuffix}.png" alt="Male" class="about-users-low-opacity">
                    <img src="public/images/femenine${imageSuffix}.png" alt="Female" class="about-users-selected">
                    <img src="public/images/non-binary${imageSuffix}.png" alt="Non-binary" class="about-users-low-opacity">
                    <img src="public/images/transexual.png" alt="Trans" class="about-users-low-opacity">
                `;
                break;
            case 'Male':
                genderHtml += `
                    <img src="public/images/male${imageSuffix}.png" alt="Male" class="about-users-selected">
                    <img src="public/images/femenine${imageSuffix}.png" alt="Female" class="about-users-low-opacity">
                    <img src="public/images/non-binary${imageSuffix}.png" alt="Non-binary" class="about-users-low-opacity">
                    <img src="public/images/transexual.png" alt="Trans" class="about-users-low-opacity">
                `;
                break;
            case 'Non Binary':
                genderHtml += `
                    <img src="public/images/male${imageSuffix}.png" alt="Male" class="about-users-low-opacity">
                    <img src="public/images/femenine${imageSuffix}.png" alt="Female" class="about-users-low-opacity">
                    <img src="public/images/non-binary${imageSuffix}.png" alt="Non-binary" class="about-users-selected">
                    <img src="public/images/transexual.png" alt="Trans" class="about-users-low-opacity">
                `;
                break;
            case 'Trans':
                genderHtml += `
                    <img src="public/images/male${imageSuffix}.png" alt="Male" class="about-users-low-opacity">
                    <img src="public/images/femenine${imageSuffix}.png" alt="Female" class="about-users-low-opacity">
                    <img src="public/images/non-binary${imageSuffix}.png" alt="Non-binary" class="about-users-low-opacity">
                    <img src="public/images/transexual.png" alt="Trans" class="about-users-selected">
                `;
                break;
            case 'Male and Female':
                genderHtml += `
                    <img src="public/images/male${imageSuffix}.png" alt="Male" class="about-users-selected">
                    <img src="public/images/femenine${imageSuffix}.png" alt="Female" class="about-users-selected">
                    <img src="public/images/non-binary${imageSuffix}.png" alt="Non-binary" class="about-users-low-opacity">
                    <img src="public/images/transexual.png" alt="Trans" class="about-users-low-opacity">
                `;
                break;
            case 'All':
                genderHtml += `
                    <img src="public/images/male${imageSuffix}.png" alt="Male" class="about-users-selected">
                    <img src="public/images/femenine${imageSuffix}.png" alt="Female" class="about-users-selected">
                    <img src="public/images/non-binary${imageSuffix}.png" alt="Non-binary" class="about-users-selected">
                    <img src="public/images/transexual.png" alt="Trans" class="about-users-selected">
                `;
                break;
            default:
                genderHtml += `
                    <img src="public/images/male${imageSuffix}.png" alt="Male" class="about-users-low-opacity">
                    <img src="public/images/femenine${imageSuffix}.png" alt="Female" class="about-users-low-opacity">
                    <img src="public/images/non-binary${imageSuffix}.png" alt="Non-binary" class="about-users-low-opacity">
                    <img src="public/images/transexual.png" alt="Trans" class="about-users-low-opacity">
                `;
                break;
        }
        
        genderHtml += `</p></div>`;
        
        // Queues Section (as previously shown)
        let queuesHtml = `
            <div class="queues about-users-containers">
                <p class="about-users-title"><strong>Queues</strong></p>
                <div class="about-users-box">
        `;
        
        switch (data.user_kindOfGamer) {
            case 'Chill':
                queuesHtml += `
                    <p class="about-users-selected">Chill</p>
                    <p class="about-users-low-opacity">Competition</p>
                `;
                break;
            case 'Competition':
                queuesHtml += `
                    <p class="about-users-low-opacity">Chill</p>
                    <p class="about-users-selected">Competition</p>
                `;
                break;
            default:
                queuesHtml += `
                    <p class="about-users-selected">Chill</p>
                    <p class="about-users-selected">Competition</p>
                `;
                break;
        }
        queuesHtml += `</div></div>`;
        
        // Server Section (new part based on game)
        let serverHtml = `
            <div style="margin-bottom: 10px;" class="server about-users-containers">
                <p class="about-users-title"><strong>Server</strong></p>
                <p class="about-users-box about-users-selected">
                    ${server ? server.toUpperCase() : "Unknow"}
                </p>
            </div>
        `;
        
        // Bio Section
        let bioHtml = `
            <div class="about-users-bio">
                <p class="about-users-box-bio">${data.user_shortBio ? sanitizeHtlm(decodeHtmlEntities(data.user_shortBio)) : "No bio available."}</p>
            </div>
        `;
        
        // Add all sections to the container
        aboutYouUsers.innerHTML = `
            <div class="top-part-about">
                ${genderHtml}
                ${queuesHtml}
                ${serverHtml}
            </div>
                ${bioHtml}
        `;
        
        receiverId.value = data.user_id;
        if (data.user_game === "League of Legends" && data.lol_role) {
            lolMain1P.innerText = data.lol_main1 || ""; 
            lolMain2P.innerText = data.lol_main2 || ""; 
            lolMain3P.innerText = data.lol_main3 || ""; 
            lolRankP.innerText = data.lol_rank || "Unranked";
            lolRoleP.innerText = data.lol_role || "Unknown";
            lolMain1Pic.src = data.lol_main1 ? `public/images/champions/${sanitize(data.lol_main1)}.png` : ""; // Empty src if no main
            lolMain1Pic.alt = data.lol_main1 || ""; 
            lolMain2Pic.src = data.lol_main2 ? `public/images/champions/${sanitize(data.lol_main2)}.png` : ""; // Empty src if no main
            lolMain2Pic.alt = data.lol_main2 || ""; 
            lolMain3Pic.src = data.lol_main3 ? `public/images/champions/${sanitize(data.lol_main3)}.png` : ""; // Empty src if no main
            lolMain3Pic.alt = data.lol_main3 || ""; 
            lolRankPic.src = `public/images/ranks/${sanitize(data.lol_rank || "default")}.png`;
            lolRankPic.alt = data.lol_rank || "Default Rank";
            lolRolePic.src = `public/images/roles/${sanitize(data.lol_role || "default")}.png`;
            lolRolePic.alt = data.lol_role || "Default Role";
        } else if (data.user_game === "Valorant" && data.valorant_role) {
            lolAccount.innerText = data.valorant_account || "Unknown Account";
            lolMain1P.innerText = data.valorant_main1 || ""; 
            lolMain2P.innerText = data.valorant_main2 || ""; 
            lolMain3P.innerText = data.valorant_main3 || ""; 
            lolRankP.innerText = data.valorant_rank || "Unranked";
            lolRoleP.innerText = data.valorant_role || "Unknown";
            lolMain1Pic.src = data.valorant_main1 ? `public/images/valorant_champions/${sanitize(data.valorant_main1)}_icon.webp` : ""; // Empty src if no main
            lolMain1Pic.alt = data.valorant_main1 || ""; 
            lolMain2Pic.src = data.valorant_main2 ? `public/images/valorant_champions/${sanitize(data.valorant_main2)}_icon.webp` : ""; // Empty src if no main
            lolMain2Pic.alt = data.valorant_main2 || ""; 
            lolMain3Pic.src = data.valorant_main3 ? `public/images/valorant_champions/${sanitize(data.valorant_main3)}_icon.webp` : ""; // Empty src if no main
            lolMain3Pic.alt = data.valorant_main3 || ""; 
            lolRankPic.src = `public/images/valorant_ranks/${sanitize(data.valorant_rank || "default")}.png`;
            lolRankPic.alt = data.valorant_rank || "Default Rank";
            lolRolePic.src = `public/images/valorant_roles/${sanitize(data.valorant_role || "default")}.webp`;
            lolRolePic.alt = data.valorant_role || "Default Role";
        }
        
        

        if (data.user_isVip === 1) {
            const spanBadge = document.createElement('span');
            spanBadge.classList.add('vip-badge');
            spanBadge.title = 'Premium Badge';
        
            const vipBadge = document.createElement('img');
            vipBadge.src = '/public/images/premium-badge.png';
            vipBadge.alt = 'Premium Badge';
        
            spanBadge.appendChild(vipBadge); 
            badgeContainer.appendChild(spanBadge); 
        }
        
        if (data.user_isPartner === 1) {
            const spanBadge = document.createElement('span');
            spanBadge.classList.add('vip-badge');
            spanBadge.title = 'Partner Badge';
        
            const partnerBadge = document.createElement('img');
            partnerBadge.src = '/public/images/partner-badge.png';
            partnerBadge.alt = 'Partner Badge';
        
            spanBadge.appendChild(partnerBadge);
            badgeContainer.appendChild(spanBadge); 
        }
        
        if (data.user_isCertified === 1) {
            const spanBadge = document.createElement('span');
            spanBadge.classList.add('vip-badge');
            spanBadge.title = 'Certified Badge';
        
            const certifiedBadge = document.createElement('img');
            certifiedBadge.src = '/public/images/certified-badge.png';
            certifiedBadge.alt = 'Certified Badge';
        
            spanBadge.appendChild(certifiedBadge);
            badgeContainer.appendChild(spanBadge);
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
        badgeContainer.innerHTML = "";
        frameSwiping.style.opacity = '0';
        ErrorSpan.innerText = "";
        reportPicture.src = "";
        reportPicture.alt = "";
        reportUsername.innerText = "";
        reportDescription.value = "";
        submitReportButton.disabled = false;
        
        // Clear text content
        sUsername.innerText = "";
        sRank.innerText = "";
        sLevel.innerText = "";
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
        fetch('index.php?action=swipeDoneWebsite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Authorization': `Bearer ${token}`,
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
        fetch('index.php?action=swipeDoneWebsite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Authorization': `Bearer ${token}`,
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
        const swipeDistanceX = touchendX - touchstartX;  // Horizontal distance
        const swipeDistanceY = touchendY - touchstartY;  // Vertical distance
        const center = screenWidth / 2;
        console.log('Handling swipe gesture');
    
        if (Math.abs(swipeDistanceX) > threshold && Math.abs(swipeDistanceX) > Math.abs(swipeDistanceY)) {
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
        touchstartY = event.changedTouches[0].screenY;  // Track vertical start position
        console.log('Touch start:', touchstartX, touchstartY);
    }, false);
    
    swipeArea.addEventListener('touchend', function(event) {
        touchendX = event.changedTouches[0].screenX;
        touchendY = event.changedTouches[0].screenY;  // Track vertical end position
        console.log('Touch end:', touchendX, touchendY);
        handleSwipeGesture();
    }, false);
});