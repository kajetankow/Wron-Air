<?php
if (!defined('APP_ACCESS')) exit('Access denied');

$airports = $airports ?? [
    'Warsaw [WAW]',
    'Cracow [KRA]',
    'Frankfurt [FRA]',
    'Vilnius [VNO]',
    'Tallinn [TLL]',
    'Bucharest [OTP]',
    'Helsinki [HEL]',
    'Stockholm [ARN]',
    'Oslo [OSL]',
    'Amsterdam [AMS]',
    'Prague [PRG]',
    'Munich [MUC]',
    'Vienna [VIE]',
    'Zagreb [ZAG]',
    'Budapest [BUD]',
    'Athens [ATH]',
    'Rome [FCO]',
    'Zurich [ZRH]',
    'Paris [CDG]',
    'Barcelona [BCN]',
    'London [LHR]',
    'Lisbon [LIS]',
    'New York [JFK]',
    'Los Angeles [LAX]',
    'Miami [MIA]'
];
?>

<div class="bg-section-border">
  <div class="bg-section">
    <div class="flight-container">
      <div class="header-title">Flights</div>

      <form action="#" method="get" class="flight-form" novalidate>
        <div class="row">
          <div class="form-group3">
            <div class="select-wrapper">
              <select id="trip-type" name="trip_type">
                <option value="round-trip">Round trip</option>
                <option value="one-way">One way</option>
              </select>
            </div>
          </div>

          <div class="passenger-group">
            <label for="passengers">No. of passengers:</label>
            <div class="select-wrapper small-select-wrapper">
              <select id="passengers" name="passengers" class="small-select">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
              </select>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="form-group">
            <input type="text" id="from" name="from" placeholder="From" autocomplete="off">
            <div id="from-suggestions" class="autocomplete-suggestions"></div>
          </div>

          <div class="form-group2">
            <input type="text" id="to" name="to" placeholder="To" autocomplete="off">
            <div id="to-suggestions" class="autocomplete-suggestions"></div>
          </div>
        </div>

        <div class="row">
          <div class="date-pair">
            <div class="form-half">
              <input
                type="text"
                id="departure-date"
                name="departure_date"
                placeholder="Departure"
                min="<?= date('Y-m-d') ?>"
                onfocus="this.type='date'; this.min='<?= date('Y-m-d') ?>';"
                onblur="if(!this.value) this.type='text';"
              />
            </div>

            <div class="form-half">
              <input
                type="text"
                id="return"
                name="return_date"
                placeholder="Return"
                min="<?= date('Y-m-d') ?>"
                onfocus="this.type='date';"
                onblur="if(!this.value) this.type='text';"
              />
            </div>
          </div>

          <div class="form-group2">
            <button type="submit" class="search-button">Search</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="line-container">
  <div class="line-left"></div>
  <div class="text-linia">DISCOVER OUR FLIGHT CONNECTIONS</div>
  <div class="line-right"></div>
</div>

<div class="map">
  <img src="../img/mapa.png" alt="Flight connections map">
</div>

<div class="odkryj-wiecej">
  <h2>WITH WronAir, DISCOVER MORE...</h2>
  <p>
    Thanks to our extensive route network, you can visit not only the world’s largest cities,
    but also hidden gems you have always dreamed of. Comfortable flights, attractive prices
    and friendly service will make every journey an unforgettable adventure.<br><br>

    WronAir - Your wings to the world!<br><br>

    Plan your journey today and make your dreams come true!<br>
  </p>
</div>

