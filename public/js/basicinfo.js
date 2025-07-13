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
        console.log("Missing fields");
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

function skipPreferences(event) {
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
        console.log("Missing fields");
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

        // Submit the form
        const token = localStorage.getItem('masterTokenWebsite');
        const form = document.querySelector(".form_signup");
        const inputs = form.elements; 
        const dataToSend = {
            googleId: inputs.googleId.value,
            username: inputs.username.value,
            gender: inputs.gender.value,
            age: inputs.age.value,
            kindOfGamer: inputs.kindofgamer.value,
            game: inputs.game.value,
            shortBio: inputs.short_bio.value
        };

        const jsonData = JSON.stringify(dataToSend);

        fetch('/createAccountSkipPreferences', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`,
        },
        body: "param=" + encodeURIComponent(jsonData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/swiping?createdUser=true';
            } else {
                const spanError = document.createElement('span');
                spanError.className = 'form-error';
                spanError.innerHTML = "Error: " + data.message;

                document.body.prepend(spanError);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
}


document.addEventListener("DOMContentLoaded",function(){

    const btnSkipPreferences = document.getElementById("skip-preferences-btn");
    btnSubmit = document.getElementById("send-button");
    btnSubmit.addEventListener("click",onClickBtnSubmit);

    btnSkipPreferences?.addEventListener("click", (event) => {
        event.preventDefault();
        skipPreferences(event);
    });

})