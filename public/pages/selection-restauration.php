<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');
require_once(__DIR__ . '/../../app/includes/logout.php');

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Get day and period parameters
$day = isset($_GET['day']) ? intval($_GET['day']) : 1;
$period = isset($_GET['period']) ? $_GET['period'] : 'morning';

// Query to retrieve all restaurants
$query = "SELECT * FROM catering";

// Add filters if present
$whereConditions = [];
$params = [];

// Filter by search term
if (isset($_GET['search']) && !empty($_GET['search'])) {
  $whereConditions[] = "(title LIKE ? OR description LIKE ? OR type LIKE ?)";
  $searchTerm = "%" . $_GET['search'] . "%";
  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $params[] = $searchTerm;
}

// Filter by type
if (isset($_GET['type']) && !empty($_GET['type'])) {
  if (is_array($_GET['type'])) {
    $typePlaceholders = implode(',', array_fill(0, count($_GET['type']), '?'));
    $whereConditions[] = "type IN ($typePlaceholders)";
    $params = array_merge($params, $_GET['type']);
  } else {
    $whereConditions[] = "type = ?";
    $params[] = $_GET['type'];
  }
}

// Filter by budget
if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
  $whereConditions[] = "base_price >= ?";
  $params[] = $_GET['min_price'];
}
if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
  $whereConditions[] = "base_price <= ?";
  $params[] = $_GET['max_price'];
}

// Filter by destination
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $selectedDestinationId = $_GET['id'];

  // Filter restaurants
  $whereConditions[] = "FIND_IN_SET(?, destinations_ids) > 0";
  $params[] = $selectedDestinationId;
}

// Construction of the query with WHERE if necessary
if (!empty($whereConditions)) {
  $query .= " WHERE " . implode(" AND ", $whereConditions);
}


// Preparation and execution of the query
try {
  $stmt = $conn->prepare($query);
  $stmt->execute($params);
  $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Error retrieving restaurants: " . $e->getMessage());
}

// Number of results found
$resultsCount = count($restaurants);

// Retrieve the destination
$destinationInfo = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $destQuery = "SELECT * FROM destinations WHERE id = ?";
  $destStmt = $conn->prepare($destQuery);
  $destStmt->execute([$_GET['id']]);
  $destinationInfo = $destStmt->fetch(PDO::FETCH_ASSOC);
}

// Retrieve the meals selected
$selectedMeals = [];
if (isset($_SESSION['selected_meals']) && isset($_GET['id'])) {
  $selectedMeals = isset($_SESSION['selected_meals'][$_GET['id']]) ?
    $_SESSION['selected_meals'][$_GET['id']] : [];
}

