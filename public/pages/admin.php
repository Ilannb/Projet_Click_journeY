<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');
require_once(__DIR__ . '/../../app/includes/logout.php');
require_once(__DIR__ . '/../../app/includes/admin_auth.php');

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// AJAX handler for user actions
if (isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
  header('Content-Type: application/json');

  if (!isset($_POST['action']) || !isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit();
  }

  $user_id = $_POST['user_id'];
  $action = $_POST['action'];

  // Simulate processing delay
  sleep(2);

  try {
    switch ($action) {
      case 'promote':
        $stmt = $conn->prepare("UPDATE users SET role = 'vip' WHERE id = ?");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'message' => 'Utilisateur promu en VIP', 'new_role' => 'vip']);
        break;

      case 'demote':
        $stmt = $conn->prepare("UPDATE users SET role = 'user' WHERE id = ?");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'message' => 'Utilisateur rétrogradé', 'new_role' => 'user']);
        break;

      case 'ban':
        $stmt = $conn->prepare("UPDATE users SET role = 'banned' WHERE id = ?");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'message' => 'Utilisateur banni', 'new_role' => 'banned']);
        break;

      case 'unban':
        $stmt = $conn->prepare("UPDATE users SET role = 'user' WHERE id = ?");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'message' => 'Utilisateur débanni', 'new_role' => 'user']);
        break;

      case 'delete':
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé', 'deleted' => true]);
        break;

      default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
  }
  exit();
}

// Pagination management
$users_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $users_per_page;

// Filter management
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = '';

if ($filter !== 'all') {
  $where_clause = "WHERE role = :role";
}

// Research management
$search = isset($_GET['search']) ? $_GET['search'] : '';
if (!empty($search)) {
  if (empty($where_clause)) {
    $where_clause = "WHERE (lastname LIKE :search OR firstname LIKE :search OR email LIKE :search OR phone LIKE :search)";
  } else {
    $where_clause .= " AND (lastname LIKE :search OR firstname LIKE :search OR email LIKE :search OR phone LIKE :search)";
  }
}

// Count the total number of users
$count_query = "SELECT COUNT(*) FROM users $where_clause";
$count_stmt = $conn->prepare($count_query);

if ($filter !== 'all') {
  $count_stmt->bindValue(':role', $filter, PDO::PARAM_STR);
}

if (!empty($search)) {
  $search_param = "%$search%";
  $count_stmt->bindValue(':search', $search_param, PDO::PARAM_STR);
}

$count_stmt->execute();
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $users_per_page);

// Retrieve users with pagination and filters
$query = "SELECT id, lastname, firstname, email, phone, DATE_FORMAT(created_at, '%d/%m/%Y') as formatted_date, role, profile_image
          FROM users $where_clause 
          ORDER BY id ASC 
          LIMIT :offset, :limit";

$stmt = $conn->prepare($query);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $users_per_page, PDO::PARAM_INT);

if ($filter !== 'all') {
  $stmt->bindValue(':role', $filter, PDO::PARAM_STR);
}

if (!empty($search)) {
  $search_param = "%$search%";
  $stmt->bindValue(':search', $search_param, PDO::PARAM_STR);
}

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Title of the page
$icon = '<i class="fa-solid fa-rectangle-list"></i>';
$title = 'Pannel Admin';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <script src="../assets/scripts/theme-init.js"></script>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Page administrateur">
  <meta name="keywords" content="LakEvasion, admin, panel, gestion utilisateur">

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
  <link rel="stylesheet" href="../assets/styles/components/search-input.css">
  <link rel="stylesheet" href="../assets/styles/components/badge.css">
  <link rel="stylesheet" href="../assets/styles/pages/admin.css">
  <link rel="stylesheet" id="theme-style" href="../assets/styles/light-mode.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - Administration</title>
</head>

