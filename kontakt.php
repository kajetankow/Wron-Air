<?php
if (!defined('APP_ACCESS')) exit('Brak dostępu');
?>

<main>
    <div class="contact-all">
        <h1>Kontakt</h1>
        <div class="contact-data">

            <div class="contact-info">
                <div class="contact-details">
                    <h2>WronAir Sp. z o.o.</h2>
                    <p>Aleje Jerozolimskie 65/79</p>
                    <p>00-697 Warszawa</p>
                    <p>tel. 123 456 789</p>
                    <p>e-mail: wronair@kontakt.pl</p>
                </div>
                <div class="contact-form">
                    <h2>Formularz zgłoszeniowy</h2>
                    <form action="#" method="post">
                        <div class="form-half">
                            <label for="name">Imię i nazwisko</label>
                            <input type="text" placeholder="Imię i nazwisko" id="name" name="name" required />
                        </div>
                        <div class="form-half">
                            <label for="email">Adres e-mail</label>
                            <input type="email" placeholder="Adres e-mail" id="email" name="email" required />
                        </div>
                        <div class="form-half">
                            <label for="phone">Numer telefonu</label>
                            <input type="tel" placeholder="Numer telefonu" id="phone" name="phone" required />
                        </div>
                        <div class="form-half">
                            <label for="orderCode">Numer rezerwacji</label>
                            <input type="text" placeholder="Numer zamówienia" id="orderCode" maxlength="12" required />
                        </div>
                        <div class="form-half">
                            <label for="message">Wiadomość</label>
                            <textarea id="message" placeholder="Wiadomość" name="message" rows="5" required></textarea>
                        </div>
                        <div class="form-full">
                            <button type="submit">Wyślij</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="contact-map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4887.757645227929!2d20.999781993228705!3d52.227419331796845!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x471ecd8ccc62aea5%3A0x4a698a1f90d7fcf2!2sPLL%20LOT%20-%20biuro%20miejskie!5e0!3m2!1spl!2spl!4v1737400172055!5m2!1spl!2spl"
                        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>

        </div>
    </div>
</main>
