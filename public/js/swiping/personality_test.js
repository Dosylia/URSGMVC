let archetypes = {
    toxic: 0,
    egirl: 0,
    boosted: 0,
    TheBaus: 0,
    cryBabyAdc: 0,
    theSmurf: 0,
  };

  let currentChart = null;
  let isFinalResult = false;
  
  const descriptions = {
    toxic: {
      main: "🔥 The Keyboard Warrior",
      core: "You peaked at silver 3 in Season 8 and never forgave the game.",
      modifiers: {
        egirl: "Mute all? Never. Flame all? Always... but somehow still getting friend requests between deaths.",
        TheBaus: "Your 1v5 attempts are just elaborate inting with extra steps.",
        cryBabyAdc: "Toxic and emotional - a true ADC main.",
        boosted: "You blame teammates while being carried harder than a shopping cart."
      }
    },
    egirl: {
      main: "💅 The Egirl",
      core: "You roam the Rift spreading chaos and charm in equal measure. Who needs skill when you've got friends in high elo!",
      modifiers: {
        boosted: "Your mechanics are questionable, but your vibes are undeniable.",
        theSmurf: "Are you actually good or just a bit delusional?",
        toxic: "At least people think it's cute when you flame them.",
        cryBabyAdc: "You tilt with elegance. 3/10 KDA but 10/10 drama."
      }
    },
    boosted: {
      main: "📈 The Clueless Carry",
      core: "Map awareness? Mechanics? You probably never heard about those!",
      modifiers: {
        egirl: "Boosted and egirl? I won't flame you, Twitter is already doing that.",
        TheBaus: "You think running it down is a valid strategy if you get one kill",
        theSmurf: "The only thing you're smurfing on is the report menu",
        cryBabyAdc: "You demand peel while positioning like a melee minion"
      }
    },
    TheBaus: {
      main: "🌪️ The Calculated Inter",
      core: "Towers are suggestions, teamplay is optional, and limit testing is mandatory.",
      modifiers: {
        toxic: "Your 'big brain plays' come with a side of all-chat manifesto",
        boosted: "Even your duo can't explain how some of these deaths work",
        cryBabyAdc: "You dive fountain then spam ping your support for not following",
        theSmurf: "Only think you're smurfing it at is speed running grey screens"
      }
    },
    cryBabyAdc: {
      main: "😭 The Emotionally Fragile ADC",
      core: "Every game is personal, every death is betrayal, every CS is therapy. One gank and you're writting your memoir in all chat.",
      modifiers: {
        toxic: "Your tears fuel both your plays and your flame",
        egirl: "You tilt like it's sponsored content. Simps will love it though.",
        boosted: "The only thing more fragile than your mental is your positioning",
        TheBaus: "You demand babysitting while perma running it down mid"
      }
    },
    theSmurf: {
      main: "🕶️ The Mystery Box",
      core: "Either a god in disguise or someone's little brother on a stolen account.",
      modifiers: {
        boosted: "You're probably smurfing in sponge bob low elo.",
        TheBaus: "Your plays make people question if smurfing works differently in Brazil",
        egirl: "Your mastery emotes outnumber your actual mastery",
        toxic: "Your 'educational' smurfing comes with bonus verbal abuse"
      }
    }
  };
  
  const relationships = {
    toxic: {
      egirl: "Your flame comes wrapped in 💖 emojis - somehow making toxicity cute",
      TheBaus: "Your limit testing includes testing teammates' mental limits",
      cryBabyAdc: "You're the reason Riot invented /deafen",
      boosted: "You flame others while being carried harder than a shopping cart"
    },
    egirl: {
      boosted: "Your charm-to-skill ratio defies mathematical logic",
      theSmurf: "Your mastery emotes outnumber your actual mastery",
      toxic: "Even your 'gl hf' sounds like a threat in pink font",
      TheBaus: "Your roams are either genius or griefing - no in between"
    },
    boosted: {
      egirl: "Your pocket duo is either ride-or-die or hostage situation",
      theSmurf: "The only thing you're smurfing on is the report system",
      TheBaus: "Your gameplay makes boosters question their life choices",
      cryBabyAdc: "You blame supports while building like a ARAM player"
    },
    TheBaus: {
      toxic: "Your all-chat essays about wave management mid-int are legendary",
      boosted: "Your gameplay looks like someone let a cat walk on the keyboard... strategically",
      theSmurf: "Either 200IQ plays or proof elo inflation exists",
      cryBabyAdc: "Your split pushing makes ADCs question life choices"
    },
    cryBabyAdc: {
      egirl: "Your pout could be seen from the enemy nexus",
      toxic: "You type essays between last-hitting... poorly",
      boosted: "The only thing more boosted than your rank is your mental",
      theSmurf: "You still end up carrying at least, or are you delusional about that too?"
    },
    theSmurf: {
      toxic: "Your 'advice' is just flame in a Harvard hoodie",
      TheBaus: "Your plays make people report you whether you're smurfing or not",
      boosted: "The only thing suspicious is your item builds",
      egirl: "Your 'educational' stream has suspiciously good lighting"
    }
  };
  
  // Special combined archetype descriptions
  const hybridDescriptions = {
    // toxic_egirl: {
    //   title: "💥 The Barbie Bombshell",
    //   text: "You flame with pink font and heart emojis - somehow making toxicity cute. Your kill participation is 20% but all-chat participation is 200%."
    // },
    // TheBaus_boosted: {
    //   title: "🎪 The Clown Carried",
    //   text: "Your gameplay walks the fine line between inting and innovation. Teammates can't tell if you're smurfing or just someone's little brother."
    // },
    // egirl_theSmurf: {
    //   title: "👑 The Queen Smurf",
    //   text: "Either actually good or the best faker since LeBlanc main. Your charm distracts from questionable plays... or reveals 200IQ baiting."
    // },
    // cryBabyAdc_toxic: {
    //   title: "🌋 The Tilted Tyrant",
    //   text: "You flame while getting caught, tilt while carrying. The ADC main character complex is strong - complete with villain arc."
    // }
  };
  
  const quizContainer = document.getElementById('quiz-container');
  const resultContainer = document.getElementById('result');
  const resultTitle = document.getElementById('result-title');
  const resultDesc = document.getElementById('result-description');
  const canvas = document.getElementById('result-chart');
  const createAccountDiv = document.getElementById('createURSGAccount');
  const progressBarContainer = document.getElementById('progressBarContainer');
  const progressPercent = document.getElementById('progressPercent');
  
  const backBtn = document.getElementById('backBtn');
  const nextBtn = document.getElementById('nextBtn');
  const confirmBtn = document.getElementById('confirmBtn');
  const nav = document.getElementById('navigation');
  
  let questions = [];
  let currentIndex = 0;
  let selectedAnswers = {}; // question index -> selected button
  
  async function fetchQuestions() {
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
    currentIndex = index; // <== ADD THIS LINE
    questions.forEach((q, i) => q.style.display = i === index ? 'block' : 'none');
    updateNavButtons();
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
    backBtn.classList.toggle('hidden', currentIndex === 0);
    nextBtn.classList.toggle('hidden', currentIndex >= questions.length - 1);
    confirmBtn.classList.toggle('hidden', currentIndex < questions.length - 1);
  }

  function updateProgressBar(progress = null) {
    const progressBar = document.getElementById('progressBar');
    if (progressBar) {
      const progressValue = progress !== null
      ? progress
      : (currentIndex / (questions.length - 1)) * 100;
      progressBar.style.width = `${progressValue}%`;
      progressPercent.textContent = `${Math.round(progressValue)}%`;
      if (progressValue === 100) {
        progressBarContainer.classList.add('hidden');
      } else {
        progressBarContainer.classList.remove('hidden');
      }
    }
  }

  function clearChart() {
    if (currentChart) {
      currentChart.destroy();
      currentChart = null;
    }
  }

  
  async function resetQuiz() {
    if (!questions || questions.length === 0) {
      try {
        questions = await fetchQuestions();
      } catch (error) {
        console.error("Failed to fetch questions:", error);
        return;
      }
    }
  
    selectedAnswers = {};
    currentIndex = 0;
  
    resultContainer.classList.add('hidden');
    nav.style.display = 'flex';
    quizContainer.classList.remove('hidden');
    document.getElementById('resetBtn').classList.add('hidden');
    createAccountDiv.classList.add('hidden');
    progressBarContainer.classList.remove('hidden');
  
    questions.forEach(q => q.style.display = 'none');
    showQuestion(currentIndex);
    isFinalResult = false;
  
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
    const topScore = sorted[0][1];
    const threshold = topScore * 0.7;  
    
    // Always include at least top 2, then others above threshold
    const dominant = [
      sorted[0], 
      sorted[1], 
      ...sorted.slice(2).filter(([_, score]) => score >= threshold)
    ];
    
    // Generate dynamic description
    resultDesc.innerHTML = generateMixedDescription(sorted, dominant);
    
    drawChart(sorted);
    nextBtn.classList.add('hidden');
    backBtn.classList.add('hidden');
    progressBarContainer.classList.add('hidden');
    resultContainer.classList.remove('hidden');
    quizContainer.classList.add('hidden');
    nav.style.display = 'none';
    document.getElementById('resetBtn').classList.remove('hidden');
  }

  function generateMixedDescription(sortedArchetypes, dominant) {
    const [mainType, mainScore] = dominant[0];
    let description = `${descriptions[mainType].main}<br>${descriptions[mainType].core}`;
  
    // Check for hybrid combinations first
    if (dominant.length > 1) {
      const [secondType, secondScore] = dominant[1];
      const hybridKey = `${mainType}_${secondType}`;
      const reverseHybridKey = `${secondType}_${mainType}`;
  
      if (hybridDescriptions[hybridKey]) {
        return `${hybridDescriptions[hybridKey].title}<br>${hybridDescriptions[hybridKey].text}`;
      }
      if (hybridDescriptions[reverseHybridKey]) {
        return `${hybridDescriptions[reverseHybridKey].title}<br>${hybridDescriptions[reverseHybridKey].text}`;
      }
  
      // Add modifier if exists
      if (descriptions[mainType].modifiers[secondType]) {
        description += `<br><br>${descriptions[mainType].modifiers[secondType]}`;
      } else {
        description += `<br><br>${relationships[mainType][secondType]}`;
      }
    }
  
    // Add special case for close scores
    if (dominant.length > 1 && mainScore - dominant[1][1] < 2) {
      const secondDesc = descriptions[dominant[1][0]];
      return `🔥 Hybrid Personality 🔥<br>` +
        `${descriptions[mainType].main} / ${secondDesc.main}<br>` +
        `${descriptions[mainType].core} ${secondDesc.core}`;
    }
  
    return description;
  }
  
  function drawChart(data) {
    let radius, margin;

    if (window.innerWidth < 400) { 
      radius = 70;
      margin = 30;
    } else if (window.innerWidth < 550) {
      radius = 90;
      margin = 40;
    } else {
      radius = 150;
      margin = 60;
    }

    const totalSize = (radius + margin) * 2;
    // Dynamic maximum value calculation
    const dataMax = Math.max(...data.map(d => d[1]));
    const minVisualScale = 0.5;
    const maxValue = dataMax;   
  
    // Clear previous chart
    d3.select("#result-chart").selectAll("*").remove();
    
    const svg = d3.select("#result-chart")
      .attr("width", totalSize)
      .attr("height", totalSize)
      .append("g")
      .attr("transform", `translate(${radius + margin}, ${radius + margin})`);
  
    // Create arc generator for individual slices
    const arc = d3.arc()
      .innerRadius(0)
      .startAngle((d, i) => i * (2 * Math.PI) / data.length)
      .endAngle((d, i) => (i + 1) * (2 * Math.PI) / data.length);
  
    // Create individual filled slices
    svg.selectAll(".slice")
    .data(data)
    .enter().append("path")
    .attr("class", "slice")
    .attr("d", (d, i) => {
      const normalized = d[1] / maxValue;
      const scaled = minVisualScale + (1 - minVisualScale) * normalized;
      return arc.outerRadius(scaled * radius)(d, i);
    })
    .attr("fill", "#e84056")
    .attr("stroke", "#980c1f")
    .attr("stroke-width", 2);
  
    // Draw radar grid lines (improved visibility)
    const gridLevels = 5;
    for (let i = 1; i <= gridLevels; i++) {
      svg.append("circle")
        .attr("r", (radius/gridLevels) * i)
        .attr("fill", "none")
        .attr("stroke", "#ddd")
        .attr("stroke-width", 1)
        .attr("stroke-dasharray", "2,2");
    }
  
    // Add axis lines with better visibility
    svg.selectAll(".axis")
      .data(data)
      .enter().append("line")
      .attr("class", "axis")
      .attr("x1", 0)
      .attr("y1", 0)
      .attr("x2", (d, i) => radius * Math.sin(i * Math.PI * 2 / data.length))
      .attr("y2", (d, i) => -radius * Math.cos(i * Math.PI * 2 / data.length))
      .attr("stroke", "#ddd")
      .attr("stroke-width", 1.5);
  
    // Add labels with improved positioning
    svg.selectAll(".axisLabel")
    .data(data)
    .enter().append("text")
    .attr("class", "axisLabel")
    .attr("x", (d, i) => {
      const midAngle = (i + 0.5) * Math.PI * 2 / data.length;
      return (radius + 30) * Math.sin(midAngle);
    })
    .attr("y", (d, i) => {
      const midAngle = (i + 0.5) * Math.PI * 2 / data.length;
      return -(radius + 30) * Math.cos(midAngle);
    })
    .attr("dy", ".35em")
    .attr("text-anchor", "middle")
    .text(d => d[0]);
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
          fetchQuestions();
          return;
        } else {
          const result = data.result;
          Object.keys(archetypes).forEach(key => {
            archetypes[key] = result[key] || 0;
          });
          showResult(true);
        }
      } else {
        console.error('Error fetching old result:', data.error);
        fetchQuestions();
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

    if (typeof userId !== 'undefined' && userId !== null) {
      getOldResult(userId);
    } else {
      const savedResult = localStorage.getItem('personalityTestResult');
      
      if (savedResult) {
        const result = JSON.parse(savedResult);
        displayCreateAccount();
        showResult(true, result); 
      } else {
        fetchQuestions();
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
  