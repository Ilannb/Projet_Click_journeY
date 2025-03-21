<?php
// app/includes/admin_auth.php

// Check if the user is logged in and has the administrator role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: /");
  exit;
}
