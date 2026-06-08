<?php
ob_start();
session_start();
define('APP_ACCESS', true);

require_once __DIR__ . '/config/db.php';
$pdo = getDb();

$errors = [];
$authErrors = [];
$authSuccess = '';
$pageStyle2 = null;

// ------------------------------
// Funkcje pomocnicze
// ------------------------------
function redirectTo(string $view): void
{
    header('Location: ' . $view);
    exit;
}

function postActionIs(string $action): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['action'])
        && $_POST['action'] === $action;
}

function generateReservationCode(int $length = 10): string
{
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $code;
}

function parsePrice(mixed $value): float
{
    return (float)str_replace([' ', ','], ['', '.'], (string)$value);
}

function safeDateOrNull(string $date): ?string
{
    return $date !== '' ? $date : null;
}

function loadReservationData(PDO $pdo, int $reservationId): array
{
    $stmt = $pdo->prepare("SELECT * FROM reservation_flights WHERE reservation_id = :rid ORDER BY flight_direction ASC");
    $stmt->execute([':rid' => $reservationId]);
    $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM reservation_passengers WHERE reservation_id = :rid ORDER BY passenger_index ASC");
    $stmt->execute([':rid' => $reservationId]);
    $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'flights' => $flights,
        'passengers' => $passengers
    ];
}

// ------------------------------
// Wylogowanie
// ------------------------------
if (
    (isset($_GET['action']) && $_GET['action'] === 'logout') ||
    (isset($_GET['view']) && $_GET['view'] === 'logout')
) {
    unset($_SESSION['user_id'], $_SESSION['user_name']);
    redirectTo('home');
}

// ------------------------------
// Anulowanie rezerwacji
// ------------------------------
if (postActionIs('cancel_reservation')) {
    if (isset($_SESSION['user_id']) && !empty($_POST['cancel_id'])) {
        $reservationId = (int)$_POST['cancel_id'];
        $userId = (int)$_SESSION['user_id'];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id FROM reservations WHERE id = :id AND user_id = :user_id LIMIT 1");
            $stmt->execute([
                ':id' => $reservationId,
                ':user_id' => $userId
            ]);

            if ($stmt->fetch()) {
                // Usuwamy powiązane dane przed rezerwacją. Przy ON DELETE CASCADE też nie szkodzi.
                $stmt = $pdo->prepare("DELETE FROM reservation_passengers WHERE reservation_id = :id");
                $stmt->execute([':id' => $reservationId]);

                $stmt = $pdo->prepare("DELETE FROM reservation_flights WHERE reservation_id = :id");
                $stmt->execute([':id' => $reservationId]);

                $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = :id AND user_id = :user_id");
                $stmt->execute([
                    ':id' => $reservationId,
                    ':user_id' => $userId
                ]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
        }
    }

    redirectTo('konto');
}

// ------------------------------
// Odprawa po kodzie rezerwacji
// ------------------------------
if (postActionIs('auto_checkin')) {
    $code = strtoupper(trim($_POST['reservation_code'] ?? ''));

    if ($code !== '') {
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_code = :code LIMIT 1");
        $stmt->execute([':code' => $code]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reservation) {
            $reservationData = loadReservationData($pdo, (int)$reservation['id']);

            $_SESSION['check_in_data'] = [
                'reservation' => $reservation,
                'flights' => $reservationData['flights'],
                'passengers' => $reservationData['passengers']
            ];

            redirectTo('weryfikacja');
        }
    }

    redirectTo('konto');
}

// ------------------------------
// Start procesu upgrade po kodzie rezerwacji
// ------------------------------
if (
    isset($_GET['view']) &&
    $_GET['view'] === 'upgrade' &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['upgrade_submit'])
) {
    $orderCode = strtoupper(trim($_POST['orderCode'] ?? ''));

    if (strlen($orderCode) === 10) {
        $stmt = $pdo->prepare("SELECT id, reservation_code FROM reservations WHERE reservation_code = :code LIMIT 1");
        $stmt->execute([':code' => $orderCode]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reservation) {
            $_SESSION['upgrade_data'] = [
                'reservation_id' => (int)$reservation['id'],
                'order_code' => $reservation['reservation_code'],
                'flight_direction' => 'departure'
            ];

            redirectTo('upgrade_flight');
        }
    }
}

