<?php
if (!defined('APP_ACCESS')) exit('Brak dostępu');

if (!isset($pdo)) {
    require_once __DIR__ . '/config/db.php';
    $pdo = getDb();
}

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$checkInError = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_in_submit'])) {
    $pdo = getDb();

    $reservationCode = trim($_POST['reservation_code'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');

    if ($reservationCode === '' || $lastName === '') {
        $checkInError = 'Podaj numer rezerwacji oraz nazwisko.';
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                r.id,
                r.reservation_code,
                r.trip_type,
                r.contact_email,
                r.contact_country,
                r.contact_phone,
                r.total_price,
                r.created_at
            FROM reservations r
            INNER JOIN reservation_passengers p
                ON p.reservation_id = r.id
            WHERE r.reservation_code = :reservation_code
              AND LOWER(p.last_name) = LOWER(:last_name)
            LIMIT 1
        ");

        $stmt->execute([
            ':reservation_code' => $reservationCode,
            ':last_name' => $lastName
        ]);

        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reservation) {
            $checkInError = 'Nie znaleziono rezerwacji dla podanego numeru i nazwiska.';
        } else {
            $reservationId = (int)$reservation['id'];

            $stmt = $pdo->prepare("
                SELECT
                    passenger_index,
                    passenger_type,
                    title,
                    first_name,
                    middle_name,
                    last_name,
                    birth_date,
                    gender,
                    seat_number,
                    baggage_count
                FROM reservation_passengers
                WHERE reservation_id = :reservation_id
                ORDER BY passenger_index ASC
            ");

            $stmt->execute([
                ':reservation_id' => $reservationId
            ]);

            $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT
                    flight_direction,
                    flight_id,
                    ticket_type,
                    price,
                    flight_date
                FROM reservation_flights
                WHERE reservation_id = :reservation_id
                ORDER BY 
                    CASE 
                        WHEN flight_direction = 'departure' THEN 1
                        WHEN flight_direction = 'return' THEN 2
                        ELSE 3
                    END
            ");

            $stmt->execute([
                ':reservation_id' => $reservationId
            ]);

            $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $_SESSION['check_in_data'] = [
                'reservation' => $reservation,
                'passengers' => $passengers,
                'flights' => $flights
            ];

            $_SESSION['current_view'] = 'weryfikacja';

            echo '<script>window.location.href = "weryfikacja";</script>';
            return;
        }
    }
}
?>

<main class="content">
    <div class="line-container">
        <div class="text-linia">ODPRAWA ONLINE</div>
        <div class="line-right"></div>
    </div>

    <div class="parent-container">
        <?php if ($checkInError !== ''): ?>
            <div class="error-box">
                <?php echo htmlspecialchars($checkInError, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form class="container" method="post" action="odprawa">
            <input
                type="text"
                id="reservationNumber"
                name="reservation_code"
                placeholder="Numer rezerwacji"
                maxlength="10"
                required
            >

            <input
                type="text"
                id="lastName"
                name="last_name"
                placeholder="Nazwisko"
                required
            >

            <button type="submit" id="checkInButton" name="check_in_submit" disabled>
                ODPRAW SIĘ
            </button>
        </form>

        <p class="description">
            Odprawa online jest dostępna dopiero na 24 godziny przed planowanym wylotem.
            Pamiętaj, że odprawa jest jednorazowa, dlatego przed jej zakończeniem upewnij się,
            że wszystkie wprowadzone dane, takie jak imię, nazwisko, dokumenty podróży oraz dane kontaktowe,
            są poprawne. Podczas odprawy możesz dokonać wyboru miejsca w samolocie lub zmienić
            już przydzielone miejsce, w zależności od dostępności i zasad linii lotniczej.
            Po zakończeniu procesu otrzymasz kartę pokładową w formie elektronicznej na e-mail
            lub do aplikacji mobilnej, którą możesz również wydrukować – karta ta jest niezbędna
            do wejścia na pokład.
        </p>
    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const reservationNumberInput = document.getElementById("reservationNumber");
    const lastNameInput = document.getElementById("lastName");
    const checkInButton = document.getElementById("checkInButton");

    function validateInputs() {
        const reservationNumber = reservationNumberInput.value.trim();
        const lastName = lastNameInput.value.trim();

        checkInButton.disabled = !(reservationNumber && lastName);
    }

    reservationNumberInput.addEventListener("input", validateInputs);
    lastNameInput.addEventListener("input", validateInputs);
});
</script>