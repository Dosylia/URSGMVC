"use strict";
import CreateAccount from "./Class/CreateAccount.js";

let btnSubmit;

function onClickBtnSubmit(event)
{
 
    let spans = document.querySelectorAll("span");
    for(const span of spans)
    {
        span.remove();
    }
    let inputs = document.querySelectorAll("input");
    let createaccount = new CreateAccount();
    createaccount.getInputs(inputs);
     
    if(createaccount._usernameError == true || createaccount._ageError == true)
    {
        event.preventDefault();
        console.log("Error champ ");
    }
    else
    {
        console.log("tout ok ");
    }
}


document.addEventListener("DOMContentLoaded",function(){

        btnSubmit = document.getElementById("send-button");
        btnSubmit.addEventListener("click",onClickBtnSubmit);

})
"use strict";
import CreateAccount from "./Class/CreateAccount.js";

let btnSubmit;

function onClickBtnSubmit(event)
{
 
    let spans = document.querySelectorAll("span");
    for(const span of spans)
    {
        span.remove();
    }
    let inputs = document.querySelectorAll("input");
    let createaccount = new CreateAccount();
    createaccount.getInputs(inputs);
     
    if(createaccount._usernameError == true || createaccount._ageError == true)
    {
        event.preventDefault();
        console.log("Error champ ");
    }
    else
    {
        console.log("tout ok ");
    }
}


document.addEventListener("DOMContentLoaded",function(){

        btnSubmit = document.getElementById("send-button");
        btnSubmit.addEventListener("click",onClickBtnSubmit);

})