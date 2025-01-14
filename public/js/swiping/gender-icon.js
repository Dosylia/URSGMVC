document.addEventListener("DOMContentLoaded", function() {
    // Check if dark mode is enabled
    const isDarkMode = document.body.classList.contains('dark-mode');

    // Define image suffix based on dark mode
    const imageSuffix = isDarkMode ? "-white" : "";

    // Select all gender-related images
    const genderImages = document.querySelectorAll('.about-users-box img');

    // Loop through each image and update the source based on gender and dark mode
    genderImages.forEach((img) => {
        const altText = img.alt.toLowerCase().replace(" ", "-"); // Convert alt text to lowercase and replace spaces with hyphens

        // Log altText for debugging
        console.log('Processing image:', altText);

        // Only apply suffix to male, female, or non-binary images
        if (altText !== "Trans Man" && altText !== "Trans Woman" && altText !== "trans") {
            const newSrc = `public/images/${altText}${imageSuffix}.png`;
            console.log('New image source:', newSrc);
            img.src = newSrc;
        }
    });
});