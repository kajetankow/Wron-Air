<?php
if (!defined('APP_ACCESS')) exit('Brak dostępu');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <base href="/WronAir/WronAir/" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="keywords" content="WronAir, linie lotnicze, tanie loty, podróże, wakacje, samolot, bilety lotnicze, loty do USA" />
  <link rel="shortcut icon" href="img/logo_16.ico">
  <link rel="shortcut icon" href="img/logo_32.ico">
  <title><?= htmlspecialchars($pageTitle ?? 'WronAir') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($pageStyle ?? 'style/style.css') ?>" />
  <link rel="stylesheet" href="style/style_headfoot.css" />
</head>
<body>
<header class="header-with-logo">
  <div class="logo-container">
    <a href="home">
      <img src="img/logoclear.png" alt="Logo WronAir" />
    </a>
  </div>

  <button class="menu-toggle" id="menuToggle">
    <span class="hamburger-bar"></span>
    <span class="hamburger-bar"></span>
    <span class="hamburger-bar"></span>
  </button>

  <div class="top-bar">
    <div class="top-bar-content">
      <?php if(isset($_SESSION['user_id'])): ?>
        <a href="konto" class="login-link"><span>Moje Konto</span></a>
        <a href="logout" class="login-link"><span>Wyloguj</span></a>
      <?php else: ?>
        <a href="logowanie" class="login-link"><span>Logowanie i rejestracja</span></a>
      <?php endif; ?>
      <div class="language-switch">
        <button class="lang-btn" id="langButton">
          <img src="img/Flag_of_Poland.svg" alt="PL" class="flag" />
          <span>PL</span>
        </button>
        <ul class="language-dropdown" id="langDropdown">
          <li class="current-language">
            <a href="#">
              <img src="img/Flag_of_Poland.svg" alt="PL" class="flag" />
              <span>PL</span>
            </a>
          </li>
          <li>
            <a href="eng/">
              <img src="img/Flag_of_the_United_States.svg.png" alt="ENG" class="flag" />
              <span>ENG</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <nav class="main-menu" id="mainNav">
    <ul class="nav-links">
      <li><a href="home"><span>Strona Główna</span></a></li>
      <li><a href="odprawa"><span>Odpraw się</span></a></li>
      <li><a href="upgrade"><span>Upgrade</span></a></li>
      <li><a href="uslugi"><span>Usługi na Pokładzie</span></a></li>
      <li><a href="faq"><span>FAQ</span></a></li>
      <li><a href="o_nas"><span>O Nas</span></a></li>
    </ul>
  </nav>

  <nav class="mobile-menu" id="mobileMenu">
    <ul>
      <?php if(isset($_SESSION['user_id'])): ?>
        <li><a href="konto">Moje Konto</a></li>
        <li><a href="logout">Wyloguj</a></li>
      <?php else: ?>
        <li><a href="logowanie">Logowanie i rejestracja</a></li>
      <?php endif; ?>
      
      <li><a href="home">Strona Główna</a></li>
      <li><a href="odprawa">Odpraw się</a></li>
      <li><a href="upgrade">Upgrade</a></li>
      <li><a href="uslugi"><span>Usługi na Pokładzie</span></a></li>
      <li><a href="faq">FAQ</a></li>
      <li><a href="o_nas">O Nas</a></li>
      <li>
        <div class="language-switch-mobile">
          <button class="lang-btn" id="langButtonMobile">
            <img src="img/Flag_of_Poland.svg" alt="PL" class="flag" />
            PL
          </button>
          <ul class="language-dropdown" id="langDropdownMobile">
            <li class="current-language">
              <a href="#">
                <img src="img/Flag_of_Poland.svg" alt="PL" class="flag" />
                PL
              </a>
            </li>
            <li>
              <a href="eng/">
                <img src="img/Flag_of_the_United_States.svg.png" alt="ENG" class="flag" />
                ENG
              </a>
            </li>
          </ul>
        </div>
      </li>
    </ul>
  </nav>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</header>
<script>
  document.addEventListener("DOMContentLoaded", function () {
  const menuToggle = document.getElementById("menuToggle");
  const mobileMenu = document.getElementById("mobileMenu");

  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener("click", function () {
      mobileMenu.classList.toggle("open");
    });
  }

  const langButton = document.getElementById("langButton");
  const langDropdown = document.getElementById("langDropdown");

  if (langButton && langDropdown) {
    langButton.addEventListener("click", function () {
      langDropdown.classList.toggle("show");
    });
  }

  const langButtonMobile = document.getElementById("langButtonMobile");
  const langDropdownMobile = document.getElementById("langDropdownMobile");

  if (langButtonMobile && langDropdownMobile) {
    langButtonMobile.addEventListener("click", function () {
      langDropdownMobile.classList.toggle("show");
    });
  }
});
</script>

<?php if (!empty($pageStyle2)): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($pageStyle2) ?>" />
<?php endif; ?>