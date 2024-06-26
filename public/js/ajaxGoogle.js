function handleCredentialResponse(response)
{

    function decodeJwtResponse(credential) 
    {
  
      const jwt = atob(credential.split('.')[1]);
  
  
      const payload = JSON.parse(jwt);
  
      return payload;
    }
  
  
      const responsePayload = decodeJwtResponse(response.credential);
  
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

      function fetchOrder(userData) {

        const requestOptions = {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded' // Indique que vous envoyez des données JSON
            },
            body: "googleData="+JSON.stringify(userData) // Convertit les données en JSON
          };

        fetch("index.php?action=googleTest", requestOptions)
        .then(handleResponse)
        .then(dataHandle)
        .catch(handleError);
    }

    function handleResponse(response) {
      // console.log(response.text());
      if(!response.ok) {
          throw new Error("La requete a échoué avec le statut : " + response.status);
      }


      return response.json();
  }

  function dataHandle(data) 
  {
    console.log('Données envoyées avec succès au serveur:', data);
    window.location.href = 'index.php?action=confirmMail';
  }
    
    function handleError(error) {
      console.error("Une erreur est survenue lors de la requete : "+error);
    }

    fetchOrder(userData);

}
  
  window.onload = function () 
  {
    google.accounts.id.initialize({
      client_id: "666369513537-r75otamfu9qqsnaklgqiromr7bhiehft.apps.googleusercontent.com",
      callback: handleCredentialResponse
    });
    google.accounts.id.renderButton(
      document.getElementById("buttonDiv"),
      { theme: "outline", size: "medium" }
    );
  }
function handleCredentialResponse(response)
{

    function decodeJwtResponse(credential) 
    {
  
      const jwt = atob(credential.split('.')[1]);
  
  
      const payload = JSON.parse(jwt);
  
      return payload;
    }
  
  
      const responsePayload = decodeJwtResponse(response.credential);
  
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

      function fetchOrder(userData) {

        const requestOptions = {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded' // Indique que vous envoyez des données JSON
            },
            body: "googleData="+JSON.stringify(userData) // Convertit les données en JSON
          };

        fetch("index.php?action=googleTest", requestOptions)
        .then(handleResponse)
        .then(dataHandle)
        .catch(handleError);
    }

    function handleResponse(response) {
      // console.log(response.text());
      if(!response.ok) {
          throw new Error("La requete a échoué avec le statut : " + response.status);
      }


      return response.json();
  }

  function dataHandle(data) 
  {
    console.log('Données envoyées avec succès au serveur:', data);
    window.location.href = 'index.php?action=confirmMail';
  }
    
    function handleError(error) {
        console.error("Une erreur est survenue lors de la requete : "+error);
    }

    fetchOrder(userData);

}
  
  window.onload = function () 
  {
    google.accounts.id.initialize({
      client_id: "666369513537-r75otamfu9qqsnaklgqiromr7bhiehft.apps.googleusercontent.com",
      callback: handleCredentialResponse
    });
    google.accounts.id.renderButton(
      document.getElementById("buttonDiv"),
      { theme: "outline", size: "medium" }
    );
  }