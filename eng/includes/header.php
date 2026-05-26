<?php
if (!defined('APP_ACCESS')) exit('Access denied');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <base href="/WronAir/WronAir/eng/" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="keywords" content="WronAir, airline, cheap flights, travel, holidays, airplane, flight tickets, flights to the USA" />
  <link rel="shortcut icon" href="../img/logo_16.ico">
  <link rel="shortcut icon" href="../img/logo_32.ico">
  <title><?= htmlspecialchars($pageTitle ?? 'WronAir') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($pageStyle ?? '../style/style.css') ?>" />
  <link rel="stylesheet" href="../style/style_headfoot.css" />
</head>
<body>
<header class="header-with-logo">
  <div class="logo-container">
    <a href="#">
      <img src="../img/logoclear.png" alt="WronAir Logo" />
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
        <a href="#" class="login-link"><span>My Account</span></a>
        <a href="#" class="login-link"><span>Log out</span></a>
      <?php else: ?>
        <a href="#" class="login-link"><span>Login and registration</span></a>
      <?php endif; ?>

      <div class="language-switch">
        <button class="lang-btn" id="langButton">
          <img src="../img/Flag_of_the_United_States.svg.png" alt="ENG" class="flag" />
          <span>ENG</span>
        </button>

        <ul class="language-dropdown" id="langDropdown">
          <li class="current-language">
            <a href="#">
              <img src="../img/Flag_of_the_United_States.svg.png" alt="ENG" class="flag" />
              <span>ENG</span>
            </a>
          </li>
          <li>
            <a href="../home">
              <img src="../img/Flag_of_Poland.svg" alt="PL" class="flag" />
              <span>PL</span>
            </a>
          </li>
          
        </ul>
      </div>
    </div>
  </div>

  <nav class="main-menu" id="mainNav">
    <ul class="nav-links">
      <li><a href="#"><span>Home</span></a></li>
      <li><a href="#"><span>Check-in</span></a></li>
      <li><a href="#"><span>Upgrade</span></a></li>
      <li><a href="#"><span>Onboard Services</span></a></li>
      <li><a href="#"><span>FAQ</span></a></li>
      <li><a href="#"><span>About Us</span></a></li>
    </ul>
  </nav>

  <nav class="mobile-menu" id="mobileMenu">
    <ul>
      <?php if(isset($_SESSION['user_id'])): ?>
        <li><a href="#">My Account</a></li>
        <li><a href="#">Log out</a></li>
      <?php else: ?>
        <li><a href="#">Login and registration</a></li>
      <?php endif; ?>

      <li><a href="#">Home</a></li>
      <li><a href="#">Check-in</a></li>
      <li><a href="#">Upgrade</a></li>
      <li><a href="#"><span>Onboard Services</span></a></li>
      <li><a href="#">FAQ</a></li>
      <li><a href="#">About Us</a></li>
      <li>
        <div class="language-switch-mobile">
          <button class="lang-btn" id="langButtonMobile">
            <img src="../img/Flag_of_the_United_States.svg.png" alt="ENG" class="flag" />
            ENG
          </button>

          <ul class="language-dropdown" id="langDropdownMobile">
            <li class="current-language">
              <a href="#">
                <img src="../img/Flag_of_the_United_States.svg.png" alt="ENG" class="flag" />
                ENG
              </a>
            </li>
            <li>
              <a href="../home">
                <img src="../img/Flag_of_Poland.svg" alt="PL" class="flag" />
                PL
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