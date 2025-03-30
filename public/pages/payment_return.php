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

      // Execute the insert query
      $query = "INSERT INTO reservations ($columns) VALUES ($values)";
      $stmt = $conn->prepare($query);
      $success = $stmt->execute($params);

      if ($success) {
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