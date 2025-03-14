<?php
session_start();

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

require_once(__DIR__ . '/../app/includes/logout.php');
require_once(__DIR__ . '/../app/includes/destinations.php');

// Retrieve 3 random destinations (or all if there are 3 or fewer)
$destinations = getRandomDestinations(3);
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
  <header>
    <nav>
      <div class="logo">
        <img src="assets/src/img/favicon.ico" alt="main-icon">
        <a href="/">LakEvasion</a>
      </div>
      <div class="middle-section">
        <i class="fa-solid fa-bullhorn"></i>
        <p>Offre unique : Hôtel <span class="underlined">gratuit</span> pour les voyages en <span
            class="bolded">France</span> !</p>
      </div>
      <?php if ($isLoggedIn): ?>
        <!-- User not connected -->
        <div class="right-section">
          <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <div class="user">
              <a class="user-link" href="pages/admin"><?php echo htmlspecialchars($_SESSION['user_firstname']); ?></a>
              <i class="fa-solid fa-screwdriver-wrench"></i>
            </div>
          <?php else: ?>
          <div class="user">
            <a class="user-link" href="pages/user"><?php echo htmlspecialchars($_SESSION['user_firstname']); ?></a>
            <i class="fa-solid fa-user"></i>
          </div>
          <?php endif; ?>
          <div class="links-box">
            <a href="?logout=1" class="logout-btn">Déconnexion
              <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
          </div>
        </div>
      <?php else: ?>
        <!-- User connected -->
        <div class="links-box">
          <a href="pages/login" class="login-btn">Se connecter</a>
          <a href="pages/register" class="signup-btn">S'inscrire</a>
        </div>
      <?php endif; ?>
    </nav>
  </header>

  <main>
    <section class="hero">
      <div class="hero-content">
        <h1 class="hero-title">Explorez les plus <span class="underlined">beaux lacs</span> du <span
            class="underlined">monde</span></h1>
        <p class="hero-description">Des voyages inoubliables au bord des lacs les plus spectaculaires</p>
        <div class="search-box">
          <div class="search-bar">
            <input type="text" placeholder="Quel lac souhaitez-vous découvrir ?">
            <i class="fa-solid fa-location-dot"></i>
          </div>
          <a href="pages/destinations" class="filter-btn">
            <i class="fa-solid fa-sliders"></i>
          </a>
          <button class="search-btn">
            Explorer
            <i class="fas fa-search"></i>
          </button>
        </div>
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
                <h3 class="title"><?php echo htmlspecialchars($destination['title']); ?></h3>
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

  <footer>
    <div class="footer-box">
      <div class="footer-top">
        <div class="footer-section agency-section">
          <div class="footer-logo">
            <img src="assets/src/img/favicon.ico" alt="LakEvasion Logo" />
            <h3>LakEvasion</h3>
          </div>
          <p class="agency">Votre spécialiste des voyages lacustres depuis 2025. Découvrez des expériences uniques au
            bord des plus beaux lacs du monde.</p>
        </div>

        <div class="footer-section">
          <h4>Navigation</h4>
          <ul class="footer-links">
            <li><a href="/">Accueil</a></li>
            <li><a href="pages/destinations">Nos Destinations</a></li>
            <li><a href="pages/about">À propos</a></li>
          </ul>
        </div>

        <div class="footer-section">
          <h4>Services</h4>
          <ul class="footer-links">
            <li><a href="pages/housing">Hebergement</a></li>
            <li><a href="pages/restoration">Restauration</a></li>
            <li><a href="pages/activities">Activités</a></li>
          </ul>
        </div>

        <div class="footer-section contact-section">
          <h4>Contactez-nous</h4>
          <div class="contact-info">
            <div><i class="fas fa-phone"></i><a href="tel:0134251010">+33 1 34 25 10 10</a></div>
            <div><i class="fas fa-envelope"></i><a href="mailto:contact@lakevasion.fr">contact@lakevasion.fr</a></div>
            <div><i class="fas fa-map-marker-alt"></i>
              <address>Av. du Parc, 95000 Cergy</address>
            </div>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2025 LakEvasion. Tous droits réservés.</p>
      </div>
    </div>
  </footer>
</body>

</html>