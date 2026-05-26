<?php
session_start();

require_once __DIR__ . '/config/db.php';
$pdo = getDb();

// =============================================
// UPGRADE REDIRECT - MUSI BYĆ PRZED WSZYSTKIM
// =============================================
if (isset($_GET['view']) && $_GET['view'] === 'upgrade' &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['upgrade_submit'])) {

    $orderCode = strtoupper(trim($_POST['orderCode'] ?? ''));

    if (strlen($orderCode) === 10) {
        $stmt = $pdo->prepare("SELECT id FROM reservations WHERE reservation_code = :code LIMIT 1");
        $stmt->execute([':code' => $orderCode]);

        if ($stmt->fetch()) {
            header('Location: index.php?view=upgrade_choice&orderCode=' . urlencode($orderCode));
            exit;
        }
    }
}

// =============================================
// RESET HOME
// =============================================
if (isset($_GET['view']) && $_GET['view'] === 'home') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    header('Location: index.php');
    exit;
}

// =============================================
// AIRPORTS
// =============================================
$stmt = $pdo->query("
    SELECT display_name
    FROM airports
    WHERE display_name IS NOT NULL
      AND display_name <> ''
    ORDER BY city ASC
");
$airports = $stmt->fetchAll(PDO::FETCH_COLUMN);

$errors = [];

function generateReservationCode(int $length = 10): string
{
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $code;
}

// =============================================
// FLIGHT SEARCH
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flight_search'])) {
    $tripType     = trim($_POST['trip_type'] ?? '');
    $passengers   = trim($_POST['passengers'] ?? '');
    $from         = trim($_POST['from'] ?? '');
    $to           = trim($_POST['to'] ?? '');
    $departureDate = trim($_POST['departure_date'] ?? '');
    $returnDate   = trim($_POST['return_date'] ?? '');

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
            'trip_type'      => $tripType,
            'passengers'     => (int)$passengers,
            'from'           => $from,
            'to'             => $to,
            'departure_date' => $departureDate,
            'return_date'    => $tripType === 'one-way' ? '' : $returnDate
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
    }
}

// =============================================
// DEPARTURE CHOICE
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['departure_choice'])) {
    if (empty($_SESSION['flight_search'])) {
        header('Location: index.php?view=home');
        exit;
    }

    $_SESSION['departure_choice'] = [
        'flight_id'     => $_POST['departure_flight_id'] ?? '',
        'ticket_type'   => $_POST['departure_ticket_type'] ?? '',
        'price'         => $_POST['departure_price'] ?? '0.00',
        'duration'      => $_POST['departure_duration'] ?? '',
        'departure_time'=> $_POST['departure_time'] ?? '',
        'arrival_time'  => $_POST['departure_arrival_time'] ?? '',
        'flight_code'   => $_POST['departure_flight_code'] ?? '',
        'operator_name' => $_POST['departure_operator_name'] ?? ''
    ];
    unset($_SESSION['seat_selection']);

    $tripType = $_SESSION['flight_search']['trip_type'] ?? 'round-trip';
    if ($tripType === 'one-way') {
        $_SESSION['current_view'] = 'passenger_data';
        $_SESSION['booking_total_price'] = $_SESSION['departure_choice']['price'];
        unset($_SESSION['return_choice']);
    } else {
        $_SESSION['current_view'] = 'return';
    }
}

// =============================================
// RETURN CHOICE
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_choice'])) {
    if (empty($_SESSION['flight_search']) || empty($_SESSION['departure_choice'])) {
        header('Location: index.php?view=home');
        exit;
    }

    $_SESSION['return_choice'] = [
        'flight_id'     => $_POST['return_flight_id'] ?? '',
        'ticket_type'   => $_POST['return_ticket_type'] ?? '',
        'price'         => $_POST['return_price'] ?? '0.00',
        'duration'      => $_POST['return_duration'] ?? '',
        'departure_time'=> $_POST['return_departure_time'] ?? '',
        'arrival_time'  => $_POST['return_arrival_time'] ?? '',
        'flight_code'   => $_POST['return_flight_code'] ?? '',
        'operator_name' => $_POST['return_operator_name'] ?? ''
    ];
    unset($_SESSION['seat_selection']);

    $_SESSION['booking_total_price'] = $_POST['total_price'] ?? '0.00';
    $_SESSION['current_view'] = 'passenger_data';
}

