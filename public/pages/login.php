<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');

// Message variables
$error = null;
$success = null;

// Redirect if user is already logged in
if (isset($_SESSION['user_id'])) {
  header("Location: /");
  exit;
}

// Check if a "stay logged in" cookie exists
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
  try {
    // Split the cookie
    list($user_id, $token, $hash) = explode(':', $_COOKIE['remember_me']);

    // Check the token in the database
    $stmt = $conn->prepare("SELECT * FROM remember_tokens WHERE user_id = :user_id AND token = :token AND expires_at > NOW()");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
      // Check that the hash is valid
      $stored_token = $stmt->fetch(PDO::FETCH_ASSOC);
      $check = hash('sha256', $user_id . $token . $_SERVER['HTTP_USER_AGENT']);

      if (hash_equals($hash, $check)) {
        // Retrieve user information
        $user_stmt = $conn->prepare("SELECT id, firstname, lastname, email, role FROM users WHERE id = :id");
        $user_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_firstname'] = $user['firstname'];
        $_SESSION['user_lastname'] = $user['lastname'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        header("Location: /");
        exit;
      } else {
        // Delete to avoid cookie manipulation
        setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
      }
    }
  } catch (PDOException $e) {
    $error = "Erreur technique: " . $e->getMessage();
  }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login-btn'])) {
  // Get and sanitize form data
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);
  $remember = isset($_POST['stay-connected']) ? true : false;

  if (empty($email) || empty($password)) {
    $error = "Veuillez remplir tous les champs";
  } else {
    try {
      // Check if email exists
      $stmt = $conn->prepare("SELECT id, firstname, lastname, email, password, role FROM users WHERE email = :email");
      $stmt->bindParam(':email', $email);
      $stmt->execute();

      if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check password
        if (password_verify($password, $user['password'])) {
          // Create session
          $_SESSION['user_id'] = $user['id'];
          $_SESSION['user_firstname'] = $user['firstname'];
          $_SESSION['user_lastname'] = $user['lastname'];
          $_SESSION['user_email'] = $user['email'];
          $_SESSION['user_role'] = $user['role'];

          // Manage the "stay connected" checkbox
          if ($remember) {
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            $user_id = $user['id'];

            // Create a hash based on the user agent to prevent cookie theft
            $hash = hash('sha256', $user_id . $token . $_SERVER['HTTP_USER_AGENT']);

            // Expiration date (30 days)
            $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));

            // Delete this user's old tokens
            $delete_stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = :user_id");
            $delete_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $delete_stmt->execute();

            // Save the new token in the database
            $insert_stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
            $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':token', $token);
            $insert_stmt->bindParam(':expires_at', $expires);
            $insert_stmt->execute();

            // Set the cookie with the format user_id:token:hash
            $cookie_value = $user_id . ':' . $token . ':' . $hash;
            setcookie('remember_me', $cookie_value, time() + (30 * 24 * 60 * 60), '/', '', isset($_SERVER['HTTPS']), true);
          }

          header("Location: /");
          exit;
        } else {
          $error = "Email ou mot de passe incorrect";
        }
      } else {
        $error = "Email ou mot de passe incorrect";
      }
    } catch (PDOException $e) {
      $error = "Erreur technique: " . $e->getMessage();;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <script src="../assets/scripts/theme-init.js"></script>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Page de connexion">
  <meta name="keywords" content="LakEvasion, connexion">

  <!-- Roboto Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://lakevasion.ddns.net/assets/fontawesome/css/all.min.css">

  <!-- CSS -->
  <link rel="stylesheet" href="../assets/styles/global.css">
  <link rel="stylesheet" href="../assets/styles/components/search-input.css">
  <link rel="stylesheet" href="../assets/styles/pages/login.css">
  <link rel="stylesheet" id="theme-style" href="../assets/styles/light-mode.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - Connexion</title>
</head>

<body>
  <div class="login-box">
    <a href="/"><i class="fas fa-arrow-left"></i>Retour à l'accueil</a>
    <h1>Se connecter</h1>

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

    <form class="login-form" action="<?php echo htmlspecialchars(preg_replace('/\.php$/', '', $_SERVER["PHP_SELF"])); ?>" method="post">
      <div class="input-bar">
        <i class="fas fa-envelope"></i>
        <input type="email" id="email" name="email" placeholder="exemple@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      </div>

      <div class="input-bar">
        <i class="fas fa-lock"></i>
        <input type="password" id="password" name="password" placeholder="Mot de passe" required>
        <button type="button" class="password-toggle">
          <i class="fas fa-eye"></i>
        </button>
      </div>

      <div class="row">
        <div class="stay-connected-box">
          <input type="checkbox" id="stay-connected" name="stay-connected">
          <label for="stay-connected">Rester connecté</label>
        </div>
        <div class="password-forgotten">
          <a href="reset_password">Mot de passe oublié ?</a>
        </div>
      </div>

      <button type="submit" class="login-btn" name="login-btn">
        Se connecter
        <i class="fas fa-arrow-right"></i>
      </button>
    </form>

    <p class="register-link">
      Pas encore de compte ? <a href="register">S'inscrire</a>
    </p>
  </div>
</body>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.password-toggle');
    toggleButtons.forEach(button => {
      button.addEventListener('click', function() {
        const input = this.previousElementSibling;
        const icon = this.querySelector('i');

        if (input.type === 'password') {
          input.type = 'text';
          icon.className = 'fas fa-eye-slash';
        } else {
          input.type = 'password';
          icon.className = 'fas fa-eye';
        }
      });
    });
  });
</script>

</html>