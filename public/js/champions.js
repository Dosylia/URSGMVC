// CHAMPION 1
const selectedChampionsContainer1 = document.getElementById('selected-champions1');
let btnPopUp1 = document.getElementById('btnMain1');
let popupMain1 = document.getElementById('popupMain1');
let inputMain1 = document.getElementById('main1');
const searchInput1 = document.getElementById("search1");
const options1 = document.querySelectorAll(".main1Ul li");

const selectedChampionsContainer2 = document.getElementById('selected-champions2');
let btnPopUp2 = document.getElementById('btnMain2');
let popupMain2 = document.getElementById('popupMain2');
let inputMain2 = document.getElementById('main2');
const searchInput2 = document.getElementById("search2");
const options2 = document.querySelectorAll(".main2Ul li");

const selectedChampionsContainer3 = document.getElementById('selected-champions3');
let btnPopUp3 = document.getElementById('btnMain3');
let popupMain3 = document.getElementById('popupMain3');
let inputMain3 = document.getElementById('main3');
const searchInput3 = document.getElementById("search3");
const options3 = document.querySelectorAll(".main3Ul li");

document.addEventListener('DOMContentLoaded', function() {


  btnPopUp1.addEventListener("click", () => {
    popupMain1.classList.toggle("open");
  });


popupMain1.addEventListener("click", (e) => {
  if (e.target.tagName === "IMG") {
    const liElement = e.target.closest("li");
    if (liElement) {
      const selectedText = liElement.getAttribute("value");
      inputMain1.value = selectedText;
        if (inputMain1.value === inputMain2.value || inputMain1.value === inputMain3.value) 
        {
            inputMain1.value = "";
        }
        else 
        {
          btnPopUp1.textContent = "";
          const imageElement = document.createElement("img");
          imageElement.src=liElement.getAttribute("data-image");
          imageElement.alt=liElement.getAttribute("value");
          btnPopUp1.style.backgroundColor = "rgb(92, 211, 79)"
          btnPopUp1.appendChild(imageElement);
          searchInput1.value = "";
          searchInput1.focus();
          options1.forEach((option) => {
            option.style.display = "none";
          });
          popupMain1.classList.remove("open");
        }
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



// CHAMPION 2
btnPopUp2.addEventListener("click", () => {
  popupMain2.classList.toggle("open");
});

popupMain2.addEventListener("click", (e) => {
  if (e.target.tagName === "IMG") {
    const liElement = e.target.closest("li");
    if (liElement) {
      const selectedText = liElement.getAttribute("value");
      inputMain2.value = selectedText;
      if (inputMain2.value === inputMain1.value || inputMain2.value === inputMain3.value) 
      {
          inputMain2.value = "";
      }
      else 
      {
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


// CHAMPION 3
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
      if (inputMain3.value === inputMain1.value || inputMain3.value === inputMain2.value) 
      {
          inputMain3.value = "";
      }
      else 
      {
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

});