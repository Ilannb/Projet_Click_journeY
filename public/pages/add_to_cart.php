<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
  // Redirect to login page
  header("Location: login");
  exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: destinations");
  exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get form data
$destination_id = isset($_POST['destination_id']) ? $_POST['destination_id'] : null;
$title = isset($_POST['title']) ? $_POST['title'] : null;
$destination_image = isset($_POST['destination_image']) ? $_POST['destination_image'] : null;
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;
$price = isset($_POST['price']) ? $_POST['price'] : null;

// Validate required data
if (!$destination_id || !$title || !$destination_image || !$start_date || !$end_date || !$price) {
  $_SESSION['error_message'] = "Données manquantes pour ajouter l'article au panier.";
  header("Location: trip?id=" . $destination_id);
  exit;
}

try {
  // Check if the same trip is already in the cart
  $check_stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = :user_id AND destination_id = :destination_id AND start_date = :start_date AND end_date = :end_date");
  $check_stmt->bindParam(':user_id', $user_id);
  $check_stmt->bindParam(':destination_id', $destination_id);
  $check_stmt->bindParam(':start_date', $start_date);
  $check_stmt->bindParam(':end_date', $end_date);
  $check_stmt->execute();

  if ($check_stmt->rowCount() > 0) {
    // Trip already in cart
    $_SESSION['success_message'] = "Ce voyage est déjà dans votre panier.";
    header("Location: cart");
    exit;
  }

  // Add to cart (only basic trip information)
  $insert_stmt = $conn->prepare("
    INSERT INTO cart (user_id, destination_id, title, destination_image, start_date, end_date, price, added_at)
    VALUES (:user_id, :destination_id, :title, :destination_image, :start_date, :end_date, :price, NOW())
  ");

  $insert_stmt->bindParam(':user_id', $user_id);
  $insert_stmt->bindParam(':destination_id', $destination_id);
  $insert_stmt->bindParam(':title', $title);
  $insert_stmt->bindParam(':destination_image', $destination_image);
  $insert_stmt->bindParam(':start_date', $start_date);
  $insert_stmt->bindParam(':end_date', $end_date);
  $insert_stmt->bindParam(':price', $price);

  $insert_stmt->execute();

  // Set success message
  $_SESSION['success_message'] = "Voyage ajouté au panier avec succès.";

  // Redirect to cart page
  header("Location: cart");
  exit;
} catch (PDOException $e) {
  // Handle database error
  $_SESSION['error_message'] = "Erreur lors de l'ajout au panier: " . $e->getMessage();
  header("Location: trip?id=" . $destination_id);
  exit;
}
