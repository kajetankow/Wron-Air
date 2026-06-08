<?php
if (!defined('APP_ACCESS')) exit('Brak dostępu');
?>

<main>
    <h1 class="main-title">Rozrywka</h1>
    <p class="main-description">
        Linia lotnicza WronAir oferuje system rozrywki, który umili każdą podróż. 
        W każdym fotelu znajduje się ekran dotykowy z dostępem do filmów, seriali i gier. 
        Nasza biblioteka jest regularnie aktualizowana, aby zapewnić dostęp do najnowszych produkcji. 
        Dzięki różnorodności gatunków każdy znajdzie coś dla siebie, 
        a młodsi pasażerowie mogą korzystać z treści przeznaczonych specjalnie dla dzieci.
        Fani gier mają do wyboru różne tytuły, które umilą czas podczas lotu. 
        WronAir sprawia, że każda podróż staje się nie tylko wygodna, ale i pełna rozrywki!
    </p>

    <div class="offers">

        <!-- FILMY -->
        <div class="type">
            <h2 class="type-heading">Filmy</h2>
            <div class="carousel-container">
                <div class="carousel" id="carousel-filmy">
                    <div class="tile"><a href="#"><img src="img/rozrywka/star_wars.webp" alt="Star Wars"><div class="tile-info"><h3>Star Wars</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/rozrywka/spider_man.webp" alt="Spider Man"><div class="tile-info"><h3>Spider Man</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/rozrywka/forest_gump.webp" alt="Forrest Gump"><div class="tile-info"><h3>Forrest Gump</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/rozrywka/lew.webp" alt="Król Lew"><div class="tile-info"><h3>Król Lew</h3></div></a></div>
                </div>
                <div class="carousel-nav">
                    <button class="df-prev">◀</button>
                    <button class="df-next">▶</button>
                </div>
            </div>
        </div>

        <!-- SERIALE -->
        <div class="type">
            <h2 class="type-heading">Seriale</h2>
            <div class="carousel-container">
                <div class="carousel" id="carousel-seriale">
                    <div class="tile"><a href="#"><img src="img/rozrywka/breaking_bad.webp" alt="Breaking Bad"><div class="tile-info"><h3>Breaking Bad</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/rozrywka/stranger_things.webp" alt="Stranger Things"><div class="tile-info"><h3>Stranger Things</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/rozrywka/gra_o_tron.webp" alt="Game of Thrones"><div class="tile-info"><h3>Game of Thrones</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/rozrywka/better_call_saul.webp" alt="Better Call Saul"><div class="tile-info"><h3>Better Call Saul</h3></div></a></div>
                </div>
                <div class="carousel-nav">
                    <button class="df-prev">◀</button>
                    <button class="df-next">▶</button>
                </div>
            </div>
        </div>

        <!-- GRY -->
        <div class="type">
            <h2 class="type-heading">Gry</h2>
            <div class="carousel-container">
                <div class="carousel" id="carousel-gry">
                    <div class="tile"><a href="#"><img src="img/rozrywka/angry_birds.webp" alt="Angry Birds"><div class="tile-info"><h3>Angry Birds</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/rozrywka/cut_rope.webp" alt="Cut the Rope"><div class="tile-info"><h3>Cut the Rope</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/rozrywka/flappy.webp" alt="Flappy Bird"><div class="tile-info"><h3>Flappy Bird</h3></div></a></div>
                    <div class="tile"><a href="#"><img src="img/rozrywka/chess.webp" alt="Szachy"><div class="tile-info"><h3>Szachy</h3></div></a></div>
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

    let currentIndex = total; // start na 1. kopii
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
