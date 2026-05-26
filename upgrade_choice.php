<?php
if (!defined('APP_ACCESS')) {
    exit('Brak dostępu');
}

$upgradeData = $_SESSION['upgrade_data'] ?? null;

if (
    empty($upgradeData) ||
    empty($upgradeData['order_code']) ||
    empty($upgradeData['reservation_id']) ||
    empty($upgradeData['flight_direction'])
) {
    header('Location: upgrade');
    exit;
}

$orderCode = $upgradeData['order_code'];
$reservationId = (int)$upgradeData['reservation_id'];
$flightDirection = $upgradeData['flight_direction'];

require_once __DIR__ . '/config/db.php';
$pdo = getDb();

$stmt = $pdo->prepare("
    SELECT 
        rp.id AS passenger_id, 
        rp.passenger_index,
        rp.first_name, 
        rp.last_name, 
        rp.seat_number,
        rp.baggage_count, 
        rf.ticket_type
    FROM reservations r
    JOIN reservation_passengers rp ON rp.reservation_id = r.id
    JOIN reservation_flights rf ON rf.reservation_id = r.id
        AND rf.flight_direction = :flight_direction
    WHERE r.id = :reservation_id
      AND r.reservation_code = :code
    ORDER BY rp.passenger_index ASC
");

$stmt->execute([
    ':reservation_id' => $reservationId,
    ':code' => $orderCode,
    ':flight_direction' => $flightDirection
]);

$passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($passengers)) {
    echo '<main><section class="upgrade-section"><p>Nie znaleziono pasażerów dla tej rezerwacji.</p></section></main>';
    return;
}
?>

<main>
    <section class="upgrade-section">
        <h1 class="upgrade-title">Wybierz pasażera</h1>
        <p class="upgrade-subtitle">Rezerwacja: <strong><?= $orderCode ?></strong></p>

        <div class="upgrade-passengers-grid">
            <?php foreach ($passengers as $p): ?>
            <div class="upgrade-passenger-card">
                <div class="upgrade-passenger-header">
                    <h3><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></h3>
                    <div class="passenger-details">
                        <div class="passenger-detail-row">
                            <span class="detail-label">Klasa</span>
                            <span class="detail-value"><?= htmlspecialchars($p['ticket_type']) ?></span>
                        </div>
                        <div class="passenger-detail-row">
                            <span class="detail-label">Miejsce</span>
                            <span class="detail-value"><?= $p['seat_number'] ? htmlspecialchars($p['seat_number']) : 'Nie wybrano' ?></span>
                        </div>
                        <div class="passenger-detail-row">
                            <span class="detail-label">Bagaż</span>
                            <span class="detail-value"><?= (int)$p['baggage_count'] ?> szt.</span>
                        </div>
                    </div>
                </div>
                <div class="upgrade-passenger-actions">
                    <form method="POST" action="upgrade_choice" style="display:inline;">
                        <input type="hidden" name="upgrade_action" value="choose_luggage">
                        <input type="hidden" name="passenger_id" value="<?= (int)$p['passenger_id'] ?>">
                        <button type="submit" class="upgrade-action-btn">
                            🧳 Zmień bagaż
                        </button>
                    </form>

                    <form method="POST" action="upgrade_choice" style="display:inline;">
                        <input type="hidden" name="upgrade_action" value="choose_seat">
                        <input type="hidden" name="passenger_id" value="<?= (int)$p['passenger_id'] ?>">
                        <button type="submit" class="upgrade-action-btn upgrade-action-btn--seat">
                            💺 Zmień klasę / miejsce
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>