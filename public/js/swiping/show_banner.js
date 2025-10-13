document.addEventListener('DOMContentLoaded', function () {
    // const banner = document.getElementById('user-top-content');
    // if (banner) {

    //     banner.addEventListener("mouseenter", () => {
    //         banner.style.backgroundImage = 'url("/public/images/store/AuroraBorealis.gif")';
    //     });

    //     banner.addEventListener("mouseleave", () => {
    //         banner.style.backgroundImage = 'url("/public/images/store/AuroraBorealis.png")';
    //     });
    // }

    // document.addEventListener('DOMContentLoaded', () => {
    //     const img = document.querySelector('#profile-banner img')
        
    //     if (img) {
    //         console.log("test:", img.dataset)
    //         img.addEventListener('mouseenter', () => {
    //             img.src = img.dataset.gif
    //         })
    //         img.addEventListener('mouseleave', () => {
    //             img.src = img.dataset.static
    //         })
    //     }
    // })

const banner = document.getElementById('profile-banner');
if (banner) {
    const baseUrl = '/public/images/store/';

    // Extract the filename from the inline background image
    let currentBg = banner.style.backgroundImage;

    // If no inline style, try to get it from computed styles (CSS)
    if (!currentBg || currentBg === 'none') {
        currentBg = getComputedStyle(banner).backgroundImage;
    }

    // Extract the file name (e.g. "AuroraBorealis.png") from url("...")
    const match = currentBg.match(/\/([^/]+)\.(png|jpg|jpeg|webp|gif)/i);

    if (match) {
        const baseName = match[1]; // e.g. AuroraBorealis
        const defaultImg = `url("${baseUrl}${baseName}.png")`;
        const hoverImg = `url("${baseUrl}${baseName}.gif")`;

        // Ensure initial background is correct
        banner.style.backgroundImage = defaultImg;

        // Hover events
        banner.addEventListener("mouseenter", () => {
            banner.style.backgroundImage = hoverImg;
        });

        banner.addEventListener("mouseleave", () => {
            banner.style.backgroundImage = defaultImg;
        });
    } else {
        console.warn("Could not detect banner image filename.");
    }
}
})
