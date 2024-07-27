function handleCredentialResponse(response) {
  function decodeJwtResponse(credential) {
      try {
          const jwt = atob(credential.split('.')[1]);
          return JSON.parse(jwt);
      } catch (error) {
          console.error("Failed to decode JWT:", error);
          return null;
      }
  }

  const responsePayload = decodeJwtResponse(response.credential);
  if (!responsePayload) {
      console.error("Invalid JWT response");
      return;
  }

  const googleId = responsePayload.sub;
  const fullName = responsePayload.name;
  const givenName = responsePayload.given_name;
  const familyName = responsePayload.family_name;
  const imageUrl = responsePayload.picture;
  const email = responsePayload.email;

  console.log("ID: " + googleId);
  console.log('Full Name: ' + fullName);
  console.log('Given Name: ' + givenName);
  console.log('Family Name: ' + familyName);
  console.log("Image URL: " + imageUrl);
  console.log("Email: " + email);

  const userData = {
      googleId: googleId,
      fullName: fullName,
      givenName: givenName,
      familyName: familyName,
      imageUrl: imageUrl,
      email: email
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

window.onload = function () {
    google.accounts.id.initialize({
        client_id: "666369513537-r75otamfu9qqsnaklgqiromr7bhiehft.apps.googleusercontent.com",
        callback: handleCredentialResponse,
        ux_mode: 'popup', 
        auto_select: true,
        use_fedcm_for_prompt: true  // Enable FedCM
    });
    google.accounts.id.renderButton(
        document.getElementById("buttonDiv"),
        { theme: "outline", size: "medium" }
    );
}
