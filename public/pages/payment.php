<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');
require_once(__DIR__ . '/../../app/includes/logout.php');
require_once(__DIR__ . '/../../app/includes/getapikey.php');

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
  header('Location: login');
  exit;
}

// Add this section to check user role
$user_id = $_SESSION['user_id'];
$role_query = "SELECT role FROM users WHERE id = ?";
$role_stmt = $conn->prepare($role_query);
$role_stmt->execute([$user_id]);
$user_role = $role_stmt->fetchColumn();

// Redirect banned users
if ($user_role === 'banned') {
  header('Location: error'); // Change : show error message
  exit;
}

// Get destination ID from URL
if (!isset($_GET['destination_id']) || empty($_GET['destination_id'])) {
  header('Location: destinations');
  exit;
}

$destination_id = $_GET['destination_id'];

// Get date parameters from URL
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Fetch destination details
$query = "SELECT * FROM destinations WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$destination_id]);
$destination = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if destination exists
if (!$destination) {
  header('Location: destinations');
  exit;
}

// Check for payment error message
$payment_error = isset($_SESSION['payment_error']) ? $_SESSION['payment_error'] : null;
unset($_SESSION['payment_error']);

// Get selected activities, meals and accommodation from session
$selectedActivities = isset($_SESSION['selected_activities'][$destination_id]) ? $_SESSION['selected_activities'][$destination_id] : [];
$selectedMeals = isset($_SESSION['selected_meals'][$destination_id]) ? $_SESSION['selected_meals'][$destination_id] : [];

// Calculate activities total and count duplicates
$activitiesTotal = 0;
$activityCounts = [];
foreach ($selectedActivities as $day => $dayActivities) {
  foreach ($dayActivities as $period => $activity) {
    if (isset($activity['activity_id']) && $activity['activity_id'] != 'none') {
      $activityQuery = "SELECT title, base_price FROM activities WHERE activity_id = ?";
      $activityStmt = $conn->prepare($activityQuery);
      $activityStmt->execute([$activity['activity_id']]);
      $activityDetails = $activityStmt->fetch(PDO::FETCH_ASSOC);

      if ($activityDetails) {
        $activitiesTotal += $activityDetails['base_price'];

        // Count occurrences of each activity
        if (!isset($activityCounts[$activity['activity_id']])) {
          $activityCounts[$activity['activity_id']] = [
            'count' => 1,
            'title' => $activityDetails['title'],
            'price' => $activityDetails['base_price']
          ];
        } else {
          $activityCounts[$activity['activity_id']]['count']++;
        }
      }
    }
  }
}

// Calculate meals total and count duplicates
$mealsTotal = 0;
$mealCounts = [];
foreach ($selectedMeals as $day => $dayMeals) {
  foreach ($dayMeals as $period => $meal) {
    if (isset($meal['restaurant_id']) && $meal['restaurant_id'] != 'none') {
      $mealQuery = "SELECT title, base_price FROM catering WHERE restaurant_id = ?";
      $mealStmt = $conn->prepare($mealQuery);
      $mealStmt->execute([$meal['restaurant_id']]);
      $mealDetails = $mealStmt->fetch(PDO::FETCH_ASSOC);

      if ($mealDetails) {
        $mealsTotal += $mealDetails['base_price'];

        // Count occurrences of each meal
        if (!isset($mealCounts[$meal['restaurant_id']])) {
          $mealCounts[$meal['restaurant_id']] = [
            'count' => 1,
            'title' => $mealDetails['title'],
            'price' => $mealDetails['base_price']
          ];
        } else {
          $mealCounts[$meal['restaurant_id']]['count']++;
        }
      }
    }
  }
}

// Calculate accommodation total
$accommodationTotal = 0;
$selectedAccommodation = null;
if (isset($_SESSION['selected_accommodation'][$destination_id])) {
  $accommodationId = $_SESSION['selected_accommodation'][$destination_id]['accommodation_id'];

  // Get accommodation details
  $accommodationQuery = "SELECT * FROM accommodations WHERE accommodation_id = ?";
  $accommodationStmt = $conn->prepare($accommodationQuery);
  $accommodationStmt->execute([$accommodationId]);
  $selectedAccommodation = $accommodationStmt->fetch(PDO::FETCH_ASSOC);

  // If it's not the default accommodation
  if ($selectedAccommodation && $accommodationId != $destination['default_accommodation_id']) {
    $accommodationPrice = $selectedAccommodation['base_price'];

    if ($accommodationPrice) {
      // Multiply by number of nights
      $accommodationTotal = $accommodationPrice * ($destination['duration'] - 1);
    }
  }
}

// Prepare CYBank payment data
$transaction_id = 'TX' . substr(md5(time() . rand(1000, 9999)), 0, 18);

// Calculate total amount
$original_amount = (float)$destination['price'] + $activitiesTotal + $mealsTotal + $accommodationTotal;
$amount = $original_amount;

