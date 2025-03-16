<?php
if (!defined('BASE_URL')) {
  define('BASE_URL', '/');
}
?>

<footer>
  <div class="footer-box">
    <div class="footer-top">
      <div class="footer-section agency-section">
        <div class="footer-logo">
          <img src="<?php echo BASE_URL; ?>assets/src/img/favicon.ico" alt="LakEvasion Logo" />
          <h3>LakEvasion</h3>
        </div>
        <p class="agency">Votre spécialiste des voyages lacustres depuis 2025. Découvrez des expériences uniques au
          bord des plus beaux lacs du monde.</p>
      </div>

      <div class="footer-section">
        <h4>Navigation</h4>
        <ul class="footer-links">
          <li><a href="<?php echo BASE_URL; ?>">Accueil</a></li>
          <li><a href="<?php echo BASE_URL; ?>pages/destinations">Nos Destinations</a></li>
          <li><a href="<?php echo BASE_URL; ?>pages/about">À propos</a></li>
        </ul>
      </div>

      <div class="footer-section">
        <h4>Services</h4>
        <ul class="footer-links">
          <li><a href="<?php echo BASE_URL; ?>pages/housing">Hébergement</a></li>
          <li><a href="<?php echo BASE_URL; ?>pages/restoration">Restauration</a></li>
          <li><a href="<?php echo BASE_URL; ?>pages/activities">Activités</a></li>
        </ul>
      </div>

      <div class="footer-section contact-section">
        <h4>Contactez-nous</h4>
        <div class="contact-info">
          <div><i class="fas fa-phone"></i><a href="tel:0134251010">+33 1 34 25 10 10</a></div>
          <div><i class="fas fa-envelope"></i><a href="mailto:contact@lakevasion.fr">contact@lakevasion.fr</a></div>
          <div><i class="fas fa-map-marker-alt"></i>
            <address>Av. du Parc, 95000 Cergy</address>
          </div>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; 2025 LakEvasion. Tous droits réservés.</p>
    </div>
  </div>
</footer>