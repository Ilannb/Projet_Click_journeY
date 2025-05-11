<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');
require_once(__DIR__ . '/../../app/includes/logout.php');

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Query to retrieve all destinations
$query = "SELECT * FROM destinations";

// Conditions and query parameters
$whereConditions = [];
$params = [];

// Get sort option from form
$sortOption = isset($_GET['sort']) ? $_GET['sort'] : 'popular';

// Filter by search term
if (isset($_GET['search']) && !empty($_GET['search'])) {
  $whereConditions[] = "(title LIKE ? OR description_long LIKE ? OR country LIKE ?)";
  $searchTerm = "%" . $_GET['search'] . "%";
  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $params[] = $searchTerm;
}

// Filter by country
if (isset($_GET['country']) && !empty($_GET['country'])) {
  if (is_array($_GET['country'])) {
    // Multiple country selection
    $countryPlaceholders = implode(',', array_fill(0, count($_GET['country']), '?'));
    $whereConditions[] = "country IN ($countryPlaceholders)";
    $params = array_merge($params, $_GET['country']);
  } else {
    // Single country selection
    $whereConditions[] = "country = ?";
    $params[] = $_GET['country'];
  }
}

// Filter by duration
if (isset($_GET['duration']) && !empty($_GET['duration'])) {
  $durationFilters = [];
  if (is_array($_GET['duration'])) {
    foreach ($_GET['duration'] as $duration) {
      if ($duration === '1-3') {
        $durationFilters[] = "(duration >= 1 AND duration <= 3)";
      } elseif ($duration === '4-7') {
        $durationFilters[] = "(duration >= 4 AND duration <= 7)";
      } elseif ($duration === '7+') {
        $durationFilters[] = "duration > 7";
      }
    }
    if (!empty($durationFilters)) {
      $whereConditions[] = "(" . implode(" OR ", $durationFilters) . ")";
    }
  }
}

// Filter by price
if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
  $whereConditions[] = "price >= ?";
  $params[] = $_GET['min_price'];
}
if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
  $whereConditions[] = "price <= ?";
  $params[] = $_GET['max_price'];
}

// Filter by activity
if (isset($_GET['activity']) && !empty($_GET['activity'])) {
  if (is_array($_GET['activity'])) {
    // Multiple activity selection
    $activityPlaceholders = implode(',', array_fill(0, count($_GET['activity']), '?'));
    $whereConditions[] = "activity IN ($activityPlaceholders)";
    $params = array_merge($params, $_GET['activity']);
  } else {
    // Single activity selection
    $whereConditions[] = "activity = ?";
    $params[] = $_GET['activity'];
  }
}

// Add WHERE clause to the query if filters are applied
if (!empty($whereConditions)) {
  $query .= " WHERE " . implode(" AND ", $whereConditions);
}

$query .= " ORDER BY rating DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Number of results
$resultsCount = count($destinations);

// Title of the page
$icon = '<i class="fa-solid fa-location-dot"></i>';
$title = 'Nos Destinations';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <script src="../assets/scripts/theme-init.js"></script>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Page des destinations">
  <meta name="keywords" content="LakEvasion, filtre, destination, activités, pays, durée, budget, voyage">

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
  <link rel="stylesheet" href="../assets/styles/components/search-input.css">
  <link rel="stylesheet" href="../assets/styles/pages/destinations.css">
  <link rel="stylesheet" id="theme-style" href="../assets/styles/light-mode.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - Destinations</title>
</head>

