<?php
// app/includes/destinations.php
require_once(__DIR__ . '/../config/database.php');

function getRandomDestinations($limit = 3)
{
  global $conn;

  // Count the total number of destinations
  $countQuery = "SELECT COUNT(*) as total FROM destinations";
  $stmt = $conn->query($countQuery);
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $totalDestinations = $result['total'];

  // If 3 or less destinations, we return them all
  if ($totalDestinations <= $limit) {
    $query = "SELECT * FROM destinations";
    $stmt = $conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Otherwise, take 3 random
  $query = "SELECT * FROM destinations ORDER BY RAND() LIMIT :limit";
  $stmt = $conn->prepare($query);
  $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
  $stmt->execute();

  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}