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

// Get error message if it exists
$error_message = isset($_SESSION['payment_error']) ? $_SESSION['payment_error'] : 'Votre paiement a été refusé.';
unset($_SESSION['payment_error']);

// Get destination ID if available
$destination_id = isset($_GET['destination_id']) ? $_GET['destination_id'] : null;

// Title of the page
$icon = '<i class="fa-solid fa-times-circle"></i>';
$title = 'Payment Declined';
?>
<!DOCTYPE html>
<html>

<head>
  <script src="../assets/scripts/theme-init.js"></script>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Payment refusé">
  <meta name="keywords" content="LakEvasion, payment, refusé">

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
  <link rel="stylesheet" href="../assets/styles/pages/declined.css">
  <link rel="stylesheet" id="theme-style" href="../assets/styles/light-mode.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - Payment Refusé</title>
</head>

<body>
  <?php require('../components/header.php'); ?>

  <main>
    <div class="declined-container">
      <div class="declined-box">
        <div class="declined-icon">
          <i class="fa-solid fa-times-circle"></i>
        </div>
        <h1>Payment Refusé</h1>
        <p><?php echo htmlspecialchars($error_message); ?></p>
        <div class="declined-actions">
          <?php if ($destination_id): ?>
            <a href="payment?destination_id=<?php echo htmlspecialchars($destination_id); ?>" class="primary-button">Try Again</a>
          <?php endif; ?>
          <a href="destinations" class="secondary-button">Découvrir d'autres destinations</a>
          <a href="contact" class="tertiary-button">Contacter le service client (pas implémenté)</a>
        </div>
      </div>
    </div>
  </main>

  <?php require('../components/footer.php'); ?>
</body>

</html>