// =============================================
// PASSENGER DATA
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['passenger_data_submit'])) {
    if (empty($_SESSION['flight_search']) || empty($_SESSION['departure_choice'])) {
        header('Location: index.php?view=home');
        exit;
    }

    $passengerCount = max(1, min(4, (int)($_POST['passenger_count'] ?? 1)));
    $passengersData = [];

    for ($i = 1; $i <= $passengerCount; $i++) {
        $passengersData[] = [
            'passenger_type' => trim($_POST["passengerType{$i}"] ?? ''),
            'title'          => trim($_POST["title{$i}"] ?? ''),
            'first_name'     => trim($_POST["firstName{$i}"] ?? ''),
            'middle_name'    => trim($_POST["middleName{$i}"] ?? ''),
            'last_name'      => trim($_POST["lastName{$i}"] ?? ''),
            'birth_date'     => trim($_POST["birthDate{$i}"] ?? ''),
            'gender'         => trim($_POST["gender{$i}"] ?? '')
        ];
    }

    $_SESSION['passenger_data'] = [
        'contact' => [
            'email'      => trim($_POST['email'] ?? ''),
            'country'    => trim($_POST['countryCode'] ?? ''),
            'phone'      => trim($_POST['phoneNumber'] ?? ''),
            'newsletter' => isset($_POST['newsletter']),
            'confirm'    => isset($_POST['confirm'])
        ],
        'passenger_count' => $passengerCount,
        'passengers'      => $passengersData
    ];

    $_SESSION['booking_total_price'] = $_POST['total_price'] ?? ($_SESSION['booking_total_price'] ?? '0.00');
    $_SESSION['current_view'] = 'seat_selection';
}

// =============================================
// SEAT SELECTION
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seat_selection_submit'])) {
    if (
        empty($_SESSION['flight_search']) ||
        empty($_SESSION['departure_choice']) ||
        empty($_SESSION['passenger_data'])
    ) {
        header('Location: index.php?view=home');
        exit;
    }

    $seatAssignmentsRaw = trim($_POST['seat_assignments'] ?? '');
    $selectedSeatsRaw   = trim($_POST['selected_seats'] ?? '');
    $baggageCountsRaw   = trim($_POST['baggage_counts'] ?? '');
    $finalPrice         = trim($_POST['final_price'] ?? '0.00');

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
        'selected_seats'   => $selectedSeats,
        'baggage_counts'   => $baggageCounts
    ];
    $_SESSION['booking_total_price'] = $finalPrice;
    $_SESSION['current_view'] = 'summary';
}

// =============================================
// EDIT RESERVATION
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_reservation'])) {
    if (!empty($_SESSION['flight_search'])) {
        $_SESSION['current_view'] = 'departure';
        $_SESSION['edit_mode'] = true;
    }
    header('Location: index.php');
    exit;
}

