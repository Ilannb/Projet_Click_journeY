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

// Check if all required return parameters are present
$requiredParams = ['transaction', 'montant', 'vendeur', 'status', 'control'];
$missingParams = false;

foreach ($requiredParams as $param) {
  if (!isset($_GET[$param])) {
    $missingParams = true;
    break;
  }
}

if ($missingParams) {
  $_SESSION['payment_error'] = "Informations de paiement incomplètes.";
  header('Location: destinations');
  exit;
}

// Extract and sanitize parameters
$transaction = htmlspecialchars($_GET['transaction']);
$amount = htmlspecialchars($_GET['montant']);
$vendor = htmlspecialchars($_GET['vendeur']);
$status = htmlspecialchars($_GET['status']);
$control = htmlspecialchars($_GET['control']);
$destination_id = isset($_GET['destination_id']) ? (int)$_GET['destination_id'] : null;

// Get API key for the vendor
$api_key = getAPIKey($vendor);

// Verify control hash
$expected_control = md5($api_key . "#" . $transaction . "#" . $amount . "#" . $vendor . "#" . $status . "#");

if ($control !== $expected_control) {
  $_SESSION['payment_error'] = "Erreur de validation de paiement.";
  header('Location: destinations');
  exit;
}

// Process the payment
if ($status === 'accepted') {
  // Payment accepted, record the reservation in database
  $user_id = $_SESSION['user_id'];

  if ($destination_id) {
    // Retrieve destination details
    $query = "SELECT * FROM destinations WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$destination_id]);
    $destination_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($destination_data) {
      // Get the column structure of reservations table
      $table_query = "SHOW COLUMNS FROM reservations";
      $table_stmt = $conn->prepare($table_query);
      $table_stmt->execute();
      $columns_data = $table_stmt->fetchAll(PDO::FETCH_COLUMN);

      // Initialize the base query
      $columns = "user_id, destination_id, transaction_id, amount, status, created_at";
      $values = "?, ?, ?, ?, 'confirmed', NOW()";
      $params = [$user_id, $destination_id, $transaction, $amount];

      // Retrieve travel dates from session
      $start_date = isset($_SESSION['payment_transaction']['start_date']) ? $_SESSION['payment_transaction']['start_date'] : null;
      $end_date = isset($_SESSION['payment_transaction']['end_date']) ? $_SESSION['payment_transaction']['end_date'] : null;

      // Add travel dates to the query
      if ($start_date) {
        $columns .= ", start_date";
        $values .= ", ?";
        $params[] = $start_date;
      }

      if ($end_date) {
        $columns .= ", end_date";
        $values .= ", ?";
        $params[] = $end_date;
      }

      // Retrieve detailed amounts from the session
      $base_price = $destination_data['price'] ?? 0;
      $activities_total = isset($_SESSION['payment_transaction']['activities_total']) ? $_SESSION['payment_transaction']['activities_total'] : 0;
      $meals_total = isset($_SESSION['payment_transaction']['meals_total']) ? $_SESSION['payment_transaction']['meals_total'] : 0;
      $accommodation_total = isset($_SESSION['payment_transaction']['accommodation_total']) ? $_SESSION['payment_transaction']['accommodation_total'] : 0;
      $discount_applied = isset($_SESSION['payment_transaction']['discount_applied']) ? $_SESSION['payment_transaction']['discount_applied'] : false;
      $discount_percentage = isset($_SESSION['payment_transaction']['discount_percentage']) ? $_SESSION['payment_transaction']['discount_percentage'] : 0;

      // Calculer le montant total avant réduction
      $original_amount = $base_price + $activities_total + $meals_total + $accommodation_total;

      // Ajouter ces informations à la requête d'insertion
      if (in_array('base_price', $columns_data)) {
        $columns .= ", base_price";
        $values .= ", ?";
        $params[] = $base_price;
      }

      if (in_array('activities_total', $columns_data)) {
        $columns .= ", activities_total";
        $values .= ", ?";
        $params[] = $activities_total;
      }

      if (in_array('meals_total', $columns_data)) {
        $columns .= ", meals_total";
        $values .= ", ?";
        $params[] = $meals_total;
      }

      if (in_array('accommodation_total', $columns_data)) {
        $columns .= ", accommodation_total";
        $values .= ", ?";
        $params[] = $accommodation_total;
      }

      if (in_array('original_amount', $columns_data)) {
        $columns .= ", original_amount";
        $values .= ", ?";
        $params[] = $original_amount;
      }

      if (in_array('discount_applied', $columns_data)) {
        $columns .= ", discount_applied";
        $values .= ", ?";
        $params[] = $discount_applied ? 1 : 0;
      }

      if (in_array('discount_percentage', $columns_data)) {
        $columns .= ", discount_percentage";
        $values .= ", ?";
        $params[] = $discount_percentage;
      }

      // Handles different column naming conventions between tables
      $field_mappings = [
        'title' => ['title', 'name', 'destination_name'],
        'image_path' => ['image_path', 'image', 'destination_image'],
        'duration' => ['duration', 'stay_duration'],
        'country' => ['country'],
        'activity' => ['activity'],
        'price' => ['price'],
      ];

      // Check which fields exist and add them to the query
      foreach ($field_mappings as $dest_field => $possible_res_fields) {
        if (isset($destination_data[$dest_field])) {
          // Try each possible column name in the reservations table
          foreach ($possible_res_fields as $res_field) {
            if (in_array($res_field, $columns_data)) {
              $columns .= ", $res_field";
              $values .= ", ?";
              $params[] = $destination_data[$dest_field];
              break;
            }
          }
        }
      }

      // Store selected activities details
      if (isset($_SESSION['selected_activities'][$destination_id]) && in_array('activities_details', $columns_data)) {
        $activities_details = json_encode($_SESSION['selected_activities'][$destination_id]);
        $columns .= ", activities_details";
        $values .= ", ?";
        $params[] = $activities_details;
      }

      // Store selected meals details
      if (isset($_SESSION['selected_meals'][$destination_id]) && in_array('meals_details', $columns_data)) {
        $meals_details = json_encode($_SESSION['selected_meals'][$destination_id]);
        $columns .= ", meals_details";
        $values .= ", ?";
        $params[] = $meals_details;
      }

      // Store selected accommodation details
      if (isset($_SESSION['selected_accommodation'][$destination_id]) && in_array('accommodation_details', $columns_data)) {
        $accommodation_details = json_encode($_SESSION['selected_accommodation'][$destination_id]);
        $columns .= ", accommodation_details";
        $values .= ", ?";
        $params[] = $accommodation_details;
      }

      // Execute the insert query
      $query = "INSERT INTO reservations ($columns) VALUES ($values)";
      $stmt = $conn->prepare($query);
      $success = $stmt->execute($params);

      if ($success) {
        // Clean up session data
        unset($_SESSION['selected_activities'][$destination_id]);
        unset($_SESSION['selected_meals'][$destination_id]);
        unset($_SESSION['selected_accommodation'][$destination_id]);
        unset($_SESSION['payment_transaction']);

        $_SESSION['payment_success'] = "Votre réservation a été confirmée. Merci pour votre confiance !";
      } else {
        $_SESSION['payment_error'] = "Erreur lors de l'enregistrement de la réservation. Veuillez contacter le support.";
      }
    } else {
      $_SESSION['payment_error'] = "Destination non trouvée.";
    }
  }

  // Redirect to confirmation page
  header('Location: confirmation');
} else {
  // Payment declined
  $_SESSION['payment_error'] = "Le paiement a été refusé. Veuillez réessayer ou contacter notre service client.";
  header('Location: declined?destination_id=' . $destination_id);
  exit;
}
