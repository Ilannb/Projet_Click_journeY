<?php
session_start();

// Check if the necessary data is sent
if (!isset($_POST['destination_id']) || !isset($_POST['day']) || !isset($_POST['period']) || !isset($_POST['restaurant_id'])) {
  header('Location: destinations');
  exit;
}

$destination_id = $_POST['destination_id'];
$day = $_POST['day'];
$period = $_POST['period']; // 'breakfast', 'morning' or 'evening'
$restaurant_id = $_POST['restaurant_id'];

// Initialize the array of selected meals if necessary
if (!isset($_SESSION['selected_meals'])) {
  $_SESSION['selected_meals'] = [];
}

// Initialize the sub-array for this destination if necessary
if (!isset($_SESSION['selected_meals'][$destination_id])) {
  $_SESSION['selected_meals'][$destination_id] = [];
}

// Initialize the sub-array for this day if necessary
if (!isset($_SESSION['selected_meals'][$destination_id][$day])) {
  $_SESSION['selected_meals'][$destination_id][$day] = [];
}

// If "none" is selected, delete the entry if it exists
if ($restaurant_id === 'none') {
  if (isset($_SESSION['selected_meals'][$destination_id][$day][$period])) {
    unset($_SESSION['selected_meals'][$destination_id][$day][$period]);
  }
} else {
  // Save the selected meal for this destination, day and period
  $_SESSION['selected_meals'][$destination_id][$day][$period] = [
    'restaurant_id' => $restaurant_id,
    'selected_at' => date('Y-m-d H:i:s')
  ];
}

// Clean up empty days
if (empty($_SESSION['selected_meals'][$destination_id][$day])) {
  unset($_SESSION['selected_meals'][$destination_id][$day]);
}

// Redirect to the trip page
header("Location: trip?id=$destination_id");
exit;
