<?php
if (!defined('BASE_URL')) {
  define('BASE_URL', '/');
}
?>

<header>
  <nav>
    <div class="logo">
      <img src="<?php echo BASE_URL; ?>assets/src/img/favicon.ico" alt="main-icon">
      <a href="<?php echo BASE_URL; ?>">LakEvasion</a>
    </div>
    <div class="middle-section">
      <?php
      echo $icon;
      ?>
      <p>
        <?php
        echo $title;
        ?>
      </p>
    </div>
    <?php if ($isLoggedIn): ?>
      <!-- User connected -->
      <div class="right-section">
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
          <div class="user">
            <a class="user-link" href="<?php echo BASE_URL; ?>pages/admin"><?php echo htmlspecialchars($_SESSION['user_firstname']); ?></a>
            <i class="fa-solid fa-screwdriver-wrench"></i>
          </div>
        <?php else: ?>
          <div class="user">
            <a class="user-link" href="<?php echo BASE_URL; ?>pages/user"><?php echo htmlspecialchars($_SESSION['user_firstname']); ?></a>
            <i class="fa-solid fa-user"></i>
          </div>
        <?php endif; ?>
        <div class="links-box">
          <a href="?logout=1" class="logout-btn">Déconnexion
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
          </a>
        </div>
      </div>
    <?php else: ?>
      <!-- User not connected -->
      <div class="links-box">
        <a href="<?php echo BASE_URL; ?>pages/login" class="login-btn">Se connecter</a>
        <a href="<?php echo BASE_URL; ?>pages/register" class="signup-btn">S'inscrire</a>
      </div>
    <?php endif; ?>
  </nav>
</header>