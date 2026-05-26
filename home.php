<?php
if (!defined('APP_ACCESS')) exit('Brak dostępu');
?>

<body>

  <!-- Sekcja z tłem i formularzem -->
  <div class="bg-section-border">
    <div class="bg-section">
      <div class="flight-container">
        <!-- Nagłówek formularza -->
        <div class="header-title">Loty</div>
        <!-- Formularz -->
        <form action="index.php" method="post" class="flight-form">
          <input type="hidden" name="flight_search" value="1">
          <div class="row">
            <div class="form-group3">
                <div class="select-wrapper">
                    <select id="trip-type" name="trip_type">
                    <option value="round-trip">W obie strony</option>
                    <option value="one-way">W jedną stronę</option>
                    </select>
                </div>
                </div>
            <div class="passenger-group">
                <label for="passengers">Ilość pasażerów:</label>
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
              <input type="text" id="from" name="from" placeholder="Skąd" required autocomplete="off">
              <div id="from-suggestions" class="autocomplete-suggestions"></div>
            </div>
            <div class="form-group2">
              <input type="text" id="to" name="to" placeholder="Dokąd" required autocomplete="off">
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
                      placeholder="Wylot" 
                      required 
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
                      placeholder="Powrót" 
                      required 
                      min="<?= date('Y-m-d') ?>"
                      onfocus="this.type='date';" 
                      onblur="if(!this.value) this.type='text';"
                  />
              </div>
            </div>
            <div class="form-group2">
              <button type="submit" class="search-button">Szukaj</button>
            </div>
          </div>
        </form>
        
      </div>
    </div>
  </div>
  <!-- LINIA PRZED MAPA -->
  <div class="line-container">
    <div class="line-left"></div>
    <div class="text-linia">POZNAJ NASZĄ OFERTĘ POŁĄCZEŃ</div>
    <div class="line-right"></div>
  </div>
  <!--MAPA-->
  <div class="map">
    <img src="img/mapa.png">
  </div>
  <div class="odkryj-wiecej">
    <h2>Z WronAir ODKRYJESZ WIĘCEJ...</h2>
    <p>Dzięki szerokiej siatce połączeń odwiedzisz nie tylko największe światowe metropolie, ale także ukryte perełki, o których marzyłeś! Komfortowe loty, 
      atrakcyjne ceny i przyjazna obsługa sprawią, że każda podróż stanie się niezapomnianą przygodą.</br>

    </br>WronAir - Twoje skrzydła do świata!</br>

    </br>Już dziś zaplanuj swoją podróż i zrealizuj swoje marzenia!</br>
    </p>
  </div>
  <!--KAFELKI-->
  <!-- Kontener karuzeli -->
  <div class="carousel-container">
    <div class="carousel" id="carousel">
      <!-- Tile 1 -->
      <div class="tile">
        <a href="#">
          <img src="img/krakow.webp" alt="Kraków">
          <div class="tile-info">
            <h3>Kraków</h3>
            <p class="price"><span style="font-size: 16px;">już od </span>500 zł*</p>
          </div>
        </a>
      </div>
      <!-- Tile 2 -->
      <div class="tile">
        <a href="#">
          <img src="img/los_angeles.webp" alt="Los Angeles">
          <div class="tile-info">
            <h3>Los Angeles</h3>
            <p class="price"><span style="font-size: 16px;">już od </span>1099 zł*</p>
          </div>
        </a>
      </div>
      <!-- Tile 3 -->
      <div class="tile">
        <a href="#">
          <img src="img/londyn.webp" alt="Londyn">
          <div class="tile-info">
            <h3>Londyn</h3>
            <p class="price"><span style="font-size: 16px;">już od </span>475 zł*</p>
          </div>
        </a>
      </div>
      <!-- Tile 4 -->
      <div class="tile">
        <a href="#">
          <img src="img/rzym.webp" alt="Rzym">
          <div class="tile-info">
            <h3>Rzym</h3>
            <p class="price"><span style="font-size: 16px;">już od </span>398 zł*</p>
          </div>
        </a>
      </div>
      <div class="tile">
        <a href="#">
          <img src="img/new_jork.webp" alt="Nowy Jork">
          <div class="tile-info">
            <h3>Nowy Jork</h3>
            <p class="price"><span style="font-size: 16px;">już od </span>1400 zł*</p>
          </div>
        </a>
      </div>
      <div class="tile">
        <a href="#">
          <img src="img/barcelona.webp" alt="Barcelona">
          <div class="tile-info">
            <h3>Barcelona</h3>
            <p class="price"><span style="font-size: 16px;">już od </span>500 zł*</p>
          </div>
        </a>
      </div>
      <!-- Tile 7 -->
      <div class="tile">
        <a href="#">
          <img src="img/ateny.webp" alt="Ateny">
          <div class="tile-info">
            <h3>Ateny</h3>
            <p class="price"><span style="font-size: 16px;">już od </span>350 zł*</p>
          </div>
        </a>
      </div>
      <div class="tile_more">
        <a href="oferta7.html">
          <img src="img/plus.png" alt="more">
          <div class="tile-info_more">
            <h3>Zobacz więcej ...</h3>
          </div>
        </a>
      </div>
    </div>
    
    <!-- Nawigacja (przyciski) -->
    <div class="carousel-nav">
      <button id="prev">◀</button>
      <button id="next">▶</button>
    </div>
  </div>
  
  <!-- Wskaźniki -->
  <div class="carousel-indicators" id="carouselIndicators">
    
  </div>


  <!--NEWSLETTER-->
  <div class="newsletter-container">
    <h2>Aby nie przegapić super okazji dołącz do naszego Newsletter'a</h2>
    <div class="input-group">
        <input type="text" placeholder="Twoje imię*" />
        <input type="email" placeholder="Twój adres e-mail*" />
        <button class="submit-btn">Zapisz się</button>
    </div>
    <p class="text-info">
        Zapisując się do Newslettera, wyrażam zgodę na otrzymywanie informacji handlowych od WronAir Sp. z o.o. Mogę cofnąć zgodę w każdym czasie. Dane będą przetwarzane do czasu cofnięcia zgody. Wyrażenie zgody jest niezbędne do realizacji umowy/otrzymanie kodu rabatowego.
    </p>
    <div class="checkbox-container">
        <label>
            <input type="checkbox">
            Tak, wyrażam zgodę na otrzymywanie informacji handlowych od firmy WronAir.* 
        </label>
      </br>
    </br>
        <p>
            Administrator przetwarza dane zgodnie z 
            <a href="#">Polityką Prywatności</a>.
            Mam prawo dostępu do danych, sprostowania, usunięcia lub ograniczenia przetwarzania, prawo sprzeciwu, prawo wniesienia skargi do organu nadzorczego lub przeniesienia danych. Wyrażoną powyżej zgodę można wycofać w dowolnym momencie, kontaktując się z Administratorem na adres: <a href="mailto:rodo@wronair.pl">rodo@wronair.pl</a>
        </p>
    </div>
  </div>
  

  <!-- Skrypt do obsługi przycisków (hamburger, wybór języka) -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
    const tripTypeSelect = document.getElementById('trip-type');
    const returnInputContainer = document.querySelector('.form-half:nth-child(2)'); 
    const departureInputContainer = document.querySelector('.form-half:nth-child(1)');
    const departureDateInput = document.getElementById('departure-date');
    const returnDateInput = document.getElementById('return');

    const today = new Date().toISOString().split('T')[0];

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

    // Funkcja zmieniająca widoczność pola "Powrót"
    function updateReturnVisibility() {
  const returnInput = document.getElementById('return');

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
    tripTypeSelect.addEventListener('change', updateReturnVisibility);
  });
  
  
    (function() {
      const carousel = document.getElementById('carousel');
      const prevBtn = document.getElementById('prev');
      const nextBtn = document.getElementById('next');
      const indicatorsContainer = document.getElementById('carouselIndicators');
      const tiles = document.querySelectorAll('.tile');
      let currentIndex = 0;
      let isDragging = false;
      let startPos = 0;
      let currentTranslate = 0;
      let prevTranslate = 0;
      let animationID;
      
      //funkcja ustalająca szerokość kafelka
      function getTileWidth() {
        return tiles[0].offsetWidth + parseInt(getComputedStyle(tiles[0]).marginLeft) + parseInt(getComputedStyle(tiles[0]).marginRight);
      }
      
      //uaktualnienie pozycji karuzeli
      function setPositionByIndex() {
        currentTranslate = -currentIndex * getTileWidth();
        prevTranslate = currentTranslate;
        setCarouselPosition();
      }
      
      function setCarouselPosition() {
        carousel.style.transform = `translateX(${currentTranslate}px)`;
      }
      
      //Aktualizacja wskaźników
      function updateIndicators() {
        indicatorsContainer.innerHTML = '';
        for (let i = 0; i < tiles.length; i++) {
          const dot = document.createElement('button');
          dot.classList.add('indicator');
          if (i === currentIndex) dot.classList.add('active');
          dot.addEventListener('click', () => {
            currentIndex = i;
            setPositionByIndex();
            updateIndicators();
          });
          indicatorsContainer.appendChild(dot);
        }
      }
      
      updateIndicators();
      
      //Obsługa przycisków nawigacji
      prevBtn.addEventListener('click', () => {
        currentIndex = (currentIndex > 0) ? currentIndex - 1 : tiles.length - 1;
        setPositionByIndex();
        updateIndicators();
      });
      
      nextBtn.addEventListener('click', () => {
        currentIndex = (currentIndex < tiles.length - 1) ? currentIndex + 1 : 0;
        setPositionByIndex();
        updateIndicators();
      });
      
      // przesuwanie
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
        //anulujemy animację ( jeśli jest )
        cancelAnimationFrame(animationID);
      }
      
      function dragEnd() {
        if (!isDragging) return;
        isDragging = false;
        carousel.classList.remove('grabbing');
        // Obliczamy przesunięcie i aktualizujemy currentIndex
        const movedBy = currentTranslate - prevTranslate;
        if (movedBy < -100 && currentIndex < tiles.length - 1)
          currentIndex++;
        if (movedBy > 100 && currentIndex > 0)
          currentIndex--;
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
      
      // Aktualizacja pozycji przy zmianie rozmiaru
      window.addEventListener('resize', setPositionByIndex);
      setPositionByIndex();
      
    // Automatyczne przesuwanie karuzeli co 8 sekund
    function startAutoSlide() {
      autoSlideInterval = setInterval(() => {
        currentIndex = currentIndex < tiles.length - 3 ? currentIndex + 1 : 0;
        setPositionByIndex();
        updateIndicators();
      }, 8000); // 8 sekund
    }
    // Funkcja resetująca automatyczne przesuwanie karuzeli
    function resetAutoSlide() {
      clearInterval(autoSlideInterval);
      startAutoSlide();
    }
    // Uruchomienie automatycznego przesuwania karuzeli
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
      } else if (input.value.trim() !== '') {
        input.value = '';
      }

      suggestionsBox.innerHTML = '';
    }, 150);
  });

  document.addEventListener('click', (event) => {
    if (!input.contains(event.target) && !suggestionsBox.contains(event.target)) {
      suggestionsBox.innerHTML = '';
    }
  });
}
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.flight-form');
  const fromInput = document.getElementById('from');
  const toInput = document.getElementById('to');

  if (form) {
    form.addEventListener('submit', (e) => {
      const fromMatch = findBestMatch(fromInput.value, locations);
      const toMatch = findBestMatch(toInput.value, locations);

      if (!fromMatch) {
        e.preventDefault();
        alert('Wybierz poprawne lotnisko w polu „Skąd” z listy podpowiedzi.');
        fromInput.focus();
        return;
      }

      if (!toMatch) {
        e.preventDefault();
        alert('Wybierz poprawne lotnisko w polu „Dokąd” z listy podpowiedzi.');
        toInput.focus();
        return;
      }

      fromInput.value = fromMatch;
      toInput.value = toMatch;

      if (fromInput.value === toInput.value) {
        e.preventDefault();
        alert('Lotnisko wylotu i przylotu nie mogą być takie same.');
        toInput.focus();
      }
    });
  }
});

  //autouzupelnianie
  setupAutocomplete('from', 'from-suggestions', 'to');
  setupAutocomplete('to', 'to-suggestions', 'from');
});
</script>