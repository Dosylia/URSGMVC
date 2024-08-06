function handleCredentialResponse(response) {

    function parseJwt(token) {
        var base64Url = token.split('.')[1];
        var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        var jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));

        return JSON.parse(jsonPayload);
    }

    let responsePayload = parseJwt(response.credential);

    const userData = {
        googleId: responsePayload.sub,
        fullName: responsePayload.name,
        givenName: responsePayload.given_name,
        familyName: responsePayload.family_name,
        imageUrl: responsePayload.picture,
        email: responsePayload.email
    };

    async function fetchOrder(userData) {
        const requestOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: "googleData=" + encodeURIComponent(JSON.stringify(userData))
        };
  
        try {
            const response = await fetch("/googleTest", requestOptions);
            await handleResponse(response);
        } catch (error) {
            handleError(error);
        }
    }
  
    async function handleResponse(response) {
        if (!response.ok) {
            throw new Error("Request failed with status: " + response.status);
        }
        const data = await response.json();
        dataHandle(data);
    }
  
    function dataHandle(data) {
        console.log('Data successfully sent to the server:', data);
        window.location.href = '/confirmMail';
    }
  
    function handleError(error) {
        console.error("An error occurred during the request:", error);
    }
  
    fetchOrder(userData);
}

