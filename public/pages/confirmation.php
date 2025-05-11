<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');
require_once(__DIR__ . '/../../app/includes/logout.php');

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
  header('Location: login');
  exit;
}

// Check if confirmation message exists
$success_message = isset($_SESSION['payment_success']) ? $_SESSION['payment_success'] : null;
unset($_SESSION['payment_success']);

// Title of the page
$icon = '<i class="fa-solid fa-check-circle"></i>';
$title = 'Confirmation ';
?>
<!DOCTYPE html>
<html>

<head>
  <script src="../assets/scripts/theme-init.js"></script>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Confirmation de réservation">
  <meta name="keywords" content="LakEvasion, confirmation, réservation">

  <!-- Roboto Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://lakevasion.ddns.net/assets/fontawesome/css/all.min.css">

  <!-- CSS -->
  <link rel="stylesheet" href="../assets/styles/global.css">
  <link rel="stylesheet" href="../assets/styles/components/header.css">
  <link rel="stylesheet" href="../assets/styles/components/footer.css">
  <link rel="stylesheet" href="../assets/styles/pages/confirmation.css">
  <link rel="stylesheet" id="theme-style" href="../assets/styles/light-mode.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - Confirmation</title>
</head>

<body>
  <?php require('../components/header.php'); ?>

  <main>
    <div class="confirmation-container">
      <div class="confirmation-box">
        <div class="confirmation-icon">
          <i class="fa-solid fa-check-circle"></i>
        </div>
        <h1>Réservation confirmée</h1>
        <?php if ($success_message): ?>
          <p><?php echo htmlspecialchars($success_message); ?></p>
        <?php else: ?>
          <p>Votre réservation a été confirmée. Merci de votre confiance !</p>
        <?php endif; ?>
        <div class="confirmation-actions">
          <a href="user" class="primary-button">Voir mes réservations</a>
          <a href="destinations" class="secondary-button">Découvrir d'autres destinations</a>
        </div>
      </div>
    </div>
  </main>

  <?php require('../components/footer.php'); ?>
</body>

</html>