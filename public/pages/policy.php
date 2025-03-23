<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');
require_once(__DIR__ . '/../../app/includes/logout.php');

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Retrieving terms from the database
try {
  $stmt = $conn->prepare("SELECT * FROM cgv ORDER BY id");
  $stmt->execute();
  $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Erreur lors de la récupération des conditions: " . $e->getMessage());
}

// Title of the page
$icon = '<i class="fa-solid fa-scale-balanced"></i>';
$title = 'Conditions Générales de Vente';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Page des conditions">
  <meta name="keywords" content="LakEvasion, conditions, règles, cgv">

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
  <link rel="stylesheet" href="../assets/styles/pages/policy.css">
  <link rel="stylesheet" href="../assets/styles/components/card.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - CGV</title>

</head>

<body>
  <?php require('../components/header.php'); ?>

  <section class="hero">
    <h1 class="hero-title">Conditions Générales de Vente</h1>
  </section>
  <nav class="cgv-index-box">
    <p class="cgv-index"><a class="link-to-index" href="../index.html">Accueil &gt;</a> <span class="bolded">Conditions générales de vente</span></p>
  </nav>

  <main>
    <section class="cgv-box">

      <?php foreach ($terms as $condition): ?>
        <div class="cgv-section">
          <h2><?php echo htmlspecialchars($condition['id']) . ". " . htmlspecialchars($condition['title']); ?></h2>
          <p><?php echo htmlspecialchars($condition['condition_long']); ?></p>
        </div>
      <?php endforeach; ?>

    </section>
  </main>

  <?php require('../components/footer.php'); ?>
</body>

</html>