// DOM ELEMENTS
deleteGoogleDiv = document.getElementById('deleteGoogle_div');
deleteRiotDiv = document.getElementById('deleteRiot_div');
deleteGoogleBtn = document.getElementById('deleteGoogle_btn');
deleteRiotBtn = document.getElementById('deleteRiot_btn');

document.addEventListener('DOMContentLoaded', function() {

    // DELETE GOOGLE ACCOUNT
    deleteGoogleBtn.addEventListener('click', function() {
        if (deleteGoogleDiv.style.display === 'block') {
            deleteGoogleDiv.style.display = 'none';
            deleteGoogleBtn.style.background = "linear-gradient(135deg, var(--main--red), #8e2730)";
        } else {
            deleteGoogleDiv.style.display = 'block';
            deleteRiotDiv.style.display = 'none';
            deleteGoogleBtn.style.background = "linear-gradient(135deg, var(--dark-ligher-grey), rgba(88, 86, 86, 0.9))";
            deleteRiotBtn.style.background = "linear-gradient(135deg, var(--main--red), #8e2730)";
        }
    });
    
    // DELETE RIOT ACCOUNT
    deleteRiotBtn.addEventListener('click', function() {
        if (deleteRiotDiv.style.display === 'block') {
            deleteRiotDiv.style.display = 'none';
            deleteRiotBtn.style.background = "linear-gradient(135deg, var(--main--red), #8e2730)";
        } else {
            deleteRiotDiv.style.display = 'block';
            deleteGoogleDiv.style.display = 'none';
            deleteRiotBtn.style.background = "linear-gradient(135deg, var(--dark-ligher-grey), rgba(88, 86, 86, 0.9))";
            deleteGoogleBtn.style.background = "linear-gradient(135deg, var(--main--red), #8e2730)";
        }
    });

    const buttonclose = document.getElementById('closeButton');

    buttonclose.addEventListener('click', (event) => {
        event.preventDefault();
        window.location.href = '/';
    });

});