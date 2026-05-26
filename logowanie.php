<?php
if (!defined('APP_ACCESS')) exit('Brak dostępu');
?>

<main class="content logowanie-page">
    <div class="auth-container">
        
        <?php if (!empty($authErrors)): ?>
            <div class="auth-alert error">
                <?php foreach($authErrors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($authSuccess)): ?>
            <div class="auth-alert success">
                <p><?= htmlspecialchars($authSuccess) ?></p>
            </div>
        <?php endif; ?>

        <div class="auth-wrapper">
            <div class="auth-box login-box">
                <h2>Logowanie</h2>
                <form method="POST" action="logowanie">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group-auth">
                        <input type="email" name="email" placeholder="Adres e-mail" required>
                    </div>
                    <div class="form-group-auth">
                        <input type="password" name="password" placeholder="Hasło" required>
                    </div>
                    <button type="submit" class="auth-submit-btn">Zaloguj się</button>
                </form>
            </div>

            <div class="auth-divider"></div>

            <div class="auth-box register-box">
                <h2>Rejestracja</h2>
                <form method="POST" action="logowanie">
                    <input type="hidden" name="action" value="register">
                    <div class="form-row-auth">
                        <div class="form-group-auth">
                            <input type="text" name="first_name" placeholder="Imię" required>
                        </div>
                        <div class="form-group-auth">
                            <input type="text" name="last_name" placeholder="Nazwisko" required>
                        </div>
                    </div>
                    <div class="form-group-auth">
                        <input type="email" name="email" placeholder="Adres e-mail" required>
                    </div>
                    <div class="form-group-auth">
                        <input type="password" name="password" placeholder="Hasło" required>
                    </div>
                    <div class="form-group-auth">
                        <input type="password" name="password_confirm" placeholder="Powtórz hasło" required>
                    </div>
                    <button type="submit" class="auth-submit-btn">Zarejestruj się</button>
                </form>
            </div>
        </div>
    </div>
</main>