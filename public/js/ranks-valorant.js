const selectedRanksLol = [
    { name: 'Iron', image: 'images/valorant_ranks/Iron.png' },
    { name: 'Bronze', image: 'images/valorant_ranks/Bronze.png' },
    { name: 'Silver', image: 'images/valorant_ranks/Silver.png' },
    { name: 'Gold', image: 'images/valorant_ranks/Gold.png' },
    { name: 'Platinum', image: 'images/valorant_ranks/Platinum.png' },
    { name: 'Emerald', image: 'images/valorant_ranks/Emerald.png' },
    { name: 'Diamond', image: 'images/valorant_ranks/Diamond.png' },
    { name: 'Ascendant', image: 'images/valorant_ranks/Ascendant.png' },
    { name: 'Immortal', image: 'images/valorant_ranks/Immortal.png' },
    { name: 'Radiant', image: 'images/valorant_ranks/Radiant.png' },
  ];
  
  const selectedRanksContainerValorant = document.getElementById('selected-ranks-valorant');
  
  document.getElementById('rank_valorant').addEventListener('change', (event) => {
    const selectedOption = event.target.selectedOptions[0];
    const rankImage = selectedOption.getAttribute('data-image');
  
    renderSelectedRankValorant(rankImage);
  });
  
  function renderSelectedRankValorant(rankImage) {
    selectedRanksContainerValorant.innerHTML = '';
  
    const rankImageElement = document.createElement('img');
    rankImageElement.src = rankImage;
  
    selectedRanksContainerValorant.appendChild(rankImageElement);
  }