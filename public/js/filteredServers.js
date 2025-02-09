document.addEventListener("DOMContentLoaded", function () {
    const filteredServers = [
        "Europe West", "North America", "Europe Nordic & East", "Brazil", 
        "Latin America North", "Latin America South", "Oceania", 
        "Russia", "Turkey", "Japan", "Korea"
    ];

    // Get stored selected servers from the hidden input
    let selectedServers = JSON.parse(document.getElementById("filteredServers").value || "[]");

    const container = document.getElementById("server-container");
    const hiddenInput = document.getElementById("filteredServers");

    function updateHiddenInput() {
        hiddenInput.value = JSON.stringify(selectedServers);
    }

    filteredServers.forEach(server => {
        const btn = document.createElement("button");
        btn.textContent = server;
        btn.classList.add("server-btn");

        if (selectedServers.includes(server)) {
            btn.classList.add("selected");
        } else {
            btn.classList.add("unselected");
        }

        btn.addEventListener("click", function () {
            event.preventDefault();
            if (selectedServers.includes(server)) {
                selectedServers = selectedServers.filter(s => s !== server);
                btn.classList.remove("selected");
                btn.classList.add("unselected");
            } else {
                selectedServers.push(server);
                btn.classList.remove("unselected");
                btn.classList.add("selected");
            }
            updateHiddenInput();
        });

        container.appendChild(btn);
    });

    // Ensure hidden input is initialized correctly
    updateHiddenInput();
});

