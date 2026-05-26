<link rel="stylesheet" href="style/style_boarding_pass.css" />
<link rel="stylesheet" href="style/style_headfoot.css" />
<?php
require_once __DIR__ . '/config/db.php';
if (!defined('APP_ACCESS')) exit('Brak dostępu');
$checkInData = $_SESSION['check_in_data'] ?? null;

if (!$checkInData) {
    echo '<main class="content"><h1>Brak danych karty pokładowej.</h1></main>';
    return;
}

$pdo = getDb();

$reservation = $checkInData['reservation'] ?? [];
$passengers = $checkInData['passengers'] ?? [];
$flights = $checkInData['flights'] ?? [];

$reservationCode = $reservation['reservation_code'] ?? '';

$departureFlight = null;

foreach ($flights as $flight) {
    if (($flight['flight_direction'] ?? '') === 'departure') {
        $departureFlight = $flight;
        break;
    }
}

if (!$departureFlight) {
    echo '<main class="content"><h1>Brak danych lotu.</h1></main>';
    return;
}

$flightId = (int)($departureFlight['flight_id'] ?? 0);
$flightDate = $departureFlight['flight_date'] ?? '';

$stmt = $pdo->prepare("
    SELECT
        flight_name,
        origin_code,
        destination_code,
        departure_time,
        departure_gate
    FROM flights
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $flightId
]);

$flightData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$flightData) {
    echo '<main class="content"><h1>Nie znaleziono danych lotu.</h1></main>';
    return;
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatPassengerName(array $passenger): string
{
    $parts = [];

    if (!empty($passenger['title'])) {
        $title = strtoupper($passenger['title']);

        $titles = [
            'MR' => 'MR',
            'MRS' => 'MRS',
            'DR' => 'DR',
            'PROF' => 'PROF',
            'MGR' => 'MGR',
            'ENG' => 'ENG'
        ];

        $parts[] = $titles[$title] ?? $title;
    }

    if (!empty($passenger['first_name'])) {
        $parts[] = strtoupper($passenger['first_name']);
    }

    if (!empty($passenger['middle_name'])) {
        $parts[] = strtoupper($passenger['middle_name']);
    }

    if (!empty($passenger['last_name'])) {
        $lastName = strtoupper($passenger['last_name']);
        $lastName = preg_replace('/[^A-ZĄĆĘŁŃÓŚŹŻ]/u', '', $lastName);
        $parts[] = $lastName;
    }

    return implode(' ', $parts);
}

function formatBoardingDate($date): string
{
    if (!$date) {
        return 'BRAK';
    }

    $months = [
        1 => 'JAN',
        2 => 'FEB',
        3 => 'MAR',
        4 => 'APR',
        5 => 'MAY',
        6 => 'JUN',
        7 => 'JUL',
        8 => 'AUG',
        9 => 'SEP',
        10 => 'OCT',
        11 => 'NOV',
        12 => 'DEC'
    ];

    $timestamp = strtotime($date);

    if (!$timestamp) {
        return 'BRAK';
    }

    $day = date('d', $timestamp);
    $month = $months[(int)date('n', $timestamp)];
    $year = date('y', $timestamp);

    return $day . $month . $year;
}

function formatTimeShort($time): string
{
    if (!$time) {
        return 'BRAK';
    }

    return substr((string)$time, 0, 5);
}

function calculateBoardingTime($date, $departureTime): string
{
    if (!$date || !$departureTime) {
        return 'BRAK';
    }

    $dateTime = new DateTime($date . ' ' . substr((string)$departureTime, 0, 5));
    $dateTime->modify('-30 minutes');

    return $dateTime->format('H:i');
}

$fromCode = strtoupper($flightData['origin_code'] ?? '');
$toCode = strtoupper($flightData['destination_code'] ?? '');
$flightName = strtoupper($flightData['flight_name'] ?? '');
$departureTime = formatTimeShort($flightData['departure_time'] ?? '');
$boardingTime = calculateBoardingTime($flightDate, $flightData['departure_time'] ?? '');
$departureDate = formatBoardingDate($flightDate);
$gate = strtoupper($flightData['departure_gate'] ?? 'TBD');
$passengerCount = count($passengers);
?>

<main class="content">

    <div class="line-container">
        <div class="text-linia">OTO TWOJA KARTA POKŁADOWA!</div>
        <div class="line-right"></div>
    </div>

    <div class="boarding-passes-wrapper">
        <?php foreach ($passengers as $index => $passenger): ?>
            <?php
                $passengerName = formatPassengerName($passenger);
                $seat = strtoupper($passenger['seat_number'] ?? 'BRAK');
                $passengerId = 'boarding-pass-' . ($index + 1);
            ?>

            <div class="boarding-pass printable-pass" id="<?php echo e($passengerId); ?>" data-passenger-index="<?php echo e($index); ?>">
                <div class="header">BOARDING PASS</div>

                <div class="content">
                    <div class="section-left">
                        <div class="qr-code">
                            <img src="img/QR.png" alt="QR Code">
                        </div>

                        <div class="logo">
                            <img src="img/logo_160.png" alt="Logo">
                        </div>
                    </div>

                    <div class="section-middle-left">
                        <p>PASSENGER:</p>
                        <p>FROM:</p>
                        <p>TO:</p>
                        <p>FLIGHT:</p>
                        <p>DEPARTURE DATE:</p>
                        <p>DEPARTURE TIME:</p>
                    </div>

                    <div class="section-middle-middle-left">
                        <p><?php echo e($passengerName); ?></p>
                        <p>[<?php echo e($fromCode); ?>]</p>
                        <p>[<?php echo e($toCode); ?>]</p>
                        <p><?php echo e($flightName); ?></p>
                        <p><?php echo e($departureDate); ?></p>
                        <p><?php echo e($departureTime); ?></p>
                    </div>

                    <div class="section-middle-middle-right">
                        <p>SEAT:</p>
                        <p>GATE:</p>
                        <p>BOARDING TIME:</p>
                        <p>RESERVATION NUMBER:</p>
                    </div>

                    <div class="section-right">
                        <p><?php echo e($seat); ?></p>
                        <p><?php echo e($gate); ?></p>
                        <p><?php echo e($boardingTime); ?></p>
                        <p><?php echo e($reservationCode); ?></p>
                    </div>

                    <div class="gate-closes-container">
                        <p class="gate-closes-text">GATE CLOSES 15 MINUTES BEFORE DEPARTURE</p>
                    </div>
                </div>

                <div class="footer">
                    <?php if ($index === 0): ?>
                        <div class="download" id="downloadBoardingPasses">
                            <?php echo $passengerCount > 1 ? 'pobierz karty pokładowe' : 'pobierz kartę pokładową'; ?>
                        </div>
                    <?php endif; ?>

                    <p>
                        Twoja karta pokładowa została pomyślnie wygenerowana!
                        Pamiętaj, aby zabrać ją ze sobą - w formie elektronicznej lub wydrukowanej -
                        jest ona niezbędna do przejścia przez kontrolę bezpieczeństwa i wejścia na pokład.
                    </p>

                    <h3>Życzymy przyjemnej podróży i do zobaczenia na pokładzie! &#9992;</h3>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="boardingPassModal" class="boarding-modal">
        <div class="boarding-modal-content">
            <button type="button" id="closeBoardingModal" class="boarding-modal-close">×</button>

            <h2>Wybierz karty pokładowe do pobrania</h2>

            <div class="boarding-modal-list">
                <?php foreach ($passengers as $index => $passenger): ?>
                    <label>
                        <input
                            type="checkbox"
                            class="boarding-pass-checkbox"
                            value="<?php echo e($index); ?>"
                            checked
                        >
                        <?php echo e(formatPassengerName($passenger)); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="button" id="downloadSelectedBoardingPasses" class="confirm-button">
                Pobierz karty wybranych pasażerów
            </button>
        </div>
    </div>

</main>

<style>
.boarding-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.boarding-modal.show {
    display: flex;
}

.boarding-modal-content {
    background: #fff;
    padding: 30px;
    border-radius: 14px;
    max-width: 520px;
    width: 90%;
    position: relative;
    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.25);
}

