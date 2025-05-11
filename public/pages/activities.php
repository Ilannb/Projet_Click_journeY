<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');
require_once(__DIR__ . '/../../app/includes/logout.php');

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Retrieving activities from the database
try {
  $stmt = $conn->prepare("SELECT * FROM activities ORDER BY title");
  $stmt->execute();
  $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Erreur lors de la récupération des activités: " . $e->getMessage());
}

// Title of the page
$icon = '<i class="fa-solid fa-person-running"></i>';
$title = 'Activités';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <script src="../assets/scripts/theme-init.js"></script>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Page des activités proposées">
  <meta name="keywords" content="LakEvasion, activités">

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
  <link rel="stylesheet" id="theme-style" href="../assets/styles/light-mode.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - </title>

</head>

<body>
  <?php require('../components/header.php'); ?>

  <section class="destinations-box">
    <h2>Activités proposées</h2>
    <div class="destinations-cards-box">

      <?php if (empty($activities)): ?>
        <p>Aucune activité n'est disponible actuellement.</p>
      <?php else: ?>
        <?php foreach ($activities as $activity): ?>
          <div class="destination-card">
            <img class="destination-image" src="<?php echo htmlspecialchars($activity['image_path']); ?>" alt="<?php echo htmlspecialchars($activity['title']); ?>">
            <div class="destination-content">
              <div class="title-rating-container">
                <h3 class="title"><?php echo htmlspecialchars($activity['title']); ?></h3>
                <div class="rating-box">
                  <?php
                  // Star rating display
                  $rating = isset($activity['rating']) ? floatval($activity['rating']) : 0;
                  $fullStars = floor($rating);
                  $halfStar = ($rating - $fullStars) >= 0.5;
                  $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                  // Full stars
                  for ($i = 0; $i < $fullStars; $i++) {
                    echo '<i class="fas fa-star"></i>';
                  }
                  // Half star
                  if ($halfStar) {
                    echo '<i class="fas fa-star-half-alt"></i>';
                  }
                  // Empty stars
                  for ($i = 0; $i < $emptyStars; $i++) {
                    echo '<i class="far fa-star"></i>';
                  }
                  ?>
                  <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                </div>
              </div>
              <div class="destination-description">
                <p><?php echo htmlspecialchars($activity['description']); ?></p>
              </div>
              <p class="price">À partir de <span class="euros bolded"><?php echo $activity['base_price']; ?>€</span> /personne</p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </section>

  <?php require('../components/footer.php'); ?>
</body>

</html>