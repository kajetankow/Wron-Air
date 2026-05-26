<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="keywords" content="WronAir, linie lotnicze, tanie loty, podróże, wakacje, samolot, bilety lotnicze, loty do USA" />
  <link rel="shortcut icon" href="img/logo_16.ico">
  <link rel="shortcut icon" href="img/logo_32.ico">
  <title><?= htmlspecialchars($pageTitle ?? 'WronAir') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($pageStyle) ?>">
  <link rel="stylesheet" href="style/style_headfoot.css" />

</head>
<body>
<header class="header-with-logo">
  <div class="logo-container">
    <a href="index.php?view=home">
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
      <a href="#" class="login-link"><span>Logowanie i rejestracja</span></a>
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
            <a href="eng/home.php">
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
      <li><a href="index.php?view=home"><span>Strona Główna</span></a></li>
      <li><a href="index.php?view=odprawa"><span>Odpraw się</span></a></li>
      <li><a href="index.php?view=upgrade"><span>Upgrade</span></a></li>
      <li><a href="index.php?view=uslugi"><span>Usługi na Pokładzie</span></a></li>
      <li><a href="index.php?view=faq"><span>FAQ</span></a></li>
      <li><a href="index.php?view=o_nas"><span>O Nas</span></a></li>
    </ul>
  </nav>

  <nav class="mobile-menu" id="mobileMenu">
    <ul>
      <li><a href="#">Logowanie i rejestracja</a></li>
      <li><a href="index.php?view=home">Strona Główna</a></li>
      <li><a href="index.php?view=odprawa">Odpraw się</a></li>
      <li><a href="index.php?view=upgrade">Upgrade</a></li>
      <li><a href="index.php?view=uslugi"><span>Usługi na Pokładzie</span></a></li>
      <li><a href="index.php?view=faq">FAQ</a></li>
      <li><a href="index.php?view=o_nas">O Nas</a></li>
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
              <a href="eng/home.php">
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
