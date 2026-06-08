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
    empty($upgradeData['flight_direction'])
) {
    header('Location: upgrade');
    exit;
}

$orderCode = $upgradeData['order_code'];
$reservationId = (int)$upgradeData['reservation_id'];
$passengerId = (int)$upgradeData['passenger_id'];
$flightDirection = $upgradeData['flight_direction'];

if (!isset($pdo)) {
    require_once __DIR__ . '/config/db.php';
    $pdo = getDb();
}

if ($orderCode === '' || $passengerId <= 0) {
    echo '<main><p>Brak wymaganych danych rezerwacji.</p></main>';
    return;
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalizeTicketClass(string $ticketType): string
{
    $ticketType = strtoupper(trim($ticketType));

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
            aircraft_model,
            price_economy,
            price_premium_economy,
            price_business
        FROM flights
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $flightId
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'flight_name' => 'Brak info',
        'duration_minutes' => 0,
        'aircraft_model' => 'Brak info',
        'price_economy' => 0,
        'price_premium_economy' => 0,
        'price_business' => 0
    ];
}

function renderUpgradeSeat(
    string $seat,
    string $seatClass,
    string $currentClass,
    array $occupiedSeats,
    string $currentSeat
): void {
    $seat = strtoupper(trim($seat));
    $seatClass = strtoupper(trim($seatClass));
    $currentClass = strtoupper(trim($currentClass));
    $currentSeat = strtoupper(trim($currentSeat));

    $isCurrentSeat = $seat === $currentSeat;
    $isOccupied = in_array($seat, $occupiedSeats, true) && !$isCurrentSeat;
    $isWrongClass = $seatClass !== $currentClass;
    $isDisabled = $isOccupied || $isWrongClass;

    $classes = ['seat-button'];

    if ($isOccupied) {
        $classes[] = 'occupied';
    }

    if ($isWrongClass) {
        $classes[] = 'unavailable-class';
    }

    if ($isCurrentSeat) {
        $classes[] = 'current-seat';
    }
    ?>
    <button
        type="button"
        class="<?= e(implode(' ', $classes)) ?>"
        data-seat="<?= e($seat) ?>"
        data-seat-class="<?= e($seatClass) ?>"
        <?= $isDisabled ? 'disabled' : '' ?>
    >
        <?= e($seat) ?>
    </button>
    <?php
}

