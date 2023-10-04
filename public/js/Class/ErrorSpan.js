"use strict";

class ErrorSpan
{
    constructor(id,message)
    {
        this._id = id;
        this._message = message; 
    }
    
    displaySpan()
    {
        let span = document.createElement("span");
        span.classList.add("form-error");
        span.textContent = this._message;
        
        document.getElementById(this._id).after(span);
    }
}
export default ErrorSpan;