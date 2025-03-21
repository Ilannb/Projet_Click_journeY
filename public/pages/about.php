<?php
session_start();

require_once(__DIR__ . '/../../app/config/database.php');
require_once(__DIR__ . '/../../app/includes/logout.php');

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Retrieving members from the database
try {
  $stmt = $conn->prepare("SELECT * FROM team ORDER BY title");
  $stmt->execute();
  $team = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Erreur lors de la récupération des activités: " . $e->getMessage());
}

// Title of the page
$icon = '<i class="fa-solid fa-circle-info"></i>';
$title = 'À propos';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="MI5-I | Illya Liganov, Ilann Boudria, Rindra Rakotonirina">
  <meta name="description" content="Page à propos de LakEvasion">
  <meta name="keywords" content="LakEvasion, agence de voyage, lacs, tourisme, à propos">

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
  <link rel="stylesheet" href="../assets/styles/pages/about.css">

  <!-- Tab Display -->
  <link rel="icon" href="../assets/src/img/favicon.ico" type="image/x-icon">
  <title>LakEvasion - À propos</title>
</head>

<body>
  <?php require('../components/header.php'); ?>

  <main>
    <section class="about-content">
      <div class="speech-section">
        <h2>Bienvenue !</h2>
        <p class="speech">L'équipe <b>LakEvasion</b> est ravie de vous accueillir sur son site web de <b>planification
            de
            voyages lacustres</b>.
          Vous y retrouverez tous les services proposés <b>aux abords des lacs</b> par notre agence, allant de
          l'<b>hébergement</b> aux <b>activités</b> les plus trépidantes, sans oublier la
          <b>restauration</b> sur place.
          Alors, si vous avez soif <b>d'aventure</b> et que l'envie vous prend de voyager aux <b>meilleurs prix</b> pour
          découvrir <b>les plus beaux lacs du monde</b>, vous êtes au bon endroit.
          N'hésitez surtout pas à créer un compte dans la rubrique <a href="register">S'inscrire</a> ou à
          vous connecter si vous êtes <a href="login">déjà membre</a>. Cela vous permettra de vivre une
          <b>expérience personnalisée</b> sur notre site <b>LakEvasion</b>.
        </p>
      </div>

      <div class="team-section">
        <h2>Notre Équipe</h2>
        <div class="team-grid">
          <?php foreach ($team as $member): ?>
            <div class="team-member">
              <div class="member-photo">
                <img src="<?php echo htmlspecialchars($member['photo_url']); ?>" alt="<?php echo htmlspecialchars($member['title']); ?>">
              </div>
              <h3><?php echo htmlspecialchars($member['name']); ?></h3>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </section>
  </main>

  <?php require('../components/footer.php'); ?>
</body>

</html>