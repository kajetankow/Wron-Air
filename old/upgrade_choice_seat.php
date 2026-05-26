<?php
$orderCode = strtoupper(trim($_GET['orderCode'] ?? ''));
$passengerId = (int)($_GET['passengerId'] ?? 0);

require_once __DIR__ . '/config/db.php';
$pdo = getDb();

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

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        return [
            'flight_name' => 'Brak info',
            'duration_minutes' => 0,
            'aircraft_model' => 'Brak info',
            'price_economy' => 0,
            'price_premium_economy' => 0,
            'price_business' => 0
        ];
    }

    return $data;
}

function getSeatClass(string $seat, bool $isLongHaul): string
{
    preg_match('/^(\d+)/', strtoupper(trim($seat)), $match);
    $row = isset($match[1]) ? (int)$match[1] : 0;

    if ($isLongHaul) {
        if ($row >= 1 && $row <= 4) {
            return 'BUSINESS';
        }

        if ($row >= 5 && $row <= 9) {
            return 'PREMIUM ECONOMY';
        }

        return 'ECONOMY';
    }

    if ($row >= 1 && $row <= 3) {
        return 'BUSINESS';
    }

    if ($row >= 4 && $row <= 7) {
        return 'PREMIUM ECONOMY';
    }

    return 'ECONOMY';
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
        class="<?php echo e(implode(' ', $classes)); ?>"
        data-seat="<?php echo e($seat); ?>"
        data-seat-class="<?php echo e($seatClass); ?>"
        <?php echo $isDisabled ? 'disabled' : ''; ?>
    >
        <?php echo e($seat); ?>
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
       AND rf.flight_direction = 'departure'
    JOIN reservations r
        ON r.id = rf.reservation_id
    WHERE rp.id = :pid
      AND r.reservation_code = :code
    LIMIT 1
");

$stmt->execute([
    ':pid' => $passengerId,
    ':code' => $orderCode
]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo '<main><p>Nie znaleziono danych pasażera.</p></main>';
    return;
}

$flightId = (int)$data['flight_id'];
$flightDate = $data['flight_date'] ?? date('Y-m-d');
$currentClass = normalizeTicketClass($data['ticket_type'] ?? 'ECONOMY');
$currentSeat = strtoupper(trim($data['seat_number'] ?? ''));
$currentPrice = (float)$data['price'];
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
            <strong><?php echo e(trim($data['first_name'] . ' ' . $data['last_name'])); ?></strong>
            &nbsp;|&nbsp; Aktualna klasa:
            <strong><?php echo e($currentClass); ?></strong>
            &nbsp;|&nbsp; Aktualne miejsce:
            <strong><?php echo e($currentSeat ?: 'Brak'); ?></strong>
            &nbsp;|&nbsp; Model samolotu:
            <strong><?php echo e($aircraftModel); ?></strong>
        </p>

        <div class="class-selector">
            <?php foreach ($classPrices as $key => $price): ?>
                <?php
                $key = strtoupper(trim($key));
                $diff = $price - $currentPrice;
                $isCurrent = $key === $currentClass;
                ?>

                <button
                    type="button"
                    class="class-btn <?php echo $isCurrent ? 'class-btn--current class-btn--selected' : ''; ?>"
                    data-class="<?php echo e($key); ?>"
                    data-price="<?php echo e($price); ?>"
                    data-diff="<?php echo e($diff); ?>"
                    data-current="<?php echo $isCurrent ? '1' : '0'; ?>"
                >
                    <?php echo e($key); ?>

                    <?php if ($isCurrent): ?>
                        <span>(posiadana)</span>
                    <?php else: ?>
                        <span>
                            <?php echo $diff > 0 ? '+' : ''; ?><?php echo number_format($diff, 2, ',', ' '); ?> zł
                        </span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <p class="seat-instruction">
            Możesz zmienić miejsce w obrębie swojej klasy za 150,00 zł. Miejsca zajęte są oznaczone na czerwono.
        </p>

        <div class="background-wrapper">
            <div class="background-container <?php echo e($aircraftTypeClass); ?>"></div>

            <div class="content-wrapper">
                <?php if ($isLongHaul): ?>

                    <div class="class-label">BUSINESS</div>
                    <div class="class-section business-class <?php echo $currentClass !== 'BUSINESS' ? 'locked-section' : ''; ?>">
                        <?php for ($row = 1; $row <= 4; $row++): ?>
                            <div class="row business-row long-haul-row">
                                <?php foreach (['A', 'C', 'D', 'H'] as $i => $letter): ?>
                                    <?php if ($i === 1 || $i === 3): ?>
                                        <div class="spacer"></div>
                                    <?php endif; ?>

                                    <?php
                                    renderUpgradeSeat(
                                        $row . $letter,
                                        'BUSINESS',
                                        $currentClass,
                                        $occupiedSeats,
                                        $currentSeat
                                    );
                                    ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="class-label">PREMIUM ECONOMY</div>
                    <div class="class-section premium-class <?php echo $currentClass !== 'PREMIUM ECONOMY' ? 'locked-section' : ''; ?>">
                        <?php for ($row = 5; $row <= 9; $row++): ?>
                            <div class="row premium-row long-haul-row">
                                <?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $i => $letter): ?>
                                    <?php if ($i === 2 || $i === 5): ?>
                                        <div class="spacer"></div>
                                    <?php endif; ?>

                                    <?php
                                    renderUpgradeSeat(
                                        $row . $letter,
                                        'PREMIUM ECONOMY',
                                        $currentClass,
                                        $occupiedSeats,
                                        $currentSeat
                                    );
                                    ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="class-label">ECONOMY</div>
                    <div class="economy-class <?php echo $currentClass !== 'ECONOMY' ? 'locked-section' : ''; ?>">
                        <?php for ($row = 10; $row <= 32; $row++): ?>
                            <?php if ($row === 22): ?>
                                <div class="row-break"></div>
                            <?php endif; ?>

                            <div class="row economy-row long-haul-row">
                                <?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $i => $letter): ?>
                                    <?php if ($i === 2 || $i === 6): ?>
                                        <div class="spacer"></div>
                                    <?php endif; ?>

                                    <?php
                                    renderUpgradeSeat(
                                        $row . $letter,
                                        'ECONOMY',
                                        $currentClass,
                                        $occupiedSeats,
                                        $currentSeat
                                    );
                                    ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                <?php else: ?>

                    <div class="class-label">BUSINESS</div>
                    <div class="class-section business-class <?php echo $currentClass !== 'BUSINESS' ? 'locked-section' : ''; ?>">
                        <?php for ($row = 1; $row <= 3; $row++): ?>
                            <div class="row business-row short-haul-row">
                                <?php foreach (['A', 'C', 'D', 'F'] as $i => $letter): ?>
                                    <?php if ($i === 2): ?>
                                        <div class="spacer"></div>
                                    <?php endif; ?>

                                    <?php
                                    renderUpgradeSeat(
                                        $row . $letter,
                                        'BUSINESS',
                                        $currentClass,
                                        $occupiedSeats,
                                        $currentSeat
                                    );
                                    ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="class-label">PREMIUM ECONOMY</div>
                    <div class="class-section premium-class <?php echo $currentClass !== 'PREMIUM ECONOMY' ? 'locked-section' : ''; ?>">
                        <?php for ($row = 4; $row <= 7; $row++): ?>
                            <div class="row premium-row short-haul-row">
                                <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $i => $letter): ?>
                                    <?php if ($i === 3): ?>
                                        <div class="spacer"></div>
                                    <?php endif; ?>

                                    <?php
                                    renderUpgradeSeat(
                                        $row . $letter,
                                        'PREMIUM ECONOMY',
                                        $currentClass,
                                        $occupiedSeats,
                                        $currentSeat
                                    );
                                    ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="class-label">ECONOMY</div>
                    <div class="economy-class <?php echo $currentClass !== 'ECONOMY' ? 'locked-section' : ''; ?>">
                        <?php for ($row = 8; $row <= 22; $row++): ?>
                            <div class="row economy-row short-haul-row">
                                <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $i => $letter): ?>
                                    <?php if ($i === 3): ?>
                                        <div class="spacer"></div>
                                    <?php endif; ?>

                                    <?php
                                    renderUpgradeSeat(
                                        $row . $letter,
                                        'ECONOMY',
                                        $currentClass,
                                        $occupiedSeats,
                                        $currentSeat
                                    );
                                    ?>
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
                <strong id="selectedSeatDisplay"><?php echo e($currentSeat ?: 'Brak'); ?></strong>
            </p>

            <p>
                Dopłata:
                <strong id="priceDiffDisplay">0,00 zł</strong>
            </p>

            <button id="confirmUpgradeSeat" class="upgrade-btn" disabled>
                Zatwierdź zmianę
            </button>
        </div>

        <div class="upgrade-back">
            <a href="index.php?view=upgrade_choice&orderCode=<?php echo urlencode($orderCode); ?>" class="upgrade-back-btn">
                ← Wróć do wyboru pasażera
            </a>
        </div>

        <form id="seatUpgradeForm" method="GET" action="index.php" style="display:none;">
            <input type="hidden" name="view" value="upgrade_end">
            <input type="hidden" name="orderCode" value="<?php echo e($orderCode); ?>">
            <input type="hidden" name="passengerId" value="<?php echo e($passengerId); ?>">
            <input type="hidden" name="upgradeChoice" value="seat">
            <input type="hidden" name="newClass" id="newClassInput">
            <input type="hidden" name="newSeat" id="newSeatInput">
            <input type="hidden" name="seatPrice" id="seatPriceInput">
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
            selectedDiff = selectedSeat === currentSeat ? 0 : seatChangeFee;
        }

        priceDiffDisplay.textContent = formatPrice(selectedDiff);

        const noSeatSelected = !selectedSeat;
        const noRealChange = selectedClass === currentClass && selectedSeat === currentSeat;

        confirmBtn.disabled = noSeatSelected || noRealChange;
    }

    classBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            classBtns.forEach(item => {
                item.classList.remove('class-btn--selected');
            });

            btn.classList.add('class-btn--selected');

            selectedClass = btn.dataset.class;
            selectedSeat = selectedClass === currentClass ? currentSeat : null;

            if (selectedClass === currentClass) {
                selectedDiff = 0;
            } else {
                selectedDiff = parseFloat(btn.dataset.diff || '0');
            }

            updateSeatsAvailability();
            updateSummary();
        });
    });

    allSeatButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.disabled) {
                return;
            }

            allSeatButtons.forEach(item => {
                item.classList.remove('selected');
            });

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