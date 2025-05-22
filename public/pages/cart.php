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

$user_id = $_SESSION['user_id'];

// Set variables for messages
$error = null;
$success = null;

if (isset($_SESSION['success_message'])) {
  $success = $_SESSION['success_message'];
  unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
  $error = $_SESSION['error_message'];
  unset($_SESSION['error_message']);
}

// Process cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Remove item from cart
  if (isset($_POST['remove_item']) && isset($_POST['cart_id'])) {
    $cart_id = $_POST['cart_id'];

    try {
      $remove_stmt = $conn->prepare("DELETE FROM cart WHERE id = :id AND user_id = :user_id");
      $remove_stmt->bindParam(':id', $cart_id);
      $remove_stmt->bindParam(':user_id', $user_id);
      $remove_stmt->execute();

      $success = "Article retiré du panier avec succès";
    } catch (PDOException $e) {
      $error = "Erreur lors de la suppression de l'article: " . $e->getMessage();
    }
  }
}

// Title of the page
$icon = '<i class="fa-solid fa-shopping-cart"></i>';
$title = 'Mon Panier';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <script src="../assets/scripts/theme-init.js"></script>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Panier d'achats de LakEvasion">
  <meta name="keywords" content="LakEvation, panier, voyages, réservation">

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
  <link rel="stylesheet" href="../assets/styles/pages/cart.css">
  <link rel="stylesheet" id="theme-style" href="../assets/styles/light-mode.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - <?php echo htmlspecialchars($title); ?></title>
</head>

<body>
  <?php require('../components/header.php'); ?>

  <main>
    <section class="cart-section">
      <div class="cart-section-header">
        <h2>Mon Panier</h2>
        <div class="sort-container">
          <label for="cart-sort">Trier par :</label>
          <select id="cart-sort" class="sort-select" onchange="window.location.href='?sort='+this.value">
            <option value="date-desc" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'date-desc') ? 'selected' : ''; ?>>Date (Plus récent)</option>
            <option value="date-asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'date-asc') ? 'selected' : ''; ?>>Date (Plus ancien)</option>
            <option value="price-desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-desc') ? 'selected' : ''; ?>>Prix (Plus élevé)</option>
            <option value="price-asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-asc') ? 'selected' : ''; ?>>Prix (Plus bas)</option>
          </select>
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

      <div class="cart-list">
        <div class="cart-header">
          <p>Référence</p>
          <p>Destination</p>
          <p>Date</p>
          <p>Prix</p>
          <p>Actions</p>
        </div>

        <?php
        // Fetch cart items
        $order_by = "added_at DESC";

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
          }
        }

        try {
          // Query cart items
          $cart_stmt = $conn->prepare(
            "
            SELECT c.id, c.destination_id, c.title, c.destination_image, c.start_date, c.end_date, c.price, c.added_at 
            FROM cart c 
            WHERE c.user_id = :user_id 
            ORDER BY " . $order_by
          );
          $cart_stmt->bindParam(':user_id', $user_id);
          $cart_stmt->execute();
          $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

          if (count($cart_items) > 0) {
            $total_price = 0;

            foreach ($cart_items as $item) {
              $start_date = new DateTime($item['start_date']);
              $end_date = new DateTime($item['end_date']);
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

              // Add to total price
              $total_price += $item['price'];
        ?>

              <div class="cart-row">
                <p class="ref-col">#<?php echo htmlspecialchars($item['id']); ?></p>
                <div class="dest-col">
                  <img src="<?php echo htmlspecialchars($item['destination_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                  <p><?php echo htmlspecialchars($item['title']); ?></p>
                </div>
                <p class="date-col"><?php echo $date_range; ?></p>
                <p class="price-col"><?php echo number_format($item['price'], 2, ',', ' ') . '€'; ?></p>
                <div class="actions-col">
                  <form action="" method="post" class="remove-form">
                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                    <button type="submit" name="remove_item" class="remove-btn" title="Supprimer">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                  <a href="payment?destination_id=<?php echo $item['destination_id']; ?>&start_date=<?php echo $item['start_date']; ?>&end_date=<?php echo $item['end_date']; ?>" class="payment-btn" title="Payer">
                    <i class="fa-solid fa-credit-card"></i>
                  </a>
                </div>
              </div>

            <?php
            }
            ?>

            <!-- Display total -->
            <div class="cart-total">
              <div class="total-row">
                <p><strong>Total du panier: <?php echo number_format($total_price, 2, ',', ' ') . '€'; ?></strong></p>
              </div>
            </div>

        <?php
          } else {
            echo '<div class="no-items">Votre panier est vide</div>';
          }
        } catch (PDOException $e) {
          echo '<div class="error-message">Erreur lors de la récupération des articles: ' . $e->getMessage() . '</div>';
        }
        ?>
      </div>
    </section>
  </main>

  <?php require('../components/footer.php'); ?>

  <script>
    // Add any JavaScript functionality here
    document.addEventListener('DOMContentLoaded', function() {
      const removeForms = document.querySelectorAll('.remove-form');
      removeForms.forEach(form => {
        form.addEventListener('submit', function(e) {
          if (!confirm('Êtes-vous sûr de vouloir retirer cet article de votre panier?')) {
            e.preventDefault();
          }
        });
      });
    });
  </script>
</body>

</html>