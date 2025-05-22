<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');
require_once(__DIR__ . '/../../app/includes/logout.php');

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login");
  exit;
}

// Check if viewing another user's profile
$viewing_other_user = false;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  // Check if current user is admin
  $admin_check = $conn->prepare("SELECT role FROM users WHERE id = :id");
  $admin_check->bindParam(':id', $_SESSION['user_id']);
  $admin_check->execute();
  $user_role = $admin_check->fetchColumn();

  // If admin, allow viewing other user's profile
  if ($user_role === 'admin') {
    $user_id = $_GET['id'];
    $viewing_other_user = true;
  } else {
    // Not admin, redirect to homepage
    header("Location: /");
    exit;
  }
} else {
  // No ID in URL, use the session user ID
  $user_id = $_SESSION['user_id'];
}

// Set variables for messages
$error = null;
$success = null;

// Create upload directory if it doesn't exist
$upload_dir = __DIR__ . '/../assets/uploads/profiles/';
if (!file_exists($upload_dir)) {
  mkdir($upload_dir, 0755, true);
}

// AJAX handler for profile updates
if (isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
  header('Content-Type: application/json');
  if ($viewing_other_user) {
    $admin_check = $conn->prepare("SELECT role FROM users WHERE id = :id");
    $admin_check->bindParam(':id', $_SESSION['user_id']);
    $admin_check->execute();
    $user_role = $admin_check->fetchColumn();

    if ($user_role !== 'admin') {
      echo json_encode(['success' => false, 'message' => 'Non autorisé']);
      exit();
    }
  }

  // Simulate processing delay
  sleep(2);

  try {
    $action = $_POST['action'] ?? '';

    switch ($action) {
      case 'update_lastname':
        $new_lastname = trim($_POST['lastname']);
        if (empty($new_lastname)) {
          echo json_encode(['success' => false, 'message' => 'Le nom ne peut pas être vide']);
          exit();
        }

        $update_stmt = $conn->prepare("UPDATE users SET lastname = :lastname WHERE id = :id");
        $update_stmt->bindParam(':lastname', $new_lastname);
        $update_stmt->bindParam(':id', $user_id);
        $update_stmt->execute();

        if (!$viewing_other_user) {
          $_SESSION['user_lastname'] = $new_lastname;
        }

        echo json_encode(['success' => true, 'message' => 'Nom mis à jour avec succès', 'value' => $new_lastname]);
        break;

      case 'update_firstname':
        $new_firstname = trim($_POST['firstname']);
        if (empty($new_firstname)) {
          echo json_encode(['success' => false, 'message' => 'Le prénom ne peut pas être vide']);
          exit();
        }

        $update_stmt = $conn->prepare("UPDATE users SET firstname = :firstname WHERE id = :id");
        $update_stmt->bindParam(':firstname', $new_firstname);
        $update_stmt->bindParam(':id', $user_id);
        $update_stmt->execute();

        if (!$viewing_other_user) {
          $_SESSION['user_firstname'] = $new_firstname;
        }

        echo json_encode(['success' => true, 'message' => 'Prénom mis à jour avec succès', 'value' => $new_firstname]);
        break;

      case 'update_email':
        $new_email = trim($_POST['email']);
        if (empty($new_email)) {
          echo json_encode(['success' => false, 'message' => 'L\'email ne peut pas être vide']);
          exit();
        }
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
          echo json_encode(['success' => false, 'message' => 'Format d\'email invalide']);
          exit();
        }

        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $check_stmt->bindParam(':email', $new_email);
        $check_stmt->bindParam(':id', $user_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
          echo json_encode(['success' => false, 'message' => 'Cette adresse email est déjà utilisée']);
          exit();
        }

        $update_stmt = $conn->prepare("UPDATE users SET email = :email WHERE id = :id");
        $update_stmt->bindParam(':email', $new_email);
        $update_stmt->bindParam(':id', $user_id);
        $update_stmt->execute();

        if (!$viewing_other_user) {
          $_SESSION['user_email'] = $new_email;
        }

        echo json_encode(['success' => true, 'message' => 'Email mis à jour avec succès', 'value' => $new_email]);
        break;

      case 'update_phone':
        $new_phone = trim($_POST['phone']);
        if (!empty($new_phone) && !preg_match('/^[+]?[0-9\s]{10,15}$/', $new_phone)) {
          echo json_encode(['success' => false, 'message' => 'Format de numéro de téléphone invalide']);
          exit();
        }

        $update_stmt = $conn->prepare("UPDATE users SET phone = :phone WHERE id = :id");
        $update_stmt->bindParam(':phone', $new_phone);
        $update_stmt->bindParam(':id', $user_id);
        $update_stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Numéro de téléphone mis à jour avec succès', 'value' => $new_phone ?: 'Non renseigné']);
        break;

      case 'update_password':
        // Get current user password
        $user_stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
        $user_stmt->bindParam(':id', $user_id);
        $user_stmt->execute();
        $current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

        $current_password = trim($_POST['current_password']);
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        if (!password_verify($current_password, $current_user['password'])) {
          echo json_encode(['success' => false, 'message' => 'Le mot de passe actuel est incorrect']);
          exit();
        }
        if ($new_password !== $confirm_password) {
          echo json_encode(['success' => false, 'message' => 'Les nouveaux mots de passe ne correspondent pas']);
          exit();
        }

        // Check password complexity
        $password_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&\-])[A-Za-z\d@$!%*?&\-]{8,}$/';
        if (!preg_match($password_regex, $new_password)) {
          echo json_encode(['success' => false, 'message' => 'Le mot de passe ne répond pas aux exigences de sécurité']);
          exit();
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
        $update_stmt->bindParam(':password', $hashed_password);
        $update_stmt->bindParam(':id', $user_id);
        $update_stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Mot de passe mis à jour avec succès']);
        break;

      default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
  }
  exit();
}

