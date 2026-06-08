<?php
if (!defined('APP_ACCESS')) exit('Brak dostępu');

if (!isset($pdo)) {
    require_once __DIR__ . '/config/db.php';
    $pdo = getDb();
}



$checkInData = $_SESSION['check_in_data'] ?? null;

if (!$checkInData) {
    echo '<main class="content"><h1>Brak danych odprawy.</h1></main>';
    return;
}

$reservation = $checkInData['reservation'] ?? [];
$passengers = $checkInData['passengers'] ?? [];
$flights = $checkInData['flights'] ?? [];

$reservationCode = $reservation['reservation_code'] ?? '';
$totalPrice = $reservation['total_price'] ?? '0.00';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
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

function formatDuration($minutes): string
{
    if ($minutes === null || $minutes === '') {
        return 'Brak danych';
    }

    $minutes = (int)$minutes;
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;

    return $hours . ' h ' . $mins . ' min';
}

function formatTimeShort($time): string
{
    if (!$time) {
        return 'Brak danych';
    }

    return substr((string)$time, 0, 5);
}

function formatDateValue($date): string
{
    if (!$date) {
        return 'Brak danych';
    }

    return (string)$date;
}

function formatTimeWithUtc($time, $utc): string
{
    $time = formatTimeShort($time);

    if (!$utc) {
        return $time;
    }

    return $time . ' ' . $utc;
}

function parseUtcOffsetMinutes($utcText): ?int
{
    if (!$utcText) {
        return null;
    }

    $utcText = strtoupper(trim((string)$utcText));

    if (!preg_match('/UTC\s*([+-])\s*(\d{1,2})(?::?(\d{2}))?/', $utcText, $matches)) {
        return null;
    }

    $sign = $matches[1] === '-' ? -1 : 1;
    $hours = (int)$matches[2];
    $minutes = isset($matches[3]) ? (int)$matches[3] : 0;

    return $sign * (($hours * 60) + $minutes);
}

function calculateArrivalDateLocal($flightDate, $departureTime, $departureUtc, $arrivalTime, $arrivalUtc): string
{
    if (!$flightDate || !$departureTime || !$departureUtc || !$arrivalTime || !$arrivalUtc) {
        return 'Brak danych';
    }

    $departureOffset = parseUtcOffsetMinutes($departureUtc);
    $arrivalOffset = parseUtcOffsetMinutes($arrivalUtc);

    if ($departureOffset === null || $arrivalOffset === null) {
        return 'Brak danych';
    }

    $departureLocal = new DateTime($flightDate . ' ' . substr((string)$departureTime, 0, 5));

    $departureUtcTime = clone $departureLocal;
    $departureUtcTime->modify((-1 * $departureOffset) . ' minutes');

    $arrivalLocal = new DateTime($flightDate . ' ' . substr((string)$arrivalTime, 0, 5));

    $arrivalUtcTime = clone $arrivalLocal;
    $arrivalUtcTime->modify((-1 * $arrivalOffset) . ' minutes');

    while ($arrivalUtcTime < $departureUtcTime) {
        $arrivalLocal->modify('+1 day');
        $arrivalUtcTime->modify('+1 day');
    }

    return $arrivalLocal->format('Y-m-d');
}

function getFlightData(PDO $pdo, $flightId): array
{
    $stmt = $pdo->prepare("
        SELECT
            flight_name,
            origin_code,
            destination_code,
            departure_time,
            departure_utc,
            arrival_time,
            arrival_utc,
            duration_minutes,
            departure_terminal,
            arrival_terminal,
            departure_gate,
            arrival_gate,
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
            'origin_code' => 'Brak danych',
            'destination_code' => 'Brak danych',
            'departure_time' => 'Brak danych',
            'departure_utc' => '',
            'arrival_time' => 'Brak danych',
            'arrival_utc' => '',
            'duration_minutes' => null,
            'departure_terminal' => 'TBD',
            'arrival_terminal' => 'TBD',
            'departure_gate' => 'TBD',
            'arrival_gate' => 'TBD',
            'aircraft_model' => 'Brak info'
        ];
    }

    return [
        'flight_name' => $data['flight_name'] ?? 'Brak info',
        'origin_code' => $data['origin_code'] ?? 'Brak danych',
        'destination_code' => $data['destination_code'] ?? 'Brak danych',
        'departure_time' => $data['departure_time'] ?? 'Brak danych',
        'departure_utc' => $data['departure_utc'] ?? '',
        'arrival_time' => $data['arrival_time'] ?? 'Brak danych',
        'arrival_utc' => $data['arrival_utc'] ?? '',
        'duration_minutes' => $data['duration_minutes'] ?? null,
        'departure_terminal' => $data['departure_terminal'] ?? 'TBD',
        'arrival_terminal' => $data['arrival_terminal'] ?? 'TBD',
        'departure_gate' => $data['departure_gate'] ?? 'TBD',
        'arrival_gate' => $data['arrival_gate'] ?? 'TBD',
        'aircraft_model' => $data['aircraft_model'] ?? 'Brak info'
    ];
}

