<?php
if (!defined('APP_ACCESS')) exit('Brak dostępu');
?>
<main>
    <h1 class="main-title">Sklep Duty-Free</h1>
    
    <p class="main-description">
        Linia lotnicza WronAir zaprasza do skorzystania z wyjątkowej oferty sklepu Duty-Free dostępnego na pokładzie. 
        Nasz asortyment obejmuje szeroki wybór luksusowych perfum, stylowych zegarków oraz eleganckiej biżuterii. 
        Dzięki konkurencyjnym cenom bez podatku, możesz pozwolić sobie na zakup wysokiej jakości produktów w atrakcyjnych cenach.
        Katalog zawiera najnowsze kolekcje znanych marek, które idealnie sprawdzą się jako prezent dla bliskich lub wyjątkowa pamiątka z podróży. 
        Pasażerowie mogą dokonywać zakupów w trakcie lotu, a załoga chętnie doradzi w wyborze. WronAir sprawia, że Twoja podróż to nie tylko komfort, 
        ale także okazja, by poczuć odrobinę luksusu na wysokościach!
    </p>

    <div class="offers">

        <!-- PERFUMY -->
        <div class="type">
            <h2 class="type-heading">Perfumy</h2>
            <div class="carousel-container">
                <div class="carousel" id="carousel-perfumy">
                    <div class="tile"><a href="#"><img src="img/duty_free/perfumA.webp" alt="Perfum A"><div class="tile-info"><h3>Perfum A</h3><p class="price">Cena 250 zł</p></div></a></div>
                    <div class="tile"><a href="#"><img src="img/duty_free/perfumB.webp" alt="Perfum B"><div class="tile-info"><h3>Perfum B</h3><p class="price">Cena 300 zł</p></div></a></div>
                    <div class="tile"><a href="#"><img src="img/duty_free/perfumC.webp" alt="Perfum C"><div class="tile-info"><h3>Perfum C</h3><p class="price">Cena 400 zł</p></div></a></div>
                    <div class="tile"><a href="#"><img src="img/duty_free/perfumD.webp" alt="Perfum D"><div class="tile-info"><h3>Perfum D</h3><p class="price">Cena 350 zł</p></div></a></div>
                </div>
                <div class="carousel-nav">
                    <button class="df-prev">◀</button>
                    <button class="df-next">▶</button>
                </div>
            </div>
        </div>

        <!-- ZEGARKI -->
        <div class="type">
            <h2 class="type-heading">Zegarki</h2>
            <div class="carousel-container">
                <div class="carousel" id="carousel-zegarki">
                    <div class="tile"><a href="#"><img src="img/duty_free/zegarA.webp" alt="Zegarek A"><div class="tile-info"><h3>Zegarek A</h3><p class="price">Cena 1200 zł</p></div></a></div>
                    <div class="tile"><a href="#"><img src="img/duty_free/zegarB.webp" alt="Zegarek B"><div class="tile-info"><h3>Zegarek B</h3><p class="price">Cena 1500 zł</p></div></a></div>
                    <div class="tile"><a href="#"><img src="img/duty_free/zegarC.webp" alt="Zegarek C"><div class="tile-info"><h3>Zegarek C</h3><p class="price">Cena 1700 zł</p></div></a></div>
                    <div class="tile"><a href="#"><img src="img/duty_free/zegarD.webp" alt="Zegarek D"><div class="tile-info"><h3>Zegarek D</h3><p class="price">Cena 2000 zł</p></div></a></div>
                </div>
                <div class="carousel-nav">
                    <button class="df-prev">◀</button>
                    <button class="df-next">▶</button>
                </div>
            </div>
        </div>

        <!-- BIŻUTERIA -->
        <div class="type">
            <h2 class="type-heading">Biżuteria</h2>
            <div class="carousel-container">
                <div class="carousel" id="carousel-bizuteria">
                    <div class="tile"><a href="#"><img src="img/duty_free/goldA.webp" alt="Biżuteria A"><div class="tile-info"><h3>Biżuteria A</h3><p class="price">Cena 3000 zł</p></div></a></div>
                    <div class="tile"><a href="#"><img src="img/duty_free/goldB.webp" alt="Biżuteria B"><div class="tile-info"><h3>Biżuteria B</h3><p class="price">Cena 4000 zł</p></div></a></div>
                    <div class="tile"><a href="#"><img src="img/duty_free/goldC.webp" alt="Biżuteria C"><div class="tile-info"><h3>Biżuteria C</h3><p class="price">Cena 2500 zł</p></div></a></div>
                    <div class="tile"><a href="#"><img src="img/duty_free/goldD.webp" alt="Biżuteria D"><div class="tile-info"><h3>Biżuteria D</h3><p class="price">Cena 3500 zł</p></div></a></div>
                </div>
                <div class="carousel-nav">
                    <button class="df-prev">◀</button>
                    <button class="df-next">▶</button>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
document.querySelectorAll('.carousel-container').forEach(container => {
    const carousel = container.querySelector('.carousel');
    const originalTiles = Array.from(carousel.querySelectorAll('.tile'));
    
    [1, 2, 3].forEach(() => {
    originalTiles.forEach(tile => {
        carousel.appendChild(tile.cloneNode(true));
    });
    });

    const allTiles = carousel.querySelectorAll('.tile');
    const total = originalTiles.length;
    let currentIndex = 0;
    let isTransitioning = false;

    function getTileWidth() {
        return allTiles[0].offsetWidth
            + parseInt(getComputedStyle(allTiles[0]).marginLeft)
            + parseInt(getComputedStyle(allTiles[0]).marginRight);
    }

    function setPosition(animated = true) {
        carousel.style.transition = animated ? 'transform 0.4s ease' : 'none';
        carousel.style.transform = `translateX(-${currentIndex * getTileWidth()}px)`;
    }

    const prevBtn = container.querySelector('.df-prev');
    const nextBtn = container.querySelector('.df-next');

    nextBtn.addEventListener('click', () => {
        if (isTransitioning) return;
        isTransitioning = true;
        currentIndex++;
        setPosition(true);

        // Gdy dojdzie do klonów — skocz cicho na oryginał
        carousel.addEventListener('transitionend', () => {
            if (currentIndex >= total) {
                currentIndex = 0;
                setPosition(false);
            }
            isTransitioning = false;
        }, { once: true });
    });

    prevBtn.addEventListener('click', () => {
        if (isTransitioning) return;
        isTransitioning = true;

        if (currentIndex <= 0) {
            // Skocz cicho na koniec klonów
            currentIndex = total;
            setPosition(false);
            requestAnimationFrame(() => requestAnimationFrame(() => {
                currentIndex--;
                setPosition(true);
                carousel.addEventListener('transitionend', () => {
                    isTransitioning = false;
                }, { once: true });
            }));
        } else {
            currentIndex--;
            setPosition(true);
            carousel.addEventListener('transitionend', () => {
                isTransitioning = false;
            }, { once: true });
        }
    });

    window.addEventListener('resize', () => setPosition(false));
    setPosition(false);
});
</script>

