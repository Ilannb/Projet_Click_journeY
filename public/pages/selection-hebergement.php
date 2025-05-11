<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');
require_once(__DIR__ . '/../../app/includes/logout.php');

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Query to retrieve all accommodations
$query = "SELECT * FROM accommodations";

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

// Filter by price
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

  // Filter accommodations
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
  $accommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Error retrieving accommodations:" . $e->getMessage());
}

// Number of results found
$resultsCount = count($accommodations);

// Retrieve the destination to know its duration and calculate the total price
$destinationInfo = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $destQuery = "SELECT * FROM destinations WHERE id = ?";
  $destStmt = $conn->prepare($destQuery);
  $destStmt->execute([$_GET['id']]);
  $destinationInfo = $destStmt->fetch(PDO::FETCH_ASSOC);
}

// Retrieve the accommodation already selected for this trip
$selectedAccommodation = null;
if (isset($_SESSION['selected_accommodation']) && isset($_GET['id']) && isset($_SESSION['selected_accommodation'][$_GET['id']])) {
  $accommodationId = $_SESSION['selected_accommodation'][$_GET['id']]['accommodation_id'];

  // Retrieve the details of the selected accommodation
  $accommQuery = "SELECT * FROM accommodations WHERE accommodation_id = ?";
  $accommStmt = $conn->prepare($accommQuery);
  $accommStmt->execute([$accommodationId]);
  $selectedAccommodation = $accommStmt->fetch(PDO::FETCH_ASSOC);
}

// Title of the page
$icon = '<i class="fa-solid fa-bed"></i>';
$title = 'Accommodation Selection';
?> 
<!DOCTYPE html>
<html lang="fr">

<head>
  <script src="../assets/scripts/theme-init.js"></script>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Accommodation selection for your trip">
  <meta name="keywords" content="LakEvasion, accommodation, hotel, lodging, travel">

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
  <title>LakEvasion - Accommodation Selection</title>
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

          <div class="filter-section">
            <h3>Search</h3>
            <div class="search-bar">
              <input type="text" name="search" placeholder="Search for accommodation..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
              <i class="fas fa-search"></i>
            </div>
          </div>

          <div class="filter-section">
            <h3>Type</h3>
            <div class="checkbox-group">
              <?php
              // Get all types from database
              $typeQuery = "SELECT DISTINCT type FROM accommodations ORDER BY type";
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
            <h3>Price per night</h3>
            <div class="range-filter">
              <div class="input-bar">
                <input type="text" name="min_price" placeholder="Min" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
                <i class="fas fa-euro-sign"></i>
              </div>
              <p class="bolded">-</p>
              <div class="input-bar">
                <input type="text" name="max_price" placeholder="Max" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
                <i class="fas fa-euro-sign"></i>
              </div>
            </div>
          </div>

          <button class="apply filters-btn">
            Apply filters
            <i class="fas fa-filter"></i>
          </button>
          <a href="selection-hebergement<?php echo isset($_GET['id']) ? '?id=' . $_GET['id'] : ''; ?>" class="reset filters-btn">
            Reset filters
            <i class="fas fa-undo"></i>
          </a>
        </form>
      </aside>

      <section class="destinations-results">
        <?php if ($destinationInfo): ?>
          <div class="day-info">
            Accommodation selection for <strong><?php echo htmlspecialchars($destinationInfo['title']); ?></strong>
            <p>Duration of stay: <strong><?php echo htmlspecialchars($destinationInfo['duration']); ?> days</strong></p>
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
              <select id="sort" name="sort">
                <option value="popular" <?php echo (!isset($_GET['sort']) || $_GET['sort'] === 'popular') ? 'selected' : ''; ?>>Popularité</option>
                <option value="price-asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price-asc') ? 'selected' : ''; ?>>Prix croissant</option>
                <option value="price-desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price-desc') ? 'selected' : ''; ?>>Prix décroissant</option>
              </select>
            </div>
          </div>
        </div>

        <form method="POST" action="process-hebergement-selection">
          <input type="hidden" name="destination_id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">

          <div class="destinations-cards-box">
            <?php global $accommodation_number;
            $accommodation_number = 0; ?>
            <?php if (empty($accommodations)): ?>
              <p>No accommodations are currently available.</p>
            <?php else: ?>
              <?php foreach ($accommodations as $accommodation): ?>
                <?php $accommodation_number++; ?>
                <div class="destination-card">
                  <input
                    class="radio"
                    type="radio"
                    name="accommodation_id"
                    <?php
                    // Check if accommodation is already selected
                    $isChecked = false;
                    if ($selectedAccommodation) {
                      $isChecked = ($selectedAccommodation['accommodation_id'] == $accommodation['accommodation_id']);
                    }
                    // Select the first one by default
                    else if ($accommodation_number == 1) {
                      $isChecked = true;
                    }
                    echo $isChecked ? 'checked' : '';
                    ?>
                    value="<?php echo htmlspecialchars($accommodation['accommodation_id']); ?>">
                  <img class="destination-image" src="<?php echo htmlspecialchars($accommodation['image_path']); ?>" alt="<?php echo htmlspecialchars($accommodation['title']); ?>">
                  <div class="destination-content">
                    <div class="title-rating-container">
                      <h3 class="title"><?php echo htmlspecialchars($accommodation['title']); ?></h3>
                      <div class="rating-box">
                        <?php
                        // Star rating display
                        $rating = isset($accommodation['rating']) ? floatval($accommodation['rating']) : 0;
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
                      <p class="description"><?php echo htmlspecialchars($accommodation['description']); ?></p>
                    </div>
                    <p class="price">
                      <span class="euros bolded"><?php echo $accommodation['base_price']; ?>€</span> /night
                      <?php if ($destinationInfo): ?>
                        <span class="total-price">(Total: <strong><?php echo $accommodation['base_price'] * ($destinationInfo['duration'] - 1); ?>€</strong> for <?php echo $destinationInfo['duration'] - 1; ?> nights)</span>
                      <?php endif; ?>
                    </p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <button type="submit" class="confirm-selection-btn">Confirm selection</button>
        </form>
      </section>
    </div>
  </main>

  <?php require('../components/footer.php'); ?>
  <script src="../assets/scripts/sort.js"></script>
</body>

</html>