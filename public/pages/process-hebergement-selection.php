<?php
session_start();

// Check if the necessary data is sent
if (!isset($_POST['destination_id']) || !isset($_POST['accommodation_id'])) {
  header('Location: destinations');
  exit;
}

$destination_id = $_POST['destination_id'];
$accommodation_id = $_POST['accommodation_id'];

// Initialize the array of selected accommodations if necessary
if (!isset($_SESSION['selected_accommodation'])) {
  $_SESSION['selected_accommodation'] = [];
}

// Save the selected accommodation for this destination
$_SESSION['selected_accommodation'][$destination_id] = [
  'accommodation_id' => $accommodation_id,
  'selected_at' => date('Y-m-d H:i:s')
];

// Redirect to the destination page with the selected accommodation
header("Location: trip?id=$destination_id");
exit;
