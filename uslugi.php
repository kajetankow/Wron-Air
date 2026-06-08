<?php
if (!defined('APP_ACCESS')) exit('Brak dostępu');
?>
<main>
    <section class="service-section">
        <h1 class="service-title">Wybierz szukaną usługę</h1>
        <div class="service-layout">
            <div class="service-options">
                <div class="option">
                    <img src="img/rozrywka.webp" alt="Rozrywka"/>
                    <button class="service-btn" onclick="window.location.href='rozrywka';">Rozrywka</button>
                </div>
                <div class="option">
                    <img src="img/posilki.webp" alt="Posiłki"/>
                    <button class="service-btn" onclick="window.location.href='posilki';">Posiłki</button>
                </div>
                <div class="option">
                    <img src="img/internet.webp" alt="Internet"/>
                    <button class="service-btn" onclick="window.location.href='internet';">Internet</button>
                </div>
                <div class="option">
                    <img src="img/perfumy.webp" alt="Duty-Free"/>
                    <button class="service-btn" onclick="window.location.href='duty_free';">Duty-Free</button>
                </div>
            </div>
        </div>
    </section>
</main>
