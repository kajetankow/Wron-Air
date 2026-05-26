<?php
if (!defined('APP_ACCESS')) {
    exit('Brak dostępu');
}

$upgradeData = $_SESSION['upgrade_data'] ?? null;

if (
    empty($upgradeData) ||
    empty($upgradeData['order_code']) ||
    empty($upgradeData['reservation_id']) ||
    empty($upgradeData['passenger_id']) ||
    empty($upgradeData['flight_direction'])
) {
    header('Location: upgrade');
    exit;
}

$orderCode = $upgradeData['order_code'];
$reservationId = (int)$upgradeData['reservation_id'];
$passengerId = (int)$upgradeData['passenger_id'];
$flightDirection = $upgradeData['flight_direction'];

require_once __DIR__ . '/config/db.php';
$pdo = getDb();

$stmt = $pdo->prepare("
    SELECT 
        rp.first_name,
        rp.last_name,
        rp.baggage_count
    FROM reservation_passengers rp
    JOIN reservations r ON r.id = rp.reservation_id
    WHERE rp.id = :pid
      AND rp.reservation_id = :rid
      AND r.reservation_code = :code
    LIMIT 1
");

$stmt->execute([
    ':pid' => $passengerId,
    ':rid' => $reservationId,
    ':code' => $orderCode
]);

$passenger = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$passenger) {
    echo '<main><section class="upgrade-section"><p>Nie znaleziono pasażera dla tej rezerwacji.</p></section></main>';
    return;
}

$options = [
    [
        'type' => '15',
        'label' => '15 kg',
        'price' => 149.00,
        'img' => 'img/luggage_15.webp',
        'count' => 0.5
    ],
    [
        'type' => '23',
        'label' => '23 kg',
        'price' => 200.00,
        'img' => 'img/luggage.webp',
        'count' => 1
    ],
    [
        'type' => '2x23',
        'label' => '2 × 23 kg',
        'price' => 274.00,
        'img' => 'img/luggage_46.webp',
        'count' => 2
    ],
];

$oldBaggageCount = (float)($passenger['baggage_count'] ?? 0);

$oldPrice = 0.00;

if ($oldBaggageCount == 0.5) {
    $oldPrice = 149.00;
} elseif ($oldBaggageCount == 1) {
    $oldPrice = 200.00;
} elseif ($oldBaggageCount >= 2) {
    $oldPrice = 274.00;
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<main>
    <section class="upgrade-section">
        <h1 class="upgrade-title">Bagaż rejestrowany</h1>

        <p class="upgrade-subtitle">
            Pasażer:
            <strong>
                <?= e(($passenger['first_name'] ?? '') . ' ' . ($passenger['last_name'] ?? '')) ?>
            </strong>
            &nbsp;|&nbsp;
            Aktualny bagaż:
            <strong><?= e($oldBaggageCount) ?> szt.</strong>
        </p>

        <div class="upgrade-options-grid">
            <?php foreach ($options as $opt): ?>
                <?php
                $isOwned = ($oldBaggageCount == $opt['count']);
                $diff = $opt['price'] - $oldPrice;

                $btnClass = 'upgrade-btn';
                $btnText = ($diff > 0 ? '+' : '') . number_format($diff, 2, ',', ' ') . ' zł';
                $isDisabled = false;

                if ($isOwned) {
                    $btnClass .= ' upgrade-btn--owned';
                    $btnText = 'Posiadany';
                    $isDisabled = true;
                }
                ?>

                <div class="upgrade-choice-card">
                    <img src="<?= e($opt['img']) ?>" alt="<?= e($opt['label']) ?>" />

                    <div class="upgrade-choice-info">
                        <h3>Bagaż rejestrowany</h3>
                        <p class="kinds"><?= e($opt['label']) ?></p>

                        <form method="POST" action="upgrade_choice_luggage">
                            <input type="hidden" name="upgrade_action" value="confirm_luggage">
                            <input type="hidden" name="luggage_type" value="<?= e($opt['type']) ?>">
                            <input type="hidden" name="luggage_count" value="<?= e($opt['count']) ?>">
                            <input type="hidden" name="luggage_price_diff" value="<?= e($diff) ?>">

                            <button type="submit" class="<?= e($btnClass) ?>" <?= $isDisabled ? 'disabled' : '' ?>>
                                <?= e($btnText) ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="upgrade-back">
            <a href="upgrade_choice" class="upgrade-back-btn">
                ← Wróć do wyboru pasażera
            </a>
        </div>
    </section>
</main>