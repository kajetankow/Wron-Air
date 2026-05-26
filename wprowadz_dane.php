<?php
require_once __DIR__ . '/config/db.php';

$search = $_SESSION['flight_search'] ?? null;
$departureChoice = $_SESSION['departure_choice'] ?? null;
$returnChoice = $_SESSION['return_choice'] ?? null;

if (!$search || !$departureChoice) {
    echo '<main class="content"><h1>Brak danych rezerwacji.</h1></main>';
    return;
}

$pdo = getDb();

$stmt = $pdo->query("
    SELECT name
    FROM countries
    ORDER BY name ASC
");

$countries = $stmt->fetchAll(PDO::FETCH_COLUMN);

$loggedUser = null;

if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT email, first_name, last_name
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $_SESSION['user_id']
    ]);

    $loggedUser = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$oldPassengerData = $_SESSION['passenger_data'] ?? [];
$oldPassengers = $oldPassengerData['passengers'] ?? [];
$oldContact = $oldPassengerData['contact'] ?? [];

if ($loggedUser) {
    if (empty($oldContact['email'])) {
        $oldContact['email'] = $loggedUser['email'] ?? '';
    }

    if (empty($oldPassengers)) {
        $oldPassengers[0] = [
            'passenger_type' => 'adult',
            'title' => '',
            'first_name' => $loggedUser['first_name'] ?? '',
            'middle_name' => '',
            'last_name' => $loggedUser['last_name'] ?? '',
            'birth_date' => '',
            'gender' => ''
        ];
    }
}

$initialPassengers = max(1, (int)($search['passengers'] ?? 1));

if (!empty($oldPassengers)) {
    $initialPassengers = max($initialPassengers, count($oldPassengers));
}

$initialPassengers = min(4, $initialPassengers);

$totalPrice = (float)($_SESSION['booking_total_price'] ?? $departureChoice['price'] ?? 0);

if (($search['trip_type'] ?? '') === 'round-trip' && $returnChoice) {
    $totalPrice = (float)($_SESSION['booking_total_price'] ?? ((float)$departureChoice['price'] + (float)$returnChoice['price']));
}

$basePricePerPassenger = $initialPassengers > 0 ? $totalPrice / $initialPassengers : $totalPrice;

