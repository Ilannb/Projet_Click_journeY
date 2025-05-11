<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');

// Redirect if user is already logged in
if (isset($_SESSION['user_id'])) {
  header("Location: /");
  exit;
}

// Message variables
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register-btn'])) {
  // Get and sanitize form data
  $lastname = trim($_POST['lastname']);
  $firstname = trim($_POST['firstname']);
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);
  $confirm_password = trim($_POST['confirm-password']);

  if (empty($lastname) || empty($firstname) || empty($email) || empty($password) || empty($confirm_password)) {
    $error = "Veuillez remplir tous les champs";
  }
  // Check email
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Format d'email invalide";
  }
  // Regex validation for email
  elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
    $error = "Format d'email invalide";
  }
  // Check email length
  elseif (strlen($email) > 100) {
    $error = "L'adresse email ne peut pas dépasser 100 caractères";
  }
  // Check password
  elseif ($password !== $confirm_password) {
    $error = "Les mots de passe ne correspondent pas";
  } else {
    // Ensure password complexity (min 8 characters, uppercase, lowercase, number, special character)
    $password_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&\-])[A-Za-z\d@$!%*?&\-]{8,}$/';
    if (!preg_match($password_regex, $password)) {
      $error = "Le mot de passe ne respecte pas les critères de sécurité";
    } else {
      try {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
          $error = "Cette adresse email est déjà utilisée";
        } else {
          // Hash the password
          $hashed_password = password_hash($password, PASSWORD_DEFAULT);

          // Insert new user into the database
          $stmt = $conn->prepare("INSERT INTO users (lastname, firstname, email, password, role, created_at) 
                                  VALUES (:lastname, :firstname, :email, :password, 'user', NOW())");
          $stmt->bindParam(':lastname', $lastname);
          $stmt->bindParam(':firstname', $firstname);
          $stmt->bindParam(':email', $email);
          $stmt->bindParam(':password', $hashed_password);
          $stmt->execute();

          $success = '<div>Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter en <a href="login">cliquant ici</a>.</div>';
        }
      } catch (PDOException $e) {
        $error = "Erreur technique: " . $e->getMessage();
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <script src="../assets/scripts/theme-init.js"></script>>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Page d'inscription">
  <meta name="keywords" content="LakEvasion, inscription">

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
  <link rel="stylesheet" href="../assets/styles/pages/register.css">
  <link rel="stylesheet" id="theme-style" href="../assets/styles/light-mode.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - Inscription</title>
</head>

<body>
  <div class="register-box">
    <a href="/"><i class="fas fa-arrow-left"></i>Retour à l'accueil</a>
    <h1>Créer un compte</h1>

    <?php if ($error): ?>
      <div class="error-message">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success-message">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
      </div>
    <?php endif; ?>

    <form class="register-form" action="<?php echo htmlspecialchars(preg_replace('/\.php$/', '', $_SERVER["PHP_SELF"])); ?>" method="post">
      <div class="name-box">
        <div class="input-bar">
          <i class="fas fa-user"></i>
          <input type="text" id="lastname" name="lastname" placeholder="Nom" required value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>">
        </div>

        <div class="input-bar">
          <i class="fas fa-user"></i>
          <input type="text" id="firstname" name="firstname" placeholder="Prénom" required value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>">
        </div>
      </div>

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

      <div class="input-bar">
        <i class="fas fa-lock"></i>
        <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirmez votre mot de passe"
          required>
        <button type="button" class="password-toggle">
          <i class="fas fa-eye"></i>
        </button>
      </div>

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

      <div class="accept-conditions">
        <input type="checkbox" id="accept-checkbox" name="accept-conditions" required>
        <label for="accept-checkbox">J'ai lu et j'accepte les <a href="policy">conditions d'utilisation</a></label>
      </div>

      <button type="submit" class="register-btn" name="register-btn">
        S'inscrire
        <i class="fas fa-arrow-right"></i>
      </button>
    </form>

    <p class="login-link">
      Déjà membre ? <a href="login">Se connecter</a>
    </p>
  </div>
</body>

<script src="../assets/scripts/password_check.js"></script>

</html>