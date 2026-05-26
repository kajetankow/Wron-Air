<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/flight_search.php';

$search = $_SESSION['flight_search'] ?? null;

if (!$search) {
    echo '<main class="content"><h1>Brak danych wyszukiwania.</h1></main>';
    return;
}

$fromCode = extractAirportCode($search['from']);
$toCode = extractAirportCode($search['to']);

if (!$fromCode || !$toCode) {
    echo '<main class="content"><h1>Nieprawidłowe dane lotnisk.</h1></main>';
    return;
}

$pdo = getDb();

$fromDisplay = getAirportDisplayName($pdo, $fromCode);
$toDisplay = getAirportDisplayName($pdo, $toCode);

$flights = findFlightOptions($pdo, $fromCode, $toCode, 4, 50);

$departureDate = $search['departure_date'] ?? 'Brak info';
$passengers = max(1, (int)($search['passengers'] ?? 1));
$tripType = $search['trip_type'] ?? 'round-trip';
$returnDate = $search['return_date'] ?? '';
?>

<link rel="stylesheet" href="style/style_departure.css" />
<link rel="stylesheet" href="style/style_headfoot.css" />

<?php if (empty($flights)): ?>
    <main class="content">
        <div class="no-connection-state">
            <h1>Przepraszamy 🙁</h1>
            <p>
                Obecnie nie realizujemy połączenia z
                <strong><?php echo htmlspecialchars($fromDisplay); ?></strong>
                do
                <strong><?php echo htmlspecialchars($toDisplay); ?></strong>.
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
            Wybierz godzinę wylotu w dniu:
            <span id="departure_date-display"><?php echo htmlspecialchars($departureDate); ?></span>
        </h1>

        <h2>
            <span class="spaces"></span>
            <span id="from-display3"><?php echo htmlspecialchars($fromDisplay); ?></span>
            ------------
            <span><img src="img/samolot.png" alt="Plane Icon" class="icon"></span>
            ------------
            <span id="to-display3"><?php echo htmlspecialchars($toDisplay); ?></span>
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

                    $departureTime = formatTimeValue($flight['departure_time'] ?? null);
                    $arrivalTime = formatTimeValue($flight['arrival_time'] ?? null);
                    $hourLabel = formatDurationLabel($flight['total_duration'] ?? null);
                ?>

                <div class="flight-options">
                    <div class="flight-info">
                        <h2>
                            <strong>
                                <span><?php echo htmlspecialchars($fromDisplay); ?></span>
                                <span class="space"></span>
                                <span><?php echo htmlspecialchars($toDisplay); ?></span>
                            </strong>
                        </h2>

                        <p class="hour"><?php echo htmlspecialchars($hourLabel); ?></p>

                        <h2>
                            <span class="departure-time" data-time="<?php echo htmlspecialchars($departureTime); ?>">
                                <?php echo htmlspecialchars($departureTime); ?>
                            </span>
                            --------------------
                            <span><img src="img/clock.png" alt="Clock Icon" class="icon"></span>
                            --------------------
                            <span class="arrival-time" data-time="<?php echo htmlspecialchars($arrivalTime); ?>">
                                <?php echo htmlspecialchars($arrivalTime); ?>
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
                                class="choose-flight-btn"
                                data-total-price="<?php echo htmlspecialchars(number_format((float)($economyTotal ?? 0), 2, '.', '')); ?>"
                                data-flight-id="<?php echo htmlspecialchars((string)($flight['id'] ?? $index)); ?>"
                                data-flight-class="ECONOMY"
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
                                class="choose-flight-btn"
                                data-total-price="<?php echo htmlspecialchars(number_format((float)($premiumTotal ?? 0), 2, '.', '')); ?>"
                                data-flight-id="<?php echo htmlspecialchars((string)($flight['id'] ?? $index)); ?>"
                                data-flight-class="PREMIUM ECONOMY"
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
                                class="choose-flight-btn"
                                data-total-price="<?php echo htmlspecialchars(number_format((float)($businessTotal ?? 0), 2, '.', '')); ?>"
                                data-flight-id="<?php echo htmlspecialchars((string)($flight['id'] ?? $index)); ?>"
                                data-flight-class="BUSINESS"
                            >
                                Wybierz
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <div class="price-container">
            <h2>Cena całkowita: <span class="price-value">0.00 PLN</span></h2>
            <a id="nextPageLink" class="return-button" href="#">
                <?php echo $tripType === 'round-trip' ? 'Wybierz lot powrotny' : 'Wprowadź dane pasażerów'; ?>
            </a>
        </div>
    </main>

    <form id="flightChoiceForm" method="post" action="index.php" style="display:none;">
        <input type="hidden" name="trip_type" value="<?php echo htmlspecialchars($tripType); ?>">
        <input type="hidden" name="from" value="<?php echo htmlspecialchars($search['from'] ?? ''); ?>">
        <input type="hidden" name="to" value="<?php echo htmlspecialchars($search['to'] ?? ''); ?>">
        <input type="hidden" name="passengers" value="<?php echo htmlspecialchars((string)$passengers); ?>">
        <input type="hidden" name="departure_date" value="<?php echo htmlspecialchars($departureDate); ?>">
        <input type="hidden" name="return_date" value="<?php echo htmlspecialchars($returnDate); ?>">

        <input type="hidden" name="departure_flight_id" id="selectedFlightId">
        <input type="hidden" name="departure_ticket_type" id="selectedTicketType">
        <input type="hidden" name="departure_price" id="selectedPrice">
        <input type="hidden" name="departure_duration" id="selectedDuration">
        <input type="hidden" name="departure_time" id="selectedDepartureTime">
        <input type="hidden" name="departure_arrival_time" id="selectedArrivalTime">
        <input type="hidden" name="departure_flight_code" id="selectedFlightCode">
        <input type="hidden" name="departure_operator_name" id="selectedOperatorName">
        <input type="hidden" name="departure_choice" value="1">
        <input type="hidden" name="next_view" id="nextView">
    </form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const priceValue = document.querySelector('.price-value');
    const nextPageLink = document.getElementById('nextPageLink');
    const chooseButtons = document.querySelectorAll('.choose-flight-btn');
    const tripType = <?php echo json_encode($tripType); ?>;

    let selectedButton = null;

    if (nextPageLink) {
        nextPageLink.classList.add('disabled');
        nextPageLink.style.pointerEvents = 'none';
        nextPageLink.style.opacity = '0.5';
        nextPageLink.textContent = tripType === 'round-trip'
            ? 'Wybierz lot powrotny'
            : 'Wprowadź dane pasażerów';
    }

    function resetSelections() {
        document.querySelectorAll('.price-box').forEach(box => {
            box.classList.remove('selected');
        });

        document.querySelectorAll('.flight-options').forEach(option => {
            option.classList.remove('flight-options-active');
        });

        document.querySelectorAll('.choose-flight-btn').forEach(button => {
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

            const totalPrice = parseFloat(button.dataset.totalPrice || '0');
            const flightId = button.dataset.flightId || '';
            const flightClass = button.dataset.flightClass || '';

            const duration = flightOptions.querySelector('.hour')?.textContent.trim() || '';
            const departureTime = flightOptions.querySelector('.departure-time')?.getAttribute('data-time') || '';
            const arrivalTime = flightOptions.querySelector('.arrival-time')?.getAttribute('data-time') || '';
            const flightCode = flightOptions.querySelector('.additional-info p')?.textContent.trim() || '';
            const operatorName = flightOptions.querySelectorAll('.additional-info p')[1]?.textContent.trim() || '';

            if (priceValue) {
                priceValue.textContent = totalPrice.toFixed(2).replace('.', ',') + ' PLN';
            }

            document.getElementById('selectedFlightId').value = flightId;
            document.getElementById('selectedTicketType').value = flightClass;
            document.getElementById('selectedPrice').value = totalPrice.toFixed(2);
            document.getElementById('selectedDuration').value = duration;
            document.getElementById('selectedDepartureTime').value = departureTime;
            document.getElementById('selectedArrivalTime').value = arrivalTime;
            document.getElementById('selectedFlightCode').value = flightCode;
            document.getElementById('selectedOperatorName').value = operatorName;

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

        document.getElementById('nextView').value =
            tripType === 'round-trip' ? 'return' : 'passenger_data';

        document.getElementById('flightChoiceForm').submit();
    });
    }
});
</script>