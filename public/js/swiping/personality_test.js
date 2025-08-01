let archetypes = {
  toxic: 0,
  egirl: 0,
  boosted: 0,
  TheBaus: 0,
  cryBabyAdc: 0,
  theSmurf: 0,
  strategic: 0,
  serious: 0,
};

let currentChart = null;
let isFinalResult = false;

const descriptions = {
  toxic: {
    main: "🔥 The Keyboard Warrior",
    core: "You peaked at silver 3 in Season 8 and never forgave the game.",
    modifiers: {
      egirl:
        "Mute all? Never. Flame all? Always... but somehow still getting friend requests between deaths.",
      TheBaus: "Your 1v5 attempts are just elaborate inting with extra steps.",
      cryBabyAdc: "Toxic and emotional - a true ADC main.",
      boosted:
        "You blame teammates while being carried harder than a shopping cart.",
      theSmurf: "Your 'educational' smurfing comes with bonus verbal abuse.",
      strategic:
        "Your 'strategic' pings are just passive-aggressive flame in disguise.",
      serious:
        "You take the game way too seriously... just not your own improvement.",
    },
    image: "toxic.png",
  },
  egirl: {
    main: "💅 The Egirl",
    core: "You roam the Rift spreading chaos and charm in equal measure. Who needs skill when you've got friends in high elo!",
    modifiers: {
      boosted:
        "Your mechanics are questionable, but your vibes are undeniable.",
      theSmurf: "Are you actually good or just a bit delusional?",
      toxic: "At least people think it's cute when you flame them.",
      cryBabyAdc: "You decided to become the carry? Looks like it failed",
      TheBaus:
        "Your 'roams' are just excuses to visit other lanes and emote spam",
      strategic:
        "Your game plan consists entirely of looking cute and hoping for ganks",
      serious:
        "You pretend to care about macro but we all know you're here for the skins",
    },
    image: "egirl.jpg",
  },
  boosted: {
    main: "📈 The Clueless Carry",
    core: "Map awareness? Mechanics? You probably never heard about those!",
    modifiers: {
      egirl:
        "Boosted and egirl? I won't flame you, Twitter is already doing that.",
      TheBaus:
        "You think running it down is a valid strategy if you get one kill",
      theSmurf: "The only thing you're smurfing on is the report menu",
      cryBabyAdc: "You demand peel while positioning like a melee minion",
      toxic: "You flame others while being carried harder than a shopping cart",
      strategic:
        "Your 'strategy' is following the nearest teammate until they carry you",
      serious:
        "You unironically think watching one pro game makes you understand macro",
    },
    image: "boosted.jpg",
  },
  TheBaus: {
    main: "🌪️ The Calculated Inter",
    core: "Towers are suggestions, teamplay is optional, and limit testing is mandatory.",
    modifiers: {
      toxic: "Your 'big brain plays' come with a side of all-chat manifesto",
      boosted: "Even your duo can't explain how some of these deaths work",
      cryBabyAdc:
        "You dive fountain then spam ping your support for not following",
      theSmurf:
        "Only think you're smurfing it at is speed running grey screens",
      egirl: "Your roams are either genius or griefing - no in between",
      strategic: "Your 'strategy' is just running it down with extra steps",
      serious:
        "You've convinced yourself your 20 death games are 'educational'",
    },
    image: "TheBaus.jpg",
  },
  cryBabyAdc: {
    main: "😭 The Emotionally Fragile ADC",
    core: "Every game is personal, every death is betrayal, every CS is therapy. One gank and you're writting your memoir in all chat.",
    modifiers: {
      toxic: "Your tears fuel both your plays and your flame",
      egirl: "You tilt like it's sponsored content. Simps will love it though.",
      boosted:
        "The only thing more fragile than your mental is your positioning",
      TheBaus: "You demand babysitting while perma running it down mid",
      theSmurf: "You think you're smurfing but your KDA says otherwise",
      strategic:
        "Your 'positioning' is just hiding behind teammates until you die anyway",
      serious: "You take every death as a personal insult from Riot Games",
    },
    image: "crybabyadc.png",
  },
  theSmurf: {
    main: "🕶️ The Mystery Box",
    core: "Either a god in disguise or someone's little brother on a stolen account.",
    modifiers: {
      boosted: "You're probably smurfing in sponge bob low elo.",
      TheBaus:
        "Your plays make people question if smurfing works differently in Brazil",
      egirl: "Your mastery emotes outnumber your actual mastery",
      toxic: "Your 'educational' smurfing comes with bonus verbal abuse",
      cryBabyAdc:
        "You still end up carrying at least, or are you delusional about that too?",
      strategic: "Your 'coaching' consists of spamming pings and typing '?'",
      serious:
        "You pretend to be educational but really just enjoy stomping noobs",
    },
    image: "theSmurf.jpg",
  },
  strategic: {
    main: "🧠 The Grand Strategist",
    core: "You see the game five moves ahead, even if your teammates are still learning chess.",
    modifiers: {
      boosted:
        "Your 'big brain plays' often leave teammates wondering if you're trolling",
      TheBaus:
        "Your 'strategic inting' is just regular inting with better vocabulary",
      egirl:
        "You claim to understand wave states but really just follow your duo's pings",
      toxic:
        "Your 'constructive criticism' is just flame wrapped in macro terms",
      cryBabyAdc: "You ping missing 15 times then tilt when no one rotates",
      theSmurf:
        "Your 'educational content' is just you smurfing with extra steps",
      serious:
        "You've watched so many guides you forgot to actually play the game",
    },
    image: "strategist.jpeg",
  },
  serious: {
    main: "🎯 The Tryhard",
    core: "Every game is a tournament final, every mistake is unacceptable, and fun is secondary to LP gains.",
    modifiers: {
      boosted: "You study pro builds but still can't hit skillshots",
      TheBaus:
        "Your 'serious gameplay' is just running it down with better excuses",
      egirl:
        "You pretend to care about macro but really just want to look smart",
      toxic: "Your 'constructive feedback' is just flame with extra steps",
      cryBabyAdc:
        "You analyze every death frame by frame then blame your support",
      theSmurf:
        "Your 'educational' attitude disappears when someone questions your calls",
      strategic:
        "You overthink simple plays until they become complicated mistakes",
    },
    image: "serious.jpg",
  },
};

