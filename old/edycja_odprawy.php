<link rel="stylesheet" href="style/style_wprowadz_dane.css" />
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

if ($reservationId <= 0) {
    echo '<main class="content"><h1>Nieprawidłowa rezerwacja.</h1></main>';
    return;
}

$stmt = $pdo->query("
    SELECT name
    FROM countries
    ORDER BY name ASC
");

$countries = $stmt->fetchAll(PDO::FETCH_COLUMN);

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_check_in_edit'])) {
    $contactEmail = trim($_POST['email'] ?? '');
    $contactCountry = trim($_POST['countryCode'] ?? '');
    $contactPhone = trim($_POST['phoneNumber'] ?? '');

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            UPDATE reservations
            SET
                contact_email = :contact_email,
                contact_country = :contact_country,
                contact_phone = :contact_phone
            WHERE id = :id
        ");

        $stmt->execute([
            ':contact_email' => $contactEmail,
            ':contact_country' => $contactCountry,
            ':contact_phone' => $contactPhone,
            ':id' => $reservationId
        ]);

        foreach ($passengers as $index => $passenger) {
            $passengerIndex = (int)($passenger['passenger_index'] ?? ($index + 1));

            $stmt = $pdo->prepare("
                UPDATE reservation_passengers
                SET
                    passenger_type = :passenger_type,
                    title = :title,
                    first_name = :first_name,
                    middle_name = :middle_name,
                    last_name = :last_name,
                    birth_date = :birth_date,
                    gender = :gender
                WHERE reservation_id = :reservation_id
                  AND passenger_index = :passenger_index
            ");

            $stmt->execute([
                ':passenger_type' => trim($_POST["passengerType{$passengerIndex}"] ?? ''),
                ':title' => trim($_POST["title{$passengerIndex}"] ?? ''),
                ':first_name' => trim($_POST["firstName{$passengerIndex}"] ?? ''),
                ':middle_name' => trim($_POST["middleName{$passengerIndex}"] ?? ''),
                ':last_name' => trim($_POST["lastName{$passengerIndex}"] ?? ''),
                ':birth_date' => trim($_POST["birthDate{$passengerIndex}"] ?? '') ?: null,
                ':gender' => trim($_POST["gender{$passengerIndex}"] ?? ''),
                ':reservation_id' => $reservationId,
                ':passenger_index' => $passengerIndex
            ]);
        }

        $pdo->commit();

        $stmt = $pdo->prepare("
            SELECT
                id,
                reservation_code,
                trip_type,
                contact_email,
                contact_country,
                contact_phone,
                total_price,
                created_at
            FROM reservations
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $reservationId
        ]);

        $updatedReservation = $stmt->fetch(PDO::FETCH_ASSOC);

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

        $updatedPassengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $_SESSION['check_in_data'] = [
            'reservation' => $updatedReservation,
            'passengers' => $updatedPassengers,
            'flights' => $flights
        ];

        $_SESSION['current_view'] = 'weryfikacja';

        echo '<script>window.location.href = "index.php?view=weryfikacja";</script>';
        return;

    } catch (Throwable $e) {
        $pdo->rollBack();
        echo '<main class="content"><h1>Błąd zapisu danych.</h1></main>';
        return;
    }
}
?>

