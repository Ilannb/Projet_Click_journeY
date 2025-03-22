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

// Get user information
$user_id = $_SESSION['user_id'];
$error = null;
$success = null;

// Create upload directory if it doesn't exist
$upload_dir = __DIR__ . '/../assets/uploads/profiles/';
if (!file_exists($upload_dir)) {
  mkdir($upload_dir, 0755, true);
}

// Fetch user data from database
try {
  $stmt = $conn->prepare("SELECT id, lastname, firstname, email, password, role, created_at, phone, profile_image FROM users WHERE id = :id");
  $stmt->bindParam(':id', $user_id);
  $stmt->execute();
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $error = "Error retrieving data: " . $e->getMessage();
}

// Form processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
  // Update last name
  elseif (isset($_POST['update_lastname'])) {
    $new_lastname = trim($_POST['lastname']);
    if (!empty($new_lastname)) {
      $update_stmt = $conn->prepare("UPDATE users SET lastname = :lastname WHERE id = :id");
      $update_stmt->bindParam(':lastname', $new_lastname);
      $update_stmt->bindParam(':id', $user_id);
      $update_stmt->execute();
      $user['lastname'] = $new_lastname;
      $_SESSION['user_lastname'] = $new_lastname;
      $success = "Nom mis à jour avec succès";
    } else {
      $error = "Le nom ne peut pas être vide";
    }
  }
  // Update first name
  elseif (isset($_POST['update_firstname'])) {
    $new_firstname = trim($_POST['firstname']);
    if (!empty($new_firstname)) {
      $update_stmt = $conn->prepare("UPDATE users SET firstname = :firstname WHERE id = :id");
      $update_stmt->bindParam(':firstname', $new_firstname);
      $update_stmt->bindParam(':id', $user_id);
      $update_stmt->execute();
      $user['firstname'] = $new_firstname;
      $_SESSION['user_firstname'] = $new_firstname;
      $success = "Prénom mis à jour avec succès";
    } else {
      $error = "Le prénom ne peut pas être vide";
    }
  }
  // Update email
  elseif (isset($_POST['update_email'])) {
    $new_email = trim($_POST['email']);
    if (empty($new_email)) {
      $error = "L'email ne peut pas être vide";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
      $error = "Format d'email invalide";
    } else {
      // Check if email already exists
      $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
      $check_stmt->bindParam(':email', $new_email);
      $check_stmt->bindParam(':id', $user_id);
      $check_stmt->execute();

      if ($check_stmt->rowCount() > 0) {
        $error = "Cette adresse email est déjà utilisée";
      } else {
        $update_stmt = $conn->prepare("UPDATE users SET email = :email WHERE id = :id");
        $update_stmt->bindParam(':email', $new_email);
        $update_stmt->bindParam(':id', $user_id);
        $update_stmt->execute();
        $user['email'] = $new_email;
        $_SESSION['user_email'] = $new_email;
        $success = "Email mis à jour avec succès";
      }
    }
  }
  // Update phone number
  elseif (isset($_POST['update_phone'])) {
    $new_phone = trim($_POST['phone']);
    if (!empty($new_phone) && !preg_match('/^[+]?[0-9\s]{10,15}$/', $new_phone)) {
      $error = "Format de numéro de téléphone invalide";
    } else {
      $update_stmt = $conn->prepare("UPDATE users SET phone = :phone WHERE id = :id");
      $update_stmt->bindParam(':phone', $new_phone);
      $update_stmt->bindParam(':id', $user_id);
      $update_stmt->execute();
      $user['phone'] = $new_phone;
      $success = "Numéro de téléphone mis à jour avec succès";
    }
  }
  // Update password
  elseif (isset($_POST['update_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!password_verify($current_password, $user['password'])) {
      $error = "Le mot de passe actuel est incorrect";
    } elseif ($new_password !== $confirm_password) {
      $error = "Les nouveaux mots de passe ne correspondent pas";
    } else {
      // Check password complexity
      $password_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&\-])[A-Za-z\d@$!%*?&\-]{8,}$/';
      if (!preg_match($password_regex, $new_password)) {
        $error = "Le mot de passe ne répond pas aux exigences de sécurité";
      } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
        $update_stmt->bindParam(':password', $hashed_password);
        $update_stmt->bindParam(':id', $user_id);
        $update_stmt->execute();
        $success = "Mot de passe mis à jour avec succès";
      }
    }
  }

  // Refresh user data after update
  if ($success) {
    $stmt = $conn->prepare("SELECT id, lastname, firstname, email, password, role, created_at, phone, profile_image FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
  }
}

// Title of the page
$icon = '<i class="fa-solid fa-address-card"></i>';
$title = 'Mon Profil';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
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

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - Mon profil</title>
</head>

