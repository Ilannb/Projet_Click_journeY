<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');
require_once(__DIR__ . '/../../app/includes/logout.php');

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Query to retrieve all transports
$query = "SELECT * FROM transports";

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

  // Filter transports
  $whereConditions[] = "FIND_IN_SET(?, destinations_ids) > 0";
  $params[] = $selectedDestinationId;
}

// Building the query with WHERE if necessary
if (!empty($whereConditions)) {
  $query .= " WHERE " . implode(" AND ", $whereConditions);
}

// Preparation and execution of the query
try {
  $stmt = $conn->prepare($query);
  $stmt->execute($params);
  $transports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Erreur lors de la récupération des transports:" . $e->getMessage());
}

// Number of results found
$resultsCount = count($transports);

// Retrieve the destination to know its duration, default transport and calculate the total price
$destinationInfo = null;
$defaultTransportId = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $destQuery = "SELECT * FROM destinations WHERE id = ?";
  $destStmt = $conn->prepare($destQuery);
  $destStmt->execute([$_GET['id']]);
  $destinationInfo = $destStmt->fetch(PDO::FETCH_ASSOC);

  if ($destinationInfo) {
    $defaultTransportId = $destinationInfo['default_transport_id'];
  }
}

// Retrieve the transport already selected for this trip
$selectedTransport = null;
$usingDefaultTransport = false;
if (isset($_SESSION['selected_transport']) && isset($_GET['id']) && isset($_SESSION['selected_transport'][$_GET['id']])) {
  $transportId = $_SESSION['selected_transport'][$_GET['id']]['transport_id'];

  // Check if using default transport
  if ($defaultTransportId && $transportId == $defaultTransportId) {
    $usingDefaultTransport = true;
  }

  // Retrieve the details of the selected transport
  $transportQuery = "SELECT * FROM transports WHERE transport_id = ?";
  $transportStmt = $conn->prepare($transportQuery);
  $transportStmt->execute([$transportId]);
  $selectedTransport = $transportStmt->fetch(PDO::FETCH_ASSOC);
}

// Page title
$icon = '<i class="fa-solid fa-plane"></i>';
$title = 'Sélection du transport';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <script src="../assets/scripts/theme-init.js"></script>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Sélection du transport pour votre voyage">
  <meta name="keywords" content="LakEvasion, transport, avion, train, bus, bateau, voyage">

  <!-- Fonts and Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
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
  <title>LakEvasion - Sélection du transport</title>
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

          <div class="filter-section">
            <h3>Recherche</h3>
            <div class="search-bar">
              <input type="text" name="search" placeholder="Rechercher un transport..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
              <i class="fas fa-search"></i>
            </div>
          </div>

          <div class="filter-section">
            <h3>Type de transport</h3>
            <div class="checkbox-group">
              <?php
              // Retrieve all types from the database
              $typeQuery = "SELECT DISTINCT type FROM transports ORDER BY type";
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
            <h3>Prix</h3>
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
            Appliquer les filtres
            <i class="fas fa-filter"></i>
          </button>
          <a href="selection-transport<?php echo isset($_GET['id']) ? '?id=' . $_GET['id'] : ''; ?>" class="reset filters-btn">
            Réinitialiser
            <i class="fas fa-undo"></i>
          </a>
        </form>
      </aside>

      <section class="destinations-results">
        <?php if ($destinationInfo): ?>
          <div class="day-info">
            Sélection du transport pour <strong><?php echo htmlspecialchars($destinationInfo['title']); ?></strong>
          </div>
        <?php endif; ?>

        <div class="results-header">
          <div class="results-controls">
            <?php if ($resultsCount > 1): ?>
              <p><span id="results-count" class="results-count bolded"><?php echo $resultsCount ?></span> résultats trouvés</p>
            <?php else: ?>
              <p><span id="results-count" class="results-count bolded"><?php echo $resultsCount ?></span> résultat trouvé</p>
            <?php endif; ?>
            <div class="sort-box">
              <label for="sort">Trier par :</label>
              <select id="sort" name="sort">
                  <option value="popular" <?php echo (!isset($_GET['sort']) || $_GET['sort'] === 'popular') ? 'selected' : ''; ?>>Popularité</option>
                  <option value="price-asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price-asc') ? 'selected' : ''; ?>>Prix croissant</option>
                  <option value="price-desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price-desc') ? 'selected' : ''; ?>>Prix décroissant</option>
              </select>
            </div>
          </div>
        </div>

        <form method="POST" action="process-transport-selection">
          <input type="hidden" name="destination_id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">

          <div class="destinations-cards-box">
            <?php global $transport_number;
            $transport_number = 0; ?>
            <?php if (empty($transports)): ?>
              <p>Aucun transport n'est actuellement disponible.</p>
            <?php else: ?>
              <?php foreach ($transports as $transport): ?>
                <?php $transport_number++; ?>
                <div class="destination-card">
                  <input
                    class="radio"
                    type="radio"
                    name="transport_id"
                    <?php
                    // Check if the transport is selected
                    $isChecked = false;
                    $isDefault = ($defaultTransportId && $transport['transport_id'] == $defaultTransportId);

                    if ($selectedTransport) {
                      $isChecked = ($selectedTransport['transport_id'] == $transport['transport_id']);
                    }
                    // Select the default one if it exists and none is selected
                    else if ($isDefault) {
                      $isChecked = true;
                    }
                    // Otherwise select the first one
                    else if ($transport_number == 1 && !$defaultTransportId) {
                      $isChecked = true;
                    }
                    echo $isChecked ? 'checked' : '';
                    ?>
                    value="<?php echo htmlspecialchars($transport['transport_id']); ?>">
                  <img class="destination-image" src="<?php echo htmlspecialchars($transport['image_path']); ?>" alt="<?php echo htmlspecialchars($transport['title']); ?>">
                  <div class="destination-content">
                    <div class="title-rating-container">
                      <h3 class="title">
                        <?php echo htmlspecialchars($transport['title']); ?>
                        <?php if ($isDefault): ?>
                          <span class="default-badge">Inclus</span>
                        <?php endif; ?>
                      </h3>
                      <div class="rating-box">
                        <?php
                        // Display rating stars
                        $rating = isset($transport['rating']) ? floatval($transport['rating']) : 0;
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
                      <p class="description"><?php echo htmlspecialchars($transport['description']); ?></p>
                    </div>
                    <p class="price">
                      <?php if ($isDefault): ?>
                        <span class="euros bolded">Inclus</span> dans le prix de base
                      <?php else: ?>
                        <span class="euros bolded"><?php echo $transport['base_price']; ?>€</span> par trajet
                      <?php endif; ?>
                    </p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <button type="submit" class="confirm-selection-btn">Confirmer la sélection</button>
        </form>
      </section>
    </div>
  </main>

  <?php require('../components/footer.php'); ?>
  <script src="../assets/scripts/sort.js"></script>
</body>

</html>