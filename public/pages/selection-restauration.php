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

// Apply sorting
if (isset($_GET['sort'])) {
  switch ($_GET['sort']) {
    case 'price-asc':
      $query .= " ORDER BY base_price ASC";
      break;
    case 'price-desc':
      $query .= " ORDER BY base_price DESC";
      break;
    case 'popular':
    default:
      $query .= " ORDER BY rating DESC";
  }
} else {
  $query .= " ORDER BY rating DESC";
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
  $title = 'Meal Selection - Breakfast (Day ' . $day . ')';
} else if ($period == 'morning') {
  $title = 'Meal Selection - Lunch (Day ' . $day . ')';
} else {
  $title = 'Meal Selection - Dinner (Day ' . $day . ')';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
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

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - Meal Selection</title>
</head>

<body>
  <?php require('../components/header.php'); ?>

  <main>
    <div class="page-container">
      <aside class="filters-sidebar">
        <h2>Filters</h2>
        <form class="filters-content" action="" method="get">
          <?php if (isset($_GET['id']) && !empty($_GET['id'])): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
          <?php endif; ?>

          <input type="hidden" name="day" value="<?php echo $day; ?>">
          <input type="hidden" name="period" value="<?php echo $period; ?>">

          <div class="filter-section">
            <h3>Search</h3>
            <div class="search-bar">
              <input type="text" name="search" placeholder="Search for a restaurant..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
              <i class="fas fa-search"></i>
            </div>
          </div>

          <div class="filter-section">
            <h3>Cuisine Type</h3>
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
            Apply Filters
            <i class="fas fa-filter"></i>
          </button>
          <a href="selection-restauration<?php echo isset($_GET['id']) ? '?id=' . $_GET['id'] . '&day=' . $day . '&period=' . $period : ''; ?>" class="reset filters-btn">
            Reset Filters
            <i class="fas fa-undo"></i>
          </a>
        </form>
      </aside>

      <section class="destinations-results">
        <?php if ($destinationInfo): ?>
          <div class="day-info">
            Meal selection for
            <?php
            if ($period == 'breakfast') {
              echo 'Breakfast';
            } else if ($period == 'morning') {
              echo 'Lunch';
            } else {
              echo 'Dinner';
            }
            ?>
            on Day <?php echo $day; ?> at <?php echo htmlspecialchars($destinationInfo['title']); ?>
          </div>
        <?php endif; ?>

        <div class="results-header">
          <div class="results-controls">
            <?php if ($resultsCount > 1): ?>
              <p><span id="results-count" class="results-count bolded"><?php echo $resultsCount ?></span> results found</p>
            <?php else: ?>
              <p><span id="results-count" class="results-count bolded"><?php echo $resultsCount ?></span> result found</p>
            <?php endif; ?>
            <div class="sort-box">
              <label for="sort">Sort by:</label>
              <select id="sort" name="sort" onchange="this.form.submit()">
                <option value="popular" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'popular') ? 'selected' : ''; ?>>Popularity</option>
                <option value="price-asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-asc') ? 'selected' : ''; ?>>Price (ascending)</option>
                <option value="price-desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-desc') ? 'selected' : ''; ?>>Price (descending)</option>
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
              <p>No restaurants are currently available.</p>
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
                    <p class="price">Starting from <span class="euros bolded"><?php echo $restaurant['base_price']; ?>€</span> /person</p>
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
                <?php
                // Check if the "No meal" option is selected 
                if (isset($selectedMeals[$day][$period]) && $selectedMeals[$day][$period]['restaurant_id'] == 'none') {
                  echo 'checked';
                }
                ?>>
              <img class="destination-image" src="https://lakevasion.ddns.net/assets/src/img/empty.png" alt="No meal">
              <div class="destination-content">
                <h3 class="title">
                  <?php if ($period == 'breakfast'): ?>
                    No breakfast reservation
                  <?php else: ?>
                    No meal reservation
                  <?php endif; ?>
                </h3>
                <div class="destination-description">
                  <div class="country-box">
                    <p class="country">
                      <?php if ($period == 'breakfast'): ?>
                        You are free to choose where to have your breakfast that day.
                      <?php else: ?>
                        You are free to choose where to eat that day.
                      <?php endif; ?>
                    </p>
                  </div>
                </div>
                <p class="price">Starting from <span class="euros bolded">0€</span> /person</p>
              </div>
            </div>
          </div>

          <button type="submit" class="confirm-selection-btn">Confirm Selection</button>
        </form>
      </section>
    </div>
  </main>

  <?php require('../components/footer.php'); ?>
</body>

</html>