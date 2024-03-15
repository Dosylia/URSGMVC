let inputMain1 = document.querySelector('#main1 img');
let inputMain2 = document.querySelector('#main2 img');
let inputMain3 = document.querySelector('#main3 img');

if(inputMain1 != undefined)
{
  let picture1 = inputMain1.getAttribute('alt');
  inputMain1.addEventListener('change', handleInput1)
}

if(inputMain2 != undefined)
{
  let picture2 = inputMain2.getAttribute('alt');
}

if(inputMain3 != undefined)
{
  let picture3 = inputMain3.getAttribute('alt');
}



function handleInput1()
{
  if(picture1.includes(picture2))
  {
    console.log('test');
  } 
  else if (picture1.includes(picture3))
  {
    resetInput.textContent = "";
  }

}


