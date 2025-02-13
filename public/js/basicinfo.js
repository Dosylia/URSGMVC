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
        // Test if local storage is filled with a friend recommanding
        const invitedBy = localStorage.getItem("invitedBy");
        if (invitedBy) {
            // Create the hidden input element
            const inputInvitedBy = document.createElement("input");
            inputInvitedBy.type = "hidden";
            inputInvitedBy.name = "friendsUsername"; // This name will be used in PHP
            inputInvitedBy.value = invitedBy;
    
            // Find the "googleId" input and insert the new input right after it
            const googleIdInput = document.querySelector('input[name="googleId"]');
            if (googleIdInput) {
                googleIdInput.insertAdjacentElement("afterend", inputInvitedBy);
            }
        }

        console.log("Creating account");
    }
}


document.addEventListener("DOMContentLoaded",function(){

        btnSubmit = document.getElementById("send-button");
        btnSubmit.addEventListener("click",onClickBtnSubmit);

})