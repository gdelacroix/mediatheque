# 📚 Application Médiathèque — Parcours POO & Architecture PHP

Ce dépôt retrace la construction pas à pas d'une application de gestion de médiathèque en PHP. Conçu comme un projet fil rouge évolutif, il permet de passer des concepts fondamentaux de la Programmation Orientée Objet (POO) jusqu'aux pratiques professionnelles de l'architecture logicielle (MVC, Tests Unitaires, Containerisation).

Le projet est découpé en plusieurs grandes parties, chacune documentée par son propre tutoriel disponible directement dans le dépôt.

---

## 🗺️ Feuille de route & Suivi des versions

Vous pouvez naviguer dans l'historique du projet et vous positionner sur les différentes étapes grâce aux commits clés ci-dessous :

### 🚀 Partie 1 : Fondations POO & CRUD Générique
*Initiation aux piliers de la POO et mise en place d'une persistance de données propre avec le pattern DAO.*
* Concepts clés : Namespaces, Encapsulation (Getters/Setters), Héritage, Polymorphisme, Interfaces, DAO Générique (CRUD abstrait).
* Tutoriel associé : PART1_TUTORIEL_POO.md
* 📌 **Commit de fin de partie** : `0f55b95b449b8f05e19f22b00f5c5211a1f98dc9` 
Ex: 
```bash
git checkout 0f55b95b449b8f05e19f22b00f5c5211a1f98dc9
```


### 🏗️ Partie 2 : Architecture MVC (À venir)
*Refactoring complet de l'application pour séparer la logique métier de l'affichage.*
* Concepts clés : Pattern Model-View-Controller, Routage (Front Controller), Clean Architecture, Séparation des responsabilités.
* 📌 Commit de fin de partie : 
`e60b74da3f9fadad1d7798d393013086b5d9658c` 
Ex: 
```bash
git checkout e60b74da3f9fadad1d7798d393013086b5d9658c
```

### 🧪 Partie 3 : Tests Unitaires & Qualité (À venir)
*Sécurisation du code de l'application par la mise en place de tests automatisés.*
* Concepts clés : PHPUnit, Mocking, Test-Driven Development (TDD), Assertions.
* 📌 Commit de fin de partie : [Bientôt disponible]

### 🐳 Partie 4 : Containerisation avec Docker (À venir)
*Déploiement de l'environnement de développement complet dans des conteneurs isolés.*
* Concepts clés : Docker, Docker Compose, isolation PHP/MySQL/Apache, reproductibilité de l'environnement.
* 📌 Commit de fin de partie : [Bientôt disponible]

---

## ⚙️ Installation et Lancement (Partie 1)

Pour tester l'état actuel du projet (Fin de la Partie 1), suivez ces étapes :

### 1. Prérequis
* Un serveur local d'exécution : WampServer, MAMP ou XAMPP.
* PHP 8.0 ou supérieur.
* Le gestionnaire de base de données MySQL / phpMyAdmin.

### 2. Clonage et configuration
1. Clonez ce dépôt dans votre dossier de publication (www/ pour Wamp, htdocs/ pour XAMPP) :
```bash
   git clone https://github.com/votre-compte/mediatheque.git
```
2. Démarrez votre serveur local et rendez-vous sur http://localhost/phpmyadmin.
3. Créez une base de données nommée mediatheque.
4. Importez le fichier bdd_mediatheque.sql situé à la racine du projet pour initialiser la structure et le jeu de données.

### 3. Accès à l'application
Ouvrez votre navigateur et accédez à l'adresse suivante :
http://localhost/mediatheque/index.php

---

## 📂 Structure du projet

### Partie 1 — POO & CRUD

```
mediatheque/
├── bdd_mediatheque.sql      # Script de création de la base de données
├── index.php                # Liste des articles (Vue + Contrôleur combinés)
├── formulaire.php           # Formulaire d'ajout et de modification (C/U)
├── supprimer.php            # Action de suppression (D)
├── emprunter.php            # Logique métier d'emprunt (Polymorphisme)
├── classes/
│   ├── config.php           # Connexion PDO globale
│   ├── DAO.class.php        # Classe mère abstraite pour le CRUD dynamique
│   ├── Article.class.php    # Modèle abstrait Article et sa DAO concrète
│   ├── Livre.class.php      # Spécification Livre (Héritage + Interface)
│   └── Dvd.class.php        # Spécification DVD (Héritage + Interface)
└── includes/                # Éléments d'interface réutilisables (Header / Footer)
```

---

### Partie 2 — Architecture MVC
 
```
mediatheque/
├── bdd_mediatheque.sql          # Inchangé
├── index.php                    # Front Controller : point d'entrée unique,
│                                # lit ?action= et délègue au Controller
├── controllers/
│   └── ArticleController.class.php  # Chef d'orchestre : reçoit les requêtes,
│                                    # appelle le Model, transmet aux Views
├── views/
│   ├── layout/
│   │   ├── header.php           # En-tête HTML commune (déplacée depuis includes/)
│   │   └── footer.php           # Pied de page HTML commun
│   └── article/
│       ├── liste.php            # Vue : affichage de la liste des articles
│       └── formulaire.php       # Vue : formulaire d'ajout et de modification
└── classes/                     # MODEL — inchangé depuis la Partie 1
    ├── config.php
    ├── DAO.class.php
    ├── Article.class.php
    ├── Empruntable.class.php
    ├── Livre.class.php
    └── Dvd.class.php
```
 
> 💡 **Ce qui change entre Partie 1 et Partie 2**
>
> Le dossier `classes/` (le **Model**) reste **identique** : les classes métier et d'accès aux données n'ont pas besoin d'être modifiées.
> Ce qui change, c'est que la logique applicative quitte les pages PHP pour rejoindre le **Controller**, et que le HTML pur est isolé dans les **Views**.
 
---
 
## 🧭 Flux d'une requête en Partie 2 (MVC)
 
```
Navigateur
    │
    │  GET index.php?action=liste
    ▼
index.php (Front Controller)
    │  lit $_GET['action'] → 'liste'
    │  instancie ArticleController
    ▼
ArticleController::liste()          ← CONTROLLER
    │  appelle $this->dao->findAll()
    ▼
ArticleDAO::findAll()               ← MODEL
    │  exécute SELECT * FROM article
    │  retourne un tableau d'objets Livre / Dvd
    ▼
ArticleController::render('article/liste', $donnees)
    │  extrait les variables ($articles, $message...)
    ▼
views/article/liste.php             ← VIEW
    │  affiche le tableau HTML
    ▼
Navigateur reçoit la page HTML
```
 
---
 
💡 Pour comprendre en détail chaque étape de construction, référez-vous aux tutoriels associés :
- **Partie 1** → `PART1_TUTORIEL_POO.md`
- **Partie 2** → `PART2_TUTORIEL_MVC.md`