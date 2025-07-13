"use strict";
import CreateValorantAccount from "./Class/CreateValorantAccount.js";

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
    let createValorantaccount = new CreateValorantAccount();
    createValorantaccount.getInputs(inputs);

    if (skipSelectionCheckbox.value == 0) {
        if(createValorantaccount._main1Error == true || createValorantaccount._main2Error == true || createValorantaccount._main3Error == true)
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
            console.log("Changing agent selection");
            if (e.target.checked) {
                e.target.value = 1;
                championSelection.style.display = 'none';
            } else {
                e.target.value = 0;
                championSelection.style.display = 'flex';
            }
        });

})