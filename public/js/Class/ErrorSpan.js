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
        const errorSpan = document.querySelector('.form-error-message');
        errorSpan.style.display = 'block';
        errorSpan.innerText = this._message;
        console.log(this._message);
    }
}
export default ErrorSpan;