// ------------------------------
// Reset danych formularza po wejściu na stronę główną
// ------------------------------
if (isset($_GET['view']) && $_GET['view'] === 'home') {
    unset(
        $_SESSION['flight_search'],
        $_SESSION['current_view'],
        $_SESSION['departure_choice'],
        $_SESSION['return_choice'],
        $_SESSION['passenger_data'],
        $_SESSION['seat_selection'],
        $_SESSION['booking_total_price'],
        $_SESSION['saved_reservation_id'],
        $_SESSION['edit_mode'],
        $_SESSION['reservation_code']
    );

    $_SESSION['current_view'] = 'home';
}

// ------------------------------
// Dane lotnisk do walidacji wyszukiwarki
// ------------------------------
$stmt = $pdo->query("
    SELECT display_name
    FROM airports
    WHERE display_name IS NOT NULL
      AND display_name <> ''
    ORDER BY city ASC
");
$airports = $stmt->fetchAll(PDO::FETCH_COLUMN);

// ------------------------------
// Wyszukiwanie lotu
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flight_search'])) {
    $tripType = trim($_POST['trip_type'] ?? '');
    $passengers = trim($_POST['passengers'] ?? '');
    $from = trim($_POST['from'] ?? '');
    $to = trim($_POST['to'] ?? '');
    $departureDate = trim($_POST['departure_date'] ?? '');
    $returnDate = trim($_POST['return_date'] ?? '');

    if ($tripType !== 'round-trip' && $tripType !== 'one-way') {
        $errors[] = 'Nieprawidłowy typ podróży.';
    }

    if ($passengers === '' || !ctype_digit($passengers) || (int)$passengers < 1 || (int)$passengers > 4) {
        $errors[] = 'Podaj poprawną liczbę pasażerów.';
    }

    if ($from === '') {
        $errors[] = 'Pole „Skąd" jest wymagane.';
    } elseif (!in_array($from, $airports, true)) {
        $errors[] = 'Pole „Skąd" musi zawierać lotnisko z listy.';
    }

    if ($to === '') {
        $errors[] = 'Pole „Dokąd" jest wymagane.';
    } elseif (!in_array($to, $airports, true)) {
        $errors[] = 'Pole „Dokąd" musi zawierać lotnisko z listy.';
    }

    if ($from !== '' && $to !== '' && $from === $to) {
        $errors[] = 'Miejsce wylotu i przylotu nie mogą być takie same.';
    }

    if ($departureDate === '') {
        $errors[] = 'Data wylotu jest wymagana.';
    }

    if ($tripType === 'round-trip' && $returnDate === '') {
        $errors[] = 'Data powrotu jest wymagana dla lotu w obie strony.';
    }

    if ($tripType === 'round-trip' && $departureDate !== '' && $returnDate !== '') {
        if (strtotime($returnDate) < strtotime($departureDate)) {
            $errors[] = 'Data powrotu nie może być wcześniejsza niż data wylotu.';
        }
    }

    if (empty($errors)) {
        $_SESSION['reservation_code'] = generateReservationCode();
        $_SESSION['flight_search'] = [
            'trip_type' => $tripType,
            'passengers' => (int)$passengers,
            'from' => $from,
            'to' => $to,
            'departure_date' => $departureDate,
            'return_date' => $tripType === 'one-way' ? '' : $returnDate
        ];
        $_SESSION['current_view'] = 'departure';

        unset(
            $_SESSION['departure_choice'],
            $_SESSION['return_choice'],
            $_SESSION['passenger_data'],
            $_SESSION['seat_selection'],
            $_SESSION['booking_total_price'],
            $_SESSION['saved_reservation_id'],
            $_SESSION['edit_mode']
        );

        redirectTo('departure');
    }
}

