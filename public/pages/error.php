<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');
require_once(__DIR__ . '/../../app/includes/logout.php');

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
// Add this section to check user role
if ($isLoggedIn) {
  $user_id = $_SESSION['user_id'];
  $role_query = "SELECT role FROM users WHERE id = ?";
  $role_stmt = $conn->prepare($role_query);
  $role_stmt->execute([$user_id]);
  $user_role = $role_stmt->fetchColumn();
}

// Title of the page
$icon = '<i class="fa-solid fa-circle-info"></i>';
$title = 'Erreur';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Page à propos de LakEvasion">
  <meta name="keywords" content="LakEvasion, agence de voyage, lacs, tourisme, à propos">

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
  <link rel="stylesheet" href="../assets/styles/pages/error.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - <?php if (isset($user_role) && $user_role === 'banned'): ?>
      Vous avez été banni<?php else: ?>
      Erreur
    <?php endif; ?></title>
</head>

<body>
  <section class="message">
    <div class="error-box">
      <?php if ($isLoggedIn && $user_role === 'banned'): ?>
        <i class="fa-solid fa-ban error-icon"></i>
        <h2>Compte Banni</h2>
        <h3>Votre compte a été suspendu en raison d'une violation de nos conditions d'utilisation.</h3>
        <p>Pour toute question, veuillez contacter notre support.</p>
      <?php else: ?>
        <i class="fa-solid fa-triangle-exclamation error-icon"></i>
        <h2>Erreur</h2>
        <h3>Une erreur inattendue s'est produite.</h3>
        <p>Si vous voyez ce message, veuillez contacter notre service client.</p>
      <?php endif; ?>
      <a href="../index.php" class="return-button">Retour à l'accueil</a>
    </div>
  </section>
</body>

</html>