$stmt = $pdo->prepare("
    SELECT 
        rp.id AS passenger_id,
        rp.first_name,
        rp.last_name,
        rp.seat_number,
        rf.ticket_type,
        rf.flight_id,
        rf.flight_date,
        rf.price,
        rf.reservation_id
    FROM reservation_passengers rp
    JOIN reservation_flights rf 
        ON rf.reservation_id = rp.reservation_id
       AND rf.flight_direction = :dir
    JOIN reservations r 
        ON r.id = rf.reservation_id
    WHERE rp.id = :pid
      AND r.id = :reservation_id
      AND r.reservation_code = :code
    LIMIT 1
");

$stmt->execute([
    ':pid' => $passengerId,
    ':reservation_id' => $reservationId,
    ':code' => $orderCode,
    ':dir' => $flightDirection
]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo '<main><p>Nie znaleziono danych pasażera dla tego lotu.</p></main>';
    return;
}

$flightId = (int)$data['flight_id'];
$flightDate = $data['flight_date'] ?? date('Y-m-d');
$currentClass = normalizeTicketClass($data['ticket_type'] ?? 'ECONOMY');
$currentSeat = strtoupper(trim($data['seat_number'] ?? ''));
$seatChangeFee = 150.00;

$flightData = getFlightData($pdo, $flightId);
$isLongHaul = (int)($flightData['duration_minutes'] ?? 0) >= 240;
$aircraftModel = $flightData['aircraft_model'] ?? ($isLongHaul ? 'Airbus A330-300' : 'Airbus A320-200');
$aircraftTypeClass = $isLongHaul ? 'long-haul-aircraft' : 'short-haul-aircraft';

$classPrices = [
    'ECONOMY' => (float)($flightData['price_economy'] ?? 0),
    'PREMIUM ECONOMY' => (float)($flightData['price_premium_economy'] ?? 0),
    'BUSINESS' => (float)($flightData['price_business'] ?? 0),
];

$currentClassBasePrice = $classPrices[$currentClass] ?? 0;

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

$occupiedSeats = array_map(
    fn($seat) => strtoupper(trim((string)$seat)),
    $stmt->fetchAll(PDO::FETCH_COLUMN)
);
?>
<main>
    <section class="upgrade-section">
        <h1 class="upgrade-title">Zmiana klasy i miejsca</h1>

        <p class="upgrade-subtitle">
            Pasażer:
            <strong><?= e(trim($data['first_name'] . ' ' . $data['last_name'])) ?></strong>
            &nbsp;|&nbsp;
            Lot:
            <strong><?= $flightDirection === 'departure' ? 'Wylot' : 'Powrót' ?></strong>
            &nbsp;|&nbsp;
            Klasa:
            <strong><?= e($currentClass) ?></strong>
        </p>

        <div class="class-selector">
            <?php foreach ($classPrices as $key => $price): ?>
                <?php
                $key = strtoupper(trim($key));
                $diff = $price - $currentClassBasePrice;
                $isCurrent = $key === $currentClass;
                ?>

                <button
                    type="button"
                    class="class-btn <?= $isCurrent ? 'class-btn--current class-btn--selected' : '' ?>"
                    data-class="<?= e($key) ?>"
                    data-price="<?= e($price) ?>"
                    data-diff="<?= e($diff) ?>"
                    data-current="<?= $isCurrent ? '1' : '0' ?>"
                >
                    <?= e($key) ?>

                    <?php if ($isCurrent): ?>
                        <span>(posiadana)</span>
                    <?php else: ?>
                        <span>
                            <?= $diff > 0 ? '+' : '' ?><?= number_format($diff, 2, ',', ' ') ?> zł
                        </span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <p class="seat-instruction">
            Możesz zmienić miejsce w obrębie swojej klasy za 150,00 zł.
            Miejsca zajęte są oznaczone na czerwono.
        </p>

        <div class="background-wrapper">
            <div class="background-container <?= e($aircraftTypeClass) ?>"></div>

            <div class="content-wrapper">
                <?php if ($isLongHaul): ?>

                    <div class="class-label">BUSINESS</div>

                    <div class="class-section business-class <?= $currentClass !== 'BUSINESS' ? 'locked-section' : '' ?>">
                        <?php for ($row = 1; $row <= 5; $row++): ?>
                            <div class="row business-row long-haul-row">
                                <?php foreach (['A', 'C', 'D', 'H'] as $index => $letter): ?>
                                    <?php if ($index === 1 || $index === 3): ?>
                                        <div class="spacer"></div>
                                    <?php endif; ?>

                                    <?php renderUpgradeSeat($row . $letter, 'BUSINESS', $currentClass, $occupiedSeats, $currentSeat); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="class-label">PREMIUM ECONOMY</div>

                    <div class="class-section premium-class <?= $currentClass !== 'PREMIUM ECONOMY' ? 'locked-section' : '' ?>">
                        <?php for ($row = 6; $row <= 12; $row++): ?>
                            <div class="row premium-row long-haul-row">
                                <?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $index => $letter): ?>
                                    <?php if ($index === 2 || $index === 5): ?>
                                        <div class="spacer"></div>
                                    <?php endif; ?>

                                    <?php renderUpgradeSeat($row . $letter, 'PREMIUM ECONOMY', $currentClass, $occupiedSeats, $currentSeat); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="class-label">ECONOMY</div>

                    <div class="economy-class <?= $currentClass !== 'ECONOMY' ? 'locked-section' : '' ?>">
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
                                <?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $index => $letter): ?>
                                    <?php if ($index === 2 || $index === 6): ?>
                                        <div class="spacer"></div>
                                    <?php endif; ?>

                                    <?php renderUpgradeSeat($row . $letter, 'ECONOMY', $currentClass, $occupiedSeats, $currentSeat); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                <?php else: ?>

                    <div class="class-label">BUSINESS</div>

                    <div class="class-section business-class <?= $currentClass !== 'BUSINESS' ? 'locked-section' : '' ?>">
                        <?php for ($row = 1; $row <= 4; $row++): ?>
                            <?php if ($row === 1): ?>
                                <div class="row-break"></div>
                                <div class="row-break"></div>
                                <div class="row-break"></div>
                                <div class="row-break"></div>
                            <?php endif; ?>

                            <div class="row business-row short-haul-row">
                                <?php foreach (['A', 'C', 'D', 'F'] as $index => $letter): ?>
                                    <?php if ($index === 2): ?>
                                        <div class="spacer"></div>
                                    <?php endif; ?>

                                    <?php renderUpgradeSeat($row . $letter, 'BUSINESS', $currentClass, $occupiedSeats, $currentSeat); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="class-label">PREMIUM ECONOMY</div>

                    <div class="class-section premium-class <?= $currentClass !== 'PREMIUM ECONOMY' ? 'locked-section' : '' ?>">
                        <?php for ($row = 5; $row <= 8; $row++): ?>
                            <div class="row premium-row short-haul-row">
                                <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $index => $letter): ?>
                                    <?php if ($index === 3): ?>
                                        <div class="spacer"></div>
                                    <?php endif; ?>

                                    <?php renderUpgradeSeat($row . $letter, 'PREMIUM ECONOMY', $currentClass, $occupiedSeats, $currentSeat); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="class-label">ECONOMY</div>

                    <div class="economy-class <?= $currentClass !== 'ECONOMY' ? 'locked-section' : '' ?>">
                        <?php for ($row = 9; $row <= 51; $row++): ?>
                            <div class="row economy-row short-haul-row">
                                <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $index => $letter): ?>
                                    <?php if ($index === 3): ?>
                                        <div class="spacer"></div>
                                    <?php endif; ?>

                                    <?php renderUpgradeSeat($row . $letter, 'ECONOMY', $currentClass, $occupiedSeats, $currentSeat); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                <?php endif; ?>
            </div>
        </div>

        <div class="upgrade-seat-summary">
            <p>
                Wybrane miejsce:
                <strong id="selectedSeatDisplay"><?= e($currentSeat ?: 'Brak') ?></strong>
            </p>

            <p>
                Różnica w cenie:
                <strong id="priceDiffDisplay">0,00 zł</strong>
            </p>

            <button id="confirmUpgradeSeat" class="upgrade-btn" disabled>
                Zatwierdź zmianę
            </button>
        </div>

        <div class="upgrade-back">
            <a href="upgrade_choice" class="upgrade-back-btn">
                ← Wróć do wyboru pasażera
            </a>
        </div>

        <form id="seatUpgradeForm" method="POST" action="upgrade_choice_seat" style="display:none;">
            <input type="hidden" name="upgrade_action" value="confirm_seat">
            <input type="hidden" name="new_class" id="newClassInput">
            <input type="hidden" name="new_seat" id="newSeatInput">
            <input type="hidden" name="seat_price" id="seatPriceInput">
        </form>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const allSeatButtons = document.querySelectorAll('.seat-button');
    const classBtns = document.querySelectorAll('.class-btn');
    const confirmBtn = document.getElementById('confirmUpgradeSeat');
    const selectedSeatDisplay = document.getElementById('selectedSeatDisplay');
    const priceDiffDisplay = document.getElementById('priceDiffDisplay');
    const form = document.getElementById('seatUpgradeForm');

    const currentClass = <?php echo json_encode($currentClass); ?>;
    const currentSeat = <?php echo json_encode($currentSeat); ?>;
    const seatChangeFee = <?php echo json_encode($seatChangeFee); ?>;

    let selectedClass = currentClass;
    let selectedSeat = currentSeat;
    let selectedDiff = 0;

    function formatPrice(value) {
        return (value > 0 ? '+' : '') + value.toFixed(2).replace('.', ',') + ' zł';
    }

    function updateSeatsAvailability() {
        allSeatButtons.forEach(btn => {
            const btnClass = btn.dataset.seatClass;
            const btnSeat = btn.dataset.seat;
            const isOccupied = btn.classList.contains('occupied');
            const isCurrentSeat = btnSeat === currentSeat;

            btn.classList.remove('selected');

            if (btnClass === selectedClass && (!isOccupied || isCurrentSeat)) {
                btn.disabled = false;
                btn.classList.remove('unavailable-class');
            } else {
                btn.disabled = true;

                if (!isOccupied && !isCurrentSeat) {
                    btn.classList.add('unavailable-class');
                }
            }
        });
    }

    function updateSummary() {
        selectedSeatDisplay.textContent = selectedSeat || 'Brak';

        if (selectedClass === currentClass) {
            selectedDiff = (selectedSeat === currentSeat) ? 0 : seatChangeFee;
        }

        priceDiffDisplay.textContent = formatPrice(selectedDiff);
        confirmBtn.disabled = !selectedSeat || (selectedClass === currentClass && selectedSeat === currentSeat);
    }

    classBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            classBtns.forEach(item => item.classList.remove('class-btn--selected'));
            btn.classList.add('class-btn--selected');

            selectedClass = btn.dataset.class;
            selectedSeat = selectedClass === currentClass ? currentSeat : null;
            selectedDiff = selectedClass === currentClass ? 0 : parseFloat(btn.dataset.diff || '0');

            updateSeatsAvailability();
            updateSummary();
        });
    });

    allSeatButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.disabled) {
                return;
            }

            allSeatButtons.forEach(item => item.classList.remove('selected'));

            selectedSeat = btn.dataset.seat;

            if (selectedSeat !== currentSeat) {
                btn.classList.add('selected');
            }

            updateSummary();
        });
    });

    confirmBtn.addEventListener('click', () => {
        document.getElementById('newClassInput').value = selectedClass;
        document.getElementById('newSeatInput').value = selectedSeat;
        document.getElementById('seatPriceInput').value = selectedDiff.toFixed(2);

        form.submit();
    });

    updateSeatsAvailability();
    updateSummary();
});
</script>