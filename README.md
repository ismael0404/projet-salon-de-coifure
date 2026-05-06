# Projet Coiffure Salon (Tya Stylex)

Bienvenue dans le projet **Coiffure Salon (Tya Stylex)** ! Ce projet est une application web en PHP/MySQL conçue pour gérer un salon de coiffure. Elle permet de gérer les rendez-vous, la base de clients, les employés et les différents services proposés.

## Structure du Projet

Voici l'organisation détaillée des fichiers et dossiers de l'application :

```text
coiffure_salon/
│
├── index.php                 # Page d'accueil principale de l'application
├── tya_stylex.sql            # Script SQL pour initialiser la base de données
├── README.md                 # Documentation du projet (ce fichier)
│
├── assets/                   # Dossier contenant les ressources statiques
│   └── css/                  
│       └── style.css         # Feuille de style principale gérant le design de l'application
│
├── pages/                    # Dossier contenant les différentes pages de l'interface
│   ├── connexion.php         # Page permettant aux utilisateurs de se connecter à leur compte
│   └── inscription.php       # Page d'enregistrement pour les nouveaux utilisateurs
│
└── php/                      # Dossier contenant la logique backend et la configuration
    └── config/               
        └── database.php      # Script gérant la connexion à la base de données MySQL
```

## Description détaillée des fichiers

### 1. Fichiers à la racine
- **`index.php`** : C'est le point d'entrée de votre site web. C'est la première page que vos visiteurs voient lorsqu'ils accèdent au projet. Elle sert généralement de page d'accueil ou de redirection vers le tableau de bord selon l'état de connexion de l'utilisateur.
- **`tya_stylex.sql`** : C'est le fichier d'export de votre base de données. Il contient la structure complète de la base `tya_stylex` (avec des tables comme `utilisateurs`, `clientes`, `employes`, `rendez_vous`, `services`, etc.) ainsi que quelques données initiales. Il est indispensable pour installer le projet.

### 2. Le dossier `assets/`
Ce dossier stocke tous les fichiers statiques nécessaires au rendu des pages.
- **`assets/css/style.css`** : Ce fichier regroupe toutes les règles CSS de votre application. Il définit l'apparence visuelle (couleurs, polices, mises en page) pour offrir une interface utilisateur agréable, cohérente et dynamique.

### 3. Le dossier `pages/`
Ce dossier regroupe les différentes vues (pages web) accessibles par les visiteurs et utilisateurs.
- **`pages/connexion.php`** : Il s'agit de l'interface de connexion. Les clients, employés et administrateurs utilisent cette page pour accéder à leur espace personnel sécurisé en renseignant leurs identifiants (email et mot de passe).
- **`pages/inscription.php`** : C'est le formulaire d'inscription. Il permet à de nouveaux visiteurs de créer un compte client pour pouvoir, par la suite, prendre des rendez-vous et suivre leur fidélité.

### 4. Le dossier `php/`
Ce dossier contient la logique "côté serveur" et la configuration globale de l'application.
- **`php/config/database.php`** : C'est un fichier critique de l'application. Il contient les informations de connexion à votre base de données locale (hôte, nom de la base de données, utilisateur, mot de passe) et établit la liaison entre le code PHP et la base de données MySQL.

## Comment installer et lancer le projet ?

1. Assurez-vous d'avoir un environnement serveur local installé (comme **XAMPP**, **WAMP**, ou **MAMP**).
2. Placez l'intégralité du dossier `coiffure_salon` dans le répertoire web de votre serveur (par exemple `htdocs` pour XAMPP ou `www` pour WAMP).
3. Ouvrez votre gestionnaire de base de données (généralement **phpMyAdmin** via `http://localhost/phpmyadmin`).
4. Importez le fichier **`tya_stylex.sql`** dans phpMyAdmin. Le script se chargera de créer la base de données `tya_stylex` et de générer toutes les tables nécessaires.
5. Ouvrez le fichier **`php/config/database.php`** et vérifiez que les identifiants de base de données correspondent bien à la configuration de votre serveur local (par défaut, l'utilisateur est souvent `root` avec un mot de passe vide).
6. Démarrez vos services Apache et MySQL.
7. Accédez au projet depuis votre navigateur en visitant l'adresse : `http://localhost/coiffure_salon/`.

## Comptes de test par défaut

Pour tester les différentes interfaces, vous pouvez utiliser les comptes suivants :

| Rôle | Email | Mot de passe |
| :--- | :--- | :--- |
| **Administrateur** | `admin@tyastylex.com` | `admin123` |
| **Employée** | `amine@tyastylex.com` | `amine123` |
| **Cliente** | `fatou@email.com` | `fatou123` |
