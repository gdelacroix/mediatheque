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


### 🏗️ Partie 2 : Architecture MVC 
*Refactoring complet de l'application pour séparer la logique métier de l'affichage.*
* Concepts clés : Pattern Model-View-Controller, Routage (Front Controller), Clean Architecture, Séparation des responsabilités.
* 📌 Commit de fin de partie : 
`e60b74da3f9fadad1d7798d393013086b5d9658c` 
Ex: 
```bash
git checkout e60b74da3f9fadad1d7798d393013086b5d9658c
```

### 🧪 Partie 3 : Tests Unitaires & Qualité 
*Sécurisation du code de l'application par la mise en place de tests automatisés.*
* Concepts clés : PHPUnit, Composer, Mocks (`createMock`, `willReturn`), TDD (cycle Rouge/Vert/Refactor), Assertions (`assertEquals`, `assertNotEquals`, `assertInstanceOf`, `expectException`), Intégration Continue (GitHub Actions).
* Tutoriel associé : `PART3_TUTORIEL_PHPUNIT.md`
* 📌 **Commit de fin de partie** : `169c19ba649fe3f43fd18127ea0899571f43ad5a`
Ex: 
```bash
git checkout 169c19ba649fe3f43fd18127ea0899571f43ad5a
```

### 🐳 Partie 4 : Containerisation avec Docker (À venir)
*Déploiement de l'environnement de développement complet dans des conteneurs isolés.*
* Concepts clés : Docker, Docker Compose, isolation PHP/MySQL/Apache, reproductibilité de l'environnement.
* Tutoriel associé : `PART4_TUTORIEL_DOCKER.md`
* 📌 **Commit de fin de partie** : `abae78f8a4c7c15354ffe36e24adb464b74f56c6`
Ex: 
```bash
git checkout abae78f8a4c7c15354ffe36e24adb464b74f56c6
```

---

## ⚙️ Installation et Lancement 

### Prérequis communs
* Git
* PHP 8.0 ou supérieur
* WampServer, MAMP ou XAMPP (Parties 1, 2 et 3)
* Docker Desktop (Partie 4)
### 1. Cloner le dépôt
```bash
git clone https://github.com/gdelacroix/mediatheque.git
```
 
### 2. Se positionner sur la partie souhaitée
Utilisez `git checkout` avec le commit correspondant (voir la feuille de route ci-dessus), ou restez sur `main` pour la version la plus récente.
 
### 3. Lancement — Parties 1 et 2 (XAMPP / WAMP)
 
1. Placez le dossier dans `www/` (WAMP) ou `htdocs/` (XAMPP)
2. Ouvrez phpMyAdmin → importez `bdd_mediatheque.sql`
3. Accédez à `http://localhost/mediatheque/index.php`
### 4. Lancement des tests — Partie 3 (PHPUnit)
 
> Composer doit être installé : `https://getcomposer.org/download/`
 
```bash
cd mediatheque
composer install
 
# Tous les tests
./vendor/bin/phpunit
 
# Avec le détail de chaque test
./vendor/bin/phpunit --testdox
 
# Un fichier de test spécifique
./vendor/bin/phpunit tests/LivreTest.php
```
 
### 5. Lancement — Partie 4 (Docker)
 
> Docker Desktop doit être installé et démarré : `https://www.docker.com/products/docker-desktop/`
 
```bash
cd mediatheque
 
# Créer le fichier .env à partir du template
cp .env.example .env
# Remplir les valeurs dans .env avec votre éditeur
 
# Démarrer les 3 conteneurs
docker compose up -d
 
# Vérifier que tout tourne
docker compose ps
```
 
| Service | URL |
|---|---|
| Application PHP | `http://localhost:8080` |
| phpMyAdmin | `http://localhost:8081` |
| MySQL | port `3306` (accès interne) |
 
```bash
# Lancer les tests unitaires (sans Docker)
./vendor/bin/phpunit --testdox
 
# Lancer les tests d'intégration (dans le conteneur Docker)
docker compose exec app ./vendor/bin/phpunit tests/IntegrationTest.php --testdox
 
# Arrêter les conteneurs
docker compose down
```
 
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

---
 
### Partie 3 — Tests Unitaires
 