// Store discount information
$discount_applied = false;
$discount_percentage = 0;
$discount_amount = 0;

// Apply VIP discount if applicable
if ($user_role === 'vip') {
  $discount_percentage = 20;
  $discount_amount = $original_amount * ($discount_percentage / 100);
  $amount = $original_amount - $discount_amount;
  $discount_applied = true;
}

$amount = number_format($amount, 2, '.', '');
$vendor = 'MI-5_I';
$return_url = 'https://lakevasion.ddns.net/pages/payment_return?destination_id=' . $destination_id;

// Get API key
$api_key = getAPIKey($vendor);
if ($api_key === "zzzz") {
  die("Invalid vendor code: " . $vendor);
}

// Generate control hash
$control = md5($api_key . "#" . $transaction_id . "#" . $amount . "#" . $vendor . "#" . $return_url . "#");

// Store transaction info in session for later verification
$_SESSION['payment_transaction'] = [
  'transaction_id' => $transaction_id,
  'amount' => $amount,
  'destination_id' => $destination_id,
  'user_role' => $user_role,
  'discount_applied' => $discount_applied,
  'discount_percentage' => $discount_percentage,
  'activities_total' => $activitiesTotal,
  'meals_total' => $mealsTotal,
  'accommodation_total' => $accommodationTotal
];

// Store dates in session for later use
if ($start_date && $end_date) {
  $_SESSION['payment_transaction']['start_date'] = $start_date;
  $_SESSION['payment_transaction']['end_date'] = $end_date;
}

// Title of the page
$icon = '<i class="fa-solid fa-wallet"></i>';
$title = 'Paiement';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Payment page">
  <meta name="keywords" content="LakEvasion, payment, reservation">

  <!-- Roboto Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://lakevasion.ddns.net/assets/fontawesome/css/all.min.css">

  <!-- CSS -->
  <link rel="stylesheet" href="../assets/styles/global.css">
  <link rel="stylesheet" href="../assets/styles/components/header.css">
  <link rel="stylesheet" href="../assets/styles/components/footer.css">
  <link rel="stylesheet" href="../assets/styles/pages/payment.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - Payment</title>
</head>

