<?php
require_once __DIR__ . '/config/db.php';

$search = $_SESSION['flight_search'] ?? null;
$departureChoice = $_SESSION['departure_choice'] ?? null;
$returnChoice = $_SESSION['return_choice'] ?? null;
$passengerData = $_SESSION['passenger_data'] ?? null;
$seatSelection = $_SESSION['seat_selection'] ?? null;
$reservationCode = $_SESSION['reservation_code'] ?? '';
$totalPrice = $_SESSION['booking_total_price'] ?? '0.00';

if (!$search || !$departureChoice || !$passengerData || !$seatSelection || !$reservationCode) {
    echo '<main class="content"><h1>Brak danych rezerwacji.</h1></main>';
    return;
}

$pdo = getDb();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function getFlightExtraData(PDO $pdo, $flightId): array
{
    $stmt = $pdo->prepare("
        SELECT
            departure_terminal,
            arrival_terminal,
            departure_gate,
            arrival_gate,
            aircraft_model
        FROM flights
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $flightId
    ]);

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        return [
            'departure_terminal' => 'TBD',
            'arrival_terminal' => 'TBD',
            'departure_gate' => 'TBD',
            'arrival_gate' => 'TBD',
            'aircraft_model' => 'Brak info'
        ];
    }

    return [
        'departure_terminal' => $data['departure_terminal'] ?? 'TBD',
        'arrival_terminal' => $data['arrival_terminal'] ?? 'TBD',
        'departure_gate' => $data['departure_gate'] ?? 'TBD',
        'arrival_gate' => $data['arrival_gate'] ?? 'TBD',
        'aircraft_model' => $data['aircraft_model'] ?? 'Brak info'
    ];
}

function formatGender(string $gender): string
{
    $gender = strtolower($gender);

    if ($gender === 'male' || $gender === 'm') {
        return 'M';
    }

    if ($gender === 'female' || $gender === 'f') {
        return 'K';
    }

    return $gender !== '' ? $gender : 'Brak danych';
}

function formatSeatAssignments(array $seatAssignments): string
{
    $seats = array_filter($seatAssignments);

    if (empty($seats)) {
        return 'BRAK';
    }

    return implode(', ', $seats);
}

function formatBaggageCounts(array $baggageCounts): string
{
    if (empty($baggageCounts)) {
        return '0 sztuk';
    }

    $result = [];

    foreach ($baggageCounts as $index => $count) {
        $result[] = 'Pasażer ' . ($index + 1) . ': ' . (int)$count . ' szt.';
    }

    return implode(', ', $result);
}

$contact = $passengerData['contact'] ?? [];
$passengers = $passengerData['passengers'] ?? [];

$seatAssignments = $seatSelection['seat_assignments'] ?? [];
$baggageCounts = $seatSelection['baggage_counts'] ?? [];

$departureExtra = getFlightExtraData($pdo, $departureChoice['flight_id'] ?? 0);

$returnExtra = null;

if (($search['trip_type'] ?? '') === 'round-trip' && $returnChoice) {
    $returnExtra = getFlightExtraData($pdo, $returnChoice['flight_id'] ?? 0);
}

$from = $search['from'] ?? '';
$to = $search['to'] ?? '';
?>

<link rel="stylesheet" href="style/style_potwierdz_dane.css" />
<link rel="stylesheet" href="style/style_headfoot.css" />