// ------------------------------
// Wybór lotu wylotowego
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['departure_choice'])) {
    if (empty($_SESSION['flight_search'])) {
        redirectTo('home');
    }

    $_SESSION['departure_choice'] = [
        'flight_id' => (int)($_POST['departure_flight_id'] ?? 0),
        'ticket_type' => trim($_POST['departure_ticket_type'] ?? ''),
        'price' => trim($_POST['departure_price'] ?? '0.00'),
        'duration' => trim($_POST['departure_duration'] ?? ''),
        'departure_time' => trim($_POST['departure_time'] ?? ''),
        'arrival_time' => trim($_POST['departure_arrival_time'] ?? ''),
        'flight_code' => trim($_POST['departure_flight_code'] ?? ''),
        'operator_name' => trim($_POST['departure_operator_name'] ?? '')
    ];

    unset($_SESSION['seat_selection']);

    $tripType = $_SESSION['flight_search']['trip_type'] ?? 'round-trip';

    if ($tripType === 'one-way') {
        $_SESSION['current_view'] = 'passenger_data';
        $_SESSION['booking_total_price'] = $_SESSION['departure_choice']['price'];
        unset($_SESSION['return_choice']);

        redirectTo('passenger_data');
    }

    $_SESSION['current_view'] = 'return';
    redirectTo('return');
}

// ------------------------------
// Wybór lotu powrotnego
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_choice'])) {
    if (empty($_SESSION['flight_search']) || empty($_SESSION['departure_choice'])) {
        redirectTo('home');
    }

    $_SESSION['return_choice'] = [
        'flight_id' => (int)($_POST['return_flight_id'] ?? 0),
        'ticket_type' => trim($_POST['return_ticket_type'] ?? ''),
        'price' => trim($_POST['return_price'] ?? '0.00'),
        'duration' => trim($_POST['return_duration'] ?? ''),
        'departure_time' => trim($_POST['return_departure_time'] ?? ''),
        'arrival_time' => trim($_POST['return_arrival_time'] ?? ''),
        'flight_code' => trim($_POST['return_flight_code'] ?? ''),
        'operator_name' => trim($_POST['return_operator_name'] ?? '')
    ];

    unset($_SESSION['seat_selection']);

    $_SESSION['booking_total_price'] = trim($_POST['total_price'] ?? '0.00');
    $_SESSION['current_view'] = 'passenger_data';

    redirectTo('passenger_data');
}

// ------------------------------
// Dane pasażerów
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['passenger_data_submit'])) {
    if (empty($_SESSION['flight_search']) || empty($_SESSION['departure_choice'])) {
        redirectTo('home');
    }

    $passengerCount = max(1, min(4, (int)($_POST['passenger_count'] ?? 1)));
    $passengersData = [];

    for ($i = 1; $i <= $passengerCount; $i++) {
        $passengersData[] = [
            'passenger_type' => trim($_POST["passengerType{$i}"] ?? ''),
            'title' => trim($_POST["title{$i}"] ?? ''),
            'first_name' => trim($_POST["firstName{$i}"] ?? ''),
            'middle_name' => trim($_POST["middleName{$i}"] ?? ''),
            'last_name' => trim($_POST["lastName{$i}"] ?? ''),
            'birth_date' => trim($_POST["birthDate{$i}"] ?? ''),
            'gender' => trim($_POST["gender{$i}"] ?? '')
        ];
    }

    $_SESSION['passenger_data'] = [
        'contact' => [
            'email' => trim($_POST['email'] ?? ''),
            'country' => trim($_POST['countryCode'] ?? ''),
            'phone' => trim($_POST['phoneNumber'] ?? ''),
            'newsletter' => isset($_POST['newsletter']),
            'confirm' => isset($_POST['confirm'])
        ],
        'passenger_count' => $passengerCount,
        'passengers' => $passengersData
    ];

    $_SESSION['booking_total_price'] = trim($_POST['total_price'] ?? ($_SESSION['booking_total_price'] ?? '0.00'));
    $_SESSION['current_view'] = 'seat_selection';

    redirectTo('seat_selection');
}