// Title of the page
$icon = '<i class="fa-solid fa-utensils"></i>';
if ($period == 'breakfast') {
  $title = 'Sélection repas - Petit Déjeuner (Day ' . $day . ')';
} else if ($period == 'morning') {
  $title = 'Sélection - Déjeuner (Day ' . $day . ')';
} else {
  $title = 'Sélection - Dîner (Day ' . $day . ')';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <script src="../assets/scripts/theme-init.js"></script>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Meal selection for your trip">
  <meta name="keywords" content="LakEvasion, restaurant, meal, lunch, dinner, travel">

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
  <link rel="stylesheet" href="../assets/styles/pages/selection-hebergement.css">
  <link rel="stylesheet" id="theme-style" href="../assets/styles/light-mode.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - Sélection Restauration</title>
</head>

<body>
  <?php require('../components/header.php'); ?>

  <main>
    <div class="page-container">
      <aside class="filters-sidebar">
        <h2>Filtres</h2>
        <form class="filters-content" action="" method="get">
          <?php if (isset($_GET['id']) && !empty($_GET['id'])): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
          <?php endif; ?>

          <input type="hidden" name="day" value="<?php echo $day; ?>">
          <input type="hidden" name="period" value="<?php echo $period; ?>">

          <div class="filter-section">
            <h3>Recherche</h3>
            <div class="search-bar">
              <input type="text" name="search" placeholder="Search for a restaurant..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
              <i class="fas fa-search"></i>
            </div>
          </div>

          <div class="filter-section">
            <h3>Type de restauration</h3>
            <div class="checkbox-group">
              <?php
              // Get all types from database
              $typeQuery = "SELECT DISTINCT type FROM catering ORDER BY type";
              $typeStmt = $conn->prepare($typeQuery);
              $typeStmt->execute();
              $types = $typeStmt->fetchAll(PDO::FETCH_COLUMN);
              // Generate checkboxes
              foreach ($types as $type) {
                $checked = isset($_GET['type']) && (
                  (is_array($_GET['type']) && in_array($type, $_GET['type'])) ||
                  $_GET['type'] === $type
                ) ? 'checked' : '';
                echo '<label class="checkbox-label">
                <input type="checkbox" name="type[]" value="' . htmlspecialchars($type) . '" ' . $checked . '>
                ' . htmlspecialchars($type) . '
              </label>';
              }
              ?>
            </div>
          </div>

          <div class="filter-section">
            <h3>Budget</h3>
            <div class="range-filter">
              <div class="input-bar">
                <input type="text" name="min_price" placeholder="Min" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
                <i class="fas fa-euro"></i>
              </div>
              <p class="bolded">-</p>
              <div class="input-bar">
                <input type="text" name="max_price" placeholder="Max" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
                <i class="fas fa-euro"></i>
              </div>
            </div>
          </div>

          <button class="apply filters-btn">
            Appliquer les filtres
            <i class="fas fa-filter"></i>
          </button>
          <a href="selection-restauration<?php echo isset($_GET['id']) ? '?id=' . $_GET['id'] . '&day=' . $day . '&period=' . $period : ''; ?>" class="reset filters-btn">
            Réinitialiser les filtres
            <i class="fas fa-undo"></i>
          </a>
        </form>
      </aside>

      <section class="destinations-results">
        <?php if ($destinationInfo): ?>
          <div class="day-info">
            Selection du 
            <?php
            if ($period == 'breakfast') {
              echo 'petit déjeuner';
            } else if ($period == 'morning') {
              echo 'Déjeuner';
            } else {
              echo 'Dîner';
            }
            ?>
            pour le jour <?php echo $day; ?> au <?php echo htmlspecialchars($destinationInfo['title']); ?>
          </div>
        <?php endif; ?>

        <div class="results-header">
          <div class="results-controls">
            <?php if ($resultsCount > 1): ?>
              <p><span id="results-count" class="results-count bolded"><?php echo $resultsCount ?></span> resultats trouvés </p>
            <?php else: ?>
              <p><span id="results-count" class="results-count bolded"><?php echo $resultsCount ?></span> resultat trouvé </p>
            <?php endif; ?>
            <div class="sort-box">
              <label for="sort">Trier par:</label>
              <select id="sort" name="sort">
                <option value="popular" <?php echo (!isset($_GET['sort']) || $_GET['sort'] === 'popular') ? 'selected' : ''; ?>>Popularité</option>
                <option value="price-asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price-asc') ? 'selected' : ''; ?>>Prix (croissant)</option>
                <option value="price-desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price-desc') ? 'selected' : ''; ?>>Prix (décroissant)</option>
              </select>
            </div>
          </div>
        </div>

        <form method="POST" action="process-restauration-selection.php">
          <input type="hidden" name="destination_id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">
          <input type="hidden" name="day" value="<?php echo $day; ?>">
          <input type="hidden" name="period" value="<?php echo $period; ?>">

          <div class="destinations-cards-box">
            <?php global $restaurant_number;
            $restaurant_number = 0; ?>
            <?php if (empty($restaurants)): ?>
              <p>Aucun restaurant n'est actuellement dsponible</p>
            <?php else: ?>
              <?php foreach ($restaurants as $restaurant): ?>
                <?php $restaurant_number++; ?>
                <div class="destination-card">
                  <input
                    class="radio"
                    type="radio"
                    name="restaurant_id"
                    <?php
                    // Check if this restaurant is selected
                    $isChecked = false;
                    if (isset($selectedMeals[$day][$period])) {
                      $isChecked = ($selectedMeals[$day][$period]['restaurant_id'] == $restaurant['restaurant_id']);
                    }
                    // Select the first one by default
                    else if ($restaurant_number == 1) {
                      $isChecked = true;
                    }
                    echo $isChecked ? 'checked' : '';
                    ?>
                    value="<?php echo htmlspecialchars($restaurant['restaurant_id']); ?>">
                  <img class="destination-image" src="<?php echo htmlspecialchars($restaurant['image_path']); ?>" alt="<?php echo htmlspecialchars($restaurant['title']); ?>">
                  <div class="destination-content">
                    <div class="title-rating-container">
                      <h3 class="title"><?php echo htmlspecialchars($restaurant['title']); ?></h3>
                      <div class="rating-box">
                        <?php
                        // Star rating display
                        $rating = isset($restaurant['rating']) ? floatval($restaurant['rating']) : 0;
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
                        <p class="country"><?php echo htmlspecialchars($restaurant['description']); ?></p>
                      </div>
                    </div>
                    <p class="price">À partir de <span class="euros bolded"><?php echo $restaurant['base_price']; ?>€</span> /personne</p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <!-- "No meal" option -->
            <div class="destination-card">
              <input
                class="radio"
                type="radio"
                name="restaurant_id"
                value="none"
                <?php if (isset($selectedMeals[$day][$period]) && $selectedMeals[$day][$period]['restaurant_id'] == 'none') echo 'checked'; ?>>
              <img class="destination-image" src="https://lakevasion.ddns.net/assets/src/img/empty.png" alt="No meal">
              <div class="destination-content">
                <div class="title-rating-container">
                  <h3 class="title">
                    <?php echo $period == 'breakfast' ? 'No breakfast reservation' : 'No meal reservation'; ?>
                  </h3>
                  <!-- Add invisible rating box -->
                  <div class="rating-box" style="display: none;">
                    <span class="rating-value">0</span>
                  </div>
                </div>
                <div class="destination-description">
                  <div class="country-box">
                    <p class="country">
                      <?php if ($period == 'breakfast'): ?>
                       Vous êtes libre de choisir où petit-déjeuner 
                      <?php else: ?>
                       Vous êtes libre de choisir où vous restaurer
                      <?php endif; ?>
                    </p>
                  </div>
                </div>
                <p class="price">A<span class="euros bolded">0€</span> /personne</p>
              </div>
            </div>
          </div>

          <button type="submit" class="confirm-selection-btn">Confirmer la Selection</button>
        </form>
      </section>
    </div>
  </main>

  <?php require('../components/footer.php'); ?>
  <script src="../assets/scripts/sort.js"></script>
</body>

</html>