<main class="content">
    <div class="container">
        <h1>TWOJA REZERWACJA nr <span><?php echo e($reservationCode); ?></span></h1>

        <div class="section">
            <div style="display: flex; justify-content: space-between; gap: 40px; flex-wrap: wrap;">
                <div>
                    <?php foreach ($passengers as $index => $passenger): ?>
                        <div class="section-title">Pasażer <?php echo $index + 1; ?></div>

                        <table class="info-table">
                            <tr>
                                <th>Imię</th>
                                <td><?php echo e($passenger['first_name'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <th>Drugie imię</th>
                                <td><?php echo e($passenger['middle_name'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <th>Nazwisko</th>
                                <td><?php echo e($passenger['last_name'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <th>Data urodzenia</th>
                                <td><?php echo e($passenger['birth_date'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <th>Płeć</th>
                                <td><?php echo e(formatGender($passenger['gender'] ?? '')); ?></td>
                            </tr>
                            <tr>
                                <th>Miejsce</th>
                                <td><?php echo e($seatAssignments[$index] ?? 'BRAK'); ?></td>
                            </tr>
                            <tr>
                                <th>Bagaż rejestrowany</th>
                                <td><?php echo e((string)($baggageCounts[$index] ?? 0)); ?> szt.</td>
                            </tr>
                        </table>
                    <?php endforeach; ?>
                </div>

                <div>
                    <div class="section-title">Dane kontaktowe</div>

                    <table class="info-table">
                        <tr>
                            <th>E-mail</th>
                            <td><?php echo e($contact['email'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Telefon</th>
                            <td><?php echo e(($contact['country'] ?? '') . ' ' . ($contact['phone'] ?? '')); ?></td>
                        </tr>
                        <tr>
                            <th>Kraj</th>
                            <td><?php echo e($contact['country'] ?? ''); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">PLAN PODRÓŻY</div>

            <h3>WYLOT</h3>

            <div class="plan">
                <div class="plan-details">
                    <div>Wylot:</div>
                    <div>Cel:</div>
                    <div>Podróż:</div>

                    <div><?php echo e($from); ?></div>
                    <div><?php echo e($to); ?></div>
                    <div>NUMER LOTU: <?php echo e($departureChoice['flight_code'] ?? ''); ?></div>

                    <div>DATA: <?php echo e($search['departure_date'] ?? ''); ?></div>
                    <div>DATA: <?php echo e($search['departure_date'] ?? ''); ?></div>
                    <div>CZAS LOTU: <?php echo e($departureChoice['duration'] ?? ''); ?></div>

                    <div>GODZINA: <?php echo e($departureChoice['departure_time'] ?? ''); ?></div>
                    <div>GODZINA: <?php echo e($departureChoice['arrival_time'] ?? ''); ?></div>
                    <div>KLASA: <?php echo e($departureChoice['ticket_type'] ?? ''); ?></div>

                    <div>TERMINAL: <?php echo e($departureExtra['departure_terminal']); ?></div>
                    <div>TERMINAL: <?php echo e($departureExtra['arrival_terminal']); ?></div>
                    <div>MIEJSCE: <?php echo e(formatSeatAssignments($seatAssignments)); ?></div>

                    <div>GATE: <?php echo e($departureExtra['departure_gate']); ?></div>
                    <div>GATE: <?php echo e($departureExtra['arrival_gate']); ?></div>
                    <div>POSIŁEK: 1 Pełny posiłek, przekąski</div>

                    <div>SAMOLOT: <?php echo e($departureExtra['aircraft_model']); ?></div>
                    <div></div>
                    <div>BAGAŻ REJESTROWANY: <?php echo e(formatBaggageCounts($baggageCounts)); ?></div>
                </div>

                <div class="price">
                    CENA: <?php echo e(number_format((float)($departureChoice['price'] ?? 0), 2, ',', ' ')); ?> PLN
                </div>
            </div>

            <?php if (($search['trip_type'] ?? '') === 'round-trip' && $returnChoice && $returnExtra): ?>
                <h3>POWRÓT</h3>

                <div class="plan">
                    <div class="plan-details">
                        <div>Wylot:</div>
                        <div>Cel:</div>
                        <div>Podróż:</div>

                        <div><?php echo e($to); ?></div>
                        <div><?php echo e($from); ?></div>
                        <div>NUMER LOTU: <?php echo e($returnChoice['flight_code'] ?? ''); ?></div>

                        <div>DATA: <?php echo e($search['return_date'] ?? ''); ?></div>
                        <div>DATA: <?php echo e($search['return_date'] ?? ''); ?></div>
                        <div>CZAS LOTU: <?php echo e($returnChoice['duration'] ?? ''); ?></div>

                        <div>GODZINA: <?php echo e($returnChoice['departure_time'] ?? ''); ?></div>
                        <div>GODZINA: <?php echo e($returnChoice['arrival_time'] ?? ''); ?></div>
                        <div>KLASA: <?php echo e($returnChoice['ticket_type'] ?? ''); ?></div>

                        <div>TERMINAL: <?php echo e($returnExtra['departure_terminal']); ?></div>
                        <div>TERMINAL: <?php echo e($returnExtra['arrival_terminal']); ?></div>
                        <div>MIEJSCE: przydzielane podczas odprawy</div>

                        <div>GATE: <?php echo e($returnExtra['departure_gate']); ?></div>
                        <div>GATE: <?php echo e($returnExtra['arrival_gate']); ?></div>
                        <div>POSIŁEK: 1 Pełny posiłek, przekąski</div>

                        <div>SAMOLOT: <?php echo e($returnExtra['aircraft_model']); ?></div>
                        <div></div>
                        <div>BAGAŻ REJESTROWANY: <?php echo e(formatBaggageCounts($baggageCounts)); ?></div>
                    </div>

                    <div class="price">
                        CENA: <?php echo e(number_format((float)($returnChoice['price'] ?? 0), 2, ',', ' ')); ?> PLN
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <form method="post" action="index.php" style="display:inline;">
            <input type="hidden" name="edit_reservation" value="1">
            <button type="submit" class="edytuj">Edytuj dane</button>
        </form>

        <div class="total-section">
            <div class="total">
                Cena całkowita: <?php echo e(number_format((float)$totalPrice, 2, ',', ' ')); ?> PLN z VAT
            </div>

            <form method="post" action="index.php" style="display:inline;">
                <input type="hidden" name="confirm_reservation" value="1">
                <button type="submit" class="confirm-button">POTWIERDŹ DANE*</button>
            </form>
        </div>

        <p>
            *Naciskając przycisk, zaświadczasz, że wprowadzone dane są prawdziwe
            i zgadzają się z danymi w dokumencie podróży.
        </p>
    </div>
</main>