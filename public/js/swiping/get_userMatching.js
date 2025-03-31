document.addEventListener("DOMContentLoaded", function() {
    // DOM elements
    let userId = document.getElementById('userId').value;
    let imageUser = document.getElementById('image_users');
    let username = document.getElementById('user_page_username');
    let userAge = document.getElementById('age_user');
    let sUsername = document.getElementById('lolsUsername');
    let lolAccount = document.getElementById('lolsUsername');
    let gender = document.getElementById('swiping_gender');
    let kindOfGamer = document.getElementById('swiping_kindOfGamer');
    let shortBio = document.getElementById('shortBio');
    let receiverId = document.getElementById('receiverId');
    let lolMain1Pic = document.getElementById('lolMain1Pic');
    let lolMain2Pic = document.getElementById('lolMain2Pic');
    let lolMain3Pic = document.getElementById('lolMain3Pic');
    let lolRankP = document.getElementById('lolRankP');
    let lolRoleP = document.getElementById('lolRoleP');
    const btnSwipeYes = document.getElementById('swipe_yes');
    const btnSwipeNo = document.getElementById('swipe_no');
    const swipeArea = document.getElementById('swipe-area');
    const frameSwiping = document.querySelector('.frame-swiping');
    const championContainer = document.querySelector('.swiping_champions');
    let profileFrames = null;
    const token = localStorage.getItem('masterTokenWebsite');
    const ErrorSpan = document.querySelector('.report-feedback');
    const badgeContainer = document.querySelector('.badge-container-swiping');
    const reportPicture = document.getElementById('image_users_modal');
    const reportUsername = document.getElementById('report-username');
    const reportDescription = document.getElementById('report-description');
    const submitReportButton = document.getElementById('submit-report');
    const picturesRow = document.querySelector(".pictures-row");
    const bonusPictureContainer = document.getElementById('bonus-picture-container');
    let hasBindedAccount = false;

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
                    throw new Error(`HTTP error! Status: ${response.status}`);z
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
    async function fillData(data) {

        clearData();
        document.querySelector('.swiping-ctn').style.display = 'flex';
        // Fill the user image
        imageUser.src = data.user_picture ? `public/upload/${data.user_picture}` : "public/images/defaultprofilepicture.jpg";
        
        if (data.lol_sUsername && data.lol_sUsername.trim()) { // Ensure it's not empty
            hasBindedAccount = true;
        
            const version = await fetchDdragonVersion();
            sUsername.innerText = data.lol_account;
        } else {
            sUsername.innerText = "UNKNOW";
            hasBindedAccount = false;
        }

        const baseRank = hasBindedAccount 
        ? data.lol_sRank.split(" ")[0].charAt(0).toUpperCase() + data.lol_sRank.split(" ")[0].slice(1).toLowerCase() 
        : (data.lol_rank || "default");

        if (data.user_bonusPicture && data.user_bonusPicture !== "[]") {
            let pictures;
        
            try {
                pictures = JSON.parse(data.user_bonusPicture);
            } catch (error) {
                console.error("Error parsing user_bonusPicture:", error);
                pictures = []; // Force it to be an empty array so it behaves like the else case
            }
        
            // Ensure it's an array and has valid pictures
            if (!Array.isArray(pictures) || pictures.length === 0) {
                bonusPictureContainer.style.display = "none";
            } else {
                console.log('Bonus pictures:', pictures);
                bonusPictureContainer.style.display = "flex";
        
                // Limit to 2 pictures only
                pictures.slice(0, 2).forEach(picture => { 
                    const picturePath = `public/upload/${picture}`;
        
                    // Create the wrapper div
                    const pictureWrapper = document.createElement("div");
                    pictureWrapper.classList.add("picture-wrapper-swiping");
        
                    // Create the image element
                    const img = document.createElement("img");
                    img.src = picturePath;
                    img.classList.add("user-picture-swiping");
                    img.alt = "User Picture";
        
                    // Append image and button to wrapper
                    pictureWrapper.appendChild(img);
        
                    // Append wrapper to the pictures row
                    picturesRow.appendChild(pictureWrapper);
                });
            }
        } else {
            bonusPictureContainer.style.display = "none";
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

        gender.innerText = data.user_gender;

        let queuesHtml = ""; // Initialize queuesHtml variable

        const newWrapper = document.createElement("div");
        newWrapper.id = "swiping_kindOfGamer";
        newWrapper.style.display = "flex";
        newWrapper.style.gap = "10px";
        newWrapper.style.flexWrap = "wrap";

        
        // Generate new spans
        switch (data.user_kindOfGamer) {
            case 'Chill':
                newWrapper.innerHTML = `
                    <span class="swiping_filters_others swiping_filters_row">Aram</span>
                    <span class="swiping_filters_others swiping_filters_row">Normal Draft</span>
                `;
                break;
            case 'Competition':
                newWrapper.innerHTML = `
                    <span class="swiping_filters_others swiping_filters_row">Ranked</span>
                `;
                break;
            default:
                newWrapper.innerHTML = `
                    <span class="swiping_filters_others swiping_filters_row">Aram</span>
                    <span class="swiping_filters_others swiping_filters_row">Normal Draft</span>
                    <span class="swiping_filters_others swiping_filters_row">Ranked</span>
                `;
                break;
        }
        
        // Replace the old container with the new one
        kindOfGamer.replaceWith(newWrapper);

        shortBio.innerHTML = sanitizeHtlm(data.user_shortBio) || "No description available";

        
        receiverId.value = data.user_id;
        if (data.user_game === "League of Legends" && data.lol_role) {
            lolRankP.innerText = hasBindedAccount ? data.lol_sRank : data.lol_rank || "Unranked ";
            lolRoleP.innerText = data.lol_role;
            lolRoleP.innerText = data.lol_role || "Unknown";
            if (data.lol_noChamp === 1) {
                championContainer.style.display = 'none';
            } else {
                lolMain1Pic.src = data.lol_main1 ? `https://ddragon.leagueoflegends.com/cdn/img/champion/loading/${sanitize(data.lol_main1)}_0.jpg` : ""; // Empty src if no main
                lolMain1Pic.alt = data.lol_main1 || ""; 
                lolMain2Pic.src = data.lol_main2 ? `https://ddragon.leagueoflegends.com/cdn/img/champion/loading/${sanitize(data.lol_main2)}_0.jpg` : ""; // Empty src if no main
                lolMain2Pic.alt = data.lol_main2 || ""; 
                lolMain3Pic.src = data.lol_main3 ? `https://ddragon.leagueoflegends.com/cdn/img/champion/loading/${sanitize(data.lol_main3)}_0.jpg` : ""; // Empty src if no main
                lolMain3Pic.alt = data.lol_main3 || ""; 
                championContainer.style.display = 'flex';
            }
        } else if (data.user_game === "Valorant" && data.valorant_role) {
            // lolAccount.innerText = data.valorant_account || "Unknown Account";
            lolRankP.innerText = data.valorant_rank;
            lolRoleP.innerText = data.lol_role;
            if (data.valorant_noChamp === 1) {
                championContainer.style.display = 'none';
            } else {
                lolMain1Pic.src = data.valorant_main1 ? `public/images/valorant_champions/${sanitize(data.valorant_main1)}_icon.webp` : ""; // Empty src if no main
                lolMain1Pic.alt = data.valorant_main1 || ""; 
                lolMain2Pic.src = data.valorant_main2 ? `public/images/valorant_champions/${sanitize(data.valorant_main2)}_icon.webp` : ""; // Empty src if no main
                lolMain2Pic.alt = data.valorant_main2 || ""; 
                lolMain3Pic.src = data.valorant_main3 ? `public/images/valorant_champions/${sanitize(data.valorant_main3)}_icon.webp` : ""; // Empty src if no main
                lolMain3Pic.alt = data.valorant_main3 || ""; 
                championContainer.style.display = 'flex';
            }
        }


        if (data.user_game === "League of Legends") {
            // Ensure the rank container has relative positioning
            lolRankP.style.position = "relative";
        
            // Check if rankIcon already exists to avoid duplicates
            let existingIcon = lolRankP.querySelector(".rank-icon");
            if (!existingIcon) {
                const rankIcon = document.createElement("i");
                rankIcon.classList.add("fa-solid", "rank-icon", hasBindedAccount ? "fa-check" : "fa-xmark");
                rankIcon.style.position = "absolute";
                rankIcon.style.top = "-10px";
                rankIcon.style.right = "-10px";
                rankIcon.style.fontSize = "14px";
                rankIcon.style.color = hasBindedAccount ? "green" : "red";
        
                lolRankP.appendChild(rankIcon);
            } else {
                // Update the existing icon if already present
                existingIcon.className = `fa-solid rank-icon ${hasBindedAccount ? "fa-check" : "fa-xmark"}`;
                existingIcon.style.color = hasBindedAccount ? "green" : "red";
            }
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
        document.querySelector('.swiping_champions').style.display = 'none';
    
        // Clear image sources and alt text
        imageUser.src = "public/images/defaultprofilepicture.jpg";
        imageUser.alt = "Default profile picture";
        frameSwiping.src = "";
        badgeContainer.innerHTML = "";
        frameSwiping.style.opacity = '0';
        ErrorSpan.innerText = "";
        reportPicture.src = "";
        reportPicture.alt = "";
        reportUsername.innerText = "";
        reportDescription.value = "";
        submitReportButton.disabled = false;
        picturesRow.innerHTML = "";
        hasBindedAccount = false;
        championContainer.style.display = 'flex';
        
        // Clear text content
        sUsername.innerText = "";
        username.innerText = "";
        userAge.innerText = "";
        lolAccount.innerText = "";
        gender.innerHTML = ""; 
        kindOfGamer.innerHTML = " "; 
        shortBio.innerHTML = ""; 
        receiverId.value = "";
    
        // Clear the League of Legends data
        lolMain1Pic.src = "";
        lolMain1Pic.alt = "";
        lolMain2Pic.src = "";
        lolMain2Pic.alt = "";
        lolMain3Pic.src = "";
        lolMain3Pic.alt = "";
    }

    // Function to show the no more profiles message
    function showNoMoreProfiles() {
        document.querySelector('.swiping-ctn').style.display = 'none';
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

const fetchDdragonVersion = async () => {
    const response = await fetch("https://ddragon.leagueoflegends.com/api/versions.json");
    const versions = await response.json();
    return versions[0];
};