// ------------------------------
// Wybór miejsc i bagażu
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seat_selection_submit'])) {
    if (
        empty($_SESSION['flight_search']) ||
        empty($_SESSION['departure_choice']) ||
        empty($_SESSION['passenger_data'])
    ) {
        redirectTo('home');
    }

    $seatAssignmentsRaw = trim($_POST['seat_assignments'] ?? '');
    $selectedSeatsRaw = trim($_POST['selected_seats'] ?? '');
    $baggageCountsRaw = trim($_POST['baggage_counts'] ?? '');
    $finalPrice = trim($_POST['final_price'] ?? '0.00');

    $seatAssignments = [];
    if ($seatAssignmentsRaw !== '') {
        $decodedAssignments = json_decode($seatAssignmentsRaw, true);
        if (is_array($decodedAssignments)) {
            $seatAssignments = $decodedAssignments;
        }
    }

    $selectedSeats = [];
    if ($selectedSeatsRaw !== '') {
        $selectedSeats = array_filter(array_map('trim', explode(',', $selectedSeatsRaw)));
    }

    $baggageCounts = [];
    if ($baggageCountsRaw !== '') {
        $baggageCounts = array_map('intval', explode('-', $baggageCountsRaw));
    }

    $_SESSION['seat_selection'] = [
        'seat_assignments' => $seatAssignments,
        'selected_seats' => $selectedSeats,
        'baggage_counts' => $baggageCounts
    ];
    $_SESSION['booking_total_price'] = $finalPrice;
    $_SESSION['current_view'] = 'summary';

    redirectTo('summary');
}

// ------------------------------
// Powrót do edycji rezerwacji
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_reservation'])) {
    if (!empty($_SESSION['flight_search'])) {
        $_SESSION['current_view'] = 'departure';
        $_SESSION['edit_mode'] = true;
    }

    redirectTo('departure');
}

