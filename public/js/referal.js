// Function to get query parameters from the URL
function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

document.addEventListener("DOMContentLoaded",function(){

    const invitedBy = getQueryParam("invitedBy");

    if (invitedBy) {
        // Store it in localStorage
        localStorage.setItem("invitedBy", invitedBy);
    }

})