"use strict";
import CreateValorantAccount from "./Class/CreateValorantAccount.js";

let btnSubmit;

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


document.addEventListener("DOMContentLoaded",function(){

        btnSubmit = document.getElementById("send-button");
        btnSubmit.addEventListener("click",onClickBtnSubmit);

})