<div class="carousel-container">
  <div class="carousel" id="carousel">
    <div class="tile">
      <a href="#">
        <img src="../img/krakow.webp" alt="Cracow">
        <div class="tile-info">
          <h3>Cracow</h3>
          <p class="price"><span style="font-size: 16px;">from </span>$125*</p>
        </div>
      </a>
    </div>

    <div class="tile">
      <a href="#">
        <img src="../img/los_angeles.webp" alt="Los Angeles">
        <div class="tile-info">
          <h3>Los Angeles</h3>
          <p class="price"><span style="font-size: 16px;">from </span>$275*</p>
        </div>
      </a>
    </div>

    <div class="tile">
      <a href="#">
        <img src="../img/londyn.webp" alt="London">
        <div class="tile-info">
          <h3>London</h3>
          <p class="price"><span style="font-size: 16px;">from </span>$119*</p>
        </div>
      </a>
    </div>

    <div class="tile">
      <a href="#">
        <img src="../img/rzym.webp" alt="Rome">
        <div class="tile-info">
          <h3>Rome</h3>
          <p class="price"><span style="font-size: 16px;">from </span>$99*</p>
        </div>
      </a>
    </div>

    <div class="tile">
      <a href="#">
        <img src="../img/new_jork.webp" alt="New York">
        <div class="tile-info">
          <h3>New York</h3>
          <p class="price"><span style="font-size: 16px;">from </span>$350*</p>
        </div>
      </a>
    </div>

    <div class="tile">
      <a href="#">
        <img src="../img/barcelona.webp" alt="Barcelona">
        <div class="tile-info">
          <h3>Barcelona</h3>
          <p class="price"><span style="font-size: 16px;">from </span>$125*</p>
        </div>
      </a>
    </div>

    <div class="tile">
      <a href="#">
        <img src="../img/ateny.webp" alt="Athens">
        <div class="tile-info">
          <h3>Athens</h3>
          <p class="price"><span style="font-size: 16px;">from </span>$89*</p>
        </div>
      </a>
    </div>

    <div class="tile_more">
      <a href="#">
        <img src="../img/plus.png" alt="More">
        <div class="tile-info_more">
          <h3>See more ...</h3>
        </div>
      </a>
    </div>
  </div>

  <div class="carousel-nav">
    <button id="prev" type="button">◀</button>
    <button id="next" type="button">▶</button>
  </div>
</div>

<div class="carousel-indicators" id="carouselIndicators"></div>

<div class="newsletter-container">
  <h2>Do not miss great deals. Join our Newsletter.</h2>

  <div class="input-group">
    <input type="text" placeholder="Your name*" />
    <input type="email" placeholder="Your e-mail address*" />
    <button class="submit-btn" type="button">Subscribe</button>
  </div>

  <p class="text-info">
    By subscribing to the Newsletter, I consent to receiving commercial information from WronAir Sp. z o.o.
    I can withdraw my consent at any time. Data will be processed until the consent is withdrawn.
    Giving consent is necessary to receive a discount code.
  </p>

  <div class="checkbox-container">
    <label>
      <input type="checkbox">
      Yes, I consent to receiving commercial information from WronAir.*
    </label>

    <br><br>

    <p>
      The administrator processes data in accordance with the
      <a href="#">Privacy Policy</a>.
      I have the right to access, rectify, delete or restrict data processing, object to processing,
      lodge a complaint with a supervisory authority or transfer data. I can withdraw my consent at any time
      by contacting the Administrator at:
      <a href="mailto:rodo@wronair.pl">rodo@wronair.pl</a>
    </p>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const tripTypeSelect = document.getElementById('trip-type');
  const returnInputContainer = document.querySelector('.form-half:nth-child(2)');
  const departureInputContainer = document.querySelector('.form-half:nth-child(1)');
  const departureDateInput = document.getElementById('departure-date');
  const returnDateInput = document.getElementById('return');

  const today = new Date().toISOString().split('T')[0];

  if (departureDateInput && returnDateInput) {
    departureDateInput.min = today;
    returnDateInput.min = today;

    departureDateInput.addEventListener('change', () => {
      if (departureDateInput.value < today) {
        departureDateInput.value = today;
      }

      returnDateInput.min = departureDateInput.value;

      if (returnDateInput.value && returnDateInput.value < departureDateInput.value) {
        returnDateInput.value = departureDateInput.value;
      }
    });

    returnDateInput.addEventListener('change', () => {
      if (departureDateInput.value && returnDateInput.value < departureDateInput.value) {
        returnDateInput.value = departureDateInput.value;
      }
    });
  }

  function updateReturnVisibility() {
    const returnInput = document.getElementById('return');

    if (!tripTypeSelect || !returnInputContainer || !departureInputContainer || !returnInput) {
      return;
    }

    if (tripTypeSelect.value === 'one-way') {
      returnInputContainer.style.display = 'none';
      departureInputContainer.classList.add('full-width-departure');
      returnInput.required = false;
      returnInput.value = '';
    } else {
      returnInputContainer.style.display = 'flex';
      departureInputContainer.classList.remove('full-width-departure');
      returnInput.required = true;
    }
  }

  updateReturnVisibility();

  if (tripTypeSelect) {
    tripTypeSelect.addEventListener('change', updateReturnVisibility);
  }
});

