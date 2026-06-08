<?php
if (!defined('APP_ACCESS')) {
    exit('Brak dostępu');
}

$upgradeData = $_SESSION['upgrade_data'] ?? null;

if (
    empty($upgradeData) ||
    empty($upgradeData['order_code']) ||
    empty($upgradeData['reservation_id']) ||
    empty($upgradeData['passenger_id']) ||
    empty($upgradeData['flight_direction']) ||
    empty($upgradeData['upgrade_choice'])
) {
    header('Location: upgrade');
    exit;
}

$orderCode = $upgradeData['order_code'];
$reservationId = (int)$upgradeData['reservation_id'];
$passengerId = (int)$upgradeData['passenger_id'];
$flightDirection = $upgradeData['flight_direction'];
$upgradeChoice = $upgradeData['upgrade_choice'];

$luggageType = $upgradeData['luggage_type'] ?? '';
$luggageCount = (float)($upgradeData['luggage_count'] ?? 0);
$luggagePriceDiff = (float)($upgradeData['luggage_price_diff'] ?? 0);

$newClass = strtoupper(trim($upgradeData['new_class'] ?? ''));
$newSeat = strtoupper(trim($upgradeData['new_seat'] ?? ''));
$seatPrice = (float)($upgradeData['seat_price'] ?? 0);

