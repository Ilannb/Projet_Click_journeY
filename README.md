# LakEvasion

<div align="center">
  <img src="https://lakevasion.ddns.net/assets/src/img/logo.png" alt="LakEvasion Logo" width="200" height="200">
</div>

## 🔗 Accès au site
Le site est hébergé et accessible à l'adresse suivante -> https://lakevasion.ddns.net

## 🌊 À propos

LakEvasion est votre spécialiste des voyages lacustres. Notre plateforme vous permet de découvrir et réserver des séjours uniques autour des plus beaux lacs du monde. Que vous soyez amateur de navigation, de randonnée ou simplement à la recherche d'une évasion relaxante au bord de l'eau, LakEvasion vous propose des expériences inoubliables adaptées à vos envies.

## 🌟 Fonctionnalités

- **Gestion de compte utilisateur** : Créez votre profil pour sauvegarder vos recherches, suivre vos réservations et accéder à l'historique de vos voyages.
- **Catalogue de séjours lacustres** : Découvrez notre sélection de circuits thématiques autour des plus beaux lacs du monde, avec des itinéraires prédéfinis mais - personnalisables.
- **Recherche avancée** : Filtrez nos voyages selon vos critères (pays, budget, activités, durée, etc ...) pour trouver le séjour lacustre idéal.
- **Personnalisation d'options** : Adaptez chaque étape de votre circuit selon vos préférences (hébergement, restauration, activités nautiques ou terrestres, transport entre les lacs).
- **Récapitulatif détaillé** : Visualisez l'ensemble de votre itinéraire avec toutes les options choisies avant de confirmer votre réservation.
- **Réservation sécurisée** : Finalisez votre séjour avec notre système de paiement en ligne sécurisé après avoir validé tous les détails de votre voyage personnalisé.

## 🖥️ Outils et langages utilisés

- HTML5
- CSS3
- PHP
- MySQL
- PhpMyAdmin
- Nginx
- No-IP
- Font Awesome
- Google Fonts (Roboto)

## 🔧 Configuration serveur

- Hébergement : Serveur personnel avec DNS dynamique
- Serveur web : Nginx 1.25.4
- PHP Version : 8.2.27
- Base de données : MySQL 9.2.0

## 📂 Structure du projet
```bash
lakevasion/
├── app/                                # Dossier privé (côté serveur) du site
│   ├── config/                           # Configurations
│   │   ├── config.local.exampe.php         # Exemple d'informations pour la connexion à la base de données
│   │   ├── config.local.php                # [Exclu de Git] Informations pour la connexion à la base de données
│   │   └── database.php                    # Connexion à la base de données
│   └── includes/                         # Fichiers (.php) réutilisables 
│       ├── admin_auth.php                  # Gestion la connexion aux ressources protégées
│       ├── destinations.php                # Gestion dynamique de destinations.php
│       ├── getapikey.php                   # Configuration CyBank
│       └── logout.php                      # Gestion la déconnexion
├── public/                             # Dossier publique (côté client) du site
│   ├── assets/                           # Ressources
│   │   ├── fontawesome/                    # [Exclu de Git] Icônes FontAwesome
│   │   ├── src/                            # Sources
│   │   │   └── img/                          # Images (.jpg)
│   │   │       └── ...
│   │   ├── styles/                         # Fichiers styles (.css)
│   │   │   ├── components/                   # Fichiers styles (.css) composants (header, footer, cards, ...)
│   │   │   │   └── ...
│   │   │   ├── pages/                        # Fichiers styles (.css) pages
│   │   │   │   └── ...
│   │   │   └── global.css                    # Styles globaux
│   │   └── upload/                         # [Exclu de Git] Photos de profil
│   │       └── ...
│   ├── components/                       # Fichiers (.php) composants réutilisables (header, footer, cards, ...)
│   │   └── ...
│   ├── pages/                            # Fichiers (.php) pages 
│   │   └── ...
│   └── index.html                        # Page d'accueil
├── .gitignore                          # Liste des fichiers ignorés par Git
├── Rapport_Projet.pdf
├── README.md                           # Ce fichier
└── Thème et Identitée visuelle.pdf
```

## 👥 Équipe

**Groupe : MI5-I** 
  - **Illya Liganov** (liganovill@cy-tech.fr)
  - **Ilann Boudria** (boudriaila@cy-tech.fr)
  - **Rindra Rakotonirina** (rakotonirinarin@cy-tech.fr)

## 📝 Licence

© 2025 LakEvasion. Tous droits réservés.

## 🛠️ Sources 
- CM : (fournis par l'école et non disponibles sur GitHub)
  - 10_Cours_complet_HTML.pdf
  - 20-CSS.pdf
  - 30_Cours_PHP.pdf

- Ressources externes :
  - [W3Schools - HTML](https://www.w3schools.com/html)
  - [W3Schools - CSS](https://www.w3schools.com/css)
  - [W3Schools - PHP](https://www.w3schools.com/php)
  - [W3Schools - MySQL](https://www.w3schools.com/MySQL)
  - [Nginx](https://nginx.org/en/docs/beginners_guide.html)

**Note :** Ce projet est un exercice académique pour apprendre à créer un site web pour une agence de voyage.

[⬆️ Retour au début](#lakevasion)