// Fetch user data from database
try {
  $stmt = $conn->prepare("SELECT id, lastname, firstname, email, password, role, created_at, phone, profile_image FROM users WHERE id = :id");
  $stmt->bindParam(':id', $user_id);
  $stmt->execute();

  if ($stmt->rowCount() === 0) {
    // User not found
    header("Location: /");
    exit;
  }

  $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $error = "Error retrieving data: " . $e->getMessage();
}

// Form processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (!isset($_POST['ajax']) || $_POST['ajax'] !== 'true')) {
  if ($viewing_other_user) {
    // Verify if the user is admin
    $admin_check = $conn->prepare("SELECT role FROM users WHERE id = :id");
    $admin_check->bindParam(':id', $_SESSION['user_id']);
    $admin_check->execute();
    $user_role = $admin_check->fetchColumn();

    if ($user_role !== 'admin') {
      // Redirect if not admin
      header("Location: /");
      exit;
    }
  }

  // Update profile image
  if (isset($_POST['update_profile_image']) && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];

    if ($file['error'] === 0) {
      $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
      if (in_array($file['type'], $allowed_types)) {
        if ($file['size'] <= 3 * 1024 * 1024) {
          $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
          $new_filename = $user_id . '_' . time() . '.' . $file_extension;
          $upload_path = $upload_dir . $new_filename;

          // Delete previous image if exists
          if ($user['profile_image'] && file_exists(__DIR__ . '/../' . $user['profile_image'])) {
            unlink(__DIR__ . '/../' . $user['profile_image']);
          }

          if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $relative_path = 'assets/uploads/profiles/' . $new_filename;
            $update_stmt = $conn->prepare("UPDATE users SET profile_image = :profile_image WHERE id = :id");
            $update_stmt->bindParam(':profile_image', $relative_path);
            $update_stmt->bindParam(':id', $user_id);
            $update_stmt->execute();
            $user['profile_image'] = $relative_path;
            $success = "Photo de profil mise à jour avec succès";
          } else {
            $error = "Erreur lors du téléchargement de l'image";
          }
        } else {
          $error = "La taille du fichier ne doit pas dépasser 3 Mo";
        }
      } else {
        $error = "Format non supporté. Utilisez JPG, PNG ou GIF";
      }
    }
  }
}

