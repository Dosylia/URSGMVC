"use strict";

import ErrorSpan from "./ErrorSpan.js";

class CreateValorantAccount{

    constructor(){
        this._main1 = "";
        this._main2 = "";
        this._main3 = "";
       
        this._main1Error = false;
        this._main2Error = false;
        this._main3Error = false;
    }

    getInputs(inputs)
    {
       for(const input of inputs)
       {
        switch (input.id) {
            case "main1":
                this.main1 = input;
                break;
            case "main2":
                this.main2 = input;
                break;
            case "main3":
                 this.main3 = input;
                break;                
            default:
                break;
        }

       }
    }

    set main1(newMain1) {
        if(newMain1.value == "")
        {
            let span = new ErrorSpan(newMain1.id,"Cannot be empty");
            span.displaySpan();
            this._main1Error = true;
        } 
        else 
        {
            this._main1 = newMain1.value;
            this._main1Error = false;
        }
    }

    set main2(newMain2) {
        if(newMain2.value == "")
        {
            let span = new ErrorSpan(newMain2.id,"Cannot be empty");
            span.displaySpan();
            this._main1Error = true;
        } 
        else 
        {
            this._main2 = newMain2.value;
            this._main2Error = false;
        }
    }

    set main3(newMain3) {
        if(newMain3.value == "")
        {
            let span = new ErrorSpan(newMain3.id,"Cannot be empty");
            span.displaySpan();
            this._main3Error = true;
        } 
        else 
        {
            this._main3 = newMain3.value;
            this._main3Error = false;
        }
    }

}

export default CreateValorantAccount;