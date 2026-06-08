<?php
if (!defined('APP_ACCESS')) exit('Brak dostępu');

if (!isset($pdo)) {
    require_once __DIR__ . '/config/db.php';
    $pdo = getDb();
}

$reservationId = $_SESSION['saved_reservation_id'] ?? null;
$reservationCode = $_SESSION['reservation_code'] ?? null;

if (!$reservationId && !$reservationCode) {
    echo '<main class="content"><h1>Brak danych rezerwacji.</h1></main>';
    return;
}

if ($reservationId) {
    $stmt = $pdo->prepare("
        SELECT reservation_code, contact_email
        FROM reservations
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $reservationId
    ]);
} else {
    $stmt = $pdo->prepare("
        SELECT reservation_code, contact_email
        FROM reservations
        WHERE reservation_code = :reservation_code
        LIMIT 1
    ");

    $stmt->execute([
        ':reservation_code' => $reservationCode
    ]);
}

$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    echo '<main class="content"><h1>Nie znaleziono rezerwacji.</h1></main>';
    return;
}

$reservationCode = $reservation['reservation_code'] ?? 'unknown';
$email = $reservation['contact_email'] ?? 'unknown';
?>

<main>
    <div class="text-linia">
        <span>Dziękujemy za wybranie WRONAIR!</span>
    </div>

    <div class="upgrade-end-container">
        <h1>
            Rezerwacja numer <?php echo htmlspecialchars($reservationCode); ?> została opłacona i zaakceptowana.
        </h1>

        <h2>
            Potwierdzenie zostało wysłane na adres e-mail:
            <?php echo htmlspecialchars($email); ?>
        </h2>

        <h2 class="end">
            Życzymy udanej podróży i do zobaczenia w przestworzach!
        </h2>

        <p>
            Przypominamy, że odprawy można dokonać online na 24 godziny przed wylotem
            lub osobiście na lotnisku na 1,5 godziny przed planowanym wylotem.
            Prosimy o przybycie na lotnisko co najmniej godzinę przed wylotem
            oraz regularne sprawdzanie statusu lotu.
        </p>

        <p class="small-end">
            W razie pytań zapraszamy do kontaktu z naszym Działem Obsługi Klienta.
        </p>

        <a href="home" class="confirm-button">
            Wróć na stronę główną
        </a>
    </div>
</main>