(function() {
  const carousel = document.getElementById('carousel');
  const prevBtn = document.getElementById('prev');
  const nextBtn = document.getElementById('next');
  const indicatorsContainer = document.getElementById('carouselIndicators');
  const tiles = document.querySelectorAll('.tile');

  if (!carousel || !prevBtn || !nextBtn || !indicatorsContainer || tiles.length === 0) {
    return;
  }

  let currentIndex = 0;
  let isDragging = false;
  let startPos = 0;
  let currentTranslate = 0;
  let prevTranslate = 0;
  let animationID;
  let autoSlideInterval;

  function getTileWidth() {
    return tiles[0].offsetWidth +
      parseInt(getComputedStyle(tiles[0]).marginLeft) +
      parseInt(getComputedStyle(tiles[0]).marginRight);
  }

  function setPositionByIndex() {
    currentTranslate = -currentIndex * getTileWidth();
    prevTranslate = currentTranslate;
    setCarouselPosition();
  }

  function setCarouselPosition() {
    carousel.style.transform = `translateX(${currentTranslate}px)`;
  }

  function updateIndicators() {
    indicatorsContainer.innerHTML = '';

    for (let i = 0; i < tiles.length; i++) {
      const dot = document.createElement('button');
      dot.classList.add('indicator');

      if (i === currentIndex) {
        dot.classList.add('active');
      }

      dot.addEventListener('click', () => {
        currentIndex = i;
        setPositionByIndex();
        updateIndicators();
      });

      indicatorsContainer.appendChild(dot);
    }
  }

  updateIndicators();

  prevBtn.addEventListener('click', () => {
    currentIndex = currentIndex > 0 ? currentIndex - 1 : tiles.length - 1;
    setPositionByIndex();
    updateIndicators();
  });

  nextBtn.addEventListener('click', () => {
    currentIndex = currentIndex < tiles.length - 1 ? currentIndex + 1 : 0;
    setPositionByIndex();
    updateIndicators();
  });

  carousel.addEventListener('mousedown', dragStart);
  carousel.addEventListener('mouseup', dragEnd);
  carousel.addEventListener('mouseleave', dragEnd);
  carousel.addEventListener('mousemove', dragAction);

  carousel.addEventListener('touchstart', dragStart);
  carousel.addEventListener('touchend', dragEnd);
  carousel.addEventListener('touchmove', dragAction);

  function dragStart(event) {
    isDragging = true;
    startPos = getPositionX(event);
    carousel.classList.add('grabbing');
    cancelAnimationFrame(animationID);
  }

  function dragEnd() {
    if (!isDragging) return;

    isDragging = false;
    carousel.classList.remove('grabbing');

    const movedBy = currentTranslate - prevTranslate;

    if (movedBy < -100 && currentIndex < tiles.length - 1) {
      currentIndex++;
    }

    if (movedBy > 100 && currentIndex > 0) {
      currentIndex--;
    }

    setPositionByIndex();
    updateIndicators();
  }

  function dragAction(event) {
    if (!isDragging) return;

    const currentPosition = getPositionX(event);
    const diff = currentPosition - startPos;
    currentTranslate = prevTranslate + diff;
    setCarouselPosition();
  }

  function getPositionX(event) {
    return event.type.includes('mouse') ? event.pageX : event.touches[0].clientX;
  }

  window.addEventListener('resize', setPositionByIndex);
  setPositionByIndex();

  function startAutoSlide() {
    autoSlideInterval = setInterval(() => {
      currentIndex = currentIndex < tiles.length - 3 ? currentIndex + 1 : 0;
      setPositionByIndex();
      updateIndicators();
    }, 8000);
  }

  startAutoSlide();
})();

