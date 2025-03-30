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
  header('Location: /'); // Change : show error message
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

// Prepare CYBank payment data
$transaction_id = 'TX' . substr(md5(time() . rand(1000, 9999)), 0, 18);

// Apply VIP discount if applicable
$original_amount = (float)$destination['price'];
$amount = $original_amount;

// Store discount information
$discount_applied = false;
$discount_percentage = 0;
$discount_amount = 0;

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
  'discount_percentage' => $discount_percentage
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
            <img src="../assets/src/img/annecy.jpg" alt="Lac d'Annecy">
            <div class="trip-info">
              <h3>Lac d'Annecy</h3>
              <div class="info-item country-box">
                <i class="fa-solid fa-globe"></i>
                <p class="country">France</p>
              </div>
              <div class="info-item duration-box">
                <i class="fa-solid fa-hourglass-half"></i>
                <p class="duration"><span class="trip-duration">3</span> days</p>
              </div>
              <div class="info-item activity-box">
                <i class="fa-solid fa-sailboat"></i>
                <p class="activity">Navigation</p>
              </div>
            </div>
            <div class="options-container">
              <h3>Selected Options</h3>
              <div class="options-grid">
                <?php if ($start_date && $end_date): ?>
                  <div class="option-item">
                    <i class="fa-solid fa-calendar-days"></i>
                    <p>Du <?php echo date('d/m/Y', strtotime($start_date)); ?> au <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                  </div>
                <?php endif; ?>
                <div class="option-item">
                  <i class="fa-solid fa-utensils"></i>
                  <p>Full Board</p>
                </div>
                <div class="option-item">
                  <i class="fa-solid fa-house"></i>
                  <p>Luxury Villa</p>
                </div>
                <div class="option-item">
                  <i class="fa-solid fa-fish"></i>
                  <p>Fishing and Relaxation</p>
                </div>
                <div class="option-item">
                  <i class="fa-solid fa-ship"></i>
                  <p>Boat Excursions</p>
                </div>
                <div class="option-item">
                  <i class="fa-solid fa-person-swimming"></i>
                  <p>Water Sports</p>
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
            <p>Number of days</p>
            <p>5</p>
          </div>
          <div class="price-item">
            <p>Price per day</p>
            <p>248€</p>
          </div>
          <div class="price-item">
            <p>Fishing and Relaxation Activity</p>
            <p>35€</p>
          </div>
          <div class="price-item">
            <p>Boat Excursions Activity</p>
            <p>50€</p>
          </div>
          <div class="price-item">
            <p>Water Sports</p>
            <p>30€</p>
          </div>
          <div class="price-item">
            <p>Gourmet Restaurant</p>
            <p>5 days x 35€</p>
          </div>
          <div class="price-item">
            <p>Luxury Villa</p>
            <p>5 days x 190€</p>
          </div>
          <?php if ($discount_applied): ?>
            <div class="price-item discount">
              <p>VIP Discount (<?php echo $discount_percentage; ?>%)</p>
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