// =============================================
// CONFIRM RESERVATION
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reservation'])) {
    if (
        empty($_SESSION['flight_search']) ||
        empty($_SESSION['departure_choice']) ||
        empty($_SESSION['passenger_data']) ||
        empty($_SESSION['seat_selection']) ||
        empty($_SESSION['reservation_code'])
    ) {
        header('Location: index.php?view=home');
        exit;
    }

    $pdo->beginTransaction();

    try {
        $search         = $_SESSION['flight_search'];
        $departureChoice = $_SESSION['departure_choice'];
        $returnChoice   = $_SESSION['return_choice'] ?? null;
        $passengerData  = $_SESSION['passenger_data'];
        $seatSelection  = $_SESSION['seat_selection'];
        $contact        = $passengerData['contact'] ?? [];
        $totalPrice     = $_SESSION['booking_total_price'] ?? '0.00';

        $stmt = $pdo->prepare("
            INSERT INTO reservations
                (reservation_code, trip_type, contact_email, contact_country, contact_phone, total_price)
            VALUES
                (:reservation_code, :trip_type, :contact_email, :contact_country, :contact_phone, :total_price)
        ");
        $stmt->execute([
            ':reservation_code' => $_SESSION['reservation_code'],
            ':trip_type'        => $search['trip_type'],
            ':contact_email'    => $contact['email'] ?? '',
            ':contact_country'  => $contact['country'] ?? '',
            ':contact_phone'    => $contact['phone'] ?? '',
            ':total_price'      => $totalPrice
        ]);
        $reservationId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO reservation_flights
                (reservation_id, flight_direction, flight_id, ticket_type, price, flight_date)
            VALUES
                (:reservation_id, :flight_direction, :flight_id, :ticket_type, :price, :flight_date)
        ");
        $stmt->execute([
            ':reservation_id'   => $reservationId,
            ':flight_direction' => 'departure',
            ':flight_id'        => $departureChoice['flight_id'],
            ':ticket_type'      => $departureChoice['ticket_type'],
            ':price'            => $departureChoice['price'],
            ':flight_date'      => $search['departure_date']
        ]);

        if ($search['trip_type'] === 'round-trip' && $returnChoice) {
            $stmt->execute([
                ':reservation_id'   => $reservationId,
                ':flight_direction' => 'return',
                ':flight_id'        => $returnChoice['flight_id'],
                ':ticket_type'      => $returnChoice['ticket_type'],
                ':price'            => $returnChoice['price'],
                ':flight_date'      => $search['return_date']
            ]);
        }

        $seatAssignments = $seatSelection['seat_assignments'] ?? [];
        $baggageCounts   = $seatSelection['baggage_counts'] ?? [];

        $stmt = $pdo->prepare("
            INSERT INTO reservation_passengers
                (reservation_id, passenger_index, passenger_type, title, first_name, middle_name,
                 last_name, birth_date, gender, seat_number, baggage_count)
            VALUES
                (:reservation_id, :passenger_index, :passenger_type, :title, :first_name, :middle_name,
                 :last_name, :birth_date, :gender, :seat_number, :baggage_count)
        ");

        foreach ($passengerData['passengers'] as $index => $passenger) {
            $stmt->execute([
                ':reservation_id'   => $reservationId,
                ':passenger_index'  => $index + 1,
                ':passenger_type'   => $passenger['passenger_type'] ?? '',
                ':title'            => $passenger['title'] ?? '',
                ':first_name'       => $passenger['first_name'] ?? '',
                ':middle_name'      => $passenger['middle_name'] ?? '',
                ':last_name'        => $passenger['last_name'] ?? '',
                ':birth_date'       => !empty($passenger['birth_date']) ? $passenger['birth_date'] : null,
                ':gender'           => $passenger['gender'] ?? '',
                ':seat_number'      => $seatAssignments[(string)$index] ?? $seatAssignments[$index] ?? null,
                ':baggage_count'    => $baggageCounts[$index] ?? 0
            ]);
        }

        $pdo->commit();
        $_SESSION['saved_reservation_id'] = $reservationId;
        $_SESSION['current_view'] = 'thank_you';
        unset($_SESSION['edit_mode']);

    } catch (Throwable $e) {
        $pdo->rollBack();
        require 'includes/header.php';
        echo '<main class="content"><h1>Błąd zapisu rezerwacji.</h1><p>Spróbuj ponownie później.</p></main>';
        require 'includes/footer.php';
        exit;
    }
}

// =============================================
// USTAL WIDOK I CSS
// =============================================
if (isset($_GET['view'])) {
    $_SESSION['current_view'] = $_GET['view'];
}

$currentView = $_SESSION['current_view'] ?? 'home';
$pageTitle   = 'WronAir';
$pageStyle   = 'style/style.css';

if ($currentView === 'uslugi') {
    $pageTitle = 'WronAir | Usługi';
    $pageStyle = 'style/style_uslugi.css';
} elseif ($currentView === 'internet') {
    $pageTitle = 'WronAir | Internet';
    $pageStyle = 'style/style_internet.css';
} elseif ($currentView === 'kontakt') {
    $pageTitle = 'WronAir | Kontakt';
    $pageStyle = 'style/style_kontakt.css';
} elseif ($currentView === 'rozrywka') {
    $pageTitle = 'WronAir | Rozrywka';
    $pageStyle = 'style/oferty_style.css';
} elseif ($currentView === 'o_nas') {
    $pageTitle = 'WronAir | O Nas';
    $pageStyle = 'style/style_o_nas.css';
} elseif ($currentView === 'posilki') {
    $pageTitle = 'WronAir | Posiłki';
    $pageStyle = 'style/oferty_style.css';
} elseif ($currentView === 'duty_free') {
    $pageTitle = 'WronAir | Duty-Free';
    $pageStyle = 'style/oferty_style.css';
} elseif ($currentView === 'faq') {
    $pageTitle = 'WronAir | FAQ';
    $pageStyle = 'style/style_faq.css';
} elseif ($currentView === 'upgrade') {
    $pageTitle = 'WronAir | Upgrade';
    $pageStyle = 'style/style_upgrade.css';
} elseif ($currentView === 'upgrade_choice') {
    $pageTitle = 'WronAir | Wybór upgrade';
    $pageStyle = 'style/style_upgrade_choice.css';
} elseif ($currentView === 'upgrade_choice_luggage') {
    $pageTitle = 'WronAir | Upgrade - Bagaż';
    $pageStyle = 'style/style_upgrade_choice.css';
} elseif ($currentView === 'upgrade_choice_seat') {
    $pageTitle  = 'WronAir | Upgrade - Siedzenie';
    $pageStyle  = 'style/style_upgrade_choice.css';
    $pageStyle2 = 'style/style_wybor_miejsca.css';
} elseif ($currentView === 'upgrade_end') {
    $pageTitle = 'WronAir | Upgrade - Podsumowanie';
    $pageStyle = 'style/style_upgrade_end.css';
} elseif ($currentView === 'flota') {
    $pageTitle = 'WronAir | Flota';
    $pageStyle = 'style/style_flota.css';
} elseif ($currentView === 'odprawa') {
    $pageTitle = 'WronAir | Odprawa';
    $pageStyle = 'style/style_odprawa.css';
} elseif ($currentView === 'weryfikacja') {
    $pageTitle = 'WronAir | Weryfikacja odprawy';
    $pageStyle = 'style/style_potwierdz_dane.css';
} elseif ($currentView === 'edycja_odprawy') {
    $pageTitle = 'WronAir | Edycja danych';
    $pageStyle = 'style/style_wprowadz_dane.css';
} elseif ($currentView === 'wybor_miejsca_odprawa') {
    $pageTitle = 'WronAir | Wybór miejsca';
    $pageStyle = 'style/style_wybor_miejsca.css';
} elseif ($currentView === 'boarding_pass') {
    $pageTitle = 'WronAir | Boarding Pass';
    $pageStyle = 'style/style_boarding_pass.css';
}

// =============================================
// OUTPUT
// =============================================
require 'includes/header.php';

$currentView = $_SESSION['current_view'] ?? 'home';

if (!empty($errors)) {
    require 'home.php';
} elseif ($currentView === 'home') {
    require 'home.php';
} elseif ($currentView === 'uslugi') {
    require 'uslugi.php';
} elseif ($currentView === 'internet') {
    require 'internet.php';
} elseif ($currentView === 'kontakt') {
    require 'kontakt.php';
} elseif ($currentView === 'rozrywka') {
    require 'rozrywka.php';
} elseif ($currentView === 'o_nas') {
    require 'o_nas.php';
} elseif ($currentView === 'posilki') {
    require 'posilki.php';
} elseif ($currentView === 'duty_free') {
    require 'duty_free.php';
} elseif ($currentView === 'faq') {
    require 'faq.php';
} elseif ($currentView === 'flota') {
    require 'flota.php';
} elseif ($currentView === 'upgrade') {
    require 'upgrade.php';
} elseif ($currentView === 'upgrade_choice') {
    require 'upgrade_choice.php';
} elseif ($currentView === 'upgrade_choice_luggage') {
    require 'upgrade_choice_luggage.php';
} elseif ($currentView === 'upgrade_choice_seat') {
    require 'upgrade_choice_seat.php';
} elseif ($currentView === 'upgrade_end') {
    require 'upgrade_end.php';
} elseif ($currentView === 'odprawa') {
    require 'odprawa.php';
} elseif ($currentView === 'weryfikacja') {
    require 'weryfikacja.php';
} elseif ($currentView === 'edycja_odprawy') {
    require 'edycja_odprawy.php';
} elseif ($currentView === 'wybor_miejsca_odprawa') {
    require 'wybor_miejsca_odprawa.php';
} elseif ($currentView === 'boarding_pass') {
    require 'boarding_pass.php';
} elseif (!empty($_SESSION['flight_search'])) {
    if ($currentView === 'departure') {
        require 'departure.php';
    } elseif ($currentView === 'return') {
        require 'return.php';
    } elseif ($currentView === 'passenger_data') {
        require 'wprowadz_dane.php';
    } elseif ($currentView === 'seat_selection') {
        require 'wybor_miejsca.php';
    } elseif ($currentView === 'summary') {
        require 'podsumowanie.php';
    } elseif ($currentView === 'thank_you') {
        require 'podziekowanie.php';
    }
} else {
    require 'home.php';
}

require 'includes/footer.php';