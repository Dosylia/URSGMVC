let archetypes = {
    shotcaller: 0,
    yasuo: 0,
    enchanter: 0,
    jungle: 0,
    otp: 0,
    aram: 0,
  };

  let currentChart = null;
  let isFinalResult = false;
  
  const descriptions = {
    shotcaller: "🧠 The Shotcaller Tyrant – Your teammates are pawns. You mute pings *except* your own.",
    yasuo: "🌀 The 0/10 Yasuo – You lock him in every game. No regrets. No results.",
    enchanter: "💖 The Enchanter Addict – You only play support because *carrying is too stressful*.",
    jungle: "🌲 The Jungle Therapist – Your lanes flame, you gank anyway. You're the unpaid mental health coach.",
    otp: "🔁 The One-Trick Pony – You don’t *play* League. You play ONE champion. Repeatedly.",
    aram: "🍕 The ARAM Enjoyer – Ranked? Too tryhard. You’re here to vibe with randoms.",
  };
  
  const quizContainer = document.getElementById('quiz-container');
  const resultContainer = document.getElementById('result');
  const resultTitle = document.getElementById('result-title');
  const resultDesc = document.getElementById('result-description');
  const canvas = document.getElementById('result-chart');
  const createAccountDiv = document.getElementById('createURSGAccount');
  
  const backBtn = document.getElementById('backBtn');
  const nextBtn = document.getElementById('nextBtn');
  const confirmBtn = document.getElementById('confirmBtn');
  const nav = document.getElementById('navigation');
  
  let questions = [];
  let currentIndex = 0;
  let selectedAnswers = {}; // question index -> selected button
  
  function fetchQuestions() {
    fetch('/questions.html')
      .then(response => response.text())
      .then(html => {
        quizContainer.innerHTML = html;
        questions = quizContainer.querySelectorAll('.question');
        questions.forEach(q => q.style.display = 'none');
        if (questions.length > 0) {
          showQuestion(currentIndex);
          updateProgressBar(0);
          nav.style.display = 'flex';
        } else {
            nav.style.display = 'none';
        }
      })
      .catch(error => console.error('Error loading questions:', error));
  }

  function displayCreateAccount() {
    createAccountDiv.classList.remove('hidden');
  }
  
  function showQuestion(index) {
    questions.forEach((q, i) => q.style.display = i === index ? 'block' : 'none');
    updateNavButtons();
    // Restore selection if user already picked something
    const buttons = questions[index].querySelectorAll('button');
    buttons.forEach(btn => {
      btn.classList.remove('selected');
      if (selectedAnswers[index] === btn.dataset.value) {
        btn.classList.add('selected');
      }
      btn.onclick = () => {
        selectedAnswers[index] = btn.dataset.value;
        buttons.forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
      };
    });

    if (!isFinalResult) {
      updateProgressBar();
    }
  }
  
  function updateNavButtons() {
    backBtn.disabled = currentIndex === 0;
    nextBtn.classList.toggle('hidden', currentIndex >= questions.length - 1);
    confirmBtn.classList.toggle('hidden', currentIndex < questions.length - 1);
  }

  function updateProgressBar(progress = null) {
    const progressBar = document.getElementById('progressBar');
    if (progressBar) {
      const progressValue = progress !== null ? progress : ((currentIndex + 1) / questions.length) * 100;
      progressBar.style.width = `${progressValue}%`;
    }
  }

  function clearChart() {
    if (currentChart) {
      currentChart.destroy();
      currentChart = null;
    }
  }

  
