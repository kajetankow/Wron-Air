<?php
if (!defined('APP_ACCESS')) exit('Brak dostępu');
?>

<link rel="stylesheet" href="style/oferty_style.css" />
<link rel="stylesheet" href="style/style_headfoot.css" />
<main>
    <h1 class="main-title">Posiłki</h1>
    <p class="main-description">
        Linia lotnicza WronAir dba o komfort pasażerów, oferując starannie dobrane posiłki na pokładzie.
        W menu znajdują się różnorodne dania główne, które zadowolą zarówno miłośników kuchni tradycyjnej, 
        jak i bardziej egzotycznych smaków. Oprócz tego serwujemy szeroki wybór przekąsek, idealnych na krótsze loty lub jako dodatek do posiłku.
        Napoje, zarówno gorące, jak i zimne, są dostępne przez cały czas trwania lotu, a nasza oferta obejmuje również wyselekcjonowane alkohole. 
        Pasażerowie mogą wybierać spośród opcji wegetariańskich, wegańskich oraz specjalnych posiłków dietetycznych, zamawianych przed wylotem. 
        WronAir sprawia, że podróż to nie tylko wygoda, ale także wyjątkowa uczta dla podniebienia!
    </p>

    <div class="offers">

        <!-- DANIA GŁÓWNE -->
        <div class="type">
            <h2 class="type-heading">Dania główne</h2>
            <div class="carousel-container">
                <div class="carousel" id="carousel-dania">
                    <div class="tile"><a href="#"><img src="img/posilki/stek.webp" alt="Stek z sałatką"><div class="tile-info"><h3>Stek z sałatką</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/posilki/pulpet.webp" alt="Pulpet z ziemniakami"><div class="tile-info"><h3>Pulpet z ziemniakami</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/posilki/kluski.webp" alt="Knedle z prażoną cebulką"><div class="tile-info"><h3>Knedle z prażoną cebulką</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/posilki/sushi.webp" alt="Japońskie sushi"><div class="tile-info"><h3>Japońskie sushi</h3></div></a></div>
                </div>
                <div class="carousel-nav">
                    <button class="df-prev">◀</button>
                    <button class="df-next">▶</button>
                </div>
            </div>
        </div>

        <!-- NAPOJE -->
        <div class="type">
            <h2 class="type-heading">Napoje</h2>
            <div class="carousel-container">
                <div class="carousel" id="carousel-napoje">
                    <div class="tile"><a href="#"><img src="img/posilki/szampan.webp" alt="Szampan"><div class="tile-info"><h3>Szampan</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/posilki/cola.webp" alt="Cola"><div class="tile-info"><h3>Cola</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/posilki/woda.webp" alt="Woda mineralna"><div class="tile-info"><h3>Woda mineralna</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/posilki/oranzada.webp" alt="Oranżada"><div class="tile-info"><h3>Oranżada</h3></div></a></div>
                </div>
                <div class="carousel-nav">
                    <button class="df-prev">◀</button>
                    <button class="df-next">▶</button>
                </div>
            </div>
        </div>

        <!-- PRZEKĄSKI -->
        <div class="type">
            <h2 class="type-heading">Przekąski</h2>
            <div class="carousel-container">
                <div class="carousel" id="carousel-przekaski">
                    <div class="tile"><a href="#"><img src="img/posilki/fries.webp" alt="Frytki"><div class="tile-info"><h3>Frytki</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/posilki/chipsy.webp" alt="Chipsy ziemniaczane"><div class="tile-info"><h3>Chipsy ziemniaczane</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/posilki/orzechy.webp" alt="Orzeszki"><div class="tile-info"><h3>Orzeszki</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/posilki/batony.webp" alt="Batony"><div class="tile-info"><h3>Batony</h3></div></a></div>
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
    const total = originalTiles.length;

    [1, 2, 3].forEach(() => {
        originalTiles.forEach(tile => {
            carousel.appendChild(tile.cloneNode(true));
        });
    });

    let currentIndex = total;
    let isTransitioning = false;

    function getTileWidth() {
        const tile = carousel.querySelectorAll('.tile')[0];
        return tile.offsetWidth
            + parseInt(getComputedStyle(tile).marginLeft)
            + parseInt(getComputedStyle(tile).marginRight);
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
        carousel.addEventListener('transitionend', () => {
            if (currentIndex >= total * 2) {
                currentIndex = total;
                setPosition(false);
            }
            isTransitioning = false;
        }, { once: true });
    });

    prevBtn.addEventListener('click', () => {
        if (isTransitioning) return;
        isTransitioning = true;
        if (currentIndex <= total) {
            currentIndex = total * 2;
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
