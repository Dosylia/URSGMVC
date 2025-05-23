const showButton = document.getElementById('signup_button');
const favDialog = document.getElementById('favDialog');
const cancelBtn = favDialog.querySelector('#cancelBtn');
const joinNowButton = document.querySelector('.mid_main_section_button');

if (showButton)
{
  showButton.addEventListener('click', () => 
  {
    openDialog();
  });
}

if (joinNowButton) 
{
    joinNowButton.addEventListener('click', () => 
    {
    openDialog();
    });
}

if (cancelBtn) 
{
    cancelBtn.addEventListener('click', () => 
    {
    favDialog.close();
    });
}

if (favDialog) 
{
    function openDialog() 
    {
    favDialog.showModal();
    }
}

window.onload = function() {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('triggerSignUp') === 'true') {
    document.getElementById('signup_button').click();
  }
};