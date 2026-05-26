<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/flight_search.php';

$search = $_SESSION['flight_search'] ?? null;
$departureChoice = $_SESSION['departure_choice'] ?? null;

if (!$search || !$departureChoice) {
    echo '<main class="content"><h1>Brak danych rezerwacji.</h1></main>';
    return;
}

$tripType = $search['trip_type'] ?? 'round-trip';
$from = $search['from'] ?? '';
$to = $search['to'] ?? '';
$passengers = max(1, (int)($search['passengers'] ?? 1));
$departureDate = $search['departure_date'] ?? '';
$returnDate = $search['return_date'] ?? '';

$departureFlightId = $departureChoice['flight_id'] ?? '';
$departureTicketType = $departureChoice['ticket_type'] ?? '';
$departurePrice = (float)($departureChoice['price'] ?? 0);
$departureDuration = $departureChoice['duration'] ?? '';
$departureTime = $departureChoice['departure_time'] ?? '';
$departureArrivalTime = $departureChoice['arrival_time'] ?? '';
$departureFlightCode = $departureChoice['flight_code'] ?? '';
$departureOperatorName = $departureChoice['operator_name'] ?? '';

$fromCode = extractAirportCode($from);
$toCode = extractAirportCode($to);

if (!$fromCode || !$toCode) {
    echo '<main class="content"><h1>Nieprawidłowe dane lotnisk.</h1></main>';
    return;
}

$pdo = getDb();

$fromDisplay = getAirportDisplayName($pdo, $fromCode);
$toDisplay = getAirportDisplayName($pdo, $toCode);

$flights = findFlightOptions($pdo, $toCode, $fromCode, 4, 50);
?>

<link rel="stylesheet" href="style/style_departure.css" />
<link rel="stylesheet" href="style/style_headfoot.css" />

<?php if (empty($flights)): ?>
    <main class="content">
        <div class="no-connection-state">
            <h1>Przepraszamy 🙁</h1>
            <p>
                Obecnie nie realizujemy połączenia z
                <strong><?php echo htmlspecialchars($toDisplay); ?></strong>
                do
                <strong><?php echo htmlspecialchars($fromDisplay); ?></strong>.
            </p>
            <p>
                Zapisz się do newslettera, aby być na bieżąco informowanym o naszych nowych ofertach podróży.
            </p>
            <a href="index.php?view=home" class="no-connection-button">Powróć do strony głównej</a>
        </div>
    </main>