document.addEventListener('DOMContentLoaded', () => {
  const locations = <?php echo json_encode($airports, JSON_UNESCAPED_UNICODE); ?>;

  function normalizeText(text) {
    return text
      .toLowerCase()
      .trim()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
  }

  function findBestMatch(value, locations) {
    const normalizedValue = normalizeText(value);

    return locations.find(location => {
      const normalizedLocation = normalizeText(location);
      const cityName = normalizeText(location.replace(/\s*\[[A-Z]{3}\]$/, ''));
      const airportCodeMatch = location.match(/\[([A-Z]{3})\]$/);
      const airportCode = airportCodeMatch ? airportCodeMatch[1].toLowerCase() : '';

      return (
        normalizedLocation === normalizedValue ||
        cityName === normalizedValue ||
        airportCode === normalizedValue
      );
    });
  }

  function setupAutocomplete(inputId, suggestionsId, otherInputId) {
    const input = document.getElementById(inputId);
    const suggestionsBox = document.getElementById(suggestionsId);
    const otherInput = document.getElementById(otherInputId);

    if (!input || !suggestionsBox || !otherInput) {
      return;
    }

    input.addEventListener('input', () => {
      const query = normalizeText(input.value);
      const otherValue = normalizeText(otherInput.value);
      suggestionsBox.innerHTML = '';

      if (!query) {
        return;
      }

      const filteredLocations = locations.filter(location => {
        const normalizedLocation = normalizeText(location);
        const normalizedCity = normalizeText(location.replace(/\s*\[[A-Z]{3}\]$/, ''));

        return (
          (normalizedLocation.includes(query) || normalizedCity.includes(query)) &&
          normalizeText(location) !== otherValue
        );
      });

      filteredLocations.forEach(location => {
        const suggestionItem = document.createElement('div');
        suggestionItem.textContent = location;

        suggestionItem.addEventListener('click', () => {
          input.value = location;
          suggestionsBox.innerHTML = '';
        });

        suggestionsBox.appendChild(suggestionItem);
      });
    });

    input.addEventListener('blur', () => {
      setTimeout(() => {
        const bestMatch = findBestMatch(input.value, locations);
        const otherValue = otherInput.value.trim();

        if (bestMatch && bestMatch !== otherValue) {
          input.value = bestMatch;
        }

        suggestionsBox.innerHTML = '';
      }, 150);
    });

    document.addEventListener('click', event => {
      if (!input.contains(event.target) && !suggestionsBox.contains(event.target)) {
        suggestionsBox.innerHTML = '';
      }
    });
  }

  setupAutocomplete('from', 'from-suggestions', 'to');
  setupAutocomplete('to', 'to-suggestions', 'from');

  const form = document.querySelector('.flight-form');

  if (form) {
    form.addEventListener('submit', event => {
      event.preventDefault();
      alert('Flight search is currently unavailable in the English demo version.');
    });
  }

  const subscribeButton = document.querySelector('.submit-btn');

  if (subscribeButton) {
    subscribeButton.addEventListener('click', () => {
      alert('Newsletter subscription is currently unavailable in the English demo version.');
    });
  }
});
</script>