const selectedRoles = [
    { name: 'Controller', image: 'images/valorant_roles/support.png' },
    { name: 'Duelist', image: 'images/valorant_roles/adcarry.png' },
    { name: 'Initiator', image: 'images/valorant_roles/midlaner.png' },
    { name: 'Sentinel', image: 'images/valorant_roles/jungle.png' },
    { name: 'Fill', image: 'images/valorant_roles/fill.png' },
  ];
  
  const selectedRolesContainer = document.getElementById('selected-roles-valorant');
  
  document.getElementById('role_valorant').addEventListener('change', (event) => {
    const selectedOption = event.target.selectedOptions[0];
    const roleName = selectedOption.value;
    const roleImage = selectedOption.getAttribute('data-image');
  
    renderSelectedRole(roleName, roleImage);
  });
  
  function renderSelectedRole(roleName, roleImage) {
    selectedRolesContainer.innerHTML = '';
  
    const roleImageElement = document.createElement('img');
    roleImageElement.src = roleImage;
  
    selectedRolesContainer.appendChild(roleImageElement);
  }