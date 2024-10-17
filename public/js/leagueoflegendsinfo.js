"use strict";
import CreateLoLAccount from "./Class/CreateLoLAccount.js";

let btnSubmit;
const skipSelectionCheckbox = document.getElementById('skipSelection');
const championSelection = document.getElementById('championSelection');

function onClickBtnSubmit(event)
{
 
    let spans = document.querySelectorAll("span");
    for(const span of spans)
    {
        span.remove();
    }
    let inputs = document.querySelectorAll("input");
    let createLoLaccount = new CreateLoLAccount();
    createLoLaccount.getInputs(inputs);
     
    if (skipSelectionCheckbox.value == 0) {
        if(createLoLaccount._main1Error == true || createLoLaccount._main2Error == true || createLoLaccount._main3Error == true)
            {
                event.preventDefault();
                console.log("Error champ");
            }
            else
            {
                console.log("tout est ok");
            }
    }

}


document.addEventListener("DOMContentLoaded",function(){

        btnSubmit = document.getElementById("send-button");
        btnSubmit.addEventListener("click",onClickBtnSubmit);

        skipSelectionCheckbox.value = 0;

        skipSelectionCheckbox.addEventListener('change', (e) => {
            if (e.target.checked) {
                e.target.value = 1;
                skipSelectionCheckbox.value = 1;
                console.log("Changing champion selection", skipSelectionCheckbox.value);
                championSelection.style.display = 'none';
            } else {
                e.target.value = 0;
                skipSelectionCheckbox.value = 0;
                console.log("Changing champion selection", skipSelectionCheckbox.value);
                championSelection.style.display = 'block';
            }
        });

})