// ------------------------------
// Zapis rezerwacji
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reservation'])) {
    if (
        empty($_SESSION['flight_search']) ||
        empty($_SESSION['departure_choice']) ||
        empty($_SESSION['passenger_data']) ||
        empty($_SESSION['seat_selection']) ||
        empty($_SESSION['reservation_code'])
    ) {
        redirectTo('home');
    }

    $pdo->beginTransaction();

    try {
        $search = $_SESSION['flight_search'];
        $departureChoice = $_SESSION['departure_choice'];
        $returnChoice = $_SESSION['return_choice'] ?? null;
        $passengerData = $_SESSION['passenger_data'];
        $seatSelection = $_SESSION['seat_selection'];
        $contact = $passengerData['contact'] ?? [];

        $totalPrice = parsePrice($_SESSION['booking_total_price'] ?? 0);
        $depPrice = parsePrice($departureChoice['price'] ?? 0);
        $depFlightId = (int)($departureChoice['flight_id'] ?? 0);

        $stmt = $pdo->prepare("
            INSERT INTO reservations
                (user_id, reservation_code, trip_type, contact_email, contact_country, contact_phone, total_price)
            VALUES
                (:user_id, :reservation_code, :trip_type, :contact_email, :contact_country, :contact_phone, :total_price)
        ");
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'] ?? null,
            ':reservation_code' => $_SESSION['reservation_code'],
            ':trip_type' => $search['trip_type'],
            ':contact_email' => $contact['email'] ?? '',
            ':contact_country' => $contact['country'] ?? '',
            ':contact_phone' => $contact['phone'] ?? '',
            ':total_price' => $totalPrice
        ]);
        $reservationId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO reservation_flights
                (reservation_id, flight_direction, flight_id, ticket_type, price, flight_date)
            VALUES
                (:reservation_id, :flight_direction, :flight_id, :ticket_type, :price, :flight_date)
        ");
        $stmt->execute([
            ':reservation_id' => $reservationId,
            ':flight_direction' => 'departure',
            ':flight_id' => $depFlightId,
            ':ticket_type' => $departureChoice['ticket_type'] ?? '',
            ':price' => $depPrice,
            ':flight_date' => $search['departure_date']
        ]);

        if ($search['trip_type'] === 'round-trip' && $returnChoice) {
            $retPrice = parsePrice($returnChoice['price'] ?? 0);
            $retFlightId = (int)($returnChoice['flight_id'] ?? 0);

            $stmt->execute([
                ':reservation_id' => $reservationId,
                ':flight_direction' => 'return',
                ':flight_id' => $retFlightId,
                ':ticket_type' => $returnChoice['ticket_type'] ?? '',
                ':price' => $retPrice,
                ':flight_date' => $search['return_date']
            ]);
        }

        $seatAssignments = $seatSelection['seat_assignments'] ?? [];
        $baggageCounts = $seatSelection['baggage_counts'] ?? [];

        $stmt = $pdo->prepare("
            INSERT INTO reservation_passengers
                (reservation_id, passenger_index, passenger_type, title, first_name, middle_name,
                 last_name, birth_date, gender, seat_number, baggage_count)
            VALUES
                (:reservation_id, :passenger_index, :passenger_type, :title, :first_name, :middle_name,
                 :last_name, :birth_date, :gender, :seat_number, :baggage_count)
        ");

        foreach ($passengerData['passengers'] as $index => $passenger) {
            $rawSeat = $seatAssignments[(string)$index] ?? $seatAssignments[$index] ?? null;
            $seatNo = !empty($rawSeat) ? $rawSeat : null;

            $stmt->execute([
                ':reservation_id' => $reservationId,
                ':passenger_index' => $index + 1,
                ':passenger_type' => $passenger['passenger_type'] ?? '',
                ':title' => $passenger['title'] ?? '',
                ':first_name' => $passenger['first_name'] ?? '',
                ':middle_name' => $passenger['middle_name'] ?? '',
                ':last_name' => $passenger['last_name'] ?? '',
                ':birth_date' => safeDateOrNull($passenger['birth_date'] ?? ''),
                ':gender' => $passenger['gender'] ?? '',
                ':seat_number' => $seatNo,
                ':baggage_count' => (int)($baggageCounts[$index] ?? 0)
            ]);
        }

        $pdo->commit();
        $_SESSION['saved_reservation_id'] = $reservationId;
        $_SESSION['current_view'] = 'thank_you';
        unset($_SESSION['edit_mode']);

        redirectTo('thank_you');
    } catch (Throwable $e) {
        $pdo->rollBack();
        require 'includes/header.php';
        echo '<main class="content"><h1 style="color:#C50914;">Wystąpił błąd podczas zapisu rezerwacji.</h1></main>';
        require 'includes/footer.php';
        exit;
    }
}

// ------------------------------
// Rejestracja i logowanie
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'register') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            $authErrors[] = 'Wypełnij wszystkie pola.';
        } elseif ($password !== $passwordConfirm) {
            $authErrors[] = 'Hasła nie są identyczne.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $authErrors[] = 'Niepoprawny adres e-mail.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);

            if ($stmt->fetch()) {
                $authErrors[] = 'Konto z tym adresem e-mail już istnieje.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (email, password, first_name, last_name)
                    VALUES (:email, :password, :first_name, :last_name)
                ");

                if ($stmt->execute([
                    ':email' => $email,
                    ':password' => $hashedPassword,
                    ':first_name' => $firstName,
                    ':last_name' => $lastName
                ])) {
                    $authSuccess = 'Rejestracja zakończona sukcesem. Możesz się zalogować.';
                } else {
                    $authErrors[] = 'Błąd podczas rejestracji.';
                }
            }
        }
    } elseif ($_POST['action'] === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $authErrors[] = 'Podaj e-mail i hasło.';
        } else {
            $stmt = $pdo->prepare("SELECT id, password, first_name FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_name'] = $user['first_name'];
                redirectTo('home');
            }

            $authErrors[] = 'Błędny e-mail lub hasło.';
        }
    }
}

// ------------------------------
// Aktualny widok
// ------------------------------
if (isset($_GET['view'])) {
    $_SESSION['current_view'] = $_GET['view'];
}

