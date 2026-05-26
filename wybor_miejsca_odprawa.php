<link rel="stylesheet" href="style/style_wybor_miejsca.css" />
<link rel="stylesheet" href="style/style_headfoot.css" />

<?php
require_once __DIR__ . '/config/db.php';

$checkInData = $_SESSION['check_in_data'] ?? null;

if (!$checkInData) {
    echo '<main class="content"><h1>Brak danych odprawy.</h1></main>';
    return;
}

$pdo = getDb();

$reservation = $checkInData['reservation'] ?? [];
$passengers = $checkInData['passengers'] ?? [];
$flights = $checkInData['flights'] ?? [];

$reservationId = (int)($reservation['id'] ?? 0);

$departureFlight = null;

foreach ($flights as $flight) {
    if (($flight['flight_direction'] ?? '') === 'departure') {
        $departureFlight = $flight;
        break;
    }
}

if (!$departureFlight || $reservationId <= 0) {
    echo '<main class="content"><h1>Brak danych lotu.</h1></main>';
    return;
}

$flightId = (int)($departureFlight['flight_id'] ?? 0);
$flightDate = $departureFlight['flight_date'] ?? date('Y-m-d');
$ticketType = strtoupper($departureFlight['ticket_type'] ?? 'ECONOMY');

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalizeTicketClass(string $ticketType): string
{
    if (str_contains($ticketType, 'BUSINESS')) {
        return 'BUSINESS';
    }

    if (str_contains($ticketType, 'PREMIUM')) {
        return 'PREMIUM ECONOMY';
    }

    return 'ECONOMY';
}

function getFlightData(PDO $pdo, int $flightId): array
{
    $stmt = $pdo->prepare("
        SELECT
            flight_name,
            duration_minutes,
            aircraft_model
        FROM flights
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $flightId
    ]);

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        return [
            'flight_name' => 'Brak info',
            'duration_minutes' => 0,
            'aircraft_model' => 'Brak info'
        ];
    }

    return $data;
}

function getSeatClass(string $seat, bool $isLongHaul): string
{
    preg_match('/^(\d+)/', strtoupper(trim($seat)), $match);
    $row = isset($match[1]) ? (int)$match[1] : 0;

    if ($isLongHaul) {
        if ($row >= 1 && $row <= 5) {
            return 'BUSINESS';
        }

        if ($row >= 6 && $row <= 12) {
            return 'PREMIUM ECONOMY';
        }

        return 'ECONOMY';
    }

    if ($row >= 1 && $row <= 4) {
        return 'BUSINESS';
    }

    if ($row >= 5 && $row <= 8) {
        return 'PREMIUM ECONOMY';
    }

    return 'ECONOMY';
}

function getAllSeats(bool $isLongHaul): array
{
    $seats = [];

    if ($isLongHaul) {
        for ($row = 1; $row <= 5; $row++) {
            foreach (['A', 'C', 'D', 'H'] as $letter) {
                $seats[] = $row . $letter;
            }
        }

        for ($row = 6; $row <= 12; $row++) {
            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $letter) {
                $seats[] = $row . $letter;
            }
        }

        for ($row = 13; $row <= 58; $row++) {
            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $letter) {
                $seats[] = $row . $letter;
            }
        }

        return $seats;
    }

    for ($row = 1; $row <= 4; $row++) {
        foreach (['A', 'C', 'D', 'F'] as $letter) {
            $seats[] = $row . $letter;
        }
    }

    for ($row = 5; $row <= 8; $row++) {
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $letter) {
            $seats[] = $row . $letter;
        }
    }

    for ($row = 9; $row <= 51; $row++) {
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $letter) {
            $seats[] = $row . $letter;
        }
    }

    return $seats;
}

function renderSeatButton(string $seat, string $seatClass, string $ticketClass, array $occupiedSeats): void
{
    $isOccupied = in_array($seat, $occupiedSeats, true);
    $isAllowedClass = $seatClass === $ticketClass;
    $isDisabled = $isOccupied || !$isAllowedClass;

    $classes = ['seat-button'];

    if ($isOccupied) {
        $classes[] = 'occupied';
    }

    if (!$isAllowedClass) {
        $classes[] = 'unavailable-class';
    }
    ?>
    <button
        type="button"
        class="<?php echo e(implode(' ', $classes)); ?>"
        data-seat="<?php echo e($seat); ?>"
        data-seat-class="<?php echo e($seatClass); ?>"
        <?php echo $isDisabled ? 'disabled' : ''; ?>
    >
        <?php echo e($seat); ?>
    </button>
    <?php
}

