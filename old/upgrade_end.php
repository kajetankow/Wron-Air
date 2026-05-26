<?php
$orderCode     = strtoupper(trim($_GET['orderCode'] ?? ''));
$passengerId   = (int)($_GET['passengerId'] ?? 0);
$upgradeChoice = $_GET['upgradeChoice'] ?? '';
$luggageType   = $_GET['luggageType'] ?? '';
$luggageCount  = (int)($_GET['luggageCount'] ?? 0);
$luggagePrice  = (float)($_GET['luggagePrice'] ?? 0);
$newClass      = strtoupper(trim($_GET['newClass'] ?? ''));
$newSeat       = trim($_GET['newSeat'] ?? '');
$seatPrice     = (float)($_GET['seatPrice'] ?? 0);

require_once __DIR__ . '/config/db.php';
$pdo = getDb();

$upgradeInfo = '';
$error = '';

try {
    $pdo->beginTransaction();

    if ($upgradeChoice === 'luggage' && $passengerId && $luggageCount > 0) {
        $stmt = $pdo->prepare("
            UPDATE reservation_passengers
            SET baggage_count = :count
            WHERE id = :pid
        ");
        $stmt->execute([':count' => $luggageCount, ':pid' => $passengerId]);

        $label = match($luggageType) {
            '15'   => '15 kg',
            '23'   => '23 kg',
            '2x23' => '2 × 23 kg',
            default => $luggageType
        };
        $upgradeInfo = "Zakupili Państwo bagaż rejestrowany:&nbsp;<strong>{$label}</strong> (koszt:&nbsp;" . number_format($luggagePrice, 2, ',', ' ') . "&nbsp;zł).";

    } elseif ($upgradeChoice === 'seat' && $passengerId && $newClass && $newSeat) {
        $stmt = $pdo->prepare("SELECT reservation_id, seat_number FROM reservation_passengers WHERE id = :pid");
        $stmt->execute([':pid' => $passengerId]);
        $passData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $reservationId = $passData['reservation_id'] ?? null;
        $oldSeat = $passData['seat_number'] ?? null;

        if ($reservationId) {
            $stmt = $pdo->prepare("
                SELECT rf.flight_id, rf.flight_date
                FROM reservation_flights rf
                WHERE rf.reservation_id = :rid AND rf.flight_direction = 'departure'
            ");
            $stmt->execute([':rid' => $reservationId]);
            $flightData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($flightData) {
                if ($oldSeat && $oldSeat !== $newSeat) {
                    $stmtDelete = $pdo->prepare("
                        DELETE FROM flight_occupied_seats 
                        WHERE flight_id = :fid AND flight_date = :fdate AND seat_number = :oldSeat
                    ");
                    $stmtDelete->execute([
                        ':fid' => $flightData['flight_id'],
                        ':fdate' => $flightData['flight_date'],
                        ':oldSeat' => $oldSeat
                    ]);
                }

                if ($newSeat) {
                    $stmtInsert = $pdo->prepare("
                        INSERT IGNORE INTO flight_occupied_seats (flight_id, flight_date, seat_number)
                        VALUES (:fid, :fdate, :seat)
                    ");
                    $stmtInsert->execute([
                        ':fid'   => $flightData['flight_id'],
                        ':fdate' => $flightData['flight_date'],
                        ':seat'  => $newSeat
                    ]);
                }
            }

            $stmt = $pdo->prepare("
                UPDATE reservation_flights
                SET ticket_type = :class, price = price + :diff
                WHERE reservation_id = :rid AND flight_direction = 'departure'
            ");
            $stmt->execute([':class' => $newClass, ':diff' => $seatPrice, ':rid' => $reservationId]);

            $stmt = $pdo->prepare("
                UPDATE reservation_passengers
                SET seat_number = :seat
                WHERE id = :pid
            ");
            $stmt->execute([':seat' => $newSeat, ':pid' => $passengerId]);

            $upgradeInfo = "Ulepszyli Państwo klasę na&nbsp;<strong>{$newClass}</strong>, miejsce:&nbsp;<strong>{$newSeat}</strong>  (dopłata:&nbsp;" . number_format($seatPrice, 2, ',', ' ') . "&nbsp;zł).";
        } else {
            $error = 'Nie znaleziono rezerwacji pasażera.';
            $pdo->rollBack();
        }

    } else {
        $error = 'Nieprawidłowe dane upgrade.';
        $pdo->rollBack();
    }

    if (!$error) {
        $pdo->commit();
    }

} catch (Throwable $e) {
    $pdo->rollBack();
    $error = 'Błąd podczas zapisu: ' . $e->getMessage();
}
?>

<main>
    <div class="text-linia">
        <span>Dziękujemy za wybranie WRONAIR!</span>
    </div>
    <div class="upgrade-end-container">
        <?php if ($error): ?>
            <h1 style="color:#C50914;"><?= htmlspecialchars($error) ?></h1>
        <?php else: ?>
            <h1 id="order-status">Zmiana rezerwacji numer&nbsp;<strong><?= $orderCode ?></strong>&nbsp;została opłacona i zaakceptowana.</h1>
            <h2 id="upgrade-info"><?= $upgradeInfo ?></h2>
            <h2 class="end">Życzymy udanej podróży i do zobaczenia w przestworzach!</h2>
            <p>Przypominamy, że odprawy można dokonać online na 24 godziny przed wylotem lub osobiście na lotnisku na 1,5 godziny przed planowanym wylotem.</p>
            <p class="small-end">W razie pytań zapraszamy do kontaktu z naszym Działem Obsługi Klienta.</p>
        <?php endif; ?>
    </div>
</main>