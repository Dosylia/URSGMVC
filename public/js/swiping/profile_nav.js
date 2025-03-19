document.addEventListener("DOMContentLoaded", () => {
    const buttons = document.querySelectorAll("#user-bottom-nav button");
    const sections = document.querySelectorAll("#aboutme-container, #pictures-container, #socials-container, #requests-container");
    const loadingIndicator = document.getElementById("loading-indicator");

    // Initially hide all sections
    sections.forEach(section => section.style.display = "none");

    // Show loading indicator
    loadingIndicator.style.display = "block";

    // Simulate content loading (replace with actual loading logic if needed)
    setTimeout(() => {
        loadingIndicator.style.display = "none"; // Hide loading indicator
        document.getElementById("aboutme-container").style.display = "flex"; // Show default section
    }, 1000); // Adjust timing if necessary

    buttons.forEach(button => {
        button.addEventListener("click", () => {
            // Remove 'focused' class from all buttons
            buttons.forEach(btn => btn.classList.remove("focused"));
            
            // Add 'focused' class to the clicked button
            button.classList.add("focused");

            // Hide all sections
            sections.forEach(section => section.style.display = "none");

            // Show the corresponding section
            const sectionId = button.id.replace("-btn", "-container");
            document.getElementById(sectionId).style.display = "flex";
        });
    });
});