<body>
  <?php require('../components/header.php'); ?>

  <main>
    <div class="payment-container">
      <div class="payment-content">
        <section class="trip-summary">
          <div class="back-link">
            <a href="trip?id=<?php echo htmlspecialchars($destination_id); ?>"><i class="fas fa-arrow-left"></i>Retour en arrière</a>
          </div>
          <h2>Résumé du voyage</h2>
          <div class="trip-details">
            <div class="trip-main-info">
              <img src="<?php echo htmlspecialchars($destination['image_path']); ?>" alt="<?php echo htmlspecialchars($destination['title']); ?>">
              <div class="trip-info">
                <h3><?php echo htmlspecialchars($destination['title']); ?></h3>
                <div class="info-item country-box">
                  <i class="fa-solid fa-globe"></i>
                  <p class="country"><?php echo htmlspecialchars($destination['country']); ?></p>
                </div>
                <div class="info-item duration-box">
                  <i class="fa-solid fa-hourglass-half"></i>
                  <p class="duration"><span class="trip-duration"><?php echo htmlspecialchars($destination['duration']); ?></span> jours</p>
                </div>
                <div class="info-item activity-box">
                  <i class="fa-solid <?php echo htmlspecialchars($destination['activity_icon']); ?>"></i>
                  <p class="activity"><?php echo htmlspecialchars($destination['activity']); ?></p>
                </div>

                <?php if ($start_date && $end_date): ?>
                  <div class="info-item date-box">
                    <i class="fa-solid fa-calendar-days"></i>
                    <p>Du <?php echo date('d/m/Y', strtotime($start_date)); ?> au <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="options-container">
              <h3>Options sélectionnées</h3>
              <div class="options-grid">
                <?php if ($selectedAccommodation): ?>
                  <div class="option-item">
                    <i class="fa-solid fa-house"></i>
                    <p><?php echo htmlspecialchars($selectedAccommodation['title']); ?></p>
                  </div>
                <?php endif; ?>

                <?php
                // Display selected activities with count
                foreach ($activityCounts as $activityId => $activity) {
                  $activityQuery = "SELECT activity_icon FROM activities WHERE activity_id = ?";
                  $activityStmt = $conn->prepare($activityQuery);
                  $activityStmt->execute([$activityId]);
                  $activityIcon = $activityStmt->fetchColumn();

                  $icon = !empty($activityIcon) ? $activityIcon : 'fa-person-hiking';
                  echo '<div class="option-item">';
                  echo '<i class="fa-solid ' . htmlspecialchars($icon) . '"></i>';
                  echo '<p>' . htmlspecialchars($activity['title']);
                  if ($activity['count'] > 1) {
                    echo ' <span class="option-count">' . $activity['count'] . '</span>';
                  }
                  echo '</p>';
                  echo '</div>';
                }

                // Display selected meals/restaurants with count
                foreach ($mealCounts as $mealId => $meal) {
                  echo '<div class="option-item">';
                  echo '<i class="fa-solid fa-utensils"></i>';
                  echo '<p>' . htmlspecialchars($meal['title']);
                  if ($meal['count'] > 1) {
                    echo ' <span class="option-count">' . $meal['count'] . '</span>';
                  }
                  echo '</p>';
                  echo '</div>';
                }

                // If no activities or meals were selected, show a generic message
                if (empty($activityCounts) && empty($mealCounts) && !$selectedAccommodation) {
                  echo '<div class="option-item">';
                  echo '<i class="fa-solid fa-circle-info"></i>';
                  echo '<p>Voyage de base sans options supplémentaires</p>';
                  echo '</div>';
                }
                ?>
              </div>
            </div>
          </div>
        </section>

        <section class="payment-form">
          <h2>Paiement 100% Sécurisé</h2>

          <!-- Trust Section -->
          <div class="payment-trust">
            <div class="trust-features-box">
              <div class="trust-feature">
                <i class="fa-solid fa-lock"></i>
                <h3>Transactions Cryptées</h3>
                <p>Toutes vos données sont cryptées avec un protocole sécurisé</p>
              </div>
              <div class="trust-feature">
                <i class="fa-solid fa-credit-card"></i>
                <h3>Paiement Sécurisé</h3>
                <p>Vos données bancaires ne sont jamais stockées sur nos serveurs</p>
              </div>
            </div>
          </div>

          <?php if ($payment_error): ?>
            <div class="payment-error-container">
              <div class="payment-error">
                <i class="fa-solid fa-exclamation-circle"></i>
                <p><?php echo htmlspecialchars($payment_error); ?></p>
              </div>
            </div>
          <?php endif; ?>

          <!-- CYBank Form -->
          <form id="payment-form" action="https://www.plateforme-smc.fr/cybank/index.php" method="POST">
            <input type='hidden' name='transaction' value='<?php echo htmlspecialchars($transaction_id); ?>'>
            <input type='hidden' name='montant' value='<?php echo htmlspecialchars($amount); ?>'>
            <input type='hidden' name='vendeur' value='<?php echo htmlspecialchars($vendor); ?>'>
            <input type='hidden' name='retour' value='<?php echo htmlspecialchars($return_url); ?>'>
            <input type='hidden' name='control' value='<?php echo htmlspecialchars($control); ?>'>

            <div class="payment-actions">
              <button type="submit" class="pay-button">Payer
                <i class="fa-solid fa-arrow-right"></i>
              </button>
            </div>
          </form>
        </section>
      </div>

      <div class="payment-summary">
        <h2>Détails du prix</h2>
        <div class="price-breakdown">
          <div class="price-item">
            <p>Nombre de jours</p>
            <p><?php echo $destination['duration']; ?></p>
          </div>

          <?php
          // Calculate price per day
          $pricePerDay = round($destination['price'] / $destination['duration']);
          ?>
          <div class="price-item">
            <p>Prix de base par jour</p>
            <p><?php echo $pricePerDay; ?>€</p>
          </div>

          <?php if ($selectedAccommodation): ?>
            <div class="price-item">
              <p><?php echo htmlspecialchars($selectedAccommodation['title']); ?></p>
              <?php if ($accommodationTotal > 0): ?>
                <p><?php echo ($destination['duration'] - 1); ?> nuits x <?php echo $selectedAccommodation['base_price']; ?>€</p>
              <?php else: ?>
                <p>Inclus</p>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php
          // Display activities with prices and count
          foreach ($activityCounts as $activityId => $activity) {
            echo '<div class="price-item">';
            echo '<p>' . htmlspecialchars($activity['title']);
            if ($activity['count'] > 1) {
              echo ' x' . $activity['count'];
            }
            echo '</p>';
            echo '<p>' . ($activity['price'] * $activity['count']) . '€</p>';
            echo '</div>';
          }

          // Display meals with prices and count
          foreach ($mealCounts as $mealId => $meal) {
            echo '<div class="price-item">';
            echo '<p>' . htmlspecialchars($meal['title']);
            if ($meal['count'] > 1) {
              echo ' x' . $meal['count'];
            }
            echo '</p>';
            echo '<p>' . ($meal['price'] * $meal['count']) . '€</p>';
            echo '</div>';
          }
          ?>

          <?php if ($discount_applied): ?>
            <div class="price-item discount">
              <p>Réduction VIP (<?php echo $discount_percentage; ?>%)</p>
              <p>-<?php echo number_format($discount_amount, 2); ?>€</p>
            </div>
          <?php endif; ?>

          <div class="price-total-box">
            <p>Total</p>
            <div class="price-total">
              <?php if ($discount_applied): ?>
                <del><?php echo number_format($original_amount, 2); ?>€</del>
              <?php endif; ?>
              <p><?php echo number_format($amount, 2); ?>€</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php require('../components/footer.php'); ?>
</body>

</html>