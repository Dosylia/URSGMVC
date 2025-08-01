
function handleCredentialResponse(response) {
    if (!response.credential) {
        console.error("No credential found in the response.");
        return;
    }

    function parseJwt(token) {
        try {
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map(c => 
                '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)
            ).join(''));
            return JSON.parse(jsonPayload);
        } catch (error) {
            console.error("Error parsing JWT:", error);
            return null;
        }
    }

    const responsePayload = parseJwt(response.credential);
    if (!responsePayload) {
        console.error("Failed to parse response payload.");
        return;
    }

    const userData = {
        googleId: responsePayload.sub,
        fullName: responsePayload.name,
        givenName: responsePayload.given_name,
        familyName: responsePayload.family_name,
        imageUrl: responsePayload.picture,
        email: responsePayload.email,
        idToken: response.credential,
    };

    if (!userData.googleId || !userData.email) {
        console.error("Incomplete user data:", userData);
        return;
    }

    async function sendUserData(userData) {
        console.log("User data:", userData);
        await fetch('/googleTest', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: "googleData=" + encodeURIComponent(JSON.stringify(userData))
        })
        .then(response => response.json())
        .then(data => {
            if (data.message === "Success") {
                const token = data.masterTokenWebsite;
                const adminToken = data.adminToken;
                // Store token in local storage
                localStorage.setItem('masterTokenWebsite', token);
                if (adminToken) {
                    localStorage.setItem('adminToken', adminToken);
                }
                console.log("Server response indicates success:", data);
                trackLogin();
                window.location.href = '/signup';
            } else {
                const spanError = document.createElement('span');
                spanError.className = 'form-error';
                spanError.innerHTML = "Error: " + data.message;

                document.body.prepend(spanError);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
    }
    
    sendUserData(userData);
}
