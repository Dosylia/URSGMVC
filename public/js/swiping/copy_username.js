document.addEventListener('DOMContentLoaded', function() {
    const lolUserText = document.getElementById('lolUserText');
    const copyIcon = document.querySelector('#lolUserText .fa-copy');
  
    if (lolUserText) {
      lolUserText.addEventListener('click', function() {
        const tempTextArea = document.createElement('textarea');
        tempTextArea.value = this.innerText.trim(); 
        document.body.appendChild(tempTextArea); 
        tempTextArea.select(); 
        document.execCommand('copy'); 
        document.body.removeChild(tempTextArea); 
  
        copyIcon.classList.add('visible');
        setTimeout(() => {
          copyIcon.classList.remove('visible');
        }, 1000); 
      });
    }
  });
  