<main class="content">
    <div class="line-container">
        <div class="text-linia">Edytuj dane pasażerów</div>
        <div class="line-right"></div>
    </div>

    <div class="container">
        <form id="reservationForm" method="post" action="index.php?view=edycja_odprawy">
            <input type="hidden" name="save_check_in_edit" value="1">

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
                            value="<?php echo e($reservation['contact_email'] ?? ''); ?>"
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
                            value="<?php echo e($reservation['contact_country'] ?? ''); ?>"
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
                            pattern="[0-9+\s-]{6,20}"
                            title="Podaj poprawny numer telefonu"
                            value="<?php echo e($reservation['contact_phone'] ?? ''); ?>"
                        >
                    </div>
                </div>

                <p>* pole obowiązkowe</p>
            </div>

            <div class="part-container">
                <?php foreach ($passengers as $passenger): ?>
                    <?php
                        $i = (int)($passenger['passenger_index'] ?? 1);
                    ?>

                    <div class="passenger">
                        <h3>Pasażer <?php echo $i; ?></h3>

                        <div class="passenger-columns">
                            <div class="passenger-left">
                                <select name="passengerType<?php echo $i; ?>" required>
                                    <option value="" disabled>Typ pasażera*</option>
                                    <option value="adult" <?php echo (($passenger['passenger_type'] ?? '') === 'adult') ? 'selected' : ''; ?>>
                                        Dorosły powyżej 16 lat
                                    </option>
                                    <option value="child" <?php echo (($passenger['passenger_type'] ?? '') === 'child') ? 'selected' : ''; ?>>
                                        Dziecko poniżej 16 lat
                                    </option>
                                </select>
                            </div>

                            <div class="passenger-right">
                                <div class="form-group">
                                    <div>
                                        <select name="title<?php echo $i; ?>" required>
                                            <option value="" disabled>Tytuł*</option>
                                            <option value="mr" <?php echo (($passenger['title'] ?? '') === 'mr') ? 'selected' : ''; ?>>Pan</option>
                                            <option value="mrs" <?php echo (($passenger['title'] ?? '') === 'mrs') ? 'selected' : ''; ?>>Pani</option>
                                            <option value="dr" <?php echo (($passenger['title'] ?? '') === 'dr') ? 'selected' : ''; ?>>Dr</option>
                                            <option value="prof" <?php echo (($passenger['title'] ?? '') === 'prof') ? 'selected' : ''; ?>>Prof</option>
                                            <option value="mgr" <?php echo (($passenger['title'] ?? '') === 'mgr') ? 'selected' : ''; ?>>Mgr</option>
                                            <option value="eng" <?php echo (($passenger['title'] ?? '') === 'eng') ? 'selected' : ''; ?>>Inż</option>
                                        </select>
                                    </div>

                                    <div>
                                        <input
                                            type="text"
                                            name="firstName<?php echo $i; ?>"
                                            placeholder="Imię*"
                                            required
                                            pattern="[A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż -]{2,}"
                                            value="<?php echo e($passenger['first_name'] ?? ''); ?>"
                                        >
                                    </div>

                                    <div>
                                        <input
                                            type="text"
                                            name="middleName<?php echo $i; ?>"
                                            placeholder="Drugie imię"
                                            pattern="[A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż -]{0,}"
                                            value="<?php echo e($passenger['middle_name'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div>
                                        <input
                                            type="text"
                                            name="lastName<?php echo $i; ?>"
                                            placeholder="Nazwisko*"
                                            required
                                            pattern="[A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż -]{2,}"
                                            value="<?php echo e($passenger['last_name'] ?? ''); ?>"
                                        >
                                    </div>

                                    <div>
                                        <input
                                            type="date"
                                            name="birthDate<?php echo $i; ?>"
                                            required
                                            value="<?php echo e($passenger['birth_date'] ?? ''); ?>"
                                        >
                                    </div>

                                    <div>
                                        <select name="gender<?php echo $i; ?>" required>
                                            <option value="" disabled>Płeć*</option>
                                            <option value="male" <?php echo (($passenger['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>
                                                Mężczyzna
                                            </option>
                                            <option value="female" <?php echo (($passenger['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>
                                                Kobieta
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <p>* dane muszą zgadzać się z danymi zawartymi w dokumencie podróży</p>
            </div>

            <div class="price-container">
                <a href="index.php?view=weryfikacja" class="edytuj">
                    Anuluj
                </a>

                <button type="submit">
                    Zapisz zmiany
                </button>
            </div>

            <div class="part-container">
                <p>
                    Chciałbyś zmienić datę podróży lub nie znajdujesz opcji, która Cię interesuje?
                    Skontaktuj się z działem obsługi klienta.
                </p>

                <a href="index.php?view=kontakt" class="confirm-button">
                    Przejdź do kontaktu
                </a>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const countries = <?php echo json_encode($countries, JSON_UNESCAPED_UNICODE); ?>;

    function normalizeText(text) {
        return text
            .toLowerCase()
            .trim()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    }

    function findBestCountryMatch(value) {
        const normalizedValue = normalizeText(value);

        return countries.find(country => normalizeText(country) === normalizedValue);
    }

    const input = document.getElementById("countryCode");
    const suggestionsBox = document.getElementById("country-suggestions");

    input.addEventListener("input", () => {
        const query = normalizeText(input.value);
        suggestionsBox.innerHTML = "";

        if (!query) {
            return;
        }

        countries
            .filter(country => normalizeText(country).includes(query))
            .forEach(country => {
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
            }

            suggestionsBox.innerHTML = "";
        }, 150);
    });

    document.getElementById("reservationForm").addEventListener("submit", function (event) {
        const countryMatch = findBestCountryMatch(input.value);

        if (!countryMatch) {
            event.preventDefault();
            alert("Wybierz poprawny kraj z listy podpowiedzi.");
            input.focus();
            return;
        }

        input.value = countryMatch;
    });
});
</script>