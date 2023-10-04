const selectedChampionsContainer3 = document.getElementById('selected-champions3');
let btnPopUp3 = document.getElementById('btnMain3');
let popupMain3 = document.getElementById('popupMain3');
let inputMain3 = document.getElementById('main3');
const searchInput3 = document.getElementById("search3");
const options3 = document.querySelectorAll(".main1Ul li");


btnPopUp3.addEventListener("click", () => {
  popupMain3.classList.toggle("open");
});

popupMain3.addEventListener("click", (e) => {
  if (e.target.tagName === "IMG") {
    const liElement = e.target.closest("li");
    if (liElement) {
      const selectedText = liElement.getAttribute("value");
      inputMain3.value = selectedText;
      btnPopUp3.textContent = "";
      const imageElement = document.createElement("img");
      imageElement.src=liElement.getAttribute("data-image");
      imageElement.alt=liElement.getAttribute("value");
      btnPopUp3.style.backgroundColor = "rgb(92, 211, 79)"
      btnPopUp3.appendChild(imageElement);
      searchInput3.value = "";
      options3.forEach((option) => {
        option.style.display = "none";
      });
      popupMain3.classList.remove("open");
    }
  }
});

searchInput3.addEventListener("input", () => {
  const searchText = searchInput3.value.toLowerCase();
  options3.forEach((option) => {
    const optionText = option.getAttribute("value").toLowerCase();
    if (optionText.startsWith(searchText)) {
      option.style.display = "inline-block";
    } else {
      option.style.display = "none";
    }
  });
});