<?php else: ?>
    <main class="content">
        <h1>
            Wybierz godzinę powrotu w dniu:
            <span id="arrival_date-display"><?php echo htmlspecialchars($returnDate); ?></span>
        </h1>

        <h2>
            <span class="spaces"></span>
            <span id="to-display3"><?php echo htmlspecialchars($toDisplay); ?></span>
            ------------
            <span><img src="img/samolot.png" alt="Plane Icon" class="icon"></span>
            ------------
            <span id="from-display3"><?php echo htmlspecialchars($fromDisplay); ?></span>
        </h2>

        <section class="departure-container">
            <?php foreach ($flights as $index => $flight): ?>
                <?php
                    $classPrices = getClassPrices($flight);

                    $economyBase = $classPrices['economy'];
                    $premiumBase = $classPrices['premium'];
                    $businessBase = $classPrices['business'];

                    $economyPerPassenger = is_numeric($economyBase) ? (float)$economyBase : null;
                    $premiumPerPassenger = is_numeric($premiumBase) ? (float)$premiumBase : null;
                    $businessPerPassenger = is_numeric($businessBase) ? (float)$businessBase : null;

                    $economyTotal = is_numeric($economyPerPassenger) ? ($economyPerPassenger * $passengers) : null;
                    $premiumTotal = is_numeric($premiumPerPassenger) ? ($premiumPerPassenger * $passengers) : null;
                    $businessTotal = is_numeric($businessPerPassenger) ? ($businessPerPassenger * $passengers) : null;

                    $transferDisplay = 'BEZPOŚREDNI';

                    if (($flight['stops'] ?? 0) > 0) {
                        $transferAirports = array_map(
                            fn($code) => getAirportDisplayName($pdo, $code),
                            $flight['transfer_codes'] ?? []
                        );

                        $transferDisplay = htmlspecialchars($flight['transfer_label']);

                        if (!empty($transferAirports)) {
                            $transferDisplay .= ' </br> ' . htmlspecialchars(implode(', ', $transferAirports));
                        }
                    }

                    $returnDepartureTime = formatTimeValue($flight['departure_time'] ?? null);
                    $returnArrivalTime = formatTimeValue($flight['arrival_time'] ?? null);
                    $returnDuration = formatDurationLabel($flight['total_duration'] ?? null);
                ?>

                <div class="flight-options">
                    <div class="flight-info">
                        <h2>
                            <strong>
                                <span><?php echo htmlspecialchars($toDisplay); ?></span>
                                <span class="space"></span>
                                <span><?php echo htmlspecialchars($fromDisplay); ?></span>
                            </strong>
                        </h2>

                        <p class="hour"><?php echo htmlspecialchars($returnDuration); ?></p>

                        <h2>
                            <span class="departure-time" data-time="<?php echo htmlspecialchars($returnDepartureTime); ?>">
                                <?php echo htmlspecialchars($returnDepartureTime); ?>
                            </span>
                            --------------------
                            <span><img src="img/clock.png" alt="Clock Icon" class="icon"></span>
                            --------------------
                            <span class="arrival-time" data-time="<?php echo htmlspecialchars($returnArrivalTime); ?>">
                                <?php echo htmlspecialchars($returnArrivalTime); ?>
                            </span>
                        </h2>

                        <h4>
                            <?php echo htmlspecialchars($flight['departure_utc'] ?? 'Brak info'); ?>
                            <span class="space"></span>
                            <?php echo htmlspecialchars($flight['arrival_utc'] ?? 'Brak info'); ?>
                        </h4>

                        <p><?php echo $transferDisplay; ?></p>

                        <div class="additional-info">
                            <p><?php echo htmlspecialchars($flight['flight_name'] ?? 'Brak info'); ?></p>
                            <p><?php echo htmlspecialchars($flight['operator_name'] ?? 'Brak info'); ?></p>
                            <p><a href="#" class="details-link">Sprawdź szczegóły</a></p>
                        </div>
                    </div>

                    <div class="flight-prices">
                        <div class="price-box">
                            <h1>ECONOMY</h1>
                            <h2>Od</h2>
                            <p class="price"><?php echo formatPricePln($economyPerPassenger); ?></p>
                            <p class="exception">*Cena za 1 pasażera</p>
                            <p>*Bez bagażu rejestrowanego</p>
                            <button
                                class="choose-return-btn"
                                data-return-price="<?php echo htmlspecialchars(number_format((float)($economyTotal ?? 0), 2, '.', '')); ?>"
                                data-return-flight-id="<?php echo htmlspecialchars((string)($flight['id'] ?? $index)); ?>"
                                data-return-class="ECONOMY"
                            >
                                Wybierz
                            </button>
                        </div>

                        <div class="price-box">
                            <h1>PREMIUM ECONOMY</h1>
                            <h2>Od</h2>
                            <p class="price"><?php echo formatPricePln($premiumPerPassenger); ?></p>
                            <p class="exception">*Cena za 1 pasażera</p>
                            <button
                                <?php echo is_numeric($premiumTotal) ? '' : 'disabled'; ?>
                                class="choose-return-btn"
                                data-return-price="<?php echo htmlspecialchars(number_format((float)($premiumTotal ?? 0), 2, '.', '')); ?>"
                                data-return-flight-id="<?php echo htmlspecialchars((string)($flight['id'] ?? $index)); ?>"
                                data-return-class="PREMIUM ECONOMY"
                            >
                                Wybierz
                            </button>
                        </div>

                        <div class="price-box">
                            <h1>BUSINESS</h1>
                            <h2>Od</h2>
                            <p class="price"><?php echo formatPricePln($businessPerPassenger); ?></p>
                            <p class="exception">*Cena za 1 pasażera</p>
                            <button
                                <?php echo is_numeric($businessTotal) ? '' : 'disabled'; ?>
                                class="choose-return-btn"
                                data-return-price="<?php echo htmlspecialchars(number_format((float)($businessTotal ?? 0), 2, '.', '')); ?>"
                                data-return-flight-id="<?php echo htmlspecialchars((string)($flight['id'] ?? $index)); ?>"
                                data-return-class="BUSINESS"
                            >
                                Wybierz
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <div class="price-container">
            <h2>
                Cena całkowita:
                <span class="price-value">
                    <?php echo number_format($departurePrice, 2, ',', ' '); ?> PLN
                </span>
            </h2>
            <a id="nextPageLink" class="return-button" href="#">Wprowadź dane pasażerów</a>
        </div>
    </main>

    <form id="returnChoiceForm" method="post" action="index.php" style="display:none;">
        <input type="hidden" name="trip_type" value="<?php echo htmlspecialchars($tripType); ?>">
        <input type="hidden" name="from" value="<?php echo htmlspecialchars($from); ?>">
        <input type="hidden" name="to" value="<?php echo htmlspecialchars($to); ?>">
        <input type="hidden" name="passengers" value="<?php echo htmlspecialchars((string)$passengers); ?>">
        <input type="hidden" name="departure_date" value="<?php echo htmlspecialchars($departureDate); ?>">
        <input type="hidden" name="return_date" value="<?php echo htmlspecialchars($returnDate); ?>">

        <input type="hidden" name="departure_flight_id" value="<?php echo htmlspecialchars($departureFlightId); ?>">
        <input type="hidden" name="departure_ticket_type" value="<?php echo htmlspecialchars($departureTicketType); ?>">
        <input type="hidden" name="departure_price" value="<?php echo htmlspecialchars(number_format($departurePrice, 2, '.', '')); ?>">
        <input type="hidden" name="departure_duration" value="<?php echo htmlspecialchars($departureDuration); ?>">
        <input type="hidden" name="departure_time" value="<?php echo htmlspecialchars($departureTime); ?>">
        <input type="hidden" name="departure_arrival_time" value="<?php echo htmlspecialchars($departureArrivalTime); ?>">
        <input type="hidden" name="departure_flight_code" value="<?php echo htmlspecialchars($departureFlightCode); ?>">
        <input type="hidden" name="departure_operator_name" value="<?php echo htmlspecialchars($departureOperatorName); ?>">

        <input type="hidden" name="return_flight_id" id="selectedReturnFlightId">
        <input type="hidden" name="return_ticket_type" id="selectedReturnTicketType">
        <input type="hidden" name="return_price" id="selectedReturnPrice">
        <input type="hidden" name="return_duration" id="selectedReturnDuration">
        <input type="hidden" name="return_departure_time" id="selectedReturnDepartureTime">
        <input type="hidden" name="return_arrival_time" id="selectedReturnArrivalTime">
        <input type="hidden" name="return_flight_code" id="selectedReturnFlightCode">
        <input type="hidden" name="return_operator_name" id="selectedReturnOperatorName">

        <input type="hidden" name="total_price" id="selectedTotalPrice">
        <input type="hidden" name="return_choice" value="1">
    </form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const priceValue = document.querySelector('.price-value');
    const nextPageLink = document.getElementById('nextPageLink');
    const chooseButtons = document.querySelectorAll('.choose-return-btn');
    const departurePrice = <?php echo json_encode(number_format($departurePrice, 2, '.', '')); ?>;

    let selectedButton = null;

    if (nextPageLink) {
        nextPageLink.classList.add('disabled');
        nextPageLink.style.pointerEvents = 'none';
        nextPageLink.style.opacity = '0.5';
        nextPageLink.textContent = 'Wprowadź dane pasażerów';
    }

    function resetSelections() {
        document.querySelectorAll('.price-box').forEach(box => {
            box.classList.remove('selected');
        });

        document.querySelectorAll('.flight-options').forEach(option => {
            option.classList.remove('flight-options-active');
        });

        document.querySelectorAll('.choose-return-btn').forEach(button => {
            button.style.backgroundColor = '';
        });
    }

    chooseButtons.forEach(button => {
        if (button.hasAttribute('disabled')) {
            return;
        }

        button.addEventListener('click', () => {
            const priceBox = button.closest('.price-box');
            const flightOptions = button.closest('.flight-options');

            if (!priceBox || !flightOptions) {
                return;
            }

            resetSelections();

            selectedButton = button;
            priceBox.classList.add('selected');
            flightOptions.classList.add('flight-options-active');
            button.style.backgroundColor = '#053364';

            const returnPrice = parseFloat(button.dataset.returnPrice || '0');
            const departurePriceNumber = parseFloat(departurePrice || '0');
            const totalPrice = departurePriceNumber + returnPrice;

            const returnFlightId = button.dataset.returnFlightId || '';
            const returnClass = button.dataset.returnClass || '';
            const returnDuration = flightOptions.querySelector('.hour')?.textContent.trim() || '';
            const returnDepartureTime = flightOptions.querySelector('.departure-time')?.getAttribute('data-time') || '';
            const returnArrivalTime = flightOptions.querySelector('.arrival-time')?.getAttribute('data-time') || '';
            const returnFlightCode = flightOptions.querySelector('.additional-info p')?.textContent.trim() || '';
            const returnOperatorName = flightOptions.querySelectorAll('.additional-info p')[1]?.textContent.trim() || '';

            if (priceValue) {
                priceValue.textContent = totalPrice.toFixed(2).replace('.', ',') + ' PLN';
            }

            document.getElementById('selectedReturnFlightId').value = returnFlightId;
            document.getElementById('selectedReturnTicketType').value = returnClass;
            document.getElementById('selectedReturnPrice').value = returnPrice.toFixed(2);
            document.getElementById('selectedReturnDuration').value = returnDuration;
            document.getElementById('selectedReturnDepartureTime').value = returnDepartureTime;
            document.getElementById('selectedReturnArrivalTime').value = returnArrivalTime;
            document.getElementById('selectedReturnFlightCode').value = returnFlightCode;
            document.getElementById('selectedReturnOperatorName').value = returnOperatorName;
            document.getElementById('selectedTotalPrice').value = totalPrice.toFixed(2);

            if (nextPageLink) {
                nextPageLink.classList.remove('disabled');
                nextPageLink.style.pointerEvents = 'auto';
                nextPageLink.style.opacity = '1';
                nextPageLink.href = '#';
            }
        });
    });

    if (nextPageLink) {
        nextPageLink.addEventListener('click', (event) => {
            event.preventDefault();

            if (!selectedButton) {
                return;
            }

            document.getElementById('returnChoiceForm').submit();
        });
    }
});
</script>