function resetQuiz() {
  selectedAnswers = {};
  currentIndex = 0;
  
  resultContainer.classList.add('hidden');
  nav.style.display = 'flex';
  quizContainer.classList.remove('hidden');
  document.getElementById('resetBtn').classList.add('hidden');
  createAccountDiv.classList.add('hidden');
  
  questions.forEach(q => q.style.display = 'none');
  showQuestion(currentIndex);

  // Reset progress bar
  updateProgressBar(0);
  clearChart();
  }
  
  function showResult(loadingOldResult = false, result = null) {
    if (!loadingOldResult && !result) {
      // Only reset if NOT loading old result
      Object.keys(archetypes).forEach(k => archetypes[k] = 0);
  
      Object.values(selectedAnswers).forEach(val => {
        val.split(',').forEach(type => {
          archetypes[type.trim()]++;
        });
      });
  
      localStorage.setItem('personalityTestResult', JSON.stringify(archetypes));
    } else if (result) {
      archetypes = result;
    }

    isFinalResult = true;
    updateProgressBar(100);
    const sorted = Object.entries(archetypes).sort((a, b) => b[1] - a[1]);
    const top = sorted[0][0];
  
    resultTitle.textContent = `Your LoL Personality:`;
    resultDesc.innerHTML = descriptions[top];
    
    drawChart(sorted);
    resultContainer.classList.remove('hidden');
    quizContainer.classList.add('hidden');
    nav.style.display = 'none';
    document.getElementById('resetBtn').classList.remove('hidden');
  }
  
  function drawChart(data) {
    const ctx = canvas.getContext('2d');
    if (currentChart) currentChart.destroy();
  
    const labels = data.map(item => item[0]);
    const values = data.map(item => item[1]);
    const colors = ['rgba(248, 113, 113, 0.6)', 'rgba(96, 165, 250, 0.6)', 'rgba(167, 139, 250, 0.6)', 'rgba(74, 222, 128, 0.6)', 'rgba(251, 191, 36, 0.6)', 'rgba(56, 189, 248, 0.6)'];
  
    currentChart = new Chart(ctx, {
      type: 'radar',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: colors,
          borderColor: colors.map(c => c.replace('0.6', '1')),
          borderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6
        }]
      },
      options: {
        responsive: true,
        scales: {
          r: {
            beginAtZero: true,
            grid: { color: 'rgba(0, 0, 0, 0.1)' },
            ticks: { display: false, backdropColor: 'transparent' },
            pointLabels: { font: { size: 14 } }
          }
        },
        plugins: {
          legend: { position: 'bottom', labels: { boxWidth: 20, padding: 15 } },
          tooltip: { enabled: false }
        },
        elements: {
          line: { tension: 0.4 }
        }
      }
    });
  }

  function getOldResult(userId) {
    const token = localStorage.getItem('masterTokenWebsite');
    fetch('/getPersonalityTestResult', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Authorization': `Bearer ${token}`,
      },
        body: `userId=${encodeURIComponent(parseInt(userId))}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        if (!data.result) {
          console.log('No previous result found for this user.');
          return;
        } else {
          const result = data.result;
          Object.keys(archetypes).forEach(key => {
            archetypes[key] = result[key] || 0;
            console.log('Result for achetype:', key, 'is', archetypes[key]);
          });
          showResult(true);
        }
      } else {
        console.error('Error fetching old result:', data.error);
      }
    })
    .catch(error => console.error('Error fetching old result:', error));
  }

  function savePersonalityTestResult(userId) {
    const token = localStorage.getItem('masterTokenWebsite');
    const result = Object.entries(archetypes).reduce((acc, [key, value]) => {
      acc[key] = value;
      return acc;
    }, {});
  
    // Include userId inside the body
    const bodyData = {
      userId: userId,
      result: result,
    };
  
    fetch('/savePersonalityTestResult', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify(bodyData),
    })
    .then(response => response.json())
    .then(data => console.log('Result saved:', data))
    .catch(error => console.error('Error saving result:', error));
  }
  
  document.addEventListener("DOMContentLoaded", function () {
    fetchQuestions();

    if (typeof userId !== 'undefined' && userId !== null) {
      getOldResult(userId);
    } else {
      const savedResult = localStorage.getItem('personalityTestResult');
      
      if (savedResult) {
        const result = JSON.parse(savedResult);
        displayCreateAccount();
        showResult(true, result); 
      }
    }
  
    backBtn.addEventListener('click', () => {
      if (currentIndex > 0) {
        currentIndex--;
        showQuestion(currentIndex);
      }
    });
  
    nextBtn.addEventListener('click', () => {
      if (currentIndex < questions.length - 1) {
        currentIndex++;
        showQuestion(currentIndex);
      }
    });

    document.getElementById('resetBtn').onclick = resetQuiz;
  
    confirmBtn.addEventListener('click', () => {
      if (Object.keys(selectedAnswers).length === questions.length) {
        showResult();
        if (typeof userId !== 'undefined' && userId !== null) {
          savePersonalityTestResult(userId);
        } else {
          // save to local storage if userId is not available
          displayCreateAccount();
          localStorage.setItem('personalityTestResult', JSON.stringify(archetypes));
        }
      } else {
        alert('Please answer all questions before submitting.');
      }
    });
  });
  