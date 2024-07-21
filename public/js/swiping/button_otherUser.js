const hiddenP = document.getElementById('hidden_p');
const imgDiscord = document.getElementById('discord_picture');

if (imgDiscord !== null && imgDiscord !== undefined) {
  imgDiscord.addEventListener('click', () => {
    if (hiddenP.style.display === "none" || hiddenP.style.display === "") {
      hiddenP.style.display = "block";
    } else {
      hiddenP.style.display = "none";
    }
  });
}
