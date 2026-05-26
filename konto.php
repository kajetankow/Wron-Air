<?php
if (!defined('APP_ACCESS')) exit('Brak dostępu');

if (!isset($_SESSION['user_id'])) {
    header('Location: logowanie');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

$stmt = $pdo->prepare("
    SELECT r.id, r.reservation_code, r.total_price, r.trip_type,
           rf_dep.flight_date AS dep_date, f_dep.departure_time AS dep_time,
           a1.city AS origin_city, a2.city AS destination_city,
           rf_ret.flight_date AS ret_date, f_ret.departure_time AS ret_time
    FROM reservations r
    JOIN reservation_flights rf_dep ON r.id = rf_dep.reservation_id AND rf_dep.flight_direction = 'departure'
    JOIN flights f_dep ON rf_dep.flight_id = f_dep.id
    JOIN airports a1 ON f_dep.origin_code = a1.code
    JOIN airports a2 ON f_dep.destination_code = a2.code
    LEFT JOIN reservation_flights rf_ret ON r.id = rf_ret.reservation_id AND rf_ret.flight_direction = 'return'
    LEFT JOIN flights f_ret ON rf_ret.flight_id = f_ret.id
    WHERE r.user_id = :user_id
    ORDER BY rf_dep.flight_date ASC, f_dep.departure_time ASC
");
$stmt->execute([':user_id' => $userId]);
$reservations = $stmt->fetchAll();
?>

<main class="content konto-page">
    <div class="konto-container">
        <h1 class="konto-title">Witaj, <?= htmlspecialchars($userName) ?>!</h1>
        <p class="konto-subtitle">Zarządzaj swoimi nadchodzącymi podróżami.</p>

        <div class="reservations-list">
            <?php if (empty($reservations)): ?>
                <div class="no-reservations">
                    <p>Nie masz jeszcze żadnych rezerwacji.</p>
                    <a href="home" class="btn-primary">Zaplanuj podróż</a>
                </div>
            <?php else: ?>
                <?php foreach ($reservations as $res): ?>
                    <?php
                    $flightDateTime = new DateTime($res['dep_date'] . ' ' . $res['dep_time']);
                    $now = new DateTime();
                    $interval = $now->diff($flightDateTime);
                    
                    $isPast = $flightDateTime < $now;
                    $daysLeft = $interval->format('%a');
                    $hoursLeft = $interval->format('%h');
                    ?>
                    
                    <div class="reservation-card <?= $isPast ? 'past-flight' : '' ?>">
                        <div class="res-header">
                            <span class="res-code">KOD: <?= htmlspecialchars($res['reservation_code']) ?></span>
                            <span class="res-price"><?= htmlspecialchars($res['total_price']) ?> PLN</span>
                        </div>
                        
                        <div class="res-body">
                            <div class="route">
                                <h3><?= htmlspecialchars($res['origin_city']) ?> ✈ <?= htmlspecialchars($res['destination_city']) ?></h3>
                                <p><?= $res['trip_type'] === 'round-trip' ? 'W obie strony' : 'W jedną stronę' ?></p>
                            </div>
                            
                            <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                                <div class="datetime">
                                    <span style="font-size: 13px; color: #888; font-weight: normal;">Wylot:</span>
                                    <p class="date"><?= htmlspecialchars($res['dep_date']) ?></p>
                                    <p class="time"><?= htmlspecialchars($res['dep_time']) ?></p>
                                </div>
                                <?php if ($res['trip_type'] === 'round-trip' && !empty($res['ret_date'])): ?>
                                    <div class="datetime">
                                        <span style="font-size: 13px; color: #888; font-weight: normal;">Powrót:</span>
                                        <p class="date"><?= htmlspecialchars($res['ret_date']) ?></p>
                                        <p class="time"><?= htmlspecialchars($res['ret_time']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="countdown">
                                <?php if ($isPast): ?>
                                    <span class="status-past">Lot zakończony</span>
                                <?php elseif ($daysLeft == 0): ?>
                                    <span class="status-urgent">Odlot za <?= $hoursLeft ?> godz.</span>
                                <?php else: ?>
                                    <span class="status-upcoming">Odlot za <?= $daysLeft ?> dni</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!$isPast): ?>
                            <div class="res-actions">
                                <form method="POST" action="index.php" class="inline-form">
                                    <input type="hidden" name="action" value="auto_checkin">
                                    <input type="hidden" name="reservation_code" value="<?= htmlspecialchars($res['reservation_code']) ?>">
                                    <button type="submit" class="btn-action btn-checkin">Odpraw się</button>
                                </form>

                                <form method="POST" action="upgrade" class="inline-form">
                                    <input type="hidden" name="upgrade_submit" value="1">
                                    <input type="hidden" name="orderCode" value="<?= htmlspecialchars($res['reservation_code']) ?>">
                                    <button type="submit" class="btn-action btn-upgrade">Ulepsz (Upgrade)</button>
                                </form>
                                
                                <form method="POST" action="konto" class="inline-form" onsubmit="return confirm('Czy na pewno chcesz anulować tę rezerwację? Tej operacji nie można cofnąć.');">
                                    <input type="hidden" name="action" value="cancel_reservation">
                                    <input type="hidden" name="cancel_id" value="<?= $res['id'] ?>">
                                    <button type="submit" class="btn-action btn-cancel">Anuluj lot</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>