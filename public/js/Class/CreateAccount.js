'use strict'

import ErrorSpan from './ErrorSpan.js'

class CreateAccount {
    constructor() {
        this._username = ''
        this._age = ''

        this._usernameError = false
        this._ageError = false
    }

    getInputs(inputs) {
        for (const input of inputs) {
            switch (input.id) {
                case 'username':
                    this.username = input
                    break
                case 'age':
                    this.age = input
                    break
                default:
                    break
            }
        }
    }

    set username(newUsername) {
        const regex = new RegExp(/^(?!.*\s{2})[a-zA-Z0-9_ ]{3,20}$/)

        if (newUsername.value == '') {
            let span = new ErrorSpan(newUsername.id, 'Cannot be empty')
            span.displaySpan()
            this._usernameError = true
        } else if (!regex.test(newUsername.value)) {
            let span = new ErrorSpan(
                newUsername.id,
                'Must respect format (3 to 20 characters, no space allowed)'
            )
            span.displaySpan()
            this._usernameError = true
        } else {
            this._username = newUsername.value
            this._usernameError = false
        }
    }

    set age(newAge) {
        if (newAge.value == '') {
            let span = new ErrorSpan(newAge.id, 'Cannot be empty')
            span.displaySpan()
            this._ageError = true
        } else if (newAge.value < 12 || newAge.value > 99) {
            let span = new ErrorSpan(
                newAge.id,
                'Your age does not respect our rules'
            )
            span.displaySpan()
            this._ageError = true
        } else if (isNaN(parseInt(newAge.value))) {
            let span = new ErrorSpan(newAge.id, 'You must give numbers only')
            span.displaySpan()
            this._ageError = true
        } else {
            this._age = newAge.value
            this._ageError = false
        }

        console.log(this._ageError)
    }
}

export default CreateAccount