<body>
  <?php require('../components/header.php'); ?>

  <main>
    <div class="page-container">
      <aside class="filters-sidebar">
        <h2>Filtres</h2>
        <form id="filters-form" class="filters-content" action="" method="get">

          <!-- Search filter section -->
          <div class="filter-section">
            <h3>Recherche</h3>
            <div class="search-bar">
              <input type="text" name="search" placeholder="Rechercher une destination..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
              <i class="fas fa-search"></i>
            </div>
          </div>

          <!-- Country filter section -->
          <div class="filter-section">
            <h3>Pays</h3>
            <div class="checkbox-group">
              <?php
              // Get countries from database
              $countryQuery = "SELECT DISTINCT country FROM destinations ORDER BY country";
              $countryStmt = $conn->prepare($countryQuery);
              $countryStmt->execute();
              $countries = $countryStmt->fetchAll(PDO::FETCH_COLUMN);

              // Checkboxes for each country
              foreach ($countries as $country) {
                $checked = isset($_GET['country']) && (
                  (is_array($_GET['country']) && in_array($country, $_GET['country'])) ||
                  $_GET['country'] === $country
                ) ? 'checked' : '';
                echo '<label class="checkbox-label">
                  <input type="checkbox" name="country[]" value="' . htmlspecialchars($country) . '" ' . $checked . '>
                  ' . htmlspecialchars($country) . '
                </label>';
              }
              ?>
            </div>
          </div>

          <!-- Duration filter section -->
          <div class="filter-section">
            <h3>Durée</h3>
            <div class="checkbox-group">
              <label class="checkbox-label">
                <input type="checkbox" name="duration[]" value="1-3" <?php echo isset($_GET['duration']) && is_array($_GET['duration']) && in_array('1-3', $_GET['duration']) ? 'checked' : ''; ?>>
                1-3 jours
              </label>
              <label class="checkbox-label">
                <input type="checkbox" name="duration[]" value="4-7" <?php echo isset($_GET['duration']) && is_array($_GET['duration']) && in_array('4-7', $_GET['duration']) ? 'checked' : ''; ?>>
                4-7 jours
              </label>
              <label class="checkbox-label">
                <input type="checkbox" name="duration[]" value="7+" <?php echo isset($_GET['duration']) && is_array($_GET['duration']) && in_array('7+', $_GET['duration']) ? 'checked' : ''; ?>>
                7+ jours
              </label>
            </div>
          </div>

          <!-- Price range filter section -->
          <div class="filter-section">
            <h3>Budget</h3>
            <div class="range-filter">
              <div class="input-bar">
                <input type="number" name="min_price" placeholder="Min" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
                <i class="fas fa-euro"></i>
              </div>
              <p class="bolded">-</p>
              <div class="input-bar">
                <input type="number" name="max_price" placeholder="Max" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
                <i class="fas fa-euro"></i>
              </div>
            </div>
          </div>

          <!-- Activities filter section -->
          <div class="filter-section">
            <h3>Activités</h3>
            <div class="checkbox-group">
              <?php
              // Get activities from database
              $activityQuery = "SELECT DISTINCT activity FROM destinations ORDER BY activity";
              $activityStmt = $conn->prepare($activityQuery);
              $activityStmt->execute();
              $activities = $activityStmt->fetchAll(PDO::FETCH_COLUMN);

              // Checkboxes for each activity
              foreach ($activities as $activity) {
                $checked = isset($_GET['activity']) && (
                  (is_array($_GET['activity']) && in_array($activity, $_GET['activity'])) ||
                  $_GET['activity'] === $activity
                ) ? 'checked' : '';
                echo '<label class="checkbox-label">
                  <input type="checkbox" name="activity[]" value="' . htmlspecialchars($activity) . '" ' . $checked . '>
                  ' . htmlspecialchars($activity) . '
                </label>';
              }
              ?>
            </div>
          </div>

          <!-- Hidden field to preserve the current sort selection when applying filters -->
          <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortOption); ?>">

          <div class="filter-buttons">
            <a href="destinations" class="reset filters-btn">
              Réinitialiser les filtres
              <i class="fas fa-undo"></i>
            </a>

            <button type="submit" class="apply filters-btn">
              Appliquer les filtres
              <i class="fas fa-filter"></i>
            </button>
          </div>
        </form>
      </aside>

      <!-- Section with destination results -->
      <section class="destinations-results">
        <div class="results-header">
          <div class="results-controls">
            <p><span class="results-count bolded"><?php echo $resultsCount; ?></span> résultats trouvés</p>
            <form action="" method="get" class="sort-form">
              <?php
              // Preserve all current GET parameters except 'sort'
              foreach ($_GET as $key => $value) {
                if ($key !== 'sort') {
                  if (is_array($value)) {
                    foreach ($value as $item) {
                      echo '<input type="hidden" name="' . htmlspecialchars($key) . '[]" value="' . htmlspecialchars($item) . '">';
                    }
                  } else {
                    echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                  }
                }
              }
              ?>
              <div class="sort-box">
                <label for="sort">Trier par :</label>
                <select id="sort" name="sort">
                    <option value="popular" <?php echo (!isset($_GET['sort']) || $_GET['sort'] === 'popular') ? 'selected' : ''; ?>>Popularité</option>
                    <option value="price-asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price-asc') ? 'selected' : ''; ?>>Prix croissant</option>
                    <option value="price-desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price-desc') ? 'selected' : ''; ?>>Prix décroissant</option>
                    <option value="duration" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'duration') ? 'selected' : ''; ?>>Durée</option>
                </select>
              </div>
            </form>
          </div>
        </div>

        <!-- Destination cards container -->
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
                  <p class="price">À partir de <span class="euros bolded"><span><?php echo htmlspecialchars($destination['price']); ?></span>€</span></p>
                  <a href="trip?id=<?php echo $destination['id']; ?>" class="details-btn">Voir les détails<i class="fa-solid fa-arrow-right"></i></a>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </main>

  <?php require('../components/footer.php'); ?>
  <script src="../assets/scripts/sort.js"></script>
</body>

</html>