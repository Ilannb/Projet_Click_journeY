<?php
session_start();

include_once '../../app/includes/logout.php';
require_once '../../app/config/database.php';

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Check for valid destination ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header('Location: destinations');
  exit;
}

$id = $_GET['id'];

// Get destination details
$query = "SELECT * FROM destinations WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$id]);
$destination = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$destination) {
  header('Location: destinations');
  exit;
}

// Get selected activities, meals and accommodation
$selectedActivities = isset($_SESSION['selected_activities'][$id]) ? $_SESSION['selected_activities'][$id] : [];
$selectedMeals = isset($_SESSION['selected_meals'][$id]) ? $_SESSION['selected_meals'][$id] : [];
$totalActivityPrice = 0;
$totalMealPrice = 0;

// Calculate total price for activities
if (!empty($selectedActivities)) {
  foreach ($selectedActivities as $day => $periods) {
    foreach ($periods as $period => $activity) {
      if (isset($activity['activity_id']) && $activity['activity_id'] != 'none') {
        $activityQuery = "SELECT base_price FROM activities WHERE activity_id = ?";
        $activityStmt = $conn->prepare($activityQuery);
        $activityStmt->execute([$activity['activity_id']]);
        $activityPrice = $activityStmt->fetchColumn();

        if ($activityPrice) {
          $totalActivityPrice += $activityPrice;
        }
      }
    }
  }
}

// Calculate total price for meals
if (!empty($selectedMeals)) {
  foreach ($selectedMeals as $day => $periods) {
    foreach ($periods as $period => $meal) {
      if (isset($meal['restaurant_id']) && $meal['restaurant_id'] != 'none') {
        $mealQuery = "SELECT base_price FROM catering WHERE restaurant_id = ?";
        $mealStmt = $conn->prepare($mealQuery);
        $mealStmt->execute([$meal['restaurant_id']]);
        $mealPrice = $mealStmt->fetchColumn();

        if ($mealPrice) {
          $totalMealPrice += $mealPrice;
        }
      }
    }
  }
}

// Get accommodation details and calculate price
$selectedAccommodation = null;
$accommodationPrice = 0;
$accommodationTotalPrice = 0;
$usingDefaultAccommodation = false;

// Check for default accommodation
$defaultAccommodationId = $destination['default_accommodation_id'];
$defaultAccommodation = null;

if ($defaultAccommodationId) {
  $defaultAccommodationQuery = "SELECT * FROM accommodations WHERE accommodation_id = ?";
  $defaultAccommodationStmt = $conn->prepare($defaultAccommodationQuery);
  $defaultAccommodationStmt->execute([$defaultAccommodationId]);
  $defaultAccommodation = $defaultAccommodationStmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_SESSION['selected_accommodation'][$id])) {
  $accommodationId = $_SESSION['selected_accommodation'][$id]['accommodation_id'];

  // If selected is the default, no extra cost
  if ($accommodationId == $defaultAccommodationId) {
    $usingDefaultAccommodation = true;
    $selectedAccommodation = $defaultAccommodation;
    $accommodationPrice = 0;
    $accommodationTotalPrice = 0;
  } else {
    // Get selected accommodation
    $accommodationQuery = "SELECT * FROM accommodations WHERE accommodation_id = ?";
    $accommodationStmt = $conn->prepare($accommodationQuery);
    $accommodationStmt->execute([$accommodationId]);
    $selectedAccommodation = $accommodationStmt->fetch(PDO::FETCH_ASSOC);

    if ($selectedAccommodation) {
      $accommodationPrice = $selectedAccommodation['base_price'];
      $accommodationTotalPrice = $accommodationPrice * ($destination['duration'] - 1);
    }
  }
} else if ($defaultAccommodation) {
  // Use default accommodation if none selected
  $usingDefaultAccommodation = true;
  $selectedAccommodation = $defaultAccommodation;
  $accommodationPrice = 0;
  $accommodationTotalPrice = 0;

  // Store the default accommodation
  $_SESSION['selected_accommodation'][$id]['accommodation_id'] = $defaultAccommodationId;
}

