<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');

$error = null;
$success = null;

$token = $_GET['token'] ?? null;
$token_valid = false;
$email = '';

// Verify token if provided
if ($token) {
  try {
    $stmt = $conn->prepare("SELECT user_id, email, expires_at FROM password_reset_tokens WHERE token = :token AND expires_at > NOW()");
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
      $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
      $token_valid = true;
      $email = $token_data['email'];
    } else {
      $error = "Le lien de réinitialisation a expiré ou n'est pas valide.";
    }
  } catch (PDOException $e) {
    $error = "Erreur technique: " . $e->getMessage();
  }
}

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request-reset'])) {
  $email = trim($_POST['email']);

  if (empty($email)) {
    $error = "Veuillez entrer votre adresse email";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Format d'email invalide";
  } else {
    try {
      $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
      $stmt->bindParam(':email', $email);
      $stmt->execute();

      if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['id'];

        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + (24 * 60 * 60));

        $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = :user_id")
          ->execute([':user_id' => $user_id]);

        $insert_stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, email, token, expires_at) VALUES (:user_id, :email, :token, :expires_at)");
        $insert_stmt->execute([
          ':user_id' => $user_id,
          ':email' => $email,
          ':token' => $token,
          ':expires_at' => $expires_at
        ]);

        $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/pages/reset_password?token=" . $token;
        $success = "Un lien de réinitialisation a été généré. <a href='$reset_link'>Cliquez ici pour réinitialiser votre mot de passe</a>";
      } else {
        $error = "Aucun compte n'est associé à cette adresse email.";
      }
    } catch (PDOException $e) {
      $error = "Erreur technique: " . $e->getMessage();
    }
  }
}

// Handle password reset with token
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset-password'])) {
  $token = trim($_POST['token']);
  $password = trim($_POST['new-password']);
  $confirm_password = trim($_POST['confirm-password']);

  if (empty($password) || empty($confirm_password)) {
    $error = "Veuillez remplir tous les champs";
  } elseif ($password !== $confirm_password) {
    $error = "Les mots de passe ne correspondent pas";
  } else {
    $password_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&\-])[A-Za-z\d@$!%*?&\-]{8,}$/';
    if (!preg_match($password_regex, $password)) {
      $error = "Le mot de passe ne respecte pas les critères de sécurité";
    } else {
      try {
        $stmt = $conn->prepare("SELECT user_id, email FROM password_reset_tokens WHERE token = :token AND expires_at > NOW()");
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
          $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
          $user_id = $token_data['user_id'];

          $hashed_password = password_hash($password, PASSWORD_DEFAULT);

          $conn->prepare("UPDATE users SET password = :password WHERE id = :user_id")
            ->execute([
              ':password' => $hashed_password,
              ':user_id' => $user_id
            ]);

          $conn->prepare("DELETE FROM password_reset_tokens WHERE token = :token")
            ->execute([':token' => $token]);

          $success = "Votre mot de passe a été réinitialisé avec succès. <a href='login'>Connexion</a>";
          $token_valid = false;
        } else {
          $error = "Le lien de réinitialisation a expiré ou n'est pas valide.";
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
  <script src="../assets/scripts/theme-init.js"></script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Page de réinitialisation de mot de passe">
  <meta name="keywords" content="LakEvasion, nouveau mot de passe, réinitialisation">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://lakevasion.ddns.net/assets/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="../assets/styles/global.css">
  <link rel="stylesheet" href="../assets/styles/components/search-input.css">
  <link rel="stylesheet" href="../assets/styles/pages/reset_password.css">
  <link rel="stylesheet" id="theme-style" href="../assets/styles/light-mode.css">

  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - Réinitialisation mot de passe</title>
</head>

<body>
  <div class="reset-box">
    <a href="/"><i class="fas fa-arrow-left"></i>Retour à l'accueil</a>
    <h1>Réinitialisation du mot de passe</h1>

    <?php if ($error): ?>
      <div class="error-message">
        <i class="fas fa-exclamation-circle"></i>
        <div>
          <?php echo htmlspecialchars($error); ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success-message">
        <i class="fas fa-check-circle"></i>
        <div>
          <?php echo $success; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!$token_valid): ?>
      <form action="<?php echo htmlspecialchars(preg_replace('/\.php$/', '', $_SERVER["PHP_SELF"]) ?? ''); ?>" method="post">
        <p>Entrez votre adresse email pour recevoir un lien de réinitialisation de mot de passe.</p>
        <div class="input-bar">
          <i class="fas fa-envelope"></i>
          <input type="email" id="email" name="email" placeholder="exemple@email.com" required value="<?php echo $_POST['email'] ?? ''; ?>">
        </div>

        <button type="submit" class="reset-btn" name="request-reset">
          Envoyer le lien de réinitialisation
          <i class="fas fa-arrow-right"></i>
        </button>
      </form>
    <?php else: ?>
      <form action="<?php echo htmlspecialchars(preg_replace('/\.php$/', '', $_SERVER["PHP_SELF"]) ?? ''); ?>" method="post">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

        <div class="input-bar">
          <i class="fas fa-envelope"></i>
          <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
        </div>

        <div class="input-bar">
          <i class="fas fa-lock"></i>
          <input type="password" id="new-password" name="new-password" placeholder="Nouveau mot de passe" required>
          <button type="button" class="password-toggle">
            <i class="fas fa-eye"></i>
          </button>
        </div>

        <div class="input-bar">
          <i class="fas fa-lock"></i>
          <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirmez le nouveau mot de passe" required>
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

        <button type="submit" class="reset-btn" name="reset-password">
          Réinitialiser le mot de passe
          <i class="fas fa-arrow-right"></i>
        </button>
      </form>
    <?php endif; ?>

    <p class="login-link">
      Retourner à la <a href="login">page de connexion</a>
    </p>
  </div>
</body>

<script src="../assets/scripts/password_check.js"></script>

</html>