let inputMain1 = document.querySelector('#main1 img');
let inputMain2 = document.querySelector('#main2 img');
let inputMain3 = document.querySelector('#main3 img');

let picture1 = inputMain1 ? inputMain1.getAttribute('alt') : '';
let picture2 = inputMain2 ? inputMain2.getAttribute('alt') : '';
let picture3 = inputMain3 ? inputMain3.getAttribute('alt') : '';

if (inputMain1) {
  inputMain1.addEventListener('load', handleInput1);
}

function handleInput1() {
  if (picture1.includes(picture2)) {
    console.log('test');
  } else if (picture1.includes(picture3)) {
    console.log('picture1 includes picture3');
  }
}