if (!isset($pdo)) {
    require_once __DIR__ . '/config/db.php';
    $pdo = getDb();
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$upgradeInfo = '';
$error = '';

try {
    $pdo->beginTransaction();

    if ($upgradeChoice === 'luggage') {
        $stmt = $pdo->prepare("
            UPDATE reservation_passengers
            SET baggage_count = :count
            WHERE id = :pid
              AND reservation_id = :rid
        ");
        $stmt->execute([
            ':count' => $luggageCount,
            ':pid' => $passengerId,
            ':rid' => $reservationId
        ]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Nie znaleziono pasażera dla tej rezerwacji.');
        }

        $stmt = $pdo->prepare("
            UPDATE reservations
            SET total_price = total_price + :diff
            WHERE id = :rid
              AND reservation_code = :code
        ");
        $stmt->execute([
            ':diff' => $luggagePriceDiff,
            ':rid' => $reservationId,
            ':code' => $orderCode
        ]);

        $label = match ($luggageType) {
            '15' => '15 kg',
            '23' => '23 kg',
            '2x23' => '2 × 23 kg',
            default => $luggageType
        };

        $diffText = number_format(abs($luggagePriceDiff), 2, ',', ' ') . '&nbsp;zł';
        $actionText = $luggagePriceDiff < 0 ? 'zwrot' : 'dopłata';

        if ($luggagePriceDiff == 0) {
            $upgradeInfo = "Wybrano bagaż rejestrowany:&nbsp;<strong>" . e($label) . "</strong> bez dodatkowych kosztów.";
        } else {
            $upgradeInfo = "Zmieniono bagaż rejestrowany na:&nbsp;<strong>" . e($label) . "</strong> ({$actionText}:&nbsp;{$diffText}).";
        }

    } elseif ($upgradeChoice === 'seat') {
        if ($newClass === '' || $newSeat === '') {
            throw new RuntimeException('Nieprawidłowe dane miejsca.');
        }

        $stmt = $pdo->prepare("
            SELECT reservation_id, seat_number
            FROM reservation_passengers
            WHERE id = :pid
              AND reservation_id = :rid
            LIMIT 1
        ");
        $stmt->execute([
            ':pid' => $passengerId,
            ':rid' => $reservationId
        ]);

        $passData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$passData) {
            throw new RuntimeException('Nie znaleziono pasażera dla tej rezerwacji.');
        }

        $oldSeat = strtoupper(trim($passData['seat_number'] ?? ''));

        $stmt = $pdo->prepare("
            SELECT flight_id, flight_date
            FROM reservation_flights
            WHERE reservation_id = :rid
              AND flight_direction = :direction
            LIMIT 1
        ");
        $stmt->execute([
            ':rid' => $reservationId,
            ':direction' => $flightDirection
        ]);

        $flightData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$flightData) {
            throw new RuntimeException('Nie znaleziono lotu dla tej rezerwacji.');
        }

        if ($oldSeat !== $newSeat) {
            if ($oldSeat !== '') {
                $stmt = $pdo->prepare("
                    DELETE FROM flight_occupied_seats
                    WHERE flight_id = :flight_id
                    AND flight_date = :flight_date
                    AND seat_number = :seat_number
                ");
                $stmt->execute([
                    ':flight_id' => $flightData['flight_id'],
                    ':flight_date' => $flightData['flight_date'],
                    ':seat_number' => $oldSeat
                ]);
            }

            $stmt = $pdo->prepare("
                INSERT INTO flight_occupied_seats
                    (flight_id, flight_date, seat_number)
                VALUES
                    (:flight_id, :flight_date, :seat_number)
            ");
            $stmt->execute([
                ':flight_id' => $flightData['flight_id'],
                ':flight_date' => $flightData['flight_date'],
                ':seat_number' => $newSeat
            ]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Wybrane miejsce jest już zajęte.');
            }
}
        $stmt = $pdo->prepare("
            UPDATE reservation_flights
            SET ticket_type = :ticket_type,
                price = price + :diff
            WHERE reservation_id = :rid
              AND flight_direction = :direction
        ");
        $stmt->execute([
            ':ticket_type' => $newClass,
            ':diff' => $seatPrice,
            ':rid' => $reservationId,
            ':direction' => $flightDirection
        ]);

        $stmt = $pdo->prepare("
            UPDATE reservations
            SET total_price = total_price + :diff
            WHERE id = :rid
              AND reservation_code = :code
        ");
        $stmt->execute([
            ':diff' => $seatPrice,
            ':rid' => $reservationId,
            ':code' => $orderCode
        ]);

        $stmt = $pdo->prepare("
            UPDATE reservation_passengers
            SET seat_number = :seat_number
            WHERE id = :pid
              AND reservation_id = :rid
        ");
        $stmt->execute([
            ':seat_number' => $newSeat,
            ':pid' => $passengerId,
            ':rid' => $reservationId
        ]);

        $diffText = number_format(abs($seatPrice), 2, ',', ' ') . '&nbsp;zł';
        $actionText = $seatPrice < 0 ? 'zwrot' : 'dopłata';

        if ($seatPrice == 0) {
            $upgradeInfo = "Zmieniono klasę na&nbsp;<strong>" . e($newClass) . "</strong>, miejsce:&nbsp;<strong>" . e($newSeat) . "</strong> bez dodatkowych kosztów.";
        } else {
            $upgradeInfo = "Zmieniono klasę na&nbsp;<strong>" . e($newClass) . "</strong>, miejsce:&nbsp;<strong>" . e($newSeat) . "</strong> ({$actionText}:&nbsp;{$diffText}).";
        }

    } else {
        throw new RuntimeException('Nieprawidłowy typ upgrade.');
    }

    $pdo->commit();
    unset($_SESSION['upgrade_data']);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $error = 'Wystąpił błąd podczas zapisu zmian. Spróbuj ponownie później.';
}
?>

<main>
    <div class="text-linia">
        <span>Dziękujemy za wybranie WRONAIR!</span>
    </div>

    <div class="upgrade-end-container">
        <?php if ($error): ?>
            <h1 style="color:#C50914;">
                <?= e($error) ?>
            </h1>

            <a href="upgrade" class="confirm-button">
                Wróć do upgrade
            </a>
        <?php else: ?>
            <h1 id="order-status">
                Zmiana rezerwacji numer&nbsp;<strong><?= e($orderCode) ?></strong>&nbsp;została przetworzona pomyślnie.
            </h1>

            <h2 id="upgrade-info">
                <?= $upgradeInfo ?>
            </h2>

            <h2 class="end">
                Życzymy udanej podróży i do zobaczenia w przestworzach!
            </h2>

            <p>
                Przypominamy, że odprawy można dokonać online na 24 godziny przed wylotem
                lub osobiście na lotnisku na 1,5 godziny przed planowanym wylotem.
            </p>

            <p class="small-end">
                W razie pytań zapraszamy do kontaktu z naszym Działem Obsługi Klienta.
            </p>

            <a href="home" class="confirm-button">
                Wróć na stronę główną
            </a>
        <?php endif; ?>
    </div>
</main>