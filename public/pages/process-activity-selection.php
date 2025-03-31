<?php
session_start();

// Check if the necessary data is sent
if (!isset($_POST['destination_id']) || !isset($_POST['day']) || !isset($_POST['activity_id']) || !isset($_POST['period'])) {
  header('Location: destinations');
  exit;
}

$destination_id = $_POST['destination_id'];
$day = intval($_POST['day']);
$activity_id = $_POST['activity_id'];
$period = $_POST['period']; // Get the period (morning/evening)

// Initialize the array of selected activities if necessary
if (!isset($_SESSION['selected_activities'])) {
  $_SESSION['selected_activities'] = [];
}

if (!isset($_SESSION['selected_activities'][$destination_id])) {
  $_SESSION['selected_activities'][$destination_id] = [];
}

if (!isset($_SESSION['selected_activities'][$destination_id][$day])) {
  $_SESSION['selected_activities'][$destination_id][$day] = [];
}

// Save the selected activity for this day and period
$_SESSION['selected_activities'][$destination_id][$day][$period] = [
  'activity_id' => $activity_id,
  'selected_at' => date('Y-m-d H:i:s')
];

// Redirect to the destination page with the selected activities
header("Location: trip?id=$destination_id");
exit;
