const archetypes = {
    shotcaller: 0,
    yasuo: 0,
    enchanter: 0,
    jungle: 0,
    otp: 0,
    aram: 0,
  };
  
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
  
  const backBtn = document.getElementById('backBtn');
  const nextBtn = document.getElementById('nextBtn');
  const confirmBtn = document.getElementById('confirmBtn');
  const nav = document.getElementById('navigation');
  console.log(nav);
  
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
          nav.classList.remove('hidden');
        } else {
            nav.classList.add('hidden');
        }
      })
      .catch(error => console.error('Error loading questions:', error));
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
  }
  
  function updateNavButtons() {
    backBtn.disabled = currentIndex === 0;
    nextBtn.classList.toggle('hidden', currentIndex >= questions.length - 1);
    confirmBtn.classList.toggle('hidden', currentIndex < questions.length - 1);
  }
  
  function showResult() {
    // Reset archetype scores
    Object.keys(archetypes).forEach(k => archetypes[k] = 0);
  
    Object.values(selectedAnswers).forEach(val => {
      val.split(',').forEach(type => {
        archetypes[type.trim()]++;
      });
    });
  
    const sorted = Object.entries(archetypes).sort((a, b) => b[1] - a[1]);
    const top = sorted[0][0];
  
    resultTitle.textContent = `Your LoL Personality:`;
    resultDesc.innerHTML = descriptions[top];
  
    drawChart(sorted);
    resultContainer.classList.remove('hidden');
    quizContainer.classList.add('hidden');
    nav.classList.add('hidden');
  }
  
  function drawChart(data) {
    const ctx = canvas.getContext('2d');
    const labels = data.map(item => item[0]);
    const values = data.map(item => item[1]);
    const colors = ['#f87171','#60a5fa','#a78bfa','#4ade80','#fbbf24','#38bdf8'];
  
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: colors,
          borderWidth: 1
        }]
      },
      options: {
        responsive: false,
        plugins: {
          legend: { position: 'bottom' },
          tooltip: { enabled: true }
        }
      }
    });
  }
  
  document.addEventListener("DOMContentLoaded", function () {
    fetchQuestions();
  
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
  
    confirmBtn.addEventListener('click', () => {
      if (Object.keys(selectedAnswers).length === questions.length) {
        showResult();
      } else {
        alert('Please answer all questions before submitting.');
      }
    });
  });
  