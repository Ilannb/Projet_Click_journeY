<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');
require_once(__DIR__ . '/../../app/includes/logout.php');

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Retrieving restaurants from the database
try {
  $stmt = $conn->prepare("SELECT * FROM catering ORDER BY title");
  $stmt->execute();
  $catering = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Erreur lors de la récupération des lieux de restauration: " . $e->getMessage());
}

// Title of the page
$icon = '<i class="fa-solid fa-utensils"></i>';
$title = 'Restauration';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Page descriptive de la restauration proposée">
  <meta name="keywords" content="LakEvasion, restauration, nourriture">

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
  <link rel="stylesheet" href="../assets/styles/components/card.css">
  <link rel="stylesheet" href="../assets/styles/components/descriptive-pages.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - Restauration</title>

</head>

<body>
  <?php require('../components/header.php'); ?>

  <section class="destinations-box">
    <h2>Restauration proposée</h2>
    <div class="destinations-cards-box">

      <?php if (empty($catering)): ?>
        <p>Aucune activité n'est disponible actuellement.</p>
      <?php else: ?>
        <?php foreach ($catering as $restaurant): ?>
          <div class="destination-card">
            <img class="destination-image" src="<?php echo htmlspecialchars($restaurant['image_path']); ?>" alt="<?php echo htmlspecialchars($restaurant['title']); ?>">
            <div class="destination-content">
              <h3 class="title"><?php echo htmlspecialchars($restaurant['title']); ?></h3>
              <div class="destination-description">
                <p><?php echo htmlspecialchars($restaurant['description']); ?></p>
              </div>
              <p class="price">À partir de <span class="euros bolded"><?php echo $restaurant['base_price']; ?>€</span> /jour</p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </section>

  <?php require('../components/footer.php'); ?>
</body>

</html>