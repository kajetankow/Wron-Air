<?php
require_once __DIR__ . '/config/db.php';

$search = $_SESSION['flight_search'] ?? null;
$departureChoice = $_SESSION['departure_choice'] ?? null;
$passengerData = $_SESSION['passenger_data'] ?? null;

if (!$search || !$departureChoice || !$passengerData) {
    echo '<main class="content"><h1>Brak danych rezerwacji.</h1></main>';
    return;
}

$pdo = getDb();

$passengersCount = max(1, (int)($search['passengers'] ?? 1));
$departureDate = $search['departure_date'] ?? date('Y-m-d');

$flightId = (int)($departureChoice['flight_id'] ?? 0);
$flightCode = $departureChoice['flight_code'] ?? 'Brak info';
$ticketType = strtoupper($departureChoice['ticket_type'] ?? 'ECONOMY');
$basePrice = (float)($_SESSION['booking_total_price'] ?? $departureChoice['price'] ?? 0);
$duration = $departureChoice['duration'] ?? '';

$oldSeatSelection = $_SESSION['seat_selection'] ?? [];
$oldSeatAssignments = $oldSeatSelection['seat_assignments'] ?? [];
$oldBaggageCounts = $oldSeatSelection['baggage_counts'] ?? [];

function parseDurationToMinutes(string $duration): int
{
    $duration = mb_strtolower($duration);

    $hours = 0;
    $minutes = 0;

    if (preg_match('/(\d+)\s*h/', $duration, $match)) {
        $hours = (int)$match[1];
    }

    if (preg_match('/(\d+)\s*min/', $duration, $match)) {
        $minutes = (int)$match[1];
    }

    return ($hours * 60) + $minutes;
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

function renderSeatButton(
    string $seat,
    string $seatClass,
    string $ticketClass,
    array $occupiedSeats
): void {
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
        class="<?php echo htmlspecialchars(implode(' ', $classes)); ?>"
        data-seat="<?php echo htmlspecialchars($seat); ?>"
        data-seat-class="<?php echo htmlspecialchars($seatClass); ?>"
        title="<?php echo !$isAllowedClass ? 'Miejsce dostępne tylko dla klasy ' . htmlspecialchars($seatClass) : ''; ?>"
        <?php echo $isDisabled ? 'disabled' : ''; ?>
    >
        <?php echo htmlspecialchars($seat); ?>
    </button>
    <?php
}

$ticketClass = normalizeTicketClass($ticketType);
$durationMinutes = parseDurationToMinutes($duration);

$isLongHaul = $durationMinutes >= 240;

$aircraftModel = $isLongHaul ? 'Airbus A330-300' : 'Airbus A320-200';
$aircraftTypeClass = $isLongHaul ? 'long-haul-aircraft' : 'short-haul-aircraft';

$occupiedSeats = [];

if ($flightId > 0) {
    $stmt = $pdo->prepare("
        SELECT seat_number
        FROM flight_occupied_seats
        WHERE flight_id = :flight_id
          AND flight_date = :flight_date
    ");

    $stmt->execute([
        ':flight_id' => $flightId,
        ':flight_date' => $departureDate
    ]);

    $occupiedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$passengers = [];

for ($i = 0; $i < $passengersCount; $i++) {
    $singlePassenger = $passengerData['passengers'][$i] ?? [];

    $firstName = trim($singlePassenger['first_name'] ?? '');
    $lastName = trim($singlePassenger['last_name'] ?? '');

    $fullName = trim($firstName . ' ' . $lastName);

    if ($fullName === '') {
        $fullName = 'Pasażer ' . ($i + 1);
    }

    $passengers[] = [
        'index' => $i,
        'label' => $fullName
    ];
}
?>

<link rel="stylesheet" href="style/style_wybor_miejsca.css" />
<link rel="stylesheet" href="style/style_headfoot.css" />

<main class="content">
    <div class="line-container">
        <div class="text-linia">Wybór miejsca</div>
        <div class="line-right"></div>
    </div>

    <p>
        Wybierz miejsca dla pasażerów. Miejsca z innych klas są widoczne, ale zablokowane.
    </p>

    <h3>Lot: <span id="flightCode"><?php echo htmlspecialchars($flightCode); ?></span></h3>
    <h3>Model samolotu: <?php echo htmlspecialchars($aircraftModel); ?></h3>
    <h3>Wybrana klasa: <?php echo htmlspecialchars($ticketClass); ?></h3>

    <div class="passenger-seat-panel">
        <h3>Przypisanie miejsc:</h3>

        <div class="passenger-tabs">
            <?php foreach ($passengers as $passenger): ?>
                <button
                    type="button"
                    class="passenger-tab <?php echo $passenger['index'] === 0 ? 'active' : ''; ?>"
                    data-passenger-index="<?php echo htmlspecialchars((string)$passenger['index']); ?>"
                >
                    <?php echo htmlspecialchars($passenger['label']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="seat-assignment-list">
            <?php foreach ($passengers as $passenger): ?>
                <p>
                    <strong><?php echo htmlspecialchars($passenger['label']); ?>:</strong>
                    <span
                        class="passenger-seat-value"
                        data-passenger-index="<?php echo htmlspecialchars((string)$passenger['index']); ?>"
                    >
                        NIE WYBRANO
                    </span>
                </p>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="background-wrapper">
        <div class="background-container <?php echo htmlspecialchars($aircraftTypeClass); ?>"></div>

        <div class="content-wrapper">
            <?php if ($isLongHaul): ?>

                <div class="class-label">BUSINESS</div>
                <div class="class-section business-class <?php echo $ticketClass !== 'BUSINESS' ? 'locked-section' : ''; ?>">
                    <?php for ($row = 1; $row <= 5; $row++): ?>
                        <div class="row business-row long-haul-row">
                            <?php foreach (['A', 'C', 'D', 'H'] as $index => $letter): ?>
                                <?php if ($index === 1 || $index === 3): ?>
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
                            <?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $index => $letter): ?>
                                <?php if ($index === 2 || $index === 5): ?>
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
                            <?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $index => $letter): ?>
                                <?php if ($index === 2 || $index === 6): ?>
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
                            <?php foreach (['A', 'C', 'D', 'F'] as $index => $letter): ?>
                                <?php if ($index === 2): ?>
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
                            <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $index => $letter): ?>
                                <?php if ($index === 3): ?>
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
                            <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $index => $letter): ?>
                                <?php if ($index === 3): ?>
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
                <span style="display:inline-block;width:20px;height:20px;background-color:green;margin-right:10px;border:1px solid black;"></span>
                Wybrane miejsce
            </li>
            <li>
                <span style="display:inline-block;width:20px;height:20px;background-color:red;margin-right:10px;border:1px solid black;"></span>
                Zajęte miejsce
            </li>
            <li>
                <span style="display:inline-block;width:20px;height:20px;background-color:#f0f0f0;margin-right:10px;border:1px solid black;"></span>
                Dostępne miejsce w Twojej klasie
            </li>
            <li>
                <span style="display:inline-block;width:20px;height:20px;background-color:#bfbfbf;margin-right:10px;border:1px solid black;"></span>
                Miejsce w innej klasie
            </li>
        </ul>
    </div>

    <div class="info-section">
        <p>
            Najpierw wybierz pasażera, a następnie kliknij wolne miejsce.
            Każdy pasażer może mieć przypisane jedno miejsce.
            Jeśli nie wybierzesz miejsca, zostanie ono przydzielone później.
        </p>

        <p class="extra">
            Wybór miejsca jest opcją dodatkowo płatną:
            <strong>599 PLN w Economy</strong>,
            <strong>399 PLN w Premium Economy</strong>,
            <strong>0 PLN w Business</strong>.
        </p>
    </div>

    <div class="line-container">
        <div class="text-linia">Bagaż rejestrowany</div>
        <div class="line-right"></div>
    </div>

    <div id="passengers-container"></div>

    <div class="price-container">
        <h2>
            Cena całkowita:
            <span class="price-value"><?php echo number_format($basePrice, 2, '.', ''); ?></span> PLN
        </h2>

        <div class="button-group">
            <a class="aboard" href="index.php?view=home" type="button">Anuluj rezerwację</a>
            <button id="confirmButton" type="button">Potwierdź</button>
        </div>
    </div>

    <form id="seatSelectionForm" method="post" action="index.php" style="display:none;">
        <input type="hidden" name="seat_selection_submit" value="1">
        <input type="hidden" name="seat_assignments" id="seatAssignmentsInput">
        <input type="hidden" name="selected_seats" id="selectedSeatsInput">
        <input type="hidden" name="baggage_counts" id="baggageCountsInput">
        <input type="hidden" name="final_price" id="finalPriceInput">
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const passengers = <?php echo json_encode($passengers, JSON_UNESCAPED_UNICODE); ?>;
    const basePrice = <?php echo json_encode(number_format($basePrice, 2, '.', '')); ?>;
    const ticketClass = <?php echo json_encode($ticketClass); ?>;

    const oldSeatAssignments = <?php echo json_encode($oldSeatAssignments, JSON_UNESCAPED_UNICODE); ?>;
    const oldBaggageCounts = <?php echo json_encode($oldBaggageCounts, JSON_UNESCAPED_UNICODE); ?>;

    const passengerTabs = document.querySelectorAll('.passenger-tab');
    const passengerSeatValues = document.querySelectorAll('.passenger-seat-value');
    const seatButtons = document.querySelectorAll('.seat-button');

    const passengersContainer = document.getElementById('passengers-container');
    const priceValue = document.querySelector('.price-value');
    const confirmButton = document.getElementById('confirmButton');

    const seatAssignmentsInput = document.getElementById('seatAssignmentsInput');
    const selectedSeatsInput = document.getElementById('selectedSeatsInput');
    const baggageCountsInput = document.getElementById('baggageCountsInput');
    const finalPriceInput = document.getElementById('finalPriceInput');
    const seatSelectionForm = document.getElementById('seatSelectionForm');

    let activePassengerIndex = 0;
    let seatAssignments = {};

    passengers.forEach(passenger => {
        const stringIndex = String(passenger.index);

        seatAssignments[passenger.index] =
            oldSeatAssignments[stringIndex] ||
            oldSeatAssignments[passenger.index] ||
            '';
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

    function getPassengerName(index) {
        const passenger = passengers.find(item => item.index === index);
        return passenger ? passenger.label : `Pasażer ${index + 1}`;
    }

    function refreshAssignmentDisplay() {
        passengerSeatValues.forEach(span => {
            const index = parseInt(span.dataset.passengerIndex, 10);
            const seat = seatAssignments[index];

            span.textContent = seat || 'NIE WYBRANO';
        });

        seatButtons.forEach(button => {
            const seat = button.dataset.seat;
            const assignedPassengerIndex = Object.keys(seatAssignments).find(
                passengerIndex => seatAssignments[passengerIndex] === seat
            );

            button.classList.remove('selected');
            button.removeAttribute('data-passenger-label');

            if (assignedPassengerIndex !== undefined) {
                button.classList.add('selected');
                button.setAttribute(
                    'data-passenger-label',
                    getPassengerName(parseInt(assignedPassengerIndex, 10))
                );
            }
        });
    }

    function getSelectedSeats() {
        return Object.values(seatAssignments).filter(seat => seat !== '');
    }

    function calculateSeatCost() {
        const selectedSeatsCount = getSelectedSeats().length;

        if (ticketClass === 'BUSINESS') {
            return 0;
        }

        if (ticketClass === 'PREMIUM ECONOMY') {
            return selectedSeatsCount * 399;
        }

        return selectedSeatsCount * 599;
    }

    function getFreeBagsCount() {
        if (ticketClass === 'BUSINESS') {
            return 2;
        }

        if (ticketClass === 'PREMIUM ECONOMY') {
            return 1;
        }

        return 0;
    }

    function calculateBaggageCost() {
        let total = 0;
        const freeBags = getFreeBagsCount();

        document.querySelectorAll('.baggage-counter-value').forEach(span => {
            const baggageCount = parseInt(span.textContent, 10) || 0;
            const paidBags = Math.max(0, baggageCount - freeBags);
            total += paidBags * 600;
        });

        return total;
    }

    function calculateTotalPrice() {
        return parseFloat(basePrice || '0') + calculateSeatCost() + calculateBaggageCost();
    }

    function updatePriceDisplay() {
        priceValue.textContent = calculateTotalPrice().toFixed(2);
    }

    function updateCounter(span, change) {
        let count = parseInt(span.textContent, 10) || 0;
        count += change;

        if (count < 0) {
            count = 0;
        }

        if (count > 2) {
            count = 2;
        }

        span.textContent = count;
        updatePriceDisplay();
    }

    function createPassengerBaggageSection(passengerName, passengerIndex) {
        const container = document.createElement('div');
        container.classList.add('baggage-container');

        const oldBaggageCount = oldBaggageCounts[passengerIndex] || 0;

        container.innerHTML = `
            <p class="passenger-label">${passengerName}</p>
            <div class="baggage-header">
                <h2>POSIADANE SZTUKI BAGAŻU REJESTROWANEGO:</h2>
                <div class="counter">
                    <button type="button" class="baggage-minus">-</button>
                    <span class="baggage-counter-value">${oldBaggageCount}</span>
                    <button type="button" class="baggage-plus">+</button>
                </div>
            </div>
            <p class="baggage-info">
                Bagaż rejestrowany to bagaż nadawany do luku bagażowego.
                Maksymalna waga jednej sztuki bagażu: 23 kg, wymiary: do 70x50x38 cm.<br>
                Każda dodatkowa płatna sztuka bagażu kosztuje 600 PLN.
            </p>
            <p class="extra-info">Możesz wybrać od 0 do 2 sztuk bagażu.</p>
        `;

        const minusButton = container.querySelector('.baggage-minus');
        const plusButton = container.querySelector('.baggage-plus');
        const counterSpan = container.querySelector('.baggage-counter-value');

        minusButton.addEventListener('click', () => updateCounter(counterSpan, -1));
        plusButton.addEventListener('click', () => updateCounter(counterSpan, 1));

        passengersContainer.appendChild(container);
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
            const seat = button.dataset.seat;

            const assignedPassengerIndex = Object.keys(seatAssignments).find(
                passengerIndex => seatAssignments[passengerIndex] === seat
            );

            if (assignedPassengerIndex !== undefined) {
                seatAssignments[assignedPassengerIndex] = '';
            }

            if (seatAssignments[activePassengerIndex] === seat) {
                seatAssignments[activePassengerIndex] = '';
            } else {
                seatAssignments[activePassengerIndex] = seat;
            }

            refreshAssignmentDisplay();
            updatePriceDisplay();
        });
    });

    passengers.forEach(passenger => {
        createPassengerBaggageSection(passenger.label, passenger.index);
    });

    confirmButton.addEventListener('click', () => {
        const baggageCounts = [];

        document.querySelectorAll('.baggage-counter-value').forEach(span => {
            baggageCounts.push(span.textContent.trim());
        });

        const selectedSeats = getSelectedSeats();

        seatAssignmentsInput.value = JSON.stringify(seatAssignments);
        selectedSeatsInput.value = selectedSeats.join(',');
        baggageCountsInput.value = baggageCounts.join('-');
        finalPriceInput.value = calculateTotalPrice().toFixed(2);

        seatSelectionForm.submit();
    });

    setActivePassenger(0);
    refreshAssignmentDisplay();
    updatePriceDisplay();
});
</script>