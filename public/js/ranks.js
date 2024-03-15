const selectedRanksLol = [
  { name: 'Iron', image: 'images/ranks/Iron.png' },
  { name: 'Bronze', image: 'images/ranks/Bronze.png' },
  { name: 'Silver', image: 'images/ranks/Silver.png' },
  { name: 'Gold', image: 'images/ranks/Gold.png' },
  { name: 'Platinum', image: 'images/ranks/Platinum.png' },
  { name: 'Diamond', image: 'images/ranks/Diamond.png' },
  { name: 'Master', image: 'images/ranks/Master.png' },
  { name: 'Grand Master', image: 'images/ranks/Grandmaster.png' },
  { name: 'Challenger', image: 'images/ranks/Challenger.png' },
];

const selectedRanksContainerLol = document.getElementById('selected-ranks-lol');

document.getElementById('rank_lol').addEventListener('change', (event) => {
  const selectedOption = event.target.selectedOptions[0];
  const rankImage = selectedOption.getAttribute('data-image');

  renderSelectedRankLol(rankImage);
});

function renderSelectedRankLol(rankImage) {
  selectedRanksContainerLol.innerHTML = '';

  const rankImageElement = document.createElement('img');
  rankImageElement.src = rankImage;

  selectedRanksContainerLol.appendChild(rankImageElement);
}