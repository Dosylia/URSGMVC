const fetchDdragonVersion = async () => {
    try {
        const response = await fetch("https://ddragon.leagueoflegends.com/api/versions.json");
        const versions = await response.json();
        return versions[0]; // Latest version
    } catch (error) {
        console.error("Failed to fetch DDragon version:", error);
        return "latest"; // Fallback
    }
};

const updateProfileIcon = async (icon) => {
    try {
        const version = await fetchDdragonVersion();
        const imgElement = document.getElementById("profilepicture_lol");
        if (imgElement) {
            imgElement.src = `http://ddragon.leagueoflegends.com/cdn/${version}/img/profileicon/${icon}.png`;
        } else {
            console.error("Image element with id 'profilepicture_lol' not found.");
        }
    } catch (error) {
        console.error("Error updating profile icon:", error);
    }
};

document.addEventListener("DOMContentLoaded", () => {
    const imgElement = document.getElementById("profilepicture_lol");
    if (imgElement) {
        const lolProfileIconId = imgElement.getAttribute("data-icon-id");
        updateProfileIcon(lolProfileIconId);
    } else {
        console.error("Image element with id 'profilepicture_lol' not found on DOMContentLoaded.");
    }
});
