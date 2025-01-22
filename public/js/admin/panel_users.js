document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchInput");
    const genderFilter = document.getElementById("genderFilter");
    const gamerTypeFilter = document.getElementById("gamerTypeFilter");
    const userTableBody = document.getElementById("userTableBody");
    const rows = Array.from(userTableBody.querySelectorAll("tr"));

    function filterTable() {
        const searchValue = searchInput.value.toLowerCase();
        const genderValue = genderFilter.value;
        const gamerTypeValue = gamerTypeFilter.value;

        rows.forEach((row) => {
            const username = row.querySelector(".username").textContent.toLowerCase();
            const gender = row.querySelector(".gender").textContent;
            const gamerType = row.querySelector(".gamer-type").textContent;

            const matchesSearch = username.includes(searchValue);
            const matchesGender = !genderValue || gender === genderValue;
            const matchesGamerType = !gamerTypeValue || gamerType === gamerTypeValue;

            if (matchesSearch && matchesGender && matchesGamerType) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }

    searchInput.addEventListener("input", filterTable);
    genderFilter.addEventListener("change", filterTable);
    gamerTypeFilter.addEventListener("change", filterTable);
});