// Calculate prices
$pricePerDay = round($destination['price'] / $destination['duration']);
$totalPrice = $destination['price'] + $totalActivityPrice + $totalMealPrice + $accommodationTotalPrice;

// Handle date selection
$defaultStartDate = date('Y-m-d', strtotime('+7 days'));
$startDate = isset($_GET['temp_start_date']) ? $_GET['temp_start_date'] : $defaultStartDate;
$duration = $destination['duration'];
$endDate = date('Y-m-d', strtotime('+' . ($duration - 1) . ' days', strtotime($startDate)));

// Update dates if form submitted
if (isset($_GET['update_dates'])) {
  header('Location: trip?id=' . $id . '&temp_start_date=' . $startDate);
  exit;
}

// Define destination highlights
$highlights = [
  ['icon' => 'fa-water', 'text' => 'Eaux cristallines'],
  ['icon' => 'fa-mountain', 'text' => 'Paysages naturels'],
  ['icon' => $destination['activity_icon'], 'text' => $destination['activity']],
  ['icon' => 'fa-sun', 'text' => 'Détente et relaxation']
];

// Page title and icon
$icon = '<i class="fa-solid fa-location-dot"></i>';
$title = $destination['title'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="<?php echo htmlspecialchars($destination['title']); ?> - LakEvasion">
  <meta name="keywords" content="LakEvasion, <?php echo htmlspecialchars($destination['title']); ?>, <?php echo htmlspecialchars($destination['country']); ?>, voyage">

  <!-- Fonts and Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://lakevasion.ddns.net/assets/fontawesome/css/all.min.css">

  <!-- CSS -->
  <link rel="stylesheet" href="../assets/styles/global.css">
  <link rel="stylesheet" href="../assets/styles/components/header.css">
  <link rel="stylesheet" href="../assets/styles/components/footer.css">
  <link rel="stylesheet" href="../assets/styles/pages/trip.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - <?php echo htmlspecialchars($destination['title']); ?></title>
</head>

<body>
  <?php require('../components/header.php'); ?>

  <main>
    <!-- Hero section with destination image and basic info -->
    <div class="destination-hero">
      <img src="<?php echo htmlspecialchars($destination['image_path']); ?>" alt="<?php echo htmlspecialchars($destination['title']); ?>">
      <div class="destination-header">
        <h1><?php echo htmlspecialchars($destination['title']); ?></h1>
        <div class="destination-tags">
          <p><i class="fa-solid fa-globe"></i><?php echo htmlspecialchars($destination['country']); ?></p>
          <p><i class="fa-solid fa-hourglass-half"></i><?php echo htmlspecialchars($destination['duration']); ?> jours</p>
          <p><i class="fa-solid <?php echo htmlspecialchars($destination['activity_icon']); ?>"></i><span><?php echo htmlspecialchars($destination['activity']); ?></span>(activité principale)</p>
        </div>
      </div>
    </div>

    <div class="content-container">
      <div class="destination-content">
        <!-- Destination description section -->
        <section class="description-section">
          <h2>À propos de cette destination</h2>
          <p><?php echo nl2br(htmlspecialchars($destination['description_long'] ?? $destination['description'] ?? "Découvrez " . $destination['title'] . " lors d'un séjour inoubliable. Profitez des paysages magnifiques et des activités variées dans cette destination unique.")); ?></p>

          <div class="highlights-grid">
            <?php foreach ($highlights as $highlight): ?>
              <div class="highlight-item">
                <i class="fas <?php echo htmlspecialchars($highlight['icon']); ?>"></i>
                <p><?php echo htmlspecialchars($highlight['text']); ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </section>

        <!-- Trip program section -->
        <section class="program-section">
          <h2>Programme du séjour</h2>

          <!-- Day 1: Arrival -->
          <div class="day-timeline">
            <div class="day-header">
              <h2>Jour 1</h2>
            </div>
            <div class="timeline-box-single">
              <div class="activity-icon">
                <i class="fas fa-plane-arrival"></i>
              </div>
              <h3 class="activity-title">Arrivée</h3>
              <p class="activity-description">Installation et découverte des lieux</p>
            </div>
          </div>

          <!-- Intermediate days: Activities and Meals -->
          <?php for ($i = 2; $i < $duration; $i++): ?>
            <div class="day-timeline">
              <div class="day-header">
                <h2>Jour <?php echo $i; ?></h2>
              </div>

              <div class="timeline-boxes">
                <!-- Breakfast -->
                <div class="timeline-box">
                  <div class="activity-icon">
                    <i class="fas fa-coffee"></i>
                  </div>
                  <h3 class="activity-title">Petit-déjeuner</h3>
                  <div class="activity-time">7h - 9h</div>

                  <?php
                  $breakfastInfo = [
                    'title' => 'Petit-déjeuner à sélectionner',
                    'description' => 'Aucun petit-déjeuner n\'a été sélectionné.',
                    'base_price' => 0,
                    'is_selected' => false
                  ];

                  if (isset($selectedMeals[$i]['breakfast']['restaurant_id'])) {
                    $breakfastRestaurantId = $selectedMeals[$i]['breakfast']['restaurant_id'];

                    if ($breakfastRestaurantId == 'none') {
                      $breakfastInfo = [
                        'title' => 'Pas de petit-déjeuner réservé',
                        'description' => 'Vous êtes libre de choisir où prendre votre petit-déjeuner ce jour-là.',
                        'base_price' => 0,
                        'is_selected' => true
                      ];
                    } else {
                      $restaurantQuery = "SELECT * FROM catering WHERE restaurant_id = ?";
                      $restaurantStmt = $conn->prepare($restaurantQuery);
                      $restaurantStmt->execute([$breakfastRestaurantId]);
                      $breakfastDetails = $restaurantStmt->fetch(PDO::FETCH_ASSOC);

                      if ($breakfastDetails) {
                        $breakfastInfo = $breakfastDetails;
                        $breakfastInfo['is_selected'] = true;
                      }
                    }
                  }
                  ?>

                  <?php if ($breakfastInfo['is_selected']): ?>
                    <div class="selection-status selected">
                      <span class="status-icon"><i class="fas fa-check-circle"></i></span>
                      <span class="status-text"><?php echo htmlspecialchars($breakfastInfo['title']); ?></span>
                    </div>
                    <?php if ($breakfastInfo['base_price'] > 0): ?>
                      <div class="activity-price"><?php echo $breakfastInfo['base_price']; ?>€</div>
                    <?php endif; ?>
                    <a href="selection-restauration?id=<?php echo $id; ?>&day=<?php echo $i; ?>&period=breakfast" class="activity-button edit">
                      <i class="fas fa-edit"></i> Modifier
                    </a>
                  <?php else: ?>
                    <div class="selection-status">
                      <span class="status-icon"><i class="fas fa-circle"></i></span>
                      <span class="status-text">À sélectionner</span>
                    </div>
                    <a href="selection-restauration?id=<?php echo $id; ?>&day=<?php echo $i; ?>&period=breakfast" class="activity-button">
                      <i class="fas fa-plus"></i> Sélectionner
                    </a>
                  <?php endif; ?>
                </div>

                <!-- Morning Activity -->
                <div class="timeline-box">
                  <div class="activity-icon">
                    <?php if (isset($selectedActivities[$i]['morning']['activity_id']) && $selectedActivities[$i]['morning']['activity_id'] != 'none'): ?>
                      <?php
                      $morningActivityId = $selectedActivities[$i]['morning']['activity_id'];
                      $iconQuery = "SELECT activity_icon FROM activities WHERE activity_id = ?";
                      $iconStmt = $conn->prepare($iconQuery);
                      $iconStmt->execute([$morningActivityId]);
                      $activityIcon = $iconStmt->fetchColumn();

                      $iconClass = !empty($activityIcon) ? $activityIcon : 'fa-question';
                      ?>
                      <i class="fas <?php echo htmlspecialchars($iconClass); ?>"></i>
                    <?php elseif (isset($selectedActivities[$i]['morning']['activity_id']) && $selectedActivities[$i]['morning']['activity_id'] == 'none'): ?>
                      <i class="fas fa-coffee"></i>
                    <?php else: ?>
                      <i class="fas fa-question"></i>
                    <?php endif; ?>
                  </div>
                  <h3 class="activity-title">Activité Matin</h3>
                  <div class="activity-time">9h - 12h</div>

                  <?php
                  $morningActivityInfo = [
                    'title' => 'Activité à sélectionner',
                    'description' => 'Aucune activité n\'a été sélectionnée pour ce matin.',
                    'base_price' => 0,
                    'is_selected' => false
                  ];

                  if (isset($selectedActivities[$i]['morning']['activity_id'])) {
                    $morningActivityId = $selectedActivities[$i]['morning']['activity_id'];

                    if ($morningActivityId == 'none') {
                      $morningActivityInfo = [
                        'title' => 'Temps libre',
                        'description' => 'Profitez de cette matinée pour explorer la région à votre rythme.',
                        'base_price' => 0,
                        'is_selected' => true
                      ];
                    } else {
                      $activityQuery = "SELECT * FROM activities WHERE activity_id = ?";
                      $activityStmt = $conn->prepare($activityQuery);
                      $activityStmt->execute([$morningActivityId]);
                      $morningActivityDetails = $activityStmt->fetch(PDO::FETCH_ASSOC);

                      if ($morningActivityDetails) {
                        $morningActivityInfo = $morningActivityDetails;
                        $morningActivityInfo['is_selected'] = true;
                      }
                    }
                  }
                  ?>

                  <?php if ($morningActivityInfo['is_selected']): ?>
                    <div class="selection-status selected">
                      <span class="status-icon"><i class="fas fa-check-circle"></i></span>
                      <span class="status-text"><?php echo htmlspecialchars($morningActivityInfo['title']); ?></span>
                    </div>
                    <?php if ($morningActivityInfo['base_price'] > 0): ?>
                      <div class="activity-price"><?php echo $morningActivityInfo['base_price']; ?>€</div>
                    <?php endif; ?>
                    <a href="selection-activite?id=<?php echo $id; ?>&day=<?php echo $i; ?>&period=morning" class="activity-button edit">
                      <i class="fas fa-edit"></i> Modifier
                    </a>
                  <?php else: ?>
                    <div class="selection-status">
                      <span class="status-icon"><i class="fas fa-circle"></i></span>
                      <span class="status-text">À sélectionner</span>
                    </div>
                    <a href="selection-activite?id=<?php echo $id; ?>&day=<?php echo $i; ?>&period=morning" class="activity-button">
                      <i class="fas fa-plus"></i> Sélectionner
                    </a>
                  <?php endif; ?>
                </div>

                <!-- Lunch -->
                <div class="timeline-box">
                  <div class="activity-icon">
                    <i class="fas fa-utensils"></i>
                  </div>
                  <h3 class="activity-title">Déjeuner</h3>
                  <div class="activity-time">12h - 14h</div>

                  <?php
                  $lunchInfo = [
                    'title' => 'Repas à sélectionner',
                    'description' => 'Aucun restaurant n\'a été sélectionné pour ce déjeuner.',
                    'base_price' => 0,
                    'is_selected' => false
                  ];

                  if (isset($selectedMeals[$i]['morning']['restaurant_id'])) {
                    $lunchRestaurantId = $selectedMeals[$i]['morning']['restaurant_id'];

                    if ($lunchRestaurantId == 'none') {
                      $lunchInfo = [
                        'title' => 'Pas de repas réservé',
                        'description' => 'Vous êtes libre de choisir où déjeuner ce jour-là.',
                        'base_price' => 0,
                        'is_selected' => true
                      ];
                    } else {
                      $restaurantQuery = "SELECT * FROM catering WHERE restaurant_id = ?";
                      $restaurantStmt = $conn->prepare($restaurantQuery);
                      $restaurantStmt->execute([$lunchRestaurantId]);
                      $lunchDetails = $restaurantStmt->fetch(PDO::FETCH_ASSOC);

                      if ($lunchDetails) {
                        $lunchInfo = $lunchDetails;
                        $lunchInfo['is_selected'] = true;
                      }
                    }
                  }
                  ?>

                  <?php if ($lunchInfo['is_selected']): ?>
                    <div class="selection-status selected">
                      <span class="status-icon"><i class="fas fa-check-circle"></i></span>
                      <span class="status-text"><?php echo htmlspecialchars($lunchInfo['title']); ?></span>
                    </div>
                    <?php if ($lunchInfo['base_price'] > 0): ?>
                      <div class="activity-price"><?php echo $lunchInfo['base_price']; ?>€</div>
                    <?php endif; ?>
                    <a href="selection-restauration?id=<?php echo $id; ?>&day=<?php echo $i; ?>&period=morning" class="activity-button edit">
                      <i class="fas fa-edit"></i> Modifier
                    </a>
                  <?php else: ?>
                    <div class="selection-status">
                      <span class="status-icon"><i class="fas fa-circle"></i></span>
                      <span class="status-text">À sélectionner</span>
                    </div>
                    <a href="selection-restauration?id=<?php echo $id; ?>&day=<?php echo $i; ?>&period=morning" class="activity-button">
                      <i class="fas fa-plus"></i> Sélectionner
                    </a>
                  <?php endif; ?>
                </div>

                <!-- Afternoon Activity -->
                <div class="timeline-box">
                  <div class="activity-icon">
                    <?php if (isset($selectedActivities[$i]['evening']['activity_id']) && $selectedActivities[$i]['evening']['activity_id'] != 'none'): ?>
                      <?php
                      $eveningActivityId = $selectedActivities[$i]['evening']['activity_id'];
                      $iconQuery = "SELECT activity_icon FROM activities WHERE activity_id = ?";
                      $iconStmt = $conn->prepare($iconQuery);
                      $iconStmt->execute([$eveningActivityId]);
                      $activityIcon = $iconStmt->fetchColumn();

                      // Use retrieved icon or default
                      $iconClass = !empty($activityIcon) ? $activityIcon : 'fa-question';
                      ?>
                      <i class="fas <?php echo htmlspecialchars($iconClass); ?>"></i>
                    <?php elseif (isset($selectedActivities[$i]['evening']['activity_id']) && $selectedActivities[$i]['evening']['activity_id'] == 'none'): ?>
                      <i class="fas fa-umbrella-beach"></i>
                    <?php else: ?>
                      <i class="fas fa-question"></i>
                    <?php endif; ?>
                  </div>
                  <h3 class="activity-title">Activité Après-midi</h3>
                  <div class="activity-time">14h - 18h</div>

                  <?php
                  $eveningActivityInfo = [
                    'title' => 'Activité à sélectionner',
                    'description' => 'Aucune activité n\'a été sélectionnée pour ce soir.',
                    'base_price' => 0,
                    'is_selected' => false
                  ];

                  if (isset($selectedActivities[$i]['evening']['activity_id'])) {
                    $eveningActivityId = $selectedActivities[$i]['evening']['activity_id'];

                    if ($eveningActivityId == 'none') {
                      $eveningActivityInfo = [
                        'title' => 'Temps libre',
                        'description' => 'Profitez de cette soirée pour explorer la région à votre rythme.',
                        'base_price' => 0,
                        'is_selected' => true
                      ];
                    } else {
                      $activityQuery = "SELECT * FROM activities WHERE activity_id = ?";
                      $activityStmt = $conn->prepare($activityQuery);
                      $activityStmt->execute([$eveningActivityId]);
                      $eveningActivityDetails = $activityStmt->fetch(PDO::FETCH_ASSOC);

                      if ($eveningActivityDetails) {
                        $eveningActivityInfo = $eveningActivityDetails;
                        $eveningActivityInfo['is_selected'] = true;
                      }
                    }
                  }
                  ?>

                  <?php if ($eveningActivityInfo['is_selected']): ?>
                    <div class="selection-status selected">
                      <span class="status-icon"><i class="fas fa-check-circle"></i></span>
                      <span class="status-text"><?php echo htmlspecialchars($eveningActivityInfo['title']); ?></span>
                    </div>
                    <?php if ($eveningActivityInfo['base_price'] > 0): ?>
                      <div class="activity-price"><?php echo $eveningActivityInfo['base_price']; ?>€</div>
                    <?php endif; ?>
                    <a href="selection-activite?id=<?php echo $id; ?>&day=<?php echo $i; ?>&period=evening" class="activity-button edit">
                      <i class="fas fa-edit"></i> Modifier
                    </a>
                  <?php else: ?>
                    <div class="selection-status">
                      <span class="status-icon"><i class="fas fa-circle"></i></span>
                      <span class="status-text">À sélectionner</span>
                    </div>
                    <a href="selection-activite?id=<?php echo $id; ?>&day=<?php echo $i; ?>&period=evening" class="activity-button">
                      <i class="fas fa-plus"></i> Sélectionner
                    </a>
                  <?php endif; ?>
                </div>

                <!-- Dinner -->
                <div class="timeline-box">
                  <div class="activity-icon">
                    <i class="fas fa-utensils"></i>
                  </div>
                  <h3 class="activity-title">Dîner</h3>
                  <div class="activity-time">19h - 21h</div>

                  <?php
                  $dinnerInfo = [
                    'title' => 'Repas à sélectionner',
                    'description' => 'Aucun restaurant n\'a été sélectionné pour ce dîner.',
                    'base_price' => 0,
                    'is_selected' => false
                  ];

                  if (isset($selectedMeals[$i]['evening']['restaurant_id'])) {
                    $dinnerRestaurantId = $selectedMeals[$i]['evening']['restaurant_id'];

                    if ($dinnerRestaurantId == 'none') {
                      $dinnerInfo = [
                        'title' => 'Pas de repas réservé',
                        'description' => 'Vous êtes libre de choisir où dîner ce jour-là.',
                        'base_price' => 0,
                        'is_selected' => true
                      ];
                    } else {
                      $restaurantQuery = "SELECT * FROM catering WHERE restaurant_id = ?";
                      $restaurantStmt = $conn->prepare($restaurantQuery);
                      $restaurantStmt->execute([$dinnerRestaurantId]);
                      $dinnerDetails = $restaurantStmt->fetch(PDO::FETCH_ASSOC);

                      if ($dinnerDetails) {
                        $dinnerInfo = $dinnerDetails;
                        $dinnerInfo['is_selected'] = true;
                      }
                    }
                  }
                  ?>

                  <?php if ($dinnerInfo['is_selected']): ?>
                    <div class="selection-status selected">
                      <span class="status-icon"><i class="fas fa-check-circle"></i></span>
                      <span class="status-text"><?php echo htmlspecialchars($dinnerInfo['title']); ?></span>
                    </div>
                    <?php if ($dinnerInfo['base_price'] > 0): ?>
                      <div class="activity-price"><?php echo $dinnerInfo['base_price']; ?>€</div>
                    <?php endif; ?>
                    <a href="selection-restauration?id=<?php echo $id; ?>&day=<?php echo $i; ?>&period=evening" class="activity-button edit">
                      <i class="fas fa-edit"></i> Modifier
                    </a>
                  <?php else: ?>
                    <div class="selection-status">
                      <span class="status-icon"><i class="fas fa-circle"></i></span>
                      <span class="status-text">À sélectionner</span>
                    </div>
                    <a href="selection-restauration?id=<?php echo $id; ?>&day=<?php echo $i; ?>&period=evening" class="activity-button">
                      <i class="fas fa-plus"></i> Sélectionner
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endfor; ?>

          <!-- Last day: Departure -->
          <div class="day-timeline">
            <div class="day-header">
              <h2>Jour <?php echo $duration; ?></h2>
            </div>
            <div class="timeline-box-single">
              <div class="activity-icon">
                <i class="fas fa-plane-departure"></i>
              </div>
              <h3 class="activity-title">Départ</h3>
              <p class="activity-description">Check-out et voyage retour</p>
            </div>
          </div>
        </section>

        <!-- Date selection section -->
        <section class="dates-section" id="dates-section">
          <h2>Choisir vos dates</h2>
          <form action="" method="get" class="date-selection-form">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="update_dates" value="1">
            <div class="date-selection-container">
              <div class="date-field">
                <label for="temp_start_date">Date de départ</label>
                <input type="date" id="temp_start_date" name="temp_start_date"
                  min="<?php echo date('Y-m-d'); ?>"
                  value="<?php echo $startDate; ?>">
              </div>
              <div class="date-field">
                <label for="end_date">Date de retour</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" readonly>
                <p class="date-info"><i class="fas fa-info-circle"></i> Calculée automatiquement (séjour de <?php echo $duration; ?> jours)</p>
              </div>
            </div>
            <button type="submit" class="update-dates-button">Mettre à jour les dates</button>
          </form>
        </section>

        <!-- Additional information section -->
        <section class="info-section">
          <h2>Informations complémentaires</h2>
          <div class="info-grid">
            <div class="info">
              <i class="fas fa-bed"></i>
              <h3>Hébergement</h3>
              <?php if ($selectedAccommodation): ?>
                <p>
                  <?php echo htmlspecialchars($selectedAccommodation['title']); ?>
                  <?php if ($usingDefaultAccommodation): ?>
                    <span class="default-badge">Inclus</span>
                  <?php endif; ?>
                </p>

                <?php if (!$usingDefaultAccommodation): ?>
                  <p class="accommodation-price">Supplément: <?php echo $accommodationPrice; ?>€ / nuit</p>
                <?php else: ?>
                  <p class="accommodation-price">Inclus dans le prix de base</p>
                <?php endif; ?>

                <a href="selection-hebergement?id=<?php echo $id; ?>" class="modify-activity-btn">
                  <i class="fas fa-edit"></i> Modifier l'hébergement
                </a>
              <?php else: ?>
                <p>Aucun hébergement sélectionné</p>
                <a href="selection-hebergement?id=<?php echo $id; ?>" class="modify-activity-btn">
                  <i class="fas fa-plus"></i> Choisir un hébergement
                </a>
              <?php endif; ?>
            </div>
          </div>
        </section>
      </div>

      <!-- Booking summary card -->
      <div class="booking-card">
        <div class="price-info">
          <span class="price-value"><?php echo htmlspecialchars($pricePerDay); ?>€</span>
          <span class="price-person">/jour</span>
        </div>

        <?php if ($accommodationTotalPrice > 0): ?>
          <div>
            <span>Logement (supplément) : <strong class="activity-price">+<?php echo htmlspecialchars($accommodationTotalPrice); ?>€</strong></span>
          </div>
        <?php endif; ?>

        <?php if ($totalActivityPrice > 0): ?>
          <div>
            <span>Activités : <strong class="activity-price">+<?php echo htmlspecialchars($totalActivityPrice); ?>€</strong></span>
          </div>
        <?php endif; ?>

        <?php if ($totalMealPrice > 0): ?>
          <div>
            <span>Restauration : <strong class="activity-price">+<?php echo htmlspecialchars($totalMealPrice); ?>€</strong></span>
          </div>
        <?php endif; ?>

        <div class="total-price">
          <span>Prix total : <strong><?php echo htmlspecialchars($totalPrice); ?>€</strong></span>
        </div>

        <form action="payment" method="get">
          <input type="hidden" name="destination_id" value="<?php echo $id; ?>">
          <input type="hidden" name="start_date" value="<?php echo $startDate; ?>">
          <input type="hidden" name="end_date" value="<?php echo $endDate; ?>">
          <input type="hidden" name="total_price" value="<?php echo $totalPrice; ?>">
          <button type="submit" class="book-button">Réserver ce séjour</button>
        </form>
      </div>
    </div>
  </main>

  <?php require('../components/footer.php'); ?>
</body>

</html>