// ------------------------------
// Proces upgrade
// ------------------------------
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['upgrade_action']) &&
    !empty($_SESSION['upgrade_data'])
) {
    $upgradeAction = $_POST['upgrade_action'];

    if ($upgradeAction === 'choose_flight') {
        $flightDirection = $_POST['flight_direction'] ?? 'departure';

        if ($flightDirection !== 'departure' && $flightDirection !== 'return') {
            $flightDirection = 'departure';
        }

        $_SESSION['upgrade_data']['flight_direction'] = $flightDirection;
        unset($_SESSION['upgrade_data']['passenger_id']);

        redirectTo('upgrade_choice');
    }

    if ($upgradeAction === 'choose_luggage' || $upgradeAction === 'choose_seat') {
        $passengerId = (int)($_POST['passenger_id'] ?? 0);

        if ($passengerId <= 0) {
            redirectTo('upgrade_choice');
        }

        $_SESSION['upgrade_data']['passenger_id'] = $passengerId;

        if ($upgradeAction === 'choose_luggage') {
            redirectTo('upgrade_choice_luggage');
        }

        redirectTo('upgrade_choice_seat');
    }

    if ($upgradeAction === 'confirm_luggage') {
        $_SESSION['upgrade_data']['upgrade_choice'] = 'luggage';
        $_SESSION['upgrade_data']['luggage_type'] = trim($_POST['luggage_type'] ?? '');
        $_SESSION['upgrade_data']['luggage_count'] = (float)($_POST['luggage_count'] ?? 0);
        $_SESSION['upgrade_data']['luggage_price_diff'] = (float)($_POST['luggage_price_diff'] ?? 0);

        redirectTo('upgrade_end');
    }

    if ($upgradeAction === 'confirm_seat') {
        $_SESSION['upgrade_data']['upgrade_choice'] = 'seat';
        $_SESSION['upgrade_data']['new_class'] = trim($_POST['new_class'] ?? '');
        $_SESSION['upgrade_data']['new_seat'] = trim($_POST['new_seat'] ?? '');
        $_SESSION['upgrade_data']['seat_price'] = (float)($_POST['seat_price'] ?? 0);

        redirectTo('upgrade_end');
    }

    redirectTo('upgrade_flight');
}

