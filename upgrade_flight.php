<?php
if (!defined('APP_ACCESS')) {
    exit('Brak dostępu');
}

$upgradeData = $_SESSION['upgrade_data'] ?? null;

if (
    empty($upgradeData) ||
    empty($upgradeData['order_code']) ||
    empty($upgradeData['reservation_id'])
) {
    header('Location: upgrade');
    exit;
}

$orderCode = $upgradeData['order_code'];
$reservationId = (int)$upgradeData['reservation_id'];

if (!isset($pdo)) {
    require_once __DIR__ . '/config/db.php';
    $pdo = getDb();
}

$stmt = $pdo->prepare("
    SELECT id, trip_type 
    FROM reservations 
    WHERE id = :id AND reservation_code = :code 
    LIMIT 1
");

$stmt->execute([
    ':id' => $reservationId,
    ':code' => $orderCode
]);

$res = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$res) {
    unset($_SESSION['upgrade_data']);
    header('Location: upgrade');
    exit;
}

if ($res['trip_type'] === 'one-way') {
    $_SESSION['upgrade_data']['flight_direction'] = 'departure';
    header('Location: upgrade_choice');
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        rf.flight_direction, 
        rf.flight_date, 
        f.flight_name, 
        a1.city AS origin, 
        a2.city AS destination
    FROM reservation_flights rf
    JOIN flights f ON rf.flight_id = f.id
    JOIN airports a1 ON f.origin_code = a1.code
    JOIN airports a2 ON f.destination_code = a2.code
    WHERE rf.reservation_id = :rid
    ORDER BY rf.flight_direction ASC
");

$stmt->execute([
    ':rid' => $reservationId
]);

$flights = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <section class="upgrade-section">
        <h1 class="upgrade-title">Wybierz lot</h1>
        <p class="upgrade-subtitle">Rezerwacja: <strong><?= htmlspecialchars($orderCode) ?></strong></p>

        <div class="upgrade-flight-grid">
            <?php foreach ($flights as $fl): ?>
                <div class="upgrade-flight-card">
                    <h3><?= $fl['flight_direction'] === 'departure' ? 'Wylot' : 'Powrót' ?></h3>

                    <div class="flight-details">
                        <p><strong>Trasa:</strong> <?= htmlspecialchars($fl['origin']) ?> ✈ <?= htmlspecialchars($fl['destination']) ?></p>
                        <p><strong>Lot:</strong> <?= htmlspecialchars($fl['flight_name']) ?></p>
                        <p><strong>Data:</strong> <?= htmlspecialchars($fl['flight_date']) ?></p>
                    </div>

                    <form method="POST" action="upgrade_flight">
                        <input type="hidden" name="upgrade_action" value="choose_flight">
                        <input type="hidden" name="flight_direction" value="<?= htmlspecialchars($fl['flight_direction']) ?>">

                        <button type="submit" class="upgrade-btn">
                            Ulepsz ten lot
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="upgrade-back" style="margin-top: 30px;">
            <a href="upgrade" class="upgrade-back-btn">← Wróć</a>
        </div>
    </section>
</main>