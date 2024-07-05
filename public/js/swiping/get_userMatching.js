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

// Function to fetch matching user data
function fetchMatchingUser(userId) {
    fetch('index.php?action=getUserMatching', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `userId=${encodeURIComponent(userId)}`
    })
    .then(response => response.json())
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
    if (data.user_picture === null || data.user_picture === undefined) {
        imageUser.src = "public/images/defaultprofilepicture.jpg";
    } else {
        imageUser.src = "public/upload/" + data.user_picture;
    }

    // Fill the league of legends data if available
    if (data.lol_sUsername !== null && data.lol_sUsername !== undefined && data.lol_sUsername.trim() !== "") {
        sProfileIcon.src = "public/images/profileicon/" + data.lol_sProfileIcon;
        sUsername.innerText = data.lol_sUsername;
        sRank.innerText = data.lol_sRank;
    } else {
        document.querySelector('.box_league_account').style.display = 'none';
    }

    // Fill other user data
    username.innerText = data.user_username;
    userAge.innerText = data.user_age;
    lolAccount.innerText = data.lol_account;
    gender.innerText += " " + data.user_gender;
    kindOfGamer.innerText += " " + data.user_kindOfGamer;
    shortBio.innerText += " " + data.user_shortBio;
    receiverId.value = data.user_id;
    lolMain1P.innerText = data.lol_main1;
    lolMain2P.innerText = data.lol_main2;
    lolMain3P.innerText = data.lol_main3;
    lolRankP.innerText = data.lol_rank;
    lolRoleP.innerText = data.lol_role;
    lolMain1Pic.src = "public/images/champions/" + sanitize(data.lol_main1)+".png";
    lolMain2Pic.src = "public/images/champions/" + sanitize(data.lol_main2)+".png";
    lolMain3Pic.src = "public/images/champions/" + sanitize(data.lol_main3)+".png";
    lolRankPic.src = "public/images/ranks/" + sanitize(data.lol_rank)+".png";
    lolRolePic.src = "public/images/roles/" + sanitize(data.lol_role)+".png";
}

// Function to show the no more profiles message
function showNoMoreProfiles() {
    document.querySelector('.user_page').style.display = 'none';
    document.querySelector('.noUserToSee').style.display = 'flex';
}

function sanitize(input) {
    return input.trim().replace(/\s+/g, '');
}

// Initial call to fetch matching user data
fetchMatchingUser(userId);