```
mediatheque/
├── (structure Partie 2 inchangée)
│
├── tests/
│   ├── init.php                     # Chargement des classes avant les tests
│   ├── LivreTest.php                # Tests unitaires de la classe Livre
│   ├── DvdTest.php                  # Tests unitaires de la classe Dvd
│   ├── ArticleValidationTest.php    # Tests de l'encapsulation (setters/validation)
│   ├── PolymorphismeTest.php        # Tests du polymorphisme par héritage et interface
│   └── ArticleDAOTest.php           # Tests de la DAO avec mocks (sans base de données)
│
├── .github/
│   └── workflows/
│       └── phpunit.yml              # Workflow GitHub Actions (CI automatique)
│
├── composer.json                    # Dépendances du projet (PHPUnit en require-dev)
├── composer.lock                    # Versions exactes installées (versionné)
├── phpunit.xml                      # Configuration PHPUnit
└── vendor/                          # Dépendances installées par Composer (non versionné)
```
 
> 💡 **Ce qui change entre Partie 1 et Partie 2**
>
> Le dossier `classes/` (le **Model**) reste **identique** : les classes métier et d'accès aux données n'ont pas besoin d'être modifiées.
> Ce qui change, c'est que la logique applicative quitte les pages PHP pour rejoindre le **Controller**, et que le HTML pur est isolé dans les **Views**.

> 💡 **Ce qui change entre Partie 2 et Partie 3 :** aucun fichier de l'application n'est modifié. On ajoute uniquement l'infrastructure de tests : le dossier `tests/`, les fichiers de configuration Composer et PHPUnit, et le workflow GitHub Actions.
 
---

### Partie 4 — Containerisation Docker
 
```
mediatheque/
├── (structure Partie 3 inchangée)
│
├── Dockerfile                       # Recette de l'image PHP+Apache+Composer
├── docker-compose.yml               # Orchestration des 3 conteneurs
├── .env                             # Variables d'environnement (non versionné)
├── .env.example                     # Template des variables (versionné)
├── .dockerignore                    # Fichiers exclus de l'image Docker
│
├── docker/
│   └── apache.conf                  # Configuration Apache du conteneur
│
├── tests/
│   ├── (tests unitaires inchangés)
│   └── IntegrationTest.php          # Tests d'intégration (nécessitent Docker)
│
.github/
    └── workflows/
        └── phpunit.yml              # Mis à jour : 3 jobs (unitaires, intégration, build Docker)
```
 
> 💡 `classes/config.php` est le seul fichier applicatif modifié : il utilise désormais `getenv()` pour lire la configuration, ce qui le rend compatible XAMPP **et** Docker sans modification.
 
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

## 🧪 Couverture des tests
 
### Tests unitaires (Partie 3) — sans base de données, avec mocks
 
| Fichier de test | Classes testées | Tests |
|---|---|---|
| `LivreTest.php` | `Livre` | 9 |
| `DvdTest.php` | `Dvd` | 6 |
| `ArticleValidationTest.php` | `Article` (via Livre) | 9 |
| `PolymorphismeTest.php` | `Livre`, `Dvd`, `Empruntable` | 5 |
| `ArticleDAOTest.php` | `ArticleDAO` (mocks PDO) | 10 |
| **Sous-total** | | **39 tests** |
 
### Tests d'intégration (Partie 4) — avec Docker et vraie base de données
 
| Fichier de test | Ce qui est testé | Tests |
|---|---|---|
| `IntegrationTest.php` | Connexion PHP→MySQL, table, données, CRUD réel | 5 |
| **Total général** | | **44 tests** |
 
---
 
## 🔄 Pipeline CI/CD (GitHub Actions — Partie 4)
 
```
git push
    │
    ├─► Job 1 : Tests unitaires (ubuntu, sans Docker)
    │       └── ./vendor/bin/phpunit (mocks uniquement)
    │
    ├─► Job 2 : Tests d'intégration (si Job 1 ✅)
    │       └── docker compose up -d
    │       └── docker compose exec app ./vendor/bin/phpunit tests/IntegrationTest.php
    │
    └─► Job 3 : Docker build (si Job 1 ✅)
            └── docker build -t mediatheque-app .
```
 
---
 
💡 Pour comprendre en détail chaque étape de construction, référez-vous aux tutoriels associés :
- **Partie 1** → `PART1_TUTORIEL_POO.md`
- **Partie 2** → `PART2_TUTORIEL_MVC.md`
- **Partie 3** → `PART3_TUTORIEL_PHPUNIT.md`
- **Partie 4** → `PART4_TUTORIEL_DOCKER.md`


![PHPUnit Tests](https://github.com/gdelacroix/mediatheque/actions/workflows/phpunit.yml/badge.svg)
