const selectedChampionsContainer1 = document.getElementById('selected-champions1');
let btnPopUp1 = document.getElementById('btnMain1');
let popupMain1 = document.getElementById('popupMain1');
let inputMain1 = document.getElementById('main1');
const searchInput1 = document.getElementById("search1");
const options1 = document.querySelectorAll(".main1Ul li");


btnPopUp1.addEventListener("click", () => {
  popupMain1.classList.toggle("open");
});

popupMain1.addEventListener("click", (e) => {
  if (e.target.tagName === "IMG") {
    const liElement = e.target.closest("li");
    if (liElement) {
      const selectedText = liElement.getAttribute("value");
      inputMain1.value = selectedText;
      btnPopUp1.textContent = "";
      const imageElement = document.createElement("img");
      imageElement.src=liElement.getAttribute("data-image");
      imageElement.alt=liElement.getAttribute("value");
      btnPopUp1.style.backgroundColor = "rgb(92, 211, 79)"
      btnPopUp1.appendChild(imageElement);
      searchInput1.value = "";
      options1.forEach((option) => {
        option.style.display = "none";
      });
      popupMain1.classList.remove("open");
    }
  }
});

searchInput1.addEventListener("input", () => {
  const searchText = searchInput1.value.toLowerCase();
  if (searchInput1.value != "") {
    options1.forEach((option) => {
      const optionText = option.getAttribute("value").toLowerCase();
      if (optionText.startsWith(searchText)) {
        option.style.display = "inline-block";
      } else {
        option.style.display = "none";
      }
    });
  }
});