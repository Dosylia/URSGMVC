document.addEventListener("DOMContentLoaded", () => {
    const buttons = document.querySelectorAll("#user-bottom-nav button");
    const sections = document.querySelectorAll("#user-bottom-container > div:not(:first-child)"); // Excludes nav and hr

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

    // Initialize: Show only the first section by default
    sections.forEach(section => section.style.display = "none");
    document.getElementById("aboutme-container").style.display = "flex";
});