<body>
  <?php require('../components/header.php'); ?>

  <main>
    <section class="personal-info-section">
      <div class="personal-info-header">
        <h2>Informations Personnelles</h2>
        <?php if ($user['role'] === 'vip'): ?>
          <div class="definition">
            <p>* <span class="status vip">VIP</span> : Le status VIP donne des avantages et des réductions sur les prix</p>
          </div>
        <?php endif; ?>
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

            <?php if ($user['role'] === 'vip'): ?>
              <span class="status vip">VIP</span>
            <?php endif; ?>
          </form>
        </div>

        <!-- User Information Grid -->
        <div class="info-grid">
          <!-- Last Name Field -->
          <div class="detail-group">
            <p>Nom</p>
            <div class="editable-field" id="lastname-field">
              <p><?php echo htmlspecialchars($user['lastname']); ?></p>
              <button class="edit-btn" onclick="toggleEditForm('lastname')">
                <i class="fas fa-pen"></i>
              </button>
            </div>
            <form action="" method="post" class="edit-form" id="lastname-form" style="display: none;">
              <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
              <div class="form-buttons">
                <button type="submit" name="update_lastname" class="save-btn"><i class="fas fa-check"></i></button>
                <button type="button" class="cancel-btn" onclick="toggleEditForm('lastname')"><i class="fas fa-times"></i></button>
              </div>
            </form>
          </div>

          <!-- First Name Field -->
          <div class="detail-group">
            <p>Prénom</p>
            <div class="editable-field" id="firstname-field">
              <p><?php echo htmlspecialchars($user['firstname']); ?></p>
              <button class="edit-btn" onclick="toggleEditForm('firstname')">
                <i class="fas fa-pen"></i>
              </button>
            </div>
            <form action="" method="post" class="edit-form" id="firstname-form" style="display: none;">
              <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
              <div class="form-buttons">
                <button type="submit" name="update_firstname" class="save-btn"><i class="fas fa-check"></i></button>
                <button type="button" class="cancel-btn" onclick="toggleEditForm('firstname')"><i class="fas fa-times"></i></button>
              </div>
            </form>
          </div>

          <!-- Email Field -->
          <div class="detail-group">
            <p>Email</p>
            <div class="editable-field" id="email-field">
              <p><?php echo htmlspecialchars($user['email']); ?></p>
              <button class="edit-btn" onclick="toggleEditForm('email')">
                <i class="fas fa-pen"></i>
              </button>
            </div>
            <form action="" method="post" class="edit-form" id="email-form" style="display: none;">
              <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
              <div class="form-buttons">
                <button type="submit" name="update_email" class="save-btn"><i class="fas fa-check"></i></button>
                <button type="button" class="cancel-btn" onclick="toggleEditForm('email')"><i class="fas fa-times"></i></button>
              </div>
            </form>
          </div>

          <!-- Phone Field -->
          <div class="detail-group">
            <p>Téléphone</p>
            <div class="editable-field" id="phone-field">
              <p><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Non renseigné'; ?></p>
              <button class="edit-btn" onclick="toggleEditForm('phone')">
                <i class="fas fa-pen"></i>
              </button>
            </div>
            <form action="" method="post" class="edit-form" id="phone-form" style="display: none;">
              <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+33 1 23 45 67 89">
              <div class="form-buttons">
                <button type="submit" name="update_phone" class="save-btn"><i class="fas fa-check"></i></button>
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
            <form action="" method="post" class="edit-form" id="password-form" style="display: none;">
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
                  <li>Au moins 8 caractères</li>
                  <li>Une lettre majuscule</li>
                  <li>Une lettre minuscule</li>
                  <li>Un chiffre</li>
                  <li>Un caractère spécial</li>
                </ul>
              </div>
              <div class="form-buttons">
                <button type="submit" name="update_password" class="save-btn"><i class="fas fa-check"></i></button>
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
        $order_by = "start_date DESC";

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
          $trips_stmt = $conn->prepare("SELECT * FROM trips WHERE user_id = :user_id ORDER BY " . $order_by);
          $trips_stmt->bindParam(':user_id', $user_id);
          $trips_stmt->execute();
          $trips = $trips_stmt->fetchAll(PDO::FETCH_ASSOC);

          if (count($trips) > 0) {
            foreach ($trips as $trip) {
              // Format date range
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

              // Translate status
              $status_text = '';
              $status_class = '';
              switch ($trip['status']) {
                case 'upcoming':
                  $status_text = 'À venir';
                  $status_class = 'upcoming';
                  break;
                case 'completed':
                  $status_text = 'Terminé';
                  $status_class = 'completed';
                  break;
                case 'cancelled':
                  $status_text = 'Annulé';
                  $status_class = 'cancelled';
                  break;
              }
        ?>

              <div class="travel-row">
                <p class="ref-col">#<?php echo htmlspecialchars($trip['id']); ?></p>
                <div class="dest-col">
                  <img src="../<?php echo htmlspecialchars($trip['destination_image']); ?>" alt="<?php echo htmlspecialchars($trip['destination']); ?>">
                  <p><?php echo htmlspecialchars($trip['destination']); ?></p>
                </div>
                <p class="date-col"><?php echo $date_range; ?></p>
                <p class="price-col"><?php echo number_format($trip['price'], 0, ',', ' '); ?>€</p>
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

  <script>
    // Function to toggle edit forms visibility
    function toggleEditForm(fieldName) {
      const field = document.getElementById(fieldName + '-field');
      const form = document.getElementById(fieldName + '-form');

      if (field.style.display === 'none') {
        field.style.display = 'flex';
        form.style.display = 'none';
      } else {
        field.style.display = 'none';
        form.style.display = 'block';
      }
    }
  </script>
</body>

</html>