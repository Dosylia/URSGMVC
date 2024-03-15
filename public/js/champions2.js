const selectedChampionsContainer2 = document.getElementById('selected-champions2');
let btnPopUp2 = document.getElementById('btnMain2');
let popupMain2 = document.getElementById('popupMain2');
let inputMain2 = document.getElementById('main2');
const searchInput2 = document.getElementById("search2");
const options2 = document.querySelectorAll(".main1Ul li");


btnPopUp2.addEventListener("click", () => {
  popupMain2.classList.toggle("open");
});

popupMain2.addEventListener("click", (e) => {
  if (e.target.tagName === "IMG") {
    const liElement = e.target.closest("li");
    if (liElement) {
      const selectedText = liElement.getAttribute("value");
      inputMain2.value = selectedText;
      btnPopUp2.textContent = "";
      const imageElement = document.createElement("img");
      imageElement.src=liElement.getAttribute("data-image");
      imageElement.alt=liElement.getAttribute("value");
      btnPopUp2.style.backgroundColor = "rgb(92, 211, 79)"
      btnPopUp2.appendChild(imageElement);
      searchInput2.value = "";
      options2.forEach((option) => {
        option.style.display = "none";
      });
      popupMain2.classList.remove("open");
    }
  }
});

searchInput2.addEventListener("input", () => {
  const searchText = searchInput2.value.toLowerCase();
  options2.forEach((option) => {
    const optionText = option.getAttribute("value").toLowerCase();
    if (optionText.startsWith(searchText)) {
      option.style.display = "inline-block";
    } else {
      option.style.display = "none";
    }
  });
});
const selectedChampionsContainer2 = document.getElementById('selected-champions2');
let btnPopUp2 = document.getElementById('btnMain2');
let popupMain2 = document.getElementById('popupMain2');
let inputMain2 = document.getElementById('main2');
const searchInput2 = document.getElementById("search2");
const options2 = document.querySelectorAll(".main1Ul li");


btnPopUp2.addEventListener("click", () => {
  popupMain2.classList.toggle("open");
});

popupMain2.addEventListener("click", (e) => {
  if (e.target.tagName === "IMG") {
    const liElement = e.target.closest("li");
    if (liElement) {
      const selectedText = liElement.getAttribute("value");
      inputMain2.value = selectedText;
      btnPopUp2.textContent = "";
      const imageElement = document.createElement("img");
      imageElement.src=liElement.getAttribute("data-image");
      imageElement.alt=liElement.getAttribute("value");
      btnPopUp2.style.backgroundColor = "rgb(92, 211, 79)"
      btnPopUp2.appendChild(imageElement);
      searchInput2.value = "";
      options2.forEach((option) => {
        option.style.display = "none";
      });
      popupMain2.classList.remove("open");
    }
  }
});

searchInput2.addEventListener("input", () => {
  const searchText = searchInput2.value.toLowerCase();
  options2.forEach((option) => {
    const optionText = option.getAttribute("value").toLowerCase();
    if (optionText.startsWith(searchText)) {
      option.style.display = "inline-block";
    } else {
      option.style.display = "none";
    }
  });
});