$flightData = getFlightData($pdo, $flightId);
$ticketClass = normalizeTicketClass($ticketType);
$isLongHaul = (int)($flightData['duration_minutes'] ?? 0) >= 240;

$aircraftModel = $flightData['aircraft_model'] ?? ($isLongHaul ? 'Airbus A330-300' : 'Airbus A320-200');
$aircraftTypeClass = $isLongHaul ? 'long-haul-aircraft' : 'short-haul-aircraft';

$stmt = $pdo->prepare("
    SELECT seat_number
    FROM flight_occupied_seats
    WHERE flight_id = :flight_id
      AND flight_date = :flight_date
");

$stmt->execute([
    ':flight_id' => $flightId,
    ':flight_date' => $flightDate
]);

$allOccupiedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);

$currentReservationSeats = [];

foreach ($passengers as $passenger) {
    if (!empty($passenger['seat_number'])) {
        $currentReservationSeats[] = $passenger['seat_number'];
    }
}

$occupiedSeats = array_values(array_diff($allOccupiedSeats, $currentReservationSeats));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_in_seat_submit'])) {
    $seatAssignmentsRaw = trim($_POST['seat_assignments'] ?? '');
    $seatAssignments = [];

    if ($seatAssignmentsRaw !== '') {
        $decoded = json_decode($seatAssignmentsRaw, true);

        if (is_array($decoded)) {
            $seatAssignments = $decoded;
        }
    }

    $allSeats = getAllSeats($isLongHaul);
    $usedSeats = $occupiedSeats;

    $pdo->beginTransaction();

    try {
        $oldReservationSeats = [];

        foreach ($passengers as $passenger) {
            if (!empty($passenger['seat_number'])) {
                $oldReservationSeats[] = $passenger['seat_number'];
            }
        }

        if (!empty($oldReservationSeats)) {
            $placeholders = implode(',', array_fill(0, count($oldReservationSeats), '?'));

            $stmt = $pdo->prepare("
                DELETE FROM flight_occupied_seats
                WHERE flight_id = ?
                  AND flight_date = ?
                  AND seat_number IN ($placeholders)
            ");

            $stmt->execute(array_merge(
                [$flightId, $flightDate],
                $oldReservationSeats
            ));
        }

        foreach ($passengers as $index => $passenger) {
            $passengerIndex = (int)($passenger['passenger_index'] ?? ($index + 1));
            $selectedSeat = trim($seatAssignments[(string)$passengerIndex] ?? '');

            if ($selectedSeat !== '') {
                $finalSeat = $selectedSeat;
            } else {
                $availableSeats = array_filter($allSeats, function ($seat) use ($ticketClass, $isLongHaul, $usedSeats) {
                    return getSeatClass($seat, $isLongHaul) === $ticketClass
                        && !in_array($seat, $usedSeats, true);
                });

                $availableSeats = array_values($availableSeats);
                $finalSeat = $availableSeats ? $availableSeats[random_int(0, count($availableSeats) - 1)] : null;
            }

            if (!$finalSeat) {
                continue;
            }

            if (!in_array($finalSeat, $usedSeats, true)) {
                $usedSeats[] = $finalSeat;
            }

            $stmt = $pdo->prepare("
                UPDATE reservation_passengers
                SET seat_number = :seat_number
                WHERE reservation_id = :reservation_id
                  AND passenger_index = :passenger_index
            ");

            $stmt->execute([
                ':seat_number' => $finalSeat,
                ':reservation_id' => $reservationId,
                ':passenger_index' => $passengerIndex
            ]);
        }

        $stmt = $pdo->prepare("
            DELETE FROM flight_occupied_seats
            WHERE flight_id = :flight_id
              AND flight_date = :flight_date
              AND seat_number IN (
                  SELECT seat_number
                  FROM reservation_passengers
                  WHERE reservation_id = :reservation_id
                    AND seat_number IS NOT NULL
                    AND seat_number <> ''
              )
        ");

        $stmt->execute([
            ':flight_id' => $flightId,
            ':flight_date' => $flightDate,
            ':reservation_id' => $reservationId
        ]);

        $stmt = $pdo->prepare("
            SELECT seat_number
            FROM reservation_passengers
            WHERE reservation_id = :reservation_id
              AND seat_number IS NOT NULL
              AND seat_number <> ''
        ");

        $stmt->execute([
            ':reservation_id' => $reservationId
        ]);

        $updatedReservationSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($updatedReservationSeats as $seatNumber) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM flight_occupied_seats
                WHERE flight_id = :flight_id
                  AND flight_date = :flight_date
                  AND seat_number = :seat_number
            ");

            $stmt->execute([
                ':flight_id' => $flightId,
                ':flight_date' => $flightDate,
                ':seat_number' => $seatNumber
            ]);

            if ((int)$stmt->fetchColumn() === 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO flight_occupied_seats
                        (
                            flight_id,
                            flight_date,
                            seat_number
                        )
                    VALUES
                        (
                            :flight_id,
                            :flight_date,
                            :seat_number
                        )
                ");

                $stmt->execute([
                    ':flight_id' => $flightId,
                    ':flight_date' => $flightDate,
                    ':seat_number' => $seatNumber
                ]);
            }
        }

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

        $_SESSION['check_in_data']['passengers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $_SESSION['current_view'] = 'boarding_pass';

        $pdo->commit();

        echo '<script>window.location.href = "boarding_pass";</script>';
        return;

    } catch (Throwable $e) {
        $pdo->rollBack();
        echo '<main class="content"><h1>Błąd zapisu miejsc.</h1></main>';
        return;
    }
}

$passengerList = [];

foreach ($passengers as $index => $passenger) {
    $passengerIndex = (int)($passenger['passenger_index'] ?? ($index + 1));
    $firstName = trim($passenger['first_name'] ?? '');
    $lastName = trim($passenger['last_name'] ?? '');
    $fullName = trim($firstName . ' ' . $lastName);

    if ($fullName === '') {
        $fullName = 'Pasażer ' . $passengerIndex;
    }

    $passengerList[] = [
        'index' => $passengerIndex,
        'label' => $fullName,
        'current_seat' => $passenger['seat_number'] ?? ''
    ];
}
?>

<main class="content">
    <div class="line-container">
        <div class="text-linia">Wybór miejsca do odprawy</div>
        <div class="line-right"></div>
    </div>

    <p>Poniżej znajduje się mapa dostępnych miejsc. Wybierz swoje miejsce, aby podróż przebiegła w pełnym komforcie.</p>

    <h3>Lot: <?php echo e($flightData['flight_name'] ?? 'Brak info'); ?></h3>
    <h3>Data lotu: <?php echo e($flightDate); ?></h3>
    <h3>Model samolotu: <?php echo e($aircraftModel); ?></h3>
    <h3>Wybrana klasa: <?php echo e($ticketClass); ?></h3>

    <div class="passenger-seat-panel">
        <h3>Przypisanie miejsc:</h3>

        <div class="passenger-tabs">
            <?php foreach ($passengerList as $index => $passenger): ?>
                <button
                    type="button"
                    class="passenger-tab <?php echo $index === 0 ? 'active' : ''; ?>"
                    data-passenger-index="<?php echo e($passenger['index']); ?>"
                >
                    <?php echo e($passenger['label']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="seat-assignment-list">
            <?php foreach ($passengerList as $passenger): ?>
                <p>
                    <strong><?php echo e($passenger['label']); ?>:</strong>
                    <span
                        class="passenger-seat-value"
                        data-passenger-index="<?php echo e($passenger['index']); ?>"
                    >
                        <?php echo $passenger['current_seat'] !== '' ? e($passenger['current_seat']) : 'NIE WYBRANO'; ?>
                    </span>
                </p>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="background-wrapper">
        <div class="background-container <?php echo e($aircraftTypeClass); ?>"></div>

        <div class="content-wrapper">
            <?php if ($isLongHaul): ?>

                <div class="class-label">BUSINESS</div>
                <div class="class-section business-class <?php echo $ticketClass !== 'BUSINESS' ? 'locked-section' : ''; ?>">
                    <?php for ($row = 1; $row <= 5; $row++): ?>
                        <div class="row business-row long-haul-row">
                            <?php foreach (['A', 'C', 'D', 'H'] as $i => $letter): ?>
                                <?php if ($i === 1 || $i === 3): ?>
                                    <div class="spacer"></div>
                                <?php endif; ?>

                                <?php renderSeatButton($row . $letter, 'BUSINESS', $ticketClass, $occupiedSeats); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="class-label">PREMIUM ECONOMY</div>
                <div class="class-section premium-class <?php echo $ticketClass !== 'PREMIUM ECONOMY' ? 'locked-section' : ''; ?>">
                    <?php for ($row = 6; $row <= 12; $row++): ?>
                        <div class="row premium-row long-haul-row">
                            <?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $i => $letter): ?>
                                <?php if ($i === 2 || $i === 5): ?>
                                    <div class="spacer"></div>
                                <?php endif; ?>

                                <?php renderSeatButton($row . $letter, 'PREMIUM ECONOMY', $ticketClass, $occupiedSeats); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="class-label">ECONOMY</div>
                <div class="economy-class <?php echo $ticketClass !== 'ECONOMY' ? 'locked-section' : ''; ?>">
                    <?php for ($row = 13; $row <= 58; $row++): ?>
                        <?php if ($row === 13): ?>
                            <div class="row-break"></div>
                        <?php endif; ?>

                        <?php if ($row === 40): ?>
                            <div class="row-break"></div>
                            <div class="row-break"></div>
                            <div class="row-break"></div>
                        <?php endif; ?>

                        <div class="row economy-row long-haul-row">
                            <?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $i => $letter): ?>
                                <?php if ($i === 2 || $i === 6): ?>
                                    <div class="spacer"></div>
                                <?php endif; ?>

                                <?php renderSeatButton($row . $letter, 'ECONOMY', $ticketClass, $occupiedSeats); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>

            <?php else: ?>

                <div class="class-label">BUSINESS</div>
                <div class="class-section business-class <?php echo $ticketClass !== 'BUSINESS' ? 'locked-section' : ''; ?>">
                    <?php for ($row = 1; $row <= 4; $row++): ?>
                        <?php if ($row === 1): ?>
                            <div class="row-break"></div>
                            <div class="row-break"></div>
                            <div class="row-break"></div>
                            <div class="row-break"></div>
                        <?php endif; ?>

                        <div class="row business-row short-haul-row">
                            <?php foreach (['A', 'C', 'D', 'F'] as $i => $letter): ?>
                                <?php if ($i === 2): ?>
                                    <div class="spacer"></div>
                                <?php endif; ?>

                                <?php renderSeatButton($row . $letter, 'BUSINESS', $ticketClass, $occupiedSeats); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="class-label">PREMIUM ECONOMY</div>
                <div class="class-section premium-class <?php echo $ticketClass !== 'PREMIUM ECONOMY' ? 'locked-section' : ''; ?>">
                    <?php for ($row = 5; $row <= 8; $row++): ?>
                        <div class="row premium-row short-haul-row">
                            <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $i => $letter): ?>
                                <?php if ($i === 3): ?>
                                    <div class="spacer"></div>
                                <?php endif; ?>

                                <?php renderSeatButton($row . $letter, 'PREMIUM ECONOMY', $ticketClass, $occupiedSeats); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="class-label">ECONOMY</div>
                <div class="economy-class <?php echo $ticketClass !== 'ECONOMY' ? 'locked-section' : ''; ?>">
                    <?php for ($row = 9; $row <= 51; $row++): ?>
                        <div class="row economy-row short-haul-row">
                            <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $i => $letter): ?>
                                <?php if ($i === 3): ?>
                                    <div class="spacer"></div>
                                <?php endif; ?>

                                <?php renderSeatButton($row . $letter, 'ECONOMY', $ticketClass, $occupiedSeats); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <div id="legend">
        <h3>Legenda</h3>
        <ul>
            <li>
                <span class="legend-box legend-selected"></span>
                Wybrane miejsce
            </li>
            <li>
                <span class="legend-box legend-occupied"></span>
                Zajęte miejsce
            </li>
            <li>
                <span class="legend-box legend-available"></span>
                Dostępne miejsce w Twojej klasie
            </li>
            <li>
                <span class="legend-box legend-unavailable"></span>
                Miejsce w innej klasie
            </li>
        </ul>
    </div>

    <div class="info-section">
        <p>
            Jeśli nie wybierzesz miejsca, system przydzieli Ci losowe wolne miejsce w Twojej klasie po kliknięciu przycisku „Dalej”.
        </p>
    </div>

    <div class="price-container check-in-price-container">
        <div class="button-group check-in-button-group">
            <a class="aboard" href="weryfikacja" type="button">Wróć</a>
            <button id="confirmButton" type="button">Dalej</button>
        </div>
    </div>

    <form id="seatSelectionForm" method="post" action="wybor_miejsca_odprawa" style="display:none;">
        <input type="hidden" name="check_in_seat_submit" value="1">
        <input type="hidden" name="seat_assignments" id="seatAssignmentsInput">
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const passengers = <?php echo json_encode($passengerList, JSON_UNESCAPED_UNICODE); ?>;

    const passengerTabs = document.querySelectorAll('.passenger-tab');
    const passengerSeatValues = document.querySelectorAll('.passenger-seat-value');
    const seatButtons = document.querySelectorAll('.seat-button');
    const confirmButton = document.getElementById('confirmButton');
    const seatAssignmentsInput = document.getElementById('seatAssignmentsInput');
    const seatSelectionForm = document.getElementById('seatSelectionForm');

    let activePassengerIndex = passengers.length > 0 ? parseInt(passengers[0].index, 10) : null;
    let seatAssignments = {};

    passengers.forEach(passenger => {
        seatAssignments[String(passenger.index)] = passenger.current_seat || '';
    });

    function setActivePassenger(index) {
        activePassengerIndex = index;

        passengerTabs.forEach(tab => {
            tab.classList.toggle(
                'active',
                parseInt(tab.dataset.passengerIndex, 10) === activePassengerIndex
            );
        });
    }

    function refreshAssignmentDisplay() {
        let hasAnyManualOrExistingSeat = false;

        passengerSeatValues.forEach(span => {
            const index = span.dataset.passengerIndex;
            const seat = seatAssignments[index];

            span.textContent = seat || 'NIE WYBRANO';

            if (seat) {
                hasAnyManualOrExistingSeat = true;
            }
        });

        seatButtons.forEach(button => {
            const seat = button.dataset.seat;

            button.classList.remove('selected');

            Object.keys(seatAssignments).forEach(index => {
                if (seatAssignments[index] === seat) {
                    button.classList.add('selected');
                }
            });
        });

        confirmButton.textContent = hasAnyManualOrExistingSeat ? 'Potwierdź wybór' : 'Dalej';
    }

    passengerTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            setActivePassenger(parseInt(tab.dataset.passengerIndex, 10));
        });
    });

    seatButtons.forEach(button => {
        if (
            button.disabled ||
            button.classList.contains('occupied') ||
            button.classList.contains('unavailable-class')
        ) {
            return;
        }

        button.addEventListener('click', () => {
            if (activePassengerIndex === null) {
                return;
            }

            const seat = button.dataset.seat;
            const activeKey = String(activePassengerIndex);

            Object.keys(seatAssignments).forEach(index => {
                if (seatAssignments[index] === seat) {
                    seatAssignments[index] = '';
                }
            });

            if (seatAssignments[activeKey] === seat) {
                seatAssignments[activeKey] = '';
            } else {
                seatAssignments[activeKey] = seat;
            }

            refreshAssignmentDisplay();
        });
    });

    confirmButton.addEventListener('click', () => {
        seatAssignmentsInput.value = JSON.stringify(seatAssignments);
        seatSelectionForm.submit();
    });

    if (activePassengerIndex !== null) {
        setActivePassenger(activePassengerIndex);
    }

    refreshAssignmentDisplay();
});
</script>