// ------------------------------
// Konfiguracja tytułów, styli i plików widoków
// ------------------------------
$viewConfig = [
    'home' => ['title' => 'WronAir', 'style' => 'style/style.css', 'file' => 'home.php'],
    'uslugi' => ['title' => 'WronAir | Usługi', 'style' => 'style/style_uslugi.css', 'file' => 'uslugi.php'],
    'internet' => ['title' => 'WronAir | Internet', 'style' => 'style/style_internet.css', 'file' => 'internet.php'],
    'kontakt' => ['title' => 'WronAir | Kontakt', 'style' => 'style/style_kontakt.css', 'file' => 'kontakt.php'],
    'logowanie' => ['title' => 'WronAir | Logowanie i Rejestracja', 'style' => 'style/style_logowanie.css', 'file' => 'logowanie.php'],
    'rozrywka' => ['title' => 'WronAir | Rozrywka', 'style' => 'style/oferty_style.css', 'file' => 'rozrywka.php'],
    'o_nas' => ['title' => 'WronAir | O Nas', 'style' => 'style/style_o_nas.css', 'file' => 'o_nas.php'],
    'upgrade_flight' => ['title' => 'WronAir | Wybierz lot', 'style' => 'style/style_upgrade_choice.css', 'style2' => 'style/style_upgrade_flight.css', 'file' => 'upgrade_flight.php'],
    'posilki' => ['title' => 'WronAir | Posiłki', 'style' => 'style/oferty_style.css', 'file' => 'posilki.php'],
    'duty_free' => ['title' => 'WronAir | Duty-Free', 'style' => 'style/oferty_style.css', 'file' => 'duty_free.php'],
    'faq' => ['title' => 'WronAir | FAQ', 'style' => 'style/style_faq.css', 'file' => 'faq.php'],
    'upgrade' => ['title' => 'WronAir | Upgrade', 'style' => 'style/style_upgrade.css', 'file' => 'upgrade.php'],
    'upgrade_choice' => ['title' => 'WronAir | Wybór upgrade', 'style' => 'style/style_upgrade_choice.css', 'file' => 'upgrade_choice.php'],
    'upgrade_choice_luggage' => ['title' => 'WronAir | Upgrade - Bagaż', 'style' => 'style/style_upgrade_choice.css', 'file' => 'upgrade_choice_luggage.php'],
    'upgrade_choice_seat' => ['title' => 'WronAir | Upgrade - Siedzenie', 'style' => 'style/style_upgrade_choice.css', 'style2' => 'style/style_wybor_miejsca.css', 'file' => 'upgrade_choice_seat.php'],
    'upgrade_end' => ['title' => 'WronAir | Upgrade - Podsumowanie', 'style' => 'style/style_upgrade_end.css', 'file' => 'upgrade_end.php'],
    'flota' => ['title' => 'WronAir | Flota', 'style' => 'style/style_flota.css', 'file' => 'flota.php'],
    'odprawa' => ['title' => 'WronAir | Odprawa', 'style' => 'style/style_odprawa.css', 'file' => 'odprawa.php'],
    'weryfikacja' => ['title' => 'WronAir | Weryfikacja odprawy', 'style' => 'style/style_potwierdz_dane.css', 'file' => 'weryfikacja.php'],
    'edycja_odprawy' => ['title' => 'WronAir | Edycja danych', 'style' => 'style/style_wprowadz_dane.css', 'file' => 'edycja_odprawy.php'],
    'wybor_miejsca_odprawa' => ['title' => 'WronAir | Wybór miejsca', 'style' => 'style/style_wybor_miejsca.css', 'file' => 'wybor_miejsca_odprawa.php'],
    'boarding_pass' => ['title' => 'WronAir | Boarding Pass', 'style' => 'style/style_boarding_pass.css', 'file' => 'boarding_pass.php'],
    'konto' => ['title' => 'WronAir | Moje Konto', 'style' => 'style/style_konto.css', 'file' => 'konto.php'],
    'departure' => ['title' => 'WronAir | Wybór lotu', 'style' => 'style/style_departure.css', 'file' => 'departure.php', 'requires_search' => true],
    'return' => [ 'title' => 'WronAir | Lot powrotny', 'style' => 'style/style_departure.css', 'file' => 'return.php', 'requires_search' => true],
    'passenger_data' => ['title' => 'WronAir | Dane pasażerów', 'style' => 'style/style_wprowadz_dane.css', 'file' => 'wprowadz_dane.php', 'requires_search' => true],
    'seat_selection' => ['title' => 'WronAir | Wybór miejsca', 'style' => 'style/style_wybor_miejsca.css', 'file' => 'wybor_miejsca.php', 'requires_search' => true],
    'summary' => ['title' => 'WronAir | Podsumowanie', 'style' => 'style/style_podsumowanie.css', 'file' => 'podsumowanie.php', 'requires_search' => true],
    'thank_you' => ['title' => 'WronAir | Dziękujemy', 'style' => 'style/style_podziekowanie.css', 'file' => 'podziekowanie.php', 'requires_search' => true]
];

$currentView = $_SESSION['current_view'] ?? 'home';
$config = $viewConfig[$currentView] ?? $viewConfig['home'];

$pageTitle = $config['title'];
$pageStyle = $config['style'];
$pageStyle2 = $config['style2'] ?? null;

require 'includes/header.php';

$currentView = $_SESSION['current_view'] ?? 'home';
$config = $viewConfig[$currentView] ?? $viewConfig['home'];

if (!empty($errors)) {
    require 'home.php';
} elseif (!empty($config['requires_search']) && empty($_SESSION['flight_search'])) {
    require 'home.php';
} else {
    require $config['file'];
}

require 'includes/footer.php';