<body>
  <?php require('../components/header.php'); ?>

  <main>
    <div class="users-section">
      <div class="users-section-header">
        <h1>Gestion des Utilisateurs</h1>
        <div class="search-container">
          <form method="GET" action="<?php echo htmlspecialchars(preg_replace('/\.php$/', '', $_SERVER["PHP_SELF"])); ?>" class="search-form">
            <div class="search-and-filter">
              <div class="search-bar">
                <input type="text" name="search" placeholder="Rechercher un utilisateur..."
                  value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-btn">
                  <i class="fas fa-search"></i>
                </button>
              </div>
              <select name="filter" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Tous les utilisateurs</option>
                <option value="admin" <?php echo $filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="vip" <?php echo $filter === 'vip' ? 'selected' : ''; ?>>VIP</option>
                <option value="user" <?php echo $filter === 'user' ? 'selected' : ''; ?>>Standard</option>
                <option value="banned" <?php echo $filter === 'banned' ? 'selected' : ''; ?>>Bannis</option>
              </select>
            </div>
          </form>
        </div>
      </div>

      <div class="users-table">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Photo</th>
              <th>Nom</th>
              <th>Prénom</th>
              <th>Email</th>
              <th>Téléphone</th>
              <th>Date d'inscription</th>
              <th>Statut</th>
              <th>Actions</th>
              <th>Voir</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="10" class="no-results">Aucun utilisateur trouvé</td>
              </tr>
            <?php else: ?>
              <?php foreach ($users as $user): ?>
                <tr data-user-id="<?php echo $user['id']; ?>">
                  <td>
                    #<?php echo htmlspecialchars($user['id']); ?>
                  </td>
                  <td class="profile-image">
                    <?php if (!empty($user['profile_image'])): ?>
                      <div class="default-avatar"><img src="../<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Photo de profil" class="user-avatar"></div>
                    <?php else: ?>
                      <div class="default-avatar"><i class="fa-solid fa-user"></i></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($user['lastname']); ?>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($user['firstname']); ?>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($user['email']); ?>
                  </td>
                  <td>
                    <?php if (!empty($user['phone'])): ?>
                      <?php echo htmlspecialchars($user['phone']); ?>
                    <?php else: ?>
                      <span class="not-provided">non renseigné</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($user['formatted_date']); ?>
                  </td>
                  <td>
                    <span class="status <?php echo htmlspecialchars(strtolower($user['role'])); ?>" data-role="<?php echo htmlspecialchars($user['role']); ?>">
                      <?php
                      switch ($user['role']) {
                        case 'admin':
                          echo 'Admin';
                          break;
                        case 'vip':
                          echo 'VIP';
                          break;
                        case 'user':
                          echo '';
                          break;
                        case 'banned':
                          echo 'Banni';
                          break;
                        default:
                          echo htmlspecialchars($user['role']);
                      }
                      ?>
                    </span>
                  </td>
                  <td class="actions-cell">
                    <div class="action-buttons" data-user-id="<?php echo $user['id']; ?>">
                      <?php if ($user['role'] !== 'banned'): ?>
                        <?php if ($user['role'] === 'user'): ?>
                          <button type="button" class="action-btn promote ajax-action" data-action="promote" data-user-id="<?php echo $user['id']; ?>" title="Promouvoir">
                            <i class="fas fa-crown"></i>
                          </button>
                        <?php elseif ($user['role'] === 'vip'): ?>
                          <button type="button" class="action-btn demote ajax-action" data-action="demote" data-user-id="<?php echo $user['id']; ?>" title="Rétrograder">
                            <i class="fas fa-level-down-alt"></i>
                          </button>
                        <?php endif; ?>

                        <button type="button" class="action-btn ban ajax-action" data-action="ban" data-user-id="<?php echo $user['id']; ?>" title="Bannir">
                          <i class="fas fa-ban"></i>
                        </button>
                      <?php else: ?>
                        <button type="button" class="action-btn unban ajax-action" data-action="unban" data-user-id="<?php echo $user['id']; ?>" title="Débannir">
                          <i class="fas fa-unlock"></i>
                        </button>
                      <?php endif; ?>

                      <button type="button" class="action-btn delete ajax-action" data-action="delete" data-user-id="<?php echo $user['id']; ?>" title="Supprimer">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                  <td class="history-cell">
                    <a href="user?id=<?php echo $user['id']; ?>" class="action-btn history" title="Voir">
                      <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <?php if ($current_page > 1): ?>
          <a href="?page=<?php echo $current_page - 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"
            class="page-btn">
            <i class="fas fa-chevron-left"></i>
          </a>
        <?php else: ?>
          <button class="page-btn" disabled><i class="fas fa-chevron-left"></i></button>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"
            class="page-btn <?php echo $i === $current_page ? 'active' : ''; ?>">
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages): ?>
          <a href="?page=<?php echo $current_page + 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"
            class="page-btn">
            <i class="fas fa-chevron-right"></i>
          </a>
        <?php else: ?>
          <button class="page-btn" disabled><i class="fas fa-chevron-right"></i></button>
        <?php endif; ?>
      </div>
    </div>
  </main>
</body>

<script type="text/javascript" src="../assets/scripts/admin.js"></script>

</html>