function formatPassengerSeats(array $passengers): string
{
    $seats = [];

    foreach ($passengers as $passenger) {
        if (!empty($passenger['seat_number'])) {
            $seats[] = $passenger['seat_number'];
        }
    }

    return empty($seats) ? 'BRAK' : implode(', ', $seats);
}

function getAirportDisplayName(PDO $pdo, $code): string
{
    if (!$code) {
        return 'Brak danych';
    }

    $stmt = $pdo->prepare("
        SELECT display_name
        FROM airports
        WHERE code = :code
        LIMIT 1
    ");

    $stmt->execute([
        ':code' => $code
    ]);

    $name = $stmt->fetchColumn();

    return $name ?: (string)$code;
}

function getCountryPhoneCode(PDO $pdo, $countryName): string
{
    if (!$countryName) {
        return '';
    }

    $stmt = $pdo->prepare("
        SELECT phone_code
        FROM countries
        WHERE name = :name
        LIMIT 1
    ");

    $stmt->execute([
        ':name' => $countryName
    ]);

    $phoneCode = $stmt->fetchColumn();

    return $phoneCode ?: '';
}

$departureFlight = null;
$returnFlight = null;

foreach ($flights as $flight) {
    if (($flight['flight_direction'] ?? '') === 'departure') {
        $departureFlight = $flight;
    }

    if (($flight['flight_direction'] ?? '') === 'return') {
        $returnFlight = $flight;
    }
}

$departureData = $departureFlight ? getFlightData($pdo, $departureFlight['flight_id'] ?? 0) : null;
$returnData = $returnFlight ? getFlightData($pdo, $returnFlight['flight_id'] ?? 0) : null;

$departureOriginName = $departureData ? getAirportDisplayName($pdo, $departureData['origin_code'] ?? '') : 'Brak danych';
$departureDestinationName = $departureData ? getAirportDisplayName($pdo, $departureData['destination_code'] ?? '') : 'Brak danych';

$returnOriginName = $returnData ? getAirportDisplayName($pdo, $returnData['origin_code'] ?? '') : 'Brak danych';
$returnDestinationName = $returnData ? getAirportDisplayName($pdo, $returnData['destination_code'] ?? '') : 'Brak danych';
$contactCountry = $reservation['contact_country'] ?? '';
$contactPhoneCode = getCountryPhoneCode($pdo, $contactCountry);
$contactCountryDisplay = trim($contactPhoneCode . ' ' . $contactCountry);
?>

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
                                <td><?php echo e($passenger['seat_number'] ?? 'BRAK'); ?></td>
                            </tr>
                            <tr>
                                <th>Bagaż rejestrowany</th>
                                <td><?php echo e((float)($passenger['baggage_count'] ?? 0)); ?> szt.</td>
                            </tr>
                        </table>
                    <?php endforeach; ?>
                </div>

                <div>
                    <div class="section-title">Dane kontaktowe</div>

                    <table class="info-table">
                        <tr>
                            <th>E-mail</th>
                            <td><?php echo e($reservation['contact_email'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Telefon</th>
                            <td><?php echo e(trim($contactPhoneCode . ' ' . ($reservation['contact_phone'] ?? ''))); ?></td>
                        </tr>
                        <tr>
                            <th>Kraj</th>
                            <td><?php echo e($contactCountry); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">PLAN PODRÓŻY</div>

            <?php if ($departureFlight && $departureData): ?>
                <h3>WYLOT</h3>

                <div class="plan">
                    <div class="plan-details">
                        <div>Wylot:</div>
                        <div>Cel:</div>
                        <div>Podróż:</div>

                        <div><?php echo e($departureOriginName); ?></div>
                        <div><?php echo e($departureDestinationName); ?></div>
                        <div>NUMER LOTU: <?php echo e($departureData['flight_name']); ?></div>

                        <div>DATA: <?php echo e(formatDateValue($departureFlight['flight_date'] ?? null)); ?></div>
                        <div>DATA: <?php echo e(calculateArrivalDateLocal(
                            $departureFlight['flight_date'] ?? null,
                            $departureData['departure_time'] ?? null,
                            $departureData['departure_utc'] ?? null,
                            $departureData['arrival_time'] ?? null,
                            $departureData['arrival_utc'] ?? null
                        )); ?></div>
                        <div>CZAS LOTU: <?php echo e(formatDuration($departureData['duration_minutes'])); ?></div>

                        <div>GODZINA: <?php echo e(formatTimeWithUtc($departureData['departure_time'], $departureData['departure_utc'])); ?></div>
                        <div>GODZINA: <?php echo e(formatTimeWithUtc($departureData['arrival_time'], $departureData['arrival_utc'])); ?></div>
                        <div>KLASA: <?php echo e($departureFlight['ticket_type'] ?? ''); ?></div>

                        <div>TERMINAL: <?php echo e($departureData['departure_terminal']); ?></div>
                        <div>TERMINAL: <?php echo e($departureData['arrival_terminal']); ?></div>
                        <div>MIEJSCE: <?php echo e(formatPassengerSeats($passengers)); ?></div>

                        <div>GATE: <?php echo e($departureData['departure_gate']); ?></div>
                        <div>GATE: <?php echo e($departureData['arrival_gate']); ?></div>
                        <div>POSIŁEK: 1 Pełny posiłek, przekąski</div>

                        <div>SAMOLOT: <?php echo e($departureData['aircraft_model']); ?></div>
                        <div></div>
                        <div></div>
                    </div>

                    <div class="price">
                        CENA: <?php echo e(number_format((float)($departureFlight['price'] ?? 0), 2, ',', ' ')); ?> PLN
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($returnFlight && $returnData): ?>
                <h3>POWRÓT</h3>

                <div class="plan">
                    <div class="plan-details">
                        <div>Wylot:</div>
                        <div>Cel:</div>
                        <div>Podróż:</div>

                        <div><?php echo e($returnOriginName); ?></div>
                        <div><?php echo e($returnDestinationName); ?></div>
                        <div>NUMER LOTU: <?php echo e($returnData['flight_name']); ?></div>

                        <div>DATA: <?php echo e(formatDateValue($returnFlight['flight_date'] ?? null)); ?></div>
                        <div>DATA: <?php echo e(calculateArrivalDateLocal(
                            $returnFlight['flight_date'] ?? null,
                            $returnData['departure_time'] ?? null,
                            $returnData['departure_utc'] ?? null,
                            $returnData['arrival_time'] ?? null,
                            $returnData['arrival_utc'] ?? null
                        )); ?></div>
                        <div>CZAS LOTU: <?php echo e(formatDuration($returnData['duration_minutes'])); ?></div>

                        <div>GODZINA: <?php echo e(formatTimeWithUtc($returnData['departure_time'], $returnData['departure_utc'])); ?></div>
                        <div>GODZINA: <?php echo e(formatTimeWithUtc($returnData['arrival_time'], $returnData['arrival_utc'])); ?></div>
                        <div>KLASA: <?php echo e($returnFlight['ticket_type'] ?? ''); ?></div>

                        <div>TERMINAL: <?php echo e($returnData['departure_terminal']); ?></div>
                        <div>TERMINAL: <?php echo e($returnData['arrival_terminal']); ?></div>
                        <div>MIEJSCE: <?php echo e(formatPassengerSeats($passengers)); ?></div>

                        <div>GATE: <?php echo e($returnData['departure_gate']); ?></div>
                        <div>GATE: <?php echo e($returnData['arrival_gate']); ?></div>
                        <div>POSIŁEK: 1 Pełny posiłek, przekąski</div>

                        <div>SAMOLOT: <?php echo e($returnData['aircraft_model']); ?></div>
                        <div></div>
                        <div></div>
                    </div>

                    <div class="price">
                        CENA: <?php echo e(number_format((float)($returnFlight['price'] ?? 0), 2, ',', ' ')); ?> PLN
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <a href="edycja_odprawy" class="edytuj">
            Edytuj dane
        </a>

        <div class="total-section">
            <div class="total">
                Cena całkowita:
                <?php echo e(number_format((float)$totalPrice, 2, ',', ' ')); ?> PLN z VAT
            </div>

            <a href="wybor_miejsca_odprawa" class="confirm-button">
                POTWIERDŹ DANE*
            </a>
        </div>

        <p>
            *Naciskając przycisk, zaświadczasz, że wprowadzone dane są prawdziwe
            i zgadzają się z danymi w dokumencie podróży.
        </p>
    </div>
</main>