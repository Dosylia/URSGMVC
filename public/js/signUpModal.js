
document.addEventListener("DOMContentLoaded", function() {
    signUpBtns = document.querySelectorAll('.openSignUp');
    modal = document.getElementById('signup-modal');
    overlay = document.getElementById('overlay');
    closeSignUpModal = document.getElementById('close-modal');

    signUpBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            modal.classList.remove('signup-modal-hidden');
            overlay.style.display = "block";
        });
    });

    closeSignUpModal.addEventListener('click', function() {
        modal.classList.add('signup-modal-hidden');
        overlay.style.display = "none";
    });

});

window.onload = function() {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('triggerSignUp') === 'true') {
      modal.classList.remove('signup-modal-hidden');
      overlay.style.display = "block";
  }
};