// Title of the page
$icon = '<i class="fa-solid fa-address-card"></i>';
$title = 'Profil de ' . $user['firstname'] . ' ' . $user['lastname'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <script src="/public/assets/scripts/theme-init.js"></script>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Page utilisateur de LakEvasion">
  <meta name="keywords" content="LakEvation, information, historique, voyages, modification">

  <!-- Roboto Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://lakevasion.ddns.net/assets/fontawesome/css/all.min.css">

  <!-- CSS -->
  <link rel="stylesheet" href="../assets/styles/global.css">
  <link rel="stylesheet" href="../assets/styles/components/header.css">
  <link rel="stylesheet" href="../assets/styles/components/footer.css">
  <link rel="stylesheet" href="../assets/styles/components/badge.css">
  <link rel="stylesheet" href="../assets/styles/pages/user.css">
  <link rel="stylesheet" id="theme-style" href="assets/styles/light-mode.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - <?php echo htmlspecialchars($title); ?></title>
</head>

<body>
  <?php require('../components/header.php'); ?>

  <main>
    <?php if ($viewing_other_user): ?>
      <div class="admin-controls">
        <a href="admin" class="back-btn"><i class="fas fa-arrow-left"></i> Retour au panel admin</a>
      </div>
    <?php endif; ?>
    <section class="personal-info-section">
      <div class="personal-info-header">
        <h2>Informations Personnelles</h2>
        <div class="user-status">
          <?php if ($user['role'] === 'vip'): ?>
            <span class="status vip">VIP</span>
          <?php elseif ($user['role'] === 'admin'): ?>
            <span class="status admin">ADMIN</span>
          <?php elseif ($user['role'] === 'banned'): ?>
            <span class="status banned">BANNED</span>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="error-message">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="success-message">
          <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
      <?php endif; ?>

      <div class="info-container">
        <!-- Profile Image Section -->
        <div class="profile-image-container">
          <form action="" method="post" enctype="multipart/form-data" id="profile-image-form">
            <?php if ($user['profile_image'] && file_exists(__DIR__ . '/../' . $user['profile_image'])): ?>
              <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Photo de profil" class="profile-image">
            <?php else: ?>
              <div class="default-avatar"><i class="fa-solid fa-user"></i></div>
            <?php endif; ?>

            <label for="profile-image-upload" class="edit-image-btn">
              <i class="fas fa-camera"></i>
            </label>
            <input type="file" id="profile-image-upload" name="profile_image" accept="image/jpeg,image/png,image/gif" style="display:none" onchange="this.form.submit()">
            <input type="hidden" name="update_profile_image" value="1">
          </form>
        </div>

        <!-- User Information Grid -->
        <div class="info-grid">
          <!-- Last Name Field -->
          <div class="detail-group">
            <p>Nom</p>
            <div class="editable-field" id="lastname-field">
              <p id="lastname-display"><?php echo htmlspecialchars($user['lastname']); ?></p>
              <?php if (!$viewing_other_user || $_SESSION['user_role'] === 'admin'): ?>
                <button class="edit-btn" onclick="toggleEditForm('lastname')">
                  <i class="fas fa-pen"></i>
                </button>
              <?php endif; ?>
            </div>
            <form class="edit-form ajax-form" id="lastname-form" style="display: none;" data-field="lastname">
              <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
              <div class="form-buttons">
                <button type="submit" class="save-btn"><i class="fas fa-check"></i></button>
                <button type="button" class="cancel-btn" onclick="toggleEditForm('lastname')"><i class="fas fa-times"></i></button>
              </div>
            </form>
          </div>

          <!-- First Name Field -->
          <div class="detail-group">
            <p>Prénom</p>
            <div class="editable-field" id="firstname-field">
              <p id="firstname-display"><?php echo htmlspecialchars($user['firstname']); ?></p>
              <button class="edit-btn" onclick="toggleEditForm('firstname')">
                <i class="fas fa-pen"></i>
              </button>
            </div>
            <form class="edit-form ajax-form" id="firstname-form" style="display: none;" data-field="firstname">
              <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
              <div class="form-buttons">
                <button type="submit" class="save-btn"><i class="fas fa-check"></i></button>
                <button type="button" class="cancel-btn" onclick="toggleEditForm('firstname')"><i class="fas fa-times"></i></button>
              </div>
            </form>
          </div>

          <!-- Email Field -->
          <div class="detail-group">
            <p>Email</p>
            <div class="editable-field" id="email-field">
              <p id="email-display"><?php echo htmlspecialchars($user['email']); ?></p>
              <button class="edit-btn" onclick="toggleEditForm('email')">
                <i class="fas fa-pen"></i>
              </button>
            </div>
            <form class="edit-form ajax-form" id="email-form" style="display: none;" data-field="email">
              <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
              <div class="form-buttons">
                <button type="submit" class="save-btn"><i class="fas fa-check"></i></button>
                <button type="button" class="cancel-btn" onclick="toggleEditForm('email')"><i class="fas fa-times"></i></button>
              </div>
            </form>
          </div>

          <!-- Phone Field -->
          <div class="detail-group">
            <p>Téléphone</p>
            <div class="editable-field" id="phone-field">
              <p id="phone-display"><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Non renseigné'; ?></p>
              <button class="edit-btn" onclick="toggleEditForm('phone')">
                <i class="fas fa-pen"></i>
              </button>
            </div>
            <form class="edit-form ajax-form" id="phone-form" style="display: none;" data-field="phone">
              <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+33 1 23 45 67 89">
              <div class="form-buttons">
                <button type="submit" class="save-btn"><i class="fas fa-check"></i></button>
                <button type="button" class="cancel-btn" onclick="toggleEditForm('phone')"><i class="fas fa-times"></i></button>
              </div>
            </form>
          </div>

          <!-- Password Field -->
          <div class="detail-group">
            <p>Mot de passe</p>
            <div class="editable-field" id="password-field">
              <p>••••••••••</p>
              <button class="edit-btn" onclick="toggleEditForm('password')">
                <i class="fas fa-pen"></i>
              </button>
            </div>
            <form class="edit-form ajax-form" id="password-form" style="display: none;" data-field="password">
              <!-- Current Password Input -->
              <div class="password-input-container">
                <input type="password" name="current_password" id="current_password" placeholder="Mot de passe actuel" required>
                <button type="button" class="password-toggle">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <!-- New Password Input -->
              <div class="password-input-container">
                <input type="password" name="new_password" id="new_password" placeholder="Nouveau mot de passe" required>
                <button type="button" class="password-toggle">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <!-- Confirm Password Input -->
              <div class="password-input-container">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirmez le mot de passe" required>
                <button type="button" class="password-toggle">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <!-- Password Requirements Info -->
              <div class="password-requirements">
                <p>Le mot de passe doit contenir :</p>
                <ul>
                  <li><i class="fas fa-xmark"></i> Au moins 8 caractères</li>
                  <li><i class="fas fa-xmark"></i> Une lettre majuscule</li>
                  <li><i class="fas fa-xmark"></i> Une lettre minuscule</li>
                  <li><i class="fas fa-xmark"></i> Un chiffre</li>
                  <li><i class="fas fa-xmark"></i> Un caractère spécial</li>
                </ul>
              </div>
              <div class="form-buttons">
                <button type="submit" class="save-btn"><i class="fas fa-check"></i></button>
                <button type="button" class="cancel-btn" onclick="toggleEditForm('password')"><i class="fas fa-times"></i></button>
              </div>
            </form>
          </div>

          <!-- Registration Date Field -->
          <div class="detail-group">
            <p>Date d'inscription</p>
            <div class="non-editable-field">
              <p><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="travel-history-section">
      <div class="history-section-header">
        <h2>Historique des Voyages</h2>
        <div class="sort-container">
          <label for="travel-sort">Trier par :</label>
          <select id="travel-sort" class="sort-select" onchange="window.location.href='?sort='+this.value">
            <option value="date-desc" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'date-desc') ? 'selected' : ''; ?>>Date (Plus récent)</option>
            <option value="date-asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'date-asc') ? 'selected' : ''; ?>>Date (Plus ancien)</option>
            <option value="price-desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-desc') ? 'selected' : ''; ?>>Prix (Plus élevé)</option>
            <option value="price-asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-asc') ? 'selected' : ''; ?>>Prix (Plus bas)</option>
            <option value="status-asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'status-asc') ? 'selected' : ''; ?>>Statut</option>
          </select>
        </div>
      </div>

      <div class="travel-list">
        <div class="travel-header">
          <p>Référence</p>
          <p>Destination</p>
          <p>Date</p>
          <p>Prix</p>
          <p>Statut</p>
          <p>Actions</p>
        </div>

        <?php
        // Fetch user trips
        $order_by = "created_at DESC";

        if (isset($_GET['sort'])) {
          switch ($_GET['sort']) {
            case 'date-asc':
              $order_by = "start_date ASC";
              break;
            case 'date-desc':
              $order_by = "start_date DESC";
              break;
            case 'price-asc':
              $order_by = "price ASC";
              break;
            case 'price-desc':
              $order_by = "price DESC";
              break;
            case 'status-asc':
              $order_by = "status ASC";
              break;
          }
        }

        try {
          // Query reservations
          $trips_stmt = $conn->prepare("SELECT * FROM reservations WHERE user_id = :user_id ORDER BY " . $order_by);
          $trips_stmt->bindParam(':user_id', $user_id);
          $trips_stmt->execute();
          $trips = $trips_stmt->fetchAll(PDO::FETCH_ASSOC);

          if (count($trips) > 0) {
            foreach ($trips as $trip) {
              $start_date = new DateTime($trip['start_date']);
              $end_date = new DateTime($trip['end_date']);
              $date_range = $start_date->format('d') . '-' . $end_date->format('d') . ' ' . $end_date->format('F Y');

              // Convert month name to French
              $months_fr = [
                'January' => 'Janvier',
                'February' => 'Février',
                'March' => 'Mars',
                'April' => 'Avril',
                'May' => 'Mai',
                'June' => 'Juin',
                'July' => 'Juillet',
                'August' => 'Août',
                'September' => 'Septembre',
                'October' => 'Octobre',
                'November' => 'Novembre',
                'December' => 'Décembre'
              ];

              foreach ($months_fr as $en => $fr) {
                $date_range = str_replace($en, $fr, $date_range);
              }

              $status_text = '';
              $status_class = '';
              switch ($trip['status']) {
                case 'cancelled':
                  $status_text = 'Annulé';
                  $status_class = 'cancelled';
                  break;
                case 'confirmed':
                  $today = new DateTime();
                  if ($end_date < $today) {
                    $status_text = 'Terminé';
                    $status_class = 'completed';
                  } else {
                    $status_text = 'À venir';
                    $status_class = 'upcoming';
                  }
                  break;
              }
        ?>

              <div class="travel-row">
                <p class="ref-col">#<?php echo htmlspecialchars($trip['id']); ?></p>
                <div class="dest-col">
                  <img src="<?php echo htmlspecialchars($trip['destination_image']); ?>" alt="<?php echo htmlspecialchars($trip['title']); ?>">
                  <p><?php echo htmlspecialchars($trip['title']); ?></p>
                </div>
                <p class="date-col"><?php echo $date_range; ?></p>
                <p class="price-col">
                  <?php
                  $displayed_price = isset($trip['amount']) ? $trip['amount'] : $trip['price'];
                  echo number_format($displayed_price, 2, ',', ' ') . '€';
                  ?>
                </p>
                <p class="travel-status <?php echo $status_class; ?>"><?php echo $status_text; ?></p>
                <div class="actions-col">
                  <a href="trip?id=<?php echo $trip['destination_id']; ?>" class="view-trip-btn" title="Voir">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                  </a>
                </div>
              </div>

        <?php
            }
          } else {
            echo '<div class="no-trips">Aucun voyage trouvé</div>';
          }
        } catch (PDOException $e) {
          echo '<div class="error-message">Erreur lors de la récupération des voyages: ' . $e->getMessage() . '</div>';
        }
        ?>
      </div>
    </section>
  </main>

  <?php require('../components/footer.php'); ?>
</body>

<script src="../assets/scripts/password_check.js"></script>
<script src="../assets/scripts/user.js"></script>

</html>