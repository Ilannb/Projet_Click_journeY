<?php
session_start();
require_once '../../app/config/database.php';

// Check if necessary data is sent
if (!isset($_POST['destination_id']) || !isset($_POST['transport_id'])) {
  header('Location: destinations');
  exit;
}

$destination_id = $_POST['destination_id'];
$transport_id = $_POST['transport_id'];

// Get the default transport for this destination
$defaultTransportId = null;
$query = "SELECT default_transport_id FROM destinations WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$destination_id]);
$defaultTransportId = $stmt->fetchColumn();

// Initialize the selected transports array if needed
if (!isset($_SESSION['selected_transport'])) {
  $_SESSION['selected_transport'] = [];
}

// Save the selected transport for this destination
$_SESSION['selected_transport'][$destination_id] = [
  'transport_id' => $transport_id,
  'selected_at' => date('Y-m-d H:i:s')
];

// Redirect to the destination page with the selected transport
header("Location: trip?id=$destination_id");
exit;
