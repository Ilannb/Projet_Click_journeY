document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('sort').addEventListener('change', function () {
    const cardsContainer = document.querySelector('.destinations-cards-box');
    const cards = Array.from(cardsContainer.getElementsByClassName('destination-card'));

    const sortValue = this.value;

    cards.sort((a, b) => {
      switch (sortValue) {
        case 'price-asc':
          const priceA = getPriceValue(a);
          const priceB = getPriceValue(b);
          return priceA - priceB;

        case 'price-desc':
          const priceDescA = getPriceValue(a);
          const priceDescB = getPriceValue(b);
          return priceDescB - priceDescA;

        case 'popular':
        default:
          const ratingA = parseFloat(a.querySelector('.rating-value').textContent);
          const ratingB = parseFloat(b.querySelector('.rating-value').textContent);
          return ratingB - ratingA;
      }
    });

    // Remove existing cards
    while (cardsContainer.firstChild) {
      cardsContainer.removeChild(cardsContainer.firstChild);
    }

    // Append sorted cards
    cards.forEach(card => cardsContainer.appendChild(card));

    // Preserve radio button states
    preserveRadioStates(cards);
  });
});

function getPriceValue(card) {
  const priceElement = card.querySelector('.euros');
  if (priceElement.textContent.trim() === 'Inclus') {
    return -1; // Put included items first
  }
  return parseFloat(priceElement.textContent.replace('€', ''));
}

function preserveRadioStates(cards) {
  cards.forEach(card => {
    const radio = card.querySelector('input[type="radio"]');
    if (radio && radio.hasAttribute('checked')) {
      radio.checked = true;
    }
  });
}

