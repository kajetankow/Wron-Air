<?php
$orderCode   = strtoupper(htmlspecialchars($_GET['orderCode'] ?? ''));
$passengerId = (int)($_GET['passengerId'] ?? 0);

require_once __DIR__ . '/config/db.php';
$pdo = getDb();

$stmt = $pdo->prepare("
    SELECT rp.first_name, rp.last_name, rp.baggage_count
    FROM reservation_passengers rp
    WHERE rp.id = :pid
");
$stmt->execute([':pid' => $passengerId]);
$passenger = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$passenger) {
    echo '<main><p>Nie znaleziono pasażera.</p></main>';
    return;
}

$options = [
    ['type' => '15',   'label' => '15 kg',     'price' => 149.00, 'img' => 'img/luggage_15.webp',  'count' => 0.5],
    ['type' => '23',   'label' => '23 kg',     'price' => 200.00, 'img' => 'img/luggage.webp',     'count' => 1],
    ['type' => '2x23', 'label' => '2 × 23 kg', 'price' => 274.00, 'img' => 'img/luggage_46.webp',  'count' => 2],
];
?>

<main>
    <section class="upgrade-section">
        <h1 class="upgrade-title">Bagaż rejestrowany</h1>
        <p class="upgrade-subtitle">
            Pasażer: <strong><?= htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']) ?></strong>
            &nbsp;|&nbsp; Aktualny bagaż: <strong><?= (float)$passenger['baggage_count'] ?> szt.</strong>
        </p>

        <div class="upgrade-options-grid">
            <?php foreach ($options as $opt): 
                $isOwned = ((float)$passenger['baggage_count'] == $opt['count']);
                
                $btnClass = 'upgrade-btn';
                $btnText = number_format($opt['price'], 2, ',', ' ') . ' zł';
                $isDisabled = false;

                if ($isOwned) {
                    $btnClass .= ' upgrade-btn--owned';
                    $btnText = 'Posiadany';
                    $isDisabled = true;
                }
            ?>
            <div class="upgrade-choice-card">
                <img src="<?= $opt['img'] ?>" alt="<?= $opt['label'] ?>" />
                <div class="upgrade-choice-info">
                    <h3>Bagaż rejestrowany</h3>
                    <p class="kinds"><?= $opt['label'] ?></p>
                    <button class="<?= $btnClass ?>"
                        <?php if (!$isDisabled): ?>
                        onclick="window.location.href='index.php?view=upgrade_end&orderCode=<?= $orderCode ?>&passengerId=<?= $passengerId ?>&upgradeChoice=luggage&luggageType=<?= $opt['type'] ?>&luggageCount=<?= $opt['count'] ?>&luggagePrice=<?= $opt['price'] ?>'"
                        <?php else: ?>
                        disabled
                        <?php endif; ?>>
                        <?= $btnText ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="upgrade-back">
            <a href="index.php?view=upgrade_choice&orderCode=<?= $orderCode ?>" class="upgrade-back-btn">
                ← Wróć do wyboru pasażera
            </a>
        </div>
    </section>
</main>