# E-election ENSAE Dakar

Plateforme de vote électronique pour l'ENSAE Dakar. Permet la gestion des élections, des candidatures, des votes, des résultats et des statistiques, avec des interfaces dédiées pour les étudiants, les membres de comité et les administrateurs.

---

## Sommaire
- [Fonctionnalités](#fonctionnalités)
- [Structure du projet](#structure-du-projet)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Sécurité](#sécurité)
- [Crédits](#crédits)

---

## Fonctionnalités

- **Authentification** (étudiant, comité, admin)
- **Inscription** avec validation d'email autorisé
- **Gestion des élections** (création, modification, statut, comité)
- **Candidatures** (dépôt, validation/rejet par comité)
- **Vote électronique** sécurisé (1 vote/poste/utilisateur)
- **Résultats et statistiques** (par poste, par élection, graphiques)
- **Gestion des comités** (nomination, recherche, droits)
- **Tableau de bord administrateur** (API, gestion utilisateurs, élections, types, postes, comités)
- **Responsive** (adapté mobile/desktop)

---

## Structure du projet

```
/ (racine)
│
├── admin/                # Interface et API d'administration
│   ├── dashboard.php     # Tableau de bord admin (HTML/PHP)
│   ├── dashboard.js      # Logique JS du dashboard
│   └── api.php           # API RESTful pour l'admin
│
├── assets/               # Ressources statiques
│   ├── css/              # Feuilles de style CSS
│   ├── js/               # Scripts JS (front)
│   ├── img/              # Images (logo, photos...)
│   └── docs/             # Documents PDF (programmes...)
│
├── components/           # Composants réutilisables (header/footer)
│   ├── header.php
│   ├── footer.php
│   ├── header_home.php
│   └── footer_home.php
│
├── config/               # Configuration
│   └── database.php      # Connexion et gestion DB (PDO, managers)
│
├── pages/                # Pages principales de l'application
│   ├── index.php         # Accueil connecté
│   ├── vote.php          # Page de vote
│   ├── campagnes.php     # Liste des campagnes/candidats
│   ├── candidature.php   # Dépôt de candidature
│   ├── profil.php        # Profil utilisateur
│   ├── resultat.php      # Résultats officiels
│   ├── statistique.php   # Statistiques détaillées
│   └── role_comite.php   # Interface comité d'organisation
│
├── uploads/              # Fichiers uploadés (photos, PDF...)
│
├── index.php             # Accueil public
├── login.php             # Connexion
├── inscription.php       # Inscription
├── avant-propos.php      # Présentation
├── logout.php            # Déconnexion
├── auth_check.php        # Fonctions d'authentification
└── README.md             # (ce fichier)
```

---

## Installation

1. **Prérequis**
   - PHP >= 7.4
   - MySQL/MariaDB
   - Serveur web (Apache, Nginx, XAMPP...)

2. **Cloner le projet**
   ```bash
   git clone <repo-url>
   ```

3. **Base de données**
   - Importer le fichier SQL fourni (`e_ensae.sql`) dans votre SGBD.
   - Adapter les identifiants dans `config/database.php` si besoin.

4. **Droits d'écriture**
   - Le dossier `uploads/` doit être accessible en écriture par le serveur web.

5. **Lancer le serveur**
   - Placer le projet dans le dossier web (ex: `htdocs` pour XAMPP).
   - Accéder à `http://localhost/e_ensae/` dans votre navigateur.

---

## Configuration

- **Base de données** : Modifier les constantes dans `config/database.php` si besoin.
- **Emails autorisés** : Ajouter les emails dans la table `gmail` pour permettre l'inscription.
- **Personnalisation** : Modifier les images dans `assets/img/`, les couleurs dans `assets/css/styles.css`.

---

## Utilisation

- **Étudiant** :
  - S'inscrire (si email autorisé)
  - Se connecter, candidater, voter, consulter résultats/statistiques

- **Membre de comité** :
  - Valider/rejeter candidatures, modifier les élections, ajouter des postes

- **Administrateur** :
  - Gérer utilisateurs, élections, types, comités, voir toutes les statistiques

- **Upload** :
  - Les photos et programmes PDF sont stockés dans `uploads/`

---

## Sécurité

- Sessions sécurisées, vérification des rôles à chaque action
- Uploads filtrés (type, taille)
- Préparation des requêtes SQL (PDO)
- Accès API protégé (admin uniquement)
- Redirections en cas d'accès non autorisé

---

## Crédits

- Plateforme développée pour l'ENSAE Dakar
- Technologies : PHP, MySQL, HTML5, CSS3, JavaScript (vanilla)
- Icônes : FontAwesome
- Charting : Chart.js

---

Pour toute question ou contribution, contactez l'équipe projet ou ouvrez une issue sur le dépôt. 