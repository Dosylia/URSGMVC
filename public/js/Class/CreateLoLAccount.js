"use strict";

import ErrorSpan from "./ErrorSpan.js";

class CreateLoLAccount{

    constructor(){
        this._account = "";
       
        this._accountError = false;
    }

    getInputs(inputs)
    {
       for(const input of inputs)
       {
        switch (input.id) {
            case "account_lol":
                this.account = input;
                break;
            default:
                break;
        }

       }
    }


    
    set account(newAccount) {
        const regex = new RegExp(/^[a-zA-Z0-9_]{3,20}$/);

        if(newAccount.value == "")
        {
            let span = new ErrorSpan(newAccount.id,"Cannot be empty");
            span.displaySpan();
            this._accountError = true;
        } 
        else if(!regex.test(newAccount.value))
        {
            let span = new ErrorSpan(newAccount.id,"Must respect format");
            span.displaySpan();
            this._accountError = true;   
        }
        else 
        {
            this._account = newAccount.value;
            this._accountError = false;
        }
    }

}

export default CreateLoLAccount;