function oldContactValue(array $oldContact, string $key): string
{
    return htmlspecialchars($oldContact[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

function oldChecked(array $oldContact, string $key): string
{
    return !empty($oldContact[$key]) ? 'checked' : '';
}
?>

<link rel="stylesheet" href="style/style_wprowadz_dane.css" />
<link rel="stylesheet" href="style/style_headfoot.css" />

<main class="content">
    <div class="line-container">
        <div class="text-linia">Wprowadź dane pasażerów</div>
        <div class="line-right"></div>
    </div>

    <div class="container">
        <form id="reservationForm" method="post" action="index.php">
            <input type="hidden" name="passenger_data_submit" value="1">
            <input type="hidden" name="passenger_count" id="passengerCountInput" value="<?php echo (int)$initialPassengers; ?>">
            <input type="hidden" name="total_price" id="totalPriceInput" value="<?php echo htmlspecialchars(number_format($totalPrice, 2, '.', '')); ?>">

            <div class="part-container">
                <h2>Dane kontaktowe rezerwacji</h2>

                <div class="form-group contact">
                    <div>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            placeholder="E-mail*"
                            value="<?php echo oldContactValue($oldContact, 'email'); ?>"
                        >
                    </div>

                    <div style="position: relative;">
                        <input
                            type="text"
                            id="countryCode"
                            name="countryCode"
                            required
                            placeholder="Kraj*"
                            autocomplete="off"
                            value="<?php echo oldContactValue($oldContact, 'country'); ?>"
                        >
                        <div id="country-suggestions" class="autocomplete-suggestions"></div>
                    </div>

                    <div>
                        <input
                            type="tel"
                            id="phoneNumber"
                            name="phoneNumber"
                            required
                            placeholder="Numer telefonu*"
                            maxlength="9"
                            pattern="[0-9]{9}"
                            title="Numer telefonu musi mieć dokładnie 9 cyfr"
                            inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"
                            value="<?php echo oldContactValue($oldContact, 'phone'); ?>"
                        >
                    </div>
                </div>

                <p>* pole obowiązkowe</p>

                <div class="checkbox-group">
                    <input
                        type="checkbox"
                        id="newsletter"
                        name="newsletter"
                        value="1"
                        <?php echo oldChecked($oldContact, 'newsletter'); ?>
                    >
                    <label for="newsletter">Zapisz się na NewsLetter</label>
                </div>
            </div>

            <div class="part-container">
                <div id="passengersContainer"></div>

                <div class="checkbox-group">
                    <input
                        type="checkbox"
                        id="confirm"
                        name="confirm"
                        required
                        value="1"
                        <?php echo oldChecked($oldContact, 'confirm'); ?>
                    >
                    <label for="confirm">
                        Oświadczam, że podane przeze mnie dane są zgodne z prawdą i kompletne.
                        Jestem świadomy/a odpowiedzialności za podanie nieprawdziwych informacji.*
                    </label>
                </div>

                <p>*pole obowiązkowe</p>
                <p>*dane muszą zgadzać się z danymi zawartymi w paszporcie</p>

                <br>

                <div class="buttons">
                    <button type="button" id="addPassengerButton">+ Dodaj pasażera</button>
                </div>
            </div>

            <div class="price-container">
                <h2>
                    Cena całkowita:
                    <span class="price-value">
                        <?php echo number_format($totalPrice, 2, ',', ' '); ?> PLN
                    </span>
                </h2>
                <button type="submit">Potwierdź</button>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const countries = <?php echo json_encode($countries, JSON_UNESCAPED_UNICODE); ?>;
    const oldPassengers = <?php echo json_encode($oldPassengers, JSON_UNESCAPED_UNICODE); ?>;

    function normalizeText(text) {
        return text
            .toLowerCase()
            .trim()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    }

    function findBestCountryMatch(value) {
        const normalizedValue = normalizeText(value);

        return countries.find(country => {
            return normalizeText(country) === normalizedValue;
        });
    }

    function setupCountryAutocomplete() {
        const input = document.getElementById("countryCode");
        const suggestionsBox = document.getElementById("country-suggestions");

        input.addEventListener("input", () => {
            const query = normalizeText(input.value);
            suggestionsBox.innerHTML = "";

            if (!query) {
                return;
            }

            const filteredCountries = countries.filter(country =>
                normalizeText(country).includes(query)
            );

            filteredCountries.forEach(country => {
                const suggestionItem = document.createElement("div");
                suggestionItem.textContent = country;

                suggestionItem.addEventListener("mousedown", (event) => {
                    event.preventDefault();
                    input.value = country;
                    suggestionsBox.innerHTML = "";
                });

                suggestionsBox.appendChild(suggestionItem);
            });
        });

        input.addEventListener("blur", () => {
            setTimeout(() => {
                const bestMatch = findBestCountryMatch(input.value);

                if (bestMatch) {
                    input.value = bestMatch;
                } else if (input.value.trim() !== "") {
                    input.value = "";
                }

                suggestionsBox.innerHTML = "";
            }, 150);
        });

        document.addEventListener("click", (event) => {
            if (!input.contains(event.target) && !suggestionsBox.contains(event.target)) {
                suggestionsBox.innerHTML = "";
            }
        });
    }

    setupCountryAutocomplete();

    const totalPriceElement = document.querySelector(".price-value");
    const totalPriceInput = document.getElementById("totalPriceInput");
    const passengerCountInput = document.getElementById("passengerCountInput");

    const initialPassengers = <?php echo (int)$initialPassengers; ?>;
    const basePricePerPassenger = <?php echo json_encode(number_format($basePricePerPassenger, 2, '.', '')); ?>;

    const passengersContainer = document.getElementById("passengersContainer");
    const addPassengerButton = document.getElementById("addPassengerButton");
    const reservationForm = document.getElementById("reservationForm");

    const maxPassengers = 4;
    let passengerCount = 0;

    function escapeHtml(value) {
        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function selected(currentValue, optionValue) {
        return currentValue === optionValue ? "selected" : "";
    }

    function updatePrice() {
        const price = parseFloat(basePricePerPassenger || "0") * passengerCount;

        totalPriceElement.textContent = price.toFixed(2).replace(".", ",") + " PLN";
        totalPriceInput.value = price.toFixed(2);
        passengerCountInput.value = passengerCount;

        return price;
    }

    function addPassenger(passengerData = {}) {
        if (passengerCount >= maxPassengers) {
            return;
        }

        passengerCount++;

        const currentNumber = passengerCount;

        const passengerType = passengerData.passenger_type || "";
        const title = passengerData.title || "";
        const firstName = passengerData.first_name || "";
        const middleName = passengerData.middle_name || "";
        const lastName = passengerData.last_name || "";
        const birthDate = passengerData.birth_date || "";
        const gender = passengerData.gender || "";

        const passengerDiv = document.createElement("div");
        passengerDiv.className = "passenger";
        passengerDiv.id = `passenger${currentNumber}`;

        passengerDiv.innerHTML = `
            <h3>Pasażer ${currentNumber}</h3>

            <div class="passenger-columns">
                <div class="passenger-left">
                    <select id="passengerType${currentNumber}" name="passengerType${currentNumber}" required>
                        <option value="" disabled ${passengerType === "" ? "selected" : ""}>Typ pasażera*</option>
                        <option value="adult" ${selected(passengerType, "adult")}>Dorosły powyżej 16 lat</option>
                        <option value="child" ${selected(passengerType, "child")}>Dziecko poniżej 16 lat</option>
                    </select>
                </div>

                <div class="passenger-right">
                    <div class="form-group">
                        <div>
                            <select id="title${currentNumber}" name="title${currentNumber}" required>
                                <option value="" disabled ${title === "" ? "selected" : ""}>Tytuł*</option>
                                <option value="mr" ${selected(title, "mr")}>Pan</option>
                                <option value="mrs" ${selected(title, "mrs")}>Pani</option>
                                <option value="dr" ${selected(title, "dr")}>Dr</option>
                                <option value="prof" ${selected(title, "prof")}>Prof</option>
                                <option value="mgr" ${selected(title, "mgr")}>Mgr</option>
                                <option value="eng" ${selected(title, "eng")}>Inż</option>
                            </select>
                        </div>

                        <div>
                            <input
                                type="text"
                                id="firstName${currentNumber}"
                                name="firstName${currentNumber}"
                                placeholder="Imię*"
                                required
                                pattern="[A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż -]{2,}"
                                title="Podaj poprawne imię"
                                value="${escapeHtml(firstName)}"
                            >
                        </div>

                        <div>
                            <input
                                type="text"
                                id="middleName${currentNumber}"
                                name="middleName${currentNumber}"
                                placeholder="Drugie imię"
                                pattern="[A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż -]{0,}"
                                title="Podaj poprawne drugie imię"
                                value="${escapeHtml(middleName)}"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <div>
                            <input
                                type="text"
                                id="lastName${currentNumber}"
                                name="lastName${currentNumber}"
                                placeholder="Nazwisko*"
                                required
                                pattern="[A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż -]{2,}"
                                title="Podaj poprawne nazwisko"
                                value="${escapeHtml(lastName)}"
                            >
                        </div>

                        <div>
                            <input
                                type="date"
                                id="birthDate${currentNumber}"
                                name="birthDate${currentNumber}"
                                required
                                value="${escapeHtml(birthDate)}"
                            >
                        </div>

                        <div>
                            <select id="gender${currentNumber}" name="gender${currentNumber}" required>
                                <option value="" disabled ${gender === "" ? "selected" : ""}>Płeć*</option>
                                <option value="male" ${selected(gender, "male")}>Mężczyzna</option>
                                <option value="female" ${selected(gender, "female")}>Kobieta</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `;

        passengersContainer.appendChild(passengerDiv);
        updatePrice();
    }

    function removePassenger() {
        if (passengerCount <= 1) {
            return;
        }

        const lastPassengerDiv = document.getElementById(`passenger${passengerCount}`);

        if (lastPassengerDiv) {
            passengersContainer.removeChild(lastPassengerDiv);
            passengerCount--;
            updatePrice();
        }
    }

    addPassengerButton.addEventListener("click", function () {
        addPassenger();
    });

    const removeButton = document.createElement("button");
    removeButton.type = "button";
    removeButton.textContent = "- Usuń pasażera";
    removeButton.style.marginTop = "10px";
    removeButton.id = "remove-passenger-btn";
    removeButton.addEventListener("click", removePassenger);

    passengersContainer.parentNode.appendChild(removeButton);

    for (let i = 0; i < initialPassengers; i++) {
        addPassenger(oldPassengers[i] || {});
    }

    reservationForm.addEventListener("submit", function (event) {
        const countryMatch = findBestCountryMatch(document.getElementById("countryCode").value);

        if (!countryMatch) {
            event.preventDefault();
            alert("Wybierz poprawny kraj z listy podpowiedzi.");
            document.getElementById("countryCode").focus();
            return;
        }

        document.getElementById("countryCode").value = countryMatch;
        updatePrice();
    });
});
</script>