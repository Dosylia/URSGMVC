document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchInput");
    const searchInputId = document.getElementById("searchInputId");
    const genderFilter = document.getElementById("genderFilter");
    const gamerTypeFilter = document.getElementById("gamerTypeFilter");
    const userTableBody = document.getElementById("userTableBody");
    const rows = Array.from(userTableBody.querySelectorAll("tr"));

    function filterTable() {
        const searchValue = searchInput.value.toLowerCase().trim();
        let searchValueId = searchInputId.value.trim() ? parseInt(searchInputId.value.trim(), 10) : null;
        if (isNaN(searchValueId)) {
            searchValueId = null;
        }
        const genderValue = genderFilter.value;
        const gamerTypeValue = gamerTypeFilter.value;

        rows.forEach((row) => {
            const username = row.querySelector(".username").textContent.toLowerCase().trim();
            const userIdTable = parseInt(row.querySelector(".userIdTable").textContent.trim(), 10);
            const gender = row.querySelector(".gender").textContent.trim();
            const gamerType = row.querySelector(".gamer-type").textContent.trim();

            const matchesSearch = username.includes(searchValue);
            const matchesSearchId = searchValueId !== null && userIdTable === searchValueId;
            const matchesGender = !genderValue || gender === genderValue;
            const matchesGamerType = !gamerTypeValue || gamerType === gamerTypeValue;

            if (searchValueId !== null) {
                row.style.display = matchesSearchId && matchesGender && matchesGamerType ? "" : "none";
            } else {
                row.style.display = matchesSearch && matchesGender && matchesGamerType ? "" : "none";
            }
        });
    }

    searchInput.addEventListener("input", filterTable);
    searchInputId.addEventListener("input", filterTable);
    genderFilter.addEventListener("change", filterTable);
    gamerTypeFilter.addEventListener("change", filterTable);
});
