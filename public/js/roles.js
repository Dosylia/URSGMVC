const selectedRoles = [
    { name: 'Support', image: 'images/roles/support.png' },
    { name: 'AD Carry', image: 'images/roles/adcarry.png' },
    { name: 'Mid laner', image: 'images/roles/midlaner.png' },
    { name: 'Jungler', image: 'images/roles/jungler.png' },
    { name: 'Top laner', image: 'images/roles/toplaner.png' },
    { name: 'Fill', image: 'images/roles/fill.png' },
  ];
  
  const selectedRolesContainer = document.getElementById('selected-roles');
  
  document.getElementById('role_lol').addEventListener('change', (event) => {
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