.boarding-modal-close {
    position: absolute;
    right: 16px;
    top: 12px;
    border: none;
    background: transparent;
    font-size: 34px;
    cursor: pointer;
}

.boarding-modal-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin: 25px 0;
}

.boarding-modal-list label {
    font-size: 18px;
}

@media print {
    body * {
        visibility: hidden !important;
    }

    .printable-pass.print-selected,
    .printable-pass.print-selected * {
        visibility: visible !important;
    }

    .printable-pass {
        display: none !important;
    }

    .printable-pass.print-selected {
        display: block !important;
        position: relative;
        page-break-after: always;
    }

    header,
    footer,
    .footer-logo,
    .line-container,
    .boarding-modal,
    .download {
        display: none !important;
    }
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const openButton = document.getElementById("downloadBoardingPasses");
    const modal = document.getElementById("boardingPassModal");
    const closeButton = document.getElementById("closeBoardingModal");
    const downloadSelectedButton = document.getElementById("downloadSelectedBoardingPasses");
    const passes = document.querySelectorAll(".printable-pass");

    if (openButton && modal) {
        openButton.addEventListener("click", function () {
            modal.classList.add("show");
        });
    }

    if (closeButton && modal) {
        closeButton.addEventListener("click", function () {
            modal.classList.remove("show");
        });
    }

    if (modal) {
        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                modal.classList.remove("show");
            }
        });
    }

    if (downloadSelectedButton) {
        downloadSelectedButton.addEventListener("click", function () {
            const selectedIndexes = Array.from(document.querySelectorAll(".boarding-pass-checkbox:checked"))
                .map(checkbox => checkbox.value);

            passes.forEach(pass => {
                pass.classList.remove("print-selected");

                if (selectedIndexes.includes(pass.dataset.passengerIndex)) {
                    pass.classList.add("print-selected");
                }
            });

            if (selectedIndexes.length === 0) {
                alert("Wybierz przynajmniej jednego pasażera.");
                return;
            }

            modal.classList.remove("show");
            window.print();
        });
    }
});
</script>