const relationships = {
  toxic: {
    egirl:
      "Your flame comes wrapped in 💖 emojis - somehow making toxicity cute",
    TheBaus: "Your limit testing includes testing teammates' mental limits",
    cryBabyAdc: "You're the reason Riot invented /deafen",
    boosted: "You flame others while being carried harder than a shopping cart",
    theSmurf: "Your 'educational' smurfing comes with bonus verbal abuse",
    strategic: "Your 'strategic advice' is just flame in macro terminology",
    serious: "You take losing more seriously than pros take Worlds",
  },
  egirl: {
    boosted: "Your charm-to-skill ratio defies mathematical logic",
    theSmurf: "Your mastery emotes outnumber your actual mastery",
    toxic: "Even your 'gl hf' sounds like a threat in pink font",
    TheBaus: "Your roams are either genius or griefing - no in between",
    cryBabyAdc: "Your pout could be seen from the enemy nexus",
    strategic: "Your 'game sense' is just following your duo's pings",
    serious:
      "You pretend to care about macro but we all know you're here for the skins",
  },
  boosted: {
    egirl: "Your pocket duo is either ride-or-die or hostage situation",
    theSmurf: "The only thing you're smurfing on is the report system",
    TheBaus: "Your gameplay makes boosters question their life choices",
    cryBabyAdc: "You blame supports while building like a ARAM player",
    toxic: "You flame teammates while being carried harder than groceries",
    strategic:
      "Your 'big brain plays' are just failed attempts at basic mechanics",
    serious:
      "You unironically think watching one pro game makes you understand macro",
  },
  TheBaus: {
    toxic: "Your all-chat essays about wave management mid-int are legendary",
    boosted:
      "Your gameplay looks like someone let a cat walk on the keyboard... strategically",
    theSmurf: "Either 200IQ plays or proof elo inflation exists",
    cryBabyAdc: "Your split pushing makes ADCs question life choices",
    egirl: "Your 'roams' are just excuses to visit other lanes and emote spam",
    strategic:
      "Your 'calculated inting' is just regular inting with better vocabulary",
    serious: "You've convinced yourself your 20 death games are 'educational'",
  },
  cryBabyAdc: {
    egirl: "Your pout could be seen from the enemy nexus",
    toxic: "You type essays between last-hitting... poorly",
    boosted: "The only thing more boosted than your rank is your mental",
    theSmurf:
      "You still end up carrying at least, or are you delusional about that too?",
    TheBaus: "You demand babysitting while perma running it down mid",
    strategic:
      "Your 'positioning' is just hiding behind teammates until you die anyway",
    serious: "You take every death as a personal insult from Riot Games",
  },
  theSmurf: {
    toxic: "Your 'advice' is just flame in a Harvard hoodie",
    TheBaus: "Your plays make people report you whether you're smurfing or not",
    boosted: "The only thing suspicious is your item builds",
    egirl: "Your 'educational' stream has suspiciously good lighting",
    cryBabyAdc:
      "You still end up carrying at least, or are you delusional about that too?",
    strategic: "Your 'coaching' consists of spamming pings and typing '?'",
    serious:
      "You pretend to be educational but really just enjoy stomping noobs",
  },
  strategic: {
    toxic:
      "Your macro knowledge is only used to flame teammates more effectively",
    egirl:
      "Your 'game plans' are just following your duo's lead with extra steps",
    boosted: "Your 'strategic genius' disappears the moment teamfights start",
    TheBaus:
      "Your 'calculated plays' are just inting with a PowerPoint presentation",
    cryBabyAdc: "You ping missing 15 times then tilt when no one rotates",
    theSmurf:
      "Your 'educational content' is just you smurfing with extra steps",
    serious:
      "You've watched so many guides you forgot to actually play the game",
  },
  serious: {
    toxic: "You take flaming as seriously as you take the game",
    egirl: "You pretend to care about macro but really just want to look smart",
    boosted: "You study pro builds but still can't hit skillshots",
    TheBaus:
      "Your 'serious gameplay' is just running it down with better excuses",
    cryBabyAdc:
      "You analyze every death frame by frame then blame your support",
    theSmurf:
      "Your 'educational' attitude disappears when someone questions your calls",
    strategic:
      "You overthink simple plays until they become complicated mistakes",
  },
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

const archetypeKeywords = {
  toxic: "flame lord",
  egirl: "charmer",
  boosted: "wildcard",
  TheBaus: "limit tester",
  cryBabyAdc: "drama carry",
  theSmurf: "secret prodigy",
  strategic: "grand tactician",
  serious: "tryhard virtuoso",
};

const archetypePhrases = {
  toxic:
    "You're a true <span class='color-red'>{keyword}</span>, but even chaos deserves calm. Join us and find your duo.",
  egirl:
    "You're a natural <span class='color-red'>{keyword}</span>, but even charmers need teammates. Join us and vibe together!",
  boosted:
    "You're the ultimate <span class='color-red'>{keyword}</span>, but even wildcards shine brightest with a crew. Join us!",
  TheBaus:
    "You're a fearless <span class='color-red'>{keyword}</span>, but even rebels need a team. Join us and send it together!",
  cryBabyAdc:
    "You're a passionate <span class='color-red'>{keyword}</span>, but even emotions win more with friends. Join us!",
  theSmurf:
    "You're a lowkey <span class='color-red'>{keyword}</span>, but even legends need recognition. Join us and show them!",
  strategic:
    "You're a brilliant <span class='color-red'>{keyword}</span>, but even the best plans need execution. Join us and lead the charge!",
  serious:
    "You're a dedicated <span class='color-red'>{keyword}</span>, but even perfectionists need allies. Join us and climb properly!",
};

const quizContainer = document.getElementById("quiz-container");
const resultContainer = document.getElementById("result");
const resultTitle = document.getElementById("result-title");
const resultDesc = document.getElementById("result-description");
const resultImage = document.getElementById("result-image");
const canvas = document.getElementById("result-chart");
const createAccountDiv = document.getElementById("createURSGAccount");
const progressBarContainer = document.getElementById("progressBarContainer");
const progressPercent = document.getElementById("progressPercent");

const backBtn = document.getElementById("backBtn");
const nextBtn = document.getElementById("nextBtn");
const confirmBtn = document.getElementById("confirmBtn");
const nav = document.getElementById("navigation");

const showMatchingUser = document.getElementById("showMatchingUser");

let questions = [];
let currentIndex = 0;
let selectedAnswers = {}; // question index -> selected button

async function fetchQuestions() {
  try {
    const response = await fetch("/questions2.html");
    const html = await response.text();
    quizContainer.innerHTML = html;
    questions = quizContainer.querySelectorAll(".question");
    questions.forEach((q) => (q.style.display = "none"));

    if (questions.length > 0) {
      showQuestion(currentIndex);
      updateProgressBar(0);
      nav.style.display = "flex";
    } else {
      nav.style.display = "none";
    }
  } catch (error) {
    console.error("Error loading questions:", error);
    nav.style.display = "none";
  }
}

function resetSelectionState() {
  selectedAnswers = {};
}

function displayCreateAccount(archetypes) {
  // Get the top archetype
  const sorted = Object.entries(archetypes).sort((a, b) => b[1] - a[1]);

  const topArchetype = sorted[0][0];

  const createAccountDesc = document.getElementById("create-account-desc");
  const keyword = archetypeKeywords[topArchetype];
  const phraseTemplate = archetypePhrases[topArchetype];

  if (keyword && phraseTemplate) {
    const phrase = phraseTemplate.replace("{keyword}", keyword);
    createAccountDesc.innerHTML = phrase;
  } else {
    createAccountDesc.innerHTML =
      "You're a unique <span class='color-red'>mystery</span>, but even mysteries deserve a match. Join us!";
  }

  createAccountDiv.classList.remove("hidden");
}

function showQuestion(index) {
  currentIndex = index; // <== ADD THIS LINE
  questions.forEach(
    (q, i) => (q.style.display = i === index ? "block" : "none")
  );
  updateNavButtons();
  const buttons = questions[index].querySelectorAll("button");
  buttons.forEach((btn) => {
    btn.classList.remove("selected");
    if (selectedAnswers[index] === btn.dataset.value) {
      btn.classList.add("selected");
    }
    btn.onclick = () => {
      selectedAnswers[index] = btn.dataset.value;
      buttons.forEach((b) => b.classList.remove("selected"));
      btn.classList.add("selected");
    };
  });

  if (!isFinalResult) {
    updateProgressBar();
  }
}

function updateNavButtons() {
  backBtn.disabled = currentIndex === 0;
  backBtn.classList.toggle("hidden", currentIndex === 0);
  nextBtn.classList.toggle("hidden", currentIndex >= questions.length - 1);
  confirmBtn.classList.toggle("hidden", currentIndex < questions.length - 1);
}

function updateProgressBar(progress = null) {
  const progressBar = document.getElementById("progressBar");
  if (progressBar) {
    const progressValue =
      progress !== null
        ? progress
        : (currentIndex / (questions.length - 1)) * 100;
    progressBar.style.width = `${progressValue}%`;
    progressPercent.textContent = `${Math.round(progressValue)}%`;
    if (progressValue === 100) {
      progressBarContainer.classList.add("hidden");
    } else {
      progressBarContainer.classList.remove("hidden");
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
  resetSelectionState();
  if (!questions || questions.length === 0) {
    try {
      await fetchQuestions(); // Wait for fetchQuestions to complete before proceeding
    } catch (error) {
      console.error("Failed to fetch questions:", error);
      return;
    }
  }

  // Ensure that questions are properly initialized after fetch
  if (questions && questions.length > 0) {
    selectedAnswers = {};
    currentIndex = 0;

    resultContainer.classList.add("hidden");
    nav.style.display = "flex";
    quizContainer.classList.remove("hidden");
    document.getElementById("resetBtn").classList.add("hidden");
    createAccountDiv.classList.add("hidden");
    progressBarContainer.classList.remove("hidden");

    questions.forEach((q) => (q.style.display = "none"));
    showQuestion(currentIndex);
    isFinalResult = false;

    // Reset progress bar
    updateProgressBar(0);
    clearChart();
  } else {
    console.error("Questions are not available or failed to load.");
  }
}

function getHighestKey(obj) {
  return Object.entries(obj).reduce((a, b) => a[1] > b[1] ? a : b)[0];
}

function matchTypes(type) {
  const typeMap = {
    // Define your strict mappings here
    TheBaus: "strategic",
    boosted: "toxic",
    cryBabyAdc: "egirl",
    egirl: "cryBabyAdc",
    serious: "theSmurf",
    strategic: "TheBaus",
    theSmurf: "serious",
    toxic: "boosted"
  };

  // Return the matched type or undefined if not found
  return typeMap[type];
}

function getMatchingPersonality(userId) {
  const token = localStorage.getItem("masterTokenWebsite");
    const personalityType = matchTypes(getHighestKey(Object.entries(archetypes).reduce((acc, [key, value]) => {
    acc[key] = value;
    return acc;
  }, {})))

  fetch("/getMatchingPersonalityUser", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      Authorization: `Bearer ${token}`,
    },
    body: `userId=${encodeURIComponent(parseInt(userId))}&personalityType=${encodeURIComponent(personalityType)}`,
  })
    .then((response) => response.json())
    .then((data) => {
      const matchingUserAvatar = document.getElementById("matchingUserAvatar");
      const matchingUserName = document.getElementById("matchingUserName");
      matchingUserAvatar.src = `public/upload/${data.result.user_picture}`;
      matchingUserAvatar.alt = `Avatar ${data.result.user_username}`;
      matchingUserName.href = `/anotherUser&username=${encodeURIComponent(data.result.user_username)}`;
      matchingUserName.querySelector(".strong_text").textContent = data.result.user_username;
    })
    .catch((error) => console.error("Error getting matching user", error));
}

function showResult(loadingOldResult = false, result = null) {
  if (!loadingOldResult && !result) {
    // Only reset if NOT loading old result
    Object.keys(archetypes).forEach((k) => (archetypes[k] = 0));

    Object.values(selectedAnswers).forEach((val) => {
      val.split(",").forEach((type) => {
        archetypes[type.trim()]++;
      });
    });

    localStorage.setItem("personalityTestResult", JSON.stringify(archetypes));
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
    ...sorted.slice(2).filter(([_, score]) => score >= threshold),
  ];

  let image = descriptions[dominant[0][0]].image;
  resultImage.src = `public/images/test/${image}`;
  resultImage.alt = descriptions[dominant[0][0]].main;

  // Generate dynamic description
  resultDesc.innerHTML = generateMixedDescription(sorted, dominant);
  resultTitle.innerHTML = descriptions[dominant[0][0]].main;

  drawChart(sorted);
  nextBtn.classList.add("hidden");
  backBtn.classList.add("hidden");
  progressBarContainer.classList.add("hidden");
  resultContainer.classList.remove("hidden");
  quizContainer.classList.add("hidden");
  nav.style.display = "none";
  document.getElementById("resetBtn").classList.remove("hidden");

  if (typeof userId !== "undefined" && userId !== null) {
    getMatchingPersonality(userId);
  }
}

function generateMixedDescription(sortedArchetypes, dominant) {
  const [mainType, mainScore] = dominant[0];
  let description = `${descriptions[mainType].core}`;

  // Check for hybrid combinations first
  if (dominant.length > 1) {
    const [secondType, secondScore] = dominant[1];
    // const hybridKey = `${mainType}_${secondType}`;
    // const reverseHybridKey = `${secondType}_${mainType}`;

    // if (hybridDescriptions[hybridKey]) {
    //   return `${hybridDescriptions[hybridKey].text}`;
    // }
    // if (hybridDescriptions[reverseHybridKey]) {
    //   return `${hybridDescriptions[reverseHybridKey].text}`;
    // }

    // Add modifier if exists
    if (descriptions[mainType].modifiers[secondType]) {
      description += `<br><br>${descriptions[mainType].modifiers[secondType]}`;
    } else if (relationships?.[mainType]?.[secondType]) {
      description += `<br><br>${relationships[mainType][secondType]}`;
    }
  }

  // Add special case for close scores
  if (dominant.length > 1 && mainScore - dominant[1][1] < 2) {
    const secondDesc = descriptions[dominant[1][0]];
    return `${descriptions[mainType].core} ${secondDesc.core}`;
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
  const dataMax = Math.max(...data.map((d) => d[1]));
  const minVisualScale = 0.5;
  const maxValue = dataMax;

  // Clear previous chart
  d3.select("#result-chart").selectAll("*").remove();

  const svg = d3
    .select("#result-chart")
    .attr("width", totalSize)
    .attr("height", totalSize)
    .append("g")
    .attr("transform", `translate(${radius + margin}, ${radius + margin})`);

  // Create arc generator for individual slices
  const arc = d3
    .arc()
    .innerRadius(0)
    .startAngle((d, i) => (i * (2 * Math.PI)) / data.length)
    .endAngle((d, i) => ((i + 1) * (2 * Math.PI)) / data.length);

  // Create individual filled slices
  svg
    .selectAll(".slice")
    .data(data)
    .enter()
    .append("path")
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
    svg
      .append("circle")
      .attr("r", (radius / gridLevels) * i)
      .attr("fill", "none")
      .attr("stroke", "#ddd")
      .attr("stroke-width", 1)
      .attr("stroke-dasharray", "2,2");
  }

  // Add axis lines with better visibility
  svg
    .selectAll(".axis")
    .data(data)
    .enter()
    .append("line")
    .attr("class", "axis")
    .attr("x1", 0)
    .attr("y1", 0)
    .attr("x2", (d, i) => radius * Math.sin((i * Math.PI * 2) / data.length))
    .attr("y2", (d, i) => -radius * Math.cos((i * Math.PI * 2) / data.length))
    .attr("stroke", "#ddd")
    .attr("stroke-width", 1.5);

  // Add labels with improved positioning
  svg
    .selectAll(".axisLabel")
    .data(data)
    .enter()
    .append("text")
    .attr("class", "axisLabel")
    .attr("x", (d, i) => {
      const midAngle = ((i + 0.5) * Math.PI * 2) / data.length;
      return (radius + 30) * Math.sin(midAngle);
    })
    .attr("y", (d, i) => {
      const midAngle = ((i + 0.5) * Math.PI * 2) / data.length;
      return -(radius + 30) * Math.cos(midAngle);
    })
    .attr("dy", ".35em")
    .attr("text-anchor", "middle")
    .text((d) => d[0]);
}

function getOldResult(userId) {
  const token = localStorage.getItem("masterTokenWebsite");
  fetch("/getPersonalityTestResult", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      Authorization: `Bearer ${token}`,
    },
    body: `userId=${encodeURIComponent(parseInt(userId))}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        if (!data.result) {
          console.log("No previous result found for this user.");
          fetchQuestions();
          return;
        } else {
          const result = data.result;
          Object.keys(archetypes).forEach((key) => {
            archetypes[key] = result[key] || 0;
          });
          showResult(true);
        }
      } else {
        console.error("Error fetching old result:", data.error);
        fetchQuestions();
      }
    })
    .catch((error) => console.error("Error fetching old result:", error));
}

function savePersonalityTestResult(userId) {
  const token = localStorage.getItem("masterTokenWebsite");
  const result = Object.entries(archetypes).reduce((acc, [key, value]) => {
    acc[key] = value;
    return acc;
  }, {});

  // Include userId inside the body
  const bodyData = {
    userId: userId,
    result: result,
  };

  fetch("/savePersonalityTestResult", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(bodyData),
  })
    .then((response) => response.json())
    .then((data) => console.log("Result saved:", data))
    .catch((error) => console.error("Error saving result:", error));
}

document.addEventListener("DOMContentLoaded", function () {
  if (typeof userId !== "undefined" && userId !== null) {
    getOldResult(userId);
  } else {
    const savedResult = localStorage.getItem("personalityTestResult");

    if (savedResult) {
      const result = JSON.parse(savedResult);
      displayCreateAccount(result);
      showResult(true, result);
    } else {
      fetchQuestions();
    }
  }

  backBtn.addEventListener("click", () => {
    if (currentIndex > 0) {
      currentIndex--;
      showQuestion(currentIndex);
    }
  });

  nextBtn.addEventListener("click", () => {
    if (currentIndex < questions.length - 1) {
      currentIndex++;
      showQuestion(currentIndex);
    }
  });

  document.getElementById("resetBtn").onclick = resetQuiz;

  confirmBtn.addEventListener("click", () => {
    if (Object.keys(selectedAnswers).length === questions.length) {
      showResult();
      if (typeof userId !== "undefined" && userId !== null) {
        savePersonalityTestResult(userId);
      } else {
        // save to local storage if userId is not available
        localStorage.setItem(
          "personalityTestResult",
          JSON.stringify(archetypes)
        );
        displayCreateAccount(archetypes);
      }
    } else {
      alert("Please answer all questions before submitting.");
    }
  });
});
