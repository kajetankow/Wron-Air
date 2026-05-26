<?php
$orderCode = strtoupper(htmlspecialchars($_GET['orderCode'] ?? ''));

require_once __DIR__ . '/config/db.php';
$pdo = getDb();

$stmt = $pdo->prepare("
    SELECT rp.id AS passenger_id, rp.passenger_index,
           rp.first_name, rp.last_name, rp.seat_number,
           rp.baggage_count, rf.ticket_type
    FROM reservations r
    JOIN reservation_passengers rp ON rp.reservation_id = r.id
    JOIN reservation_flights rf ON rf.reservation_id = r.id
        AND rf.flight_direction = 'departure'
    WHERE r.reservation_code = :code
    ORDER BY rp.passenger_index ASC
");
$stmt->execute([':code' => $orderCode]);
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
                    <a class="upgrade-action-btn"
                       href="index.php?view=upgrade_choice_luggage&orderCode=<?= $orderCode ?>&passengerId=<?= $p['passenger_id'] ?>">
                        🧳 Zmień bagaż
                    </a>
                    <a class="upgrade-action-btn upgrade-action-btn--seat"
                       href="index.php?view=upgrade_choice_seat&orderCode=<?= $orderCode ?>&passengerId=<?= $p['passenger_id'] ?>">
                        💺 Zmień klasę / miejsce
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>