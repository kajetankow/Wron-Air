<?php
$upgradeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upgrade_submit'])) {
    $orderCode = strtoupper(trim($_POST['orderCode'] ?? ''));

    if (strlen($orderCode) !== 10) {
        $upgradeError = 'Kod zamówienia musi mieć dokładnie 10 znaków.';
    } else {
        require_once __DIR__ . '/config/db.php';
        $pdo = getDb();
        $stmt = $pdo->prepare("SELECT id FROM reservations WHERE reservation_code = :code LIMIT 1");
        $stmt->execute([':code' => $orderCode]);

        if (!$stmt->fetch()) {
            $upgradeError = 'Nie znaleziono rezerwacji o podanym kodzie. Sprawdź poprawność kodu i spróbuj ponownie.';
        }
    }
}
?>

<main>
    <section class="upgrade-section">
        <h1 class="upgrade-title">Upgrade</h1>

        <?php if ($upgradeError): ?>
            <div class="upgrade-alert">
                <span class="upgrade-alert-icon">⚠</span>
                <?= htmlspecialchars($upgradeError) ?>
            </div>
        <?php endif; ?>

        <div class="upgrade-layout">
            <div class="upgrade-content">
                <form class="upgrade-form" id="upgradeForm" method="POST"
                      action="upgrade">
                    <input type="hidden" name="upgrade_submit" value="1">
                    <h1>Chcesz wykupić UPGRADE?</h1>
                    <h2>Wpisz poniżej kod swojego zamówienia i kliknij UPGRADE</h2>
                    <input
                        type="text"
                        placeholder="Numer zamówienia"
                        id="orderCode"
                        name="orderCode"
                        maxlength="10"
                        value="<?= htmlspecialchars($_POST['orderCode'] ?? '') ?>"
                        required
                        style="text-transform: uppercase;"
                    />
                    <button type="submit">UPGRADE</button>
                </form>
            </div>

            <div class="upgrade-options">
                <div class="upgrade-card">
                    <img src="img/luggage.webp" alt="Bagaż rejestrowany" />
                    <div class="upgrade-card-info">
                        <h3>Bagaż rejestrowany</h3>
                        <p class="kinds">15 kg | 23 kg | 2 × 23 kg</p>
                    </div>
                </div>
                <div class="upgrade-card">
                    <img src="img/seat.webp" alt="Klasa siedzenia" />
                    <div class="upgrade-card-info">
                        <h3>Klasa siedzenia</h3>
                        <p class="kinds">Economy | Business | First Class</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>