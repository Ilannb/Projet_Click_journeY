<?php
session_start();

require_once(__DIR__ . '/../app/includes/logout.php');
require_once(__DIR__ . '/../app/includes/destinations.php');

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Retrieve 3 random destinations (or all if there are 3 or fewer)
$destinations = getRandomDestinations(3);

// Title of the page
$icon = '<i class="fa-solid fa-bullhorn"></i>';
$title = 'Offre unique : Hôtel <span class="underlined">gratuit</span> pour les voyages en <span class="bolded">France</span> !';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Main page for travel agency">
  <meta name="keywords" content="LakEvasion, voyage, destination, activités, lac">

  <!-- Roboto Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://lakevasion.ddns.net/assets/fontawesome/css/all.min.css">

  <!-- CSS -->
  <link rel="stylesheet" href="assets/styles/global.css">
  <link rel="stylesheet" href="assets/styles/components/header.css">
  <link rel="stylesheet" href="assets/styles/components/footer.css">
  <link rel="stylesheet" href="assets/styles/components/card.css">
  <link rel="stylesheet" href="assets/styles/components/search-input.css">
  <link rel="stylesheet" href="assets/styles/pages/index.css">

  <!-- Tab Display -->
  <link rel="icon" href="assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - Accueil</title>
</head>

<body>
  <?php require('components/header.php'); ?>

  <main>
    <section class="hero">
      <div class="hero-content">
        <h1 class="hero-title">Explorez les plus <span class="underlined">beaux lacs</span> du <span
            class="underlined">monde</span></h1>
        <p class="hero-description">Des voyages inoubliables au bord des lacs les plus spectaculaires</p>
        <form action="pages/destinations.php" method="get" class="search-box">
          <div class="search-bar">
            <input type="text" name="search" placeholder="Quel lac souhaitez-vous découvrir ?">
            <i class="fa-solid fa-location-dot"></i>
          </div>
          <a href="pages/destinations" class="filter-btn">
            <i class="fa-solid fa-sliders"></i>
          </a>
          <button type="submit" class="search-btn">
            Explorer
            <i class="fas fa-search"></i>
          </button>
        </form>
      </div>
    </section>

    <section class="destinations-box">
      <h2>Destinations qui pourraient vous plaire</h2>
      <div class="destinations-cards-box">

        <?php if (empty($destinations)): ?>
          <p>Aucune destination disponible pour le moment.</p>
        <?php else: ?>
          <?php foreach ($destinations as $destination): ?>
            <div class="destination-card">
              <img class="destination-image" src="<?php echo htmlspecialchars($destination['image_path']); ?>" alt="<?php echo htmlspecialchars($destination['title']); ?>">
              <div class="destination-content">
                <div class="title-rating-container">
                  <h3 class="title"><?php echo htmlspecialchars($destination['title']); ?></h3>
                  <div class="rating-box">
                    <?php
                    // Star rating display
                    $rating = isset($destination['rating']) ? floatval($destination['rating']) : 0;
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
                  <div class="country-box">
                    <i class="fa-solid fa-globe"></i>
                    <p class="country"><?php echo htmlspecialchars($destination['country']); ?></p>
                  </div>
                  <div class="duration-box">
                    <i class="fa-solid fa-hourglass-half"></i>
                    <p class="duration"><span class="trip-duration"><?php echo htmlspecialchars($destination['duration']); ?></span> jours</p>
                  </div>
                  <div class="activity-box">
                    <i class="fa-solid <?php echo htmlspecialchars($destination['activity_icon']); ?>"></i>
                    <p class="activity"><?php echo htmlspecialchars($destination['activity']); ?></p>
                  </div>
                </div>
                <p class="price">À partir de <span class="euros bolded"><span id="trip-price"><?php echo htmlspecialchars($destination['price']); ?></span>€</span></p>
                <a href="pages/trip?id=<?php echo $destination['id']; ?>" class="details-btn">Voir les détails<i class="fa-solid fa-arrow-right"></i></a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>
    </section>

    <section class="why-us">
      <h2>Pourquoi choisir LakEvasion ?</h2>
      <div class="features">
        <div class="feature">
          <i class="fas fa-map-marked-alt"></i>
          <h3>Lacs Sélectionnés</h3>
          <p>Les plus beaux lacs soigneusement choisis pour vous</p>
        </div>
        <div class="feature">
          <i class="fas fa-hiking"></i>
          <h3>Activités Lacustres</h3>
          <p>Une multitude d'activités autour des lacs</p>
        </div>
        <div class="feature">
          <i class="fas fa-shield-alt"></i>
          <h3>Experts Locaux</h3>
          <p>Des guides connaissant parfaitement leur région</p>
        </div>
      </div>
    </section>
  </main>

  <?php require('components/footer.php'); ?>
</body>

</html>