"use strict";
import CreateLoLAccount from "./Class/CreateLoLAccount.js";

let btnSubmit;

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
     
    if(createLoLaccount._accountError == true)
    {
        event.preventDefault();
        console.log("Error champ");
    }
    else
    {
        console.log("tout est ok");
    }
}


document.addEventListener("DOMContentLoaded",function(){

        btnSubmit = document.getElementById("send-button");
        btnSubmit.addEventListener("click",onClickBtnSubmit);

})