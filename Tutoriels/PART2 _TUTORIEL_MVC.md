# Tutoriel MVC en PHP — Refactoring de la Médiathèque

**Prérequis :** avoir terminé le tutoriel POO. L'application médiathèque fonctionne.

**Objectif :** transformer le projet existant en architecture MVC, fichier par fichier, en comprenant pourquoi chaque déplacement de code est justifié.

**Durée :** une journée.

> Ce tutoriel est un **refactoring** : on ne repart pas de zéro. On réorganise le code existant. À la fin, l'application fait exactement la même chose qu'avant — mais son code est mieux découpé.

---

## Étape 0 — Comprendre MVC avant de toucher au code

### Le problème avec notre projet actuel

Ouvrez `index.php` du projet précédent. Vous y trouvez en mélange :
- une connexion à la base de données
- un appel à la DAO
- de la logique (`if empty($articles)`)
- du HTML

Ouvrez `formulaire.php`. Même constat : validation des données, création d'objet, enregistrement en base, ET le formulaire HTML sont tous dans le même fichier.

Ce mélange a un nom : le **couplage**. Tout est lié à tout. Si on veut changer la façon dont la liste s'affiche, on touche au même fichier que celui qui gère l'enregistrement. Si on veut changer la requête SQL, on risque de casser le HTML. C'est difficile à maintenir, à tester, et à faire évoluer.

### La solution : MVC

**MVC** (Model - View - Controller) est un **patron d'architecture** qui impose de séparer le code en trois responsabilités distinctes :

| Couche | Rôle | Règle d'or |
|---|---|---|
| **Model** | Les données et les règles métier | Ne sait pas qu'une page web existe |
| **View** | L'affichage HTML | Ne fait jamais de SQL, jamais de logique métier |
| **Controller** | Le chef d'orchestre | Reçoit la requête, appelle le Model, choisit la View |

Voyons comment ça s'articule sur un exemple concret — l'affichage de la liste :

```
Navigateur                 Controller              Model (DAO)           View
    │                          │                       │                  │
    │── GET index.php ────────>│                       │                  │
    │                          │── findAll() ─────────>│                  │
    │                          │<── $articles ─────────│                  │
    │                          │── render(liste, $articles) ─────────────>│
    │<── HTML ─────────────────────────────────────────────────────────── │
```

1. Le navigateur envoie une requête.
2. Le **Controller** la reçoit, demande les données au **Model**.
3. Le Model renvoie des objets PHP (pas de HTML).
4. Le Controller transmet ces objets à la **View**.
5. La View génère uniquement le HTML, sans se soucier d'où viennent les données.

### Ce qui NE change PAS

Bonne nouvelle : **tout le dossier `classes/` reste identique**. Les classes `Article`, `Livre`, `Dvd`, `DAO`, `ArticleDAO`, `Empruntable` constituent déjà la couche **Model**. Elles ne savent pas que des pages web existent — elles ne font que manipuler des données. C'est exactement ce qu'on attend d'un Model.

### Ce qui change

| Avant | Après | Pourquoi |
|---|---|---|
| `index.php` mélange tout | `index.php` est juste un routeur | Un seul point d'entrée |
| `formulaire.php` mélange tout | Controller + View séparés | Séparation des responsabilités |
| `includes/header.php` | `views/layout/header.php` | Les vues ont leur propre dossier |
| Pas de Controller | `controllers/ArticleController.class.php` | La logique a sa place |

---

## Étape 1 — La nouvelle arborescence

Voici la cible. Prenez le temps de la lire et de comparer avec l'ancienne.

```
mediatheque/
│
├── index.php                          ← NOUVEAU : front controller (point d'entrée unique)
│
├── classes/                           ← INCHANGÉ : c'est le Model
│   ├── config.php
│   ├── DAO.class.php
│   ├── Article.class.php
│   ├── Empruntable.class.php
│   ├── Livre.class.php
│   └── Dvd.class.php
│
├── controllers/                       ← NOUVEAU dossier
│   └── ArticleController.class.php
│
└── views/                             ← NOUVEAU dossier (remplace includes/ et les pages)
    ├── layout/
    │   ├── header.php                 ← déplacé depuis includes/
    │   └── footer.php                 ← déplacé depuis includes/
    └── article/
        ├── liste.php                  ← extrait de index.php (le HTML uniquement)
        └── formulaire.php             ← extrait de formulaire.php (le HTML uniquement)
```

**Ce qu'on supprime :**
- `formulaire.php` (à la racine) → le HTML part dans `views/article/formulaire.php`, la logique dans le Controller
- `supprimer.php` → devient une méthode du Controller
- `emprunter.php` → devient une méthode du Controller
- `includes/` → renommé `views/layout/`

**Ce qu'on crée :**
- Un `index.php` minimaliste qui fait office de **front controller** (routeur)
- `controllers/ArticleController.class.php`
- Les vues dans `views/article/`

### Créez immédiatement la nouvelle structure de dossiers

Dans votre projet, créez (sans rien supprimer encore) :
```
mediatheque/controllers/
mediatheque/views/layout/
mediatheque/views/article/
```

---

## Étape 2 — Le Front Controller : `index.php`

### Pourquoi un point d'entrée unique ?

Dans le projet précédent, chaque page avait sa propre URL :
- `index.php`
- `formulaire.php`
- `supprimer.php`
- `emprunter.php`

En MVC, **toutes les requêtes passent par un seul fichier** : `index.php`. L'action demandée est transmise via un paramètre GET :

```
index.php                → afficher la liste
index.php?action=formulaire        → formulaire d'ajout
index.php?action=formulaire&id=3   → formulaire de modification
index.php?action=supprimer&id=3    → supprimer
index.php?action=emprunter&id=3    → emprunter/rendre
```

C'est le **Front Controller**. Son rôle est minimal : lire l'action demandée, et déléguer au bon Controller.

### Remplacez le contenu de `index.php` par ceci :

```php
<?php

// ============================================================
//  FRONT CONTROLLER
//  Point d'entrée unique de l'application.
//  Ce fichier ne contient aucune logique métier.
//  Il lit l'action demandée et délègue au bon contrôleur.
// ============================================================

// Chargement de toutes les classes nécessaires
require_once __DIR__ . '/classes/config.php';
require_once __DIR__ . '/classes/DAO.class.php';
require_once __DIR__ . '/classes/Article.class.php';
require_once __DIR__ . '/classes/Empruntable.class.php';
require_once __DIR__ . '/classes/Livre.class.php';
require_once __DIR__ . '/classes/Dvd.class.php';
require_once __DIR__ . '/controllers/ArticleController.class.php';

use Mediatheque\Controller\ArticleController;

// Connexion à la base, passée au contrôleur
$pdo        = getConnexion();
$controller = new ArticleController($pdo);

// Lecture de l'action demandée (liste par défaut)
$action = $_GET['action'] ?? 'liste';

// Routage : on délègue au contrôleur selon l'action
switch ($action) {
    case 'liste':
        $controller->liste();
        break;

    case 'formulaire':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->enregistrer();
        } else {
            $controller->formulaire();
        }
        break;

    case 'supprimer':
        $controller->supprimer();
        break;

    case 'emprunter':
        $controller->emprunter();
        break;

    default:
        $controller->liste();
}
```

### Explication

**`$action = $_GET['action'] ?? 'liste'`**
On lit le paramètre `action` dans l'URL. Si absent, l'action par défaut est `'liste'`.

**`new ArticleController($pdo)`**
On crée le contrôleur en lui passant la connexion PDO. C'est le contrôleur qui la transmettra à la DAO.

**Le `switch`**
C'est le **routeur** : selon l'action, on appelle la bonne méthode du contrôleur. Le front controller ne contient aucune logique métier — il ne sait pas ce que fait `liste()` ou `supprimer()`, il se contente de déléguer.

**`if ($_SERVER['REQUEST_METHOD'] === 'POST')`**
Pour l'action `formulaire`, on distingue :
- GET → afficher le formulaire (méthode `formulaire()`)
- POST → traiter les données soumises (méthode `enregistrer()`)

C'est une séparation propre : une méthode = une responsabilité.

**`use Mediatheque\Controller\ArticleController`**
Le contrôleur sera dans le sous-namespace `Mediatheque\Controller`. On retrouve ici les sous-namespaces introduits en bonus dans le tutoriel précédent — cette fois on les utilise vraiment.

---

## Étape 3 — Le Controller : `controllers/ArticleController.class.php`

C'est la pièce centrale du MVC. Le Controller :
1. reçoit la requête (via les méthodes appelées par le front controller)
2. interagit avec le Model (la DAO)
3. prépare les données
4. transmet ces données à la Vue et demande son affichage

### Une méthode utilitaire : `render()`

Avant d'écrire les méthodes métier, on a besoin d'une façon propre d'afficher une vue. On va créer une méthode privée `render()` qui :
- reçoit le nom d'une vue et un tableau de données
- extrait les données en variables locales (avec `extract()`)
- inclut le fichier de vue

```php
private function render(string $vue, array $donnees = []): void
{
    extract($donnees);
    // $donnees = ['articles' => [...], 'message' => '...']
    // devient : $articles = [...]; $message = '...'
    // Ces variables sont disponibles dans le fichier de vue inclus.
    require __DIR__ . '/../views/' . $vue . '.php';
}
```

### Créez `controllers/ArticleController.class.php` :

```php
<?php

namespace Mediatheque\Controller;

use Mediatheque\ArticleDAO;
use Mediatheque\Livre;
use Mediatheque\Dvd;

// ============================================================
//  CLASSE ArticleController
//
//  Contient une méthode par action possible sur les articles.
//  Le Controller ne produit jamais de HTML directement.
//  Il prépare des données et les confie à une Vue.
// ============================================================

class ArticleController
{
    private ArticleDAO $dao;

    public function __construct(\PDO $pdo)
    {
        $this->dao = new ArticleDAO($pdo);
    }

    // ── Action : afficher la liste ────────────────────────────
    public function liste(): void
    {
        $articles = $this->dao->findAll();
        $message  = $_GET['message'] ?? null;
        $erreur   = $_GET['erreur']  ?? null;

        $this->render('article/liste', [
            'articles' => $articles,
            'message'  => $message,
            'erreur'   => $erreur,
        ]);
    }

    // ── Action : afficher le formulaire (ajout ou modification) ──
    public function formulaire(): void
    {
        $id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $article = null;

        if ($id > 0) {
            $article = $this->dao->find($id);
            if ($article === null) {
                $this->redirect('liste', erreur: "Article introuvable.");
            }
        }

        $this->render('article/formulaire', [
            'article' => $article,
            'erreurs' => [],
            'post'    => [],
        ]);
    }

    // ── Action : traiter le formulaire soumis (POST) ──────────
    public function enregistrer(): void
    {
        $id     = (int) ($_POST['id']     ?? 0);
        $type   = $_POST['type']   ?? '';
        $titre  = trim($_POST['titre']  ?? '');
        $auteur = trim($_POST['auteur'] ?? '');
        $annee  = $_POST['annee']  ?? '';
        $mode   = $id > 0 ? 'modification' : 'ajout';

        // ── Validation ────────────────────────────────────────
        $erreurs = [];

        if ($mode === 'ajout' && !in_array($type, ['livre', 'dvd'], true)) {
            $erreurs[] = "Veuillez choisir un type (Livre ou DVD).";
        }
        if ($titre === '') {
            $erreurs[] = "Le titre est obligatoire.";
        }
        if ($auteur === '') {
            $erreurs[] = "L'auteur / réalisateur est obligatoire.";
        }
        if (!ctype_digit($annee)) {
            $erreurs[] = "L'année doit être un nombre entier.";
        }

        // ── Si erreurs : réafficher le formulaire ─────────────
        if (!empty($erreurs)) {
            $article = $id > 0 ? $this->dao->find($id) : null;

            $this->render('article/formulaire', [
                'article' => $article,
                'erreurs' => $erreurs,
                'post'    => $_POST,
            ]);
            return;
        }

        // ── Sinon : enregistrer ───────────────────────────────
        try {
            if ($mode === 'modification') {
                $article = $this->dao->find($id);
                $article->setTitre($titre);
                $article->setAuteur($auteur);
                $article->setAnnee((int) $annee);
                $this->dao->update($id, $article);
                $this->redirect('liste', message: 'Article mis à jour !');

            } else {
                $article = ($type === 'livre')
                    ? new Livre($titre, $auteur, (int) $annee)
                    : new Dvd($titre, $auteur, (int) $annee);
                $this->dao->create($article);
                $this->redirect('liste', message: 'Article ajouté !');
            }

        } catch (\Exception $e) {
            $article = $id > 0 ? $this->dao->find($id) : null;
            $this->render('article/formulaire', [
                'article' => $article,
                'erreurs' => ["Erreur : " . $e->getMessage()],
                'post'    => $_POST,
            ]);
        }
    }

    // ── Action : supprimer un article ─────────────────────────
    public function supprimer(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $this->dao->delete($id);
        }

        $this->redirect('liste', message: 'Article supprimé.');
    }

    // ── Action : emprunter ou rendre ──────────────────────────
    public function emprunter(): void
    {
        $id     = (int) ($_GET['id']   ?? 0);
        $action = $_GET['type'] ?? '';

        $article = $this->dao->find($id);

        if ($article === null) {
            $this->redirect('liste', erreur: "Article introuvable.");
        }

        try {
            if ($action === 'emprunter') {
                $article->emprunter();
                $msg = "« {$article->getTitre()} » a été emprunté.";
            } else {
                $article->rendre();
                $msg = "« {$article->getTitre()} » a été rendu.";
            }

            $this->dao->update($id, $article);
            $this->redirect('liste', message: $msg);

        } catch (\RuntimeException $e) {
            $this->redirect('liste', erreur: $e->getMessage());
        }
    }

    // ── Méthodes privées utilitaires ──────────────────────────

    /**
     * Inclut un fichier de vue en lui transmettant des variables.
     * Le nom de vue est relatif au dossier views/.
     * Exemple : render('article/liste', ['articles' => $articles])
     */
    private function render(string $vue, array $donnees = []): void
    {
        // extract() transforme ['articles' => [...]] en $articles = [...]
        // Ces variables deviennent disponibles dans le fichier de vue.
        extract($donnees);
        require __DIR__ . '/../views/' . $vue . '.php';
    }

    /**
     * Redirige vers une action du front controller.
     * Les arguments nommés PHP 8 permettent d'écrire :
     *   $this->redirect('liste', message: 'OK')
     * au lieu de :
     *   $this->redirect('liste', 'OK', null)
     */
    private function redirect(
        string  $action,
        ?string $message = null,
        ?string $erreur  = null
    ): never {
        $params = ['action' => $action];
        if ($message !== null) $params['message'] = $message;
        if ($erreur  !== null) $params['erreur']  = $erreur;

        header('Location: index.php?' . http_build_query($params));
        exit;
    }
}
```

### Explication bloc par bloc

**`namespace Mediatheque\Controller`**

Le Controller est dans un sous-namespace distinct du Model. Ça reflète qu'il joue un rôle différent. Il a besoin des classes du Model, donc il les importe avec `use Mediatheque\ArticleDAO` etc.

**Le constructeur**

```php
public function __construct(\PDO $pdo)
{
    $this->dao = new ArticleDAO($pdo);
}
```

Le Controller instancie la DAO dont il a besoin. On n'expose pas la DAO à l'extérieur : c'est un détail d'implémentation interne. Si demain on change de DAO, seul le Controller est impacté.

**`liste()`**

```php
public function liste(): void
{
    $articles = $this->dao->findAll();
    $this->render('article/liste', ['articles' => $articles, ...]);
}
```

Simple et lisible : on appelle le Model, on passe le résultat à la Vue. Aucun HTML ici.

**`formulaire()` et `enregistrer()` : deux méthodes au lieu d'une**

Dans `formulaire.php` précédent, tout était dans un seul fichier. Maintenant :
- `formulaire()` gère l'affichage (GET) : charger l'article si modification, afficher la vue.
- `enregistrer()` gère la soumission (POST) : valider, enregistrer, rediriger.

Chaque méthode a une seule responsabilité. C'est le principe **Single Responsibility**.

**`render(string $vue, array $donnees)`**

```php
extract($donnees);
require __DIR__ . '/../views/' . $vue . '.php';
```

`extract()` est une fonction PHP qui transforme un tableau associatif en variables locales. Si `$donnees = ['articles' => [...], 'message' => 'OK']`, après `extract()` on a `$articles = [...]` et `$message = 'OK'`. Ces variables sont accessibles dans le fichier de vue inclus juste après.

**`redirect(string $action, ?string $message, ?string $erreur): never`**

```php
header('Location: index.php?' . http_build_query($params));
exit;
```

`http_build_query(['action' => 'liste', 'message' => 'OK'])` construit proprement la chaîne `action=liste&message=OK` (les caractères spéciaux sont encodés). Le type de retour `never` indique que cette méthode ne revient jamais — elle appelle toujours `exit`. C'est une information utile pour les outils d'analyse de code.

Les **arguments nommés PHP 8** permettent d'écrire `$this->redirect('liste', message: 'OK')` au lieu de devoir préciser tous les arguments dans l'ordre : propre et lisible.
---

## Étape 4 — Les vues layout : `views/layout/`

Les vues layout sont les parties communes à toutes les pages. On les déplace simplement depuis `includes/` vers `views/layout/`, avec une adaptation : les liens pointent maintenant tous vers `index.php?action=...`.

### Créez `views/layout/header.php` :

```php
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médiathèque</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f8; margin: 0; color: #222; }
        header { background: #2c3e66; color: white; padding: 16px 30px; }
        header h1 { margin: 0; font-size: 22px; }
        nav { margin-top: 8px; }
        nav a { color: #c5d4f0; text-decoration: none; margin-right: 20px; font-size: 14px; }
        nav a:hover { text-decoration: underline; }
        main { max-width: 900px; margin: 30px auto; background: white;
               padding: 25px 30px; border-radius: 8px;
               box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        h2 { color: #2c3e66; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #e0e0e0;
                 font-size: 14px; text-align: left; }
        th { background: #eef1f8; color: #2c3e66; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px;
                 font-size: 12px; font-weight: bold; color: white; }
        .badge-livre { background: #2c6e9e; }
        .badge-dvd   { background: #8e3a2e; }
        .dispo-oui { color: #2e7d4f; font-weight: bold; }
        .dispo-non { color: #b85042; font-weight: bold; }
        .btn { display: inline-block; background: #2c3e66; color: white;
               padding: 7px 14px; border: none; border-radius: 5px;
               cursor: pointer; font-size: 13px; text-decoration: none; margin-right: 4px; }
        .btn:hover          { background: #3a4f8c; }
        .btn-danger         { background: #b85042; }
        .btn-danger:hover   { background: #922e22; }
        .btn-success        { background: #2e7d4f; }
        .btn-success:hover  { background: #245f3c; }
        .btn-warning        { background: #b07d1a; }
        .btn-warning:hover  { background: #8a6114; }
        .alerte { padding: 10px 14px; border-radius: 5px;
                  margin-bottom: 15px; font-size: 14px; }
        .alerte-succes { background: #e4f6e9; color: #1d7a3a; }
        .alerte-erreur { background: #fde8e7; color: #a32f2f; }
        form label { display: block; font-size: 13px; font-weight: bold;
                     color: #444; margin-top: 14px; }
        form input, form select { width: 100%; padding: 9px; margin-top: 4px;
                                  border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        form .btn { margin-top: 20px; }
        .lien-retour { display: inline-block; margin-top: 16px;
                       color: #1c7293; font-size: 14px; text-decoration: none; }
        .lien-retour:hover { text-decoration: underline; }
    </style>
</head>
<body>
<header>
    <h1>📚 Médiathèque</h1>
    <nav>
        <!-- Tous les liens pointent vers index.php avec un paramètre action -->
        <a href="index.php?action=liste">Liste des articles</a>
        <a href="index.php?action=formulaire">Ajouter un article</a>
    </nav>
</header>
<main>
```

### Créez `views/layout/footer.php` :

```php
</main>
</body>
</html>
```

### Ce qui a changé par rapport à l'ancien `includes/`

```html
<!-- AVANT -->
<a href="index.php">Liste des articles</a>
<a href="formulaire.php">Ajouter un article</a>

<!-- APRÈS : tout passe par index.php avec un paramètre action -->
<a href="index.php?action=liste">Liste des articles</a>
<a href="index.php?action=formulaire">Ajouter un article</a>
```

C'est tout. La structure HTML est identique — seules les URLs changent.

---

## Étape 5 — La vue liste : `views/article/liste.php`

### Ce qu'est une vue en MVC

Une vue est un fichier PHP qui **ne fait qu'afficher**. Elle :
- ne contacte jamais la base de données
- ne contient aucune logique métier (pas de `if ($disponible)` pour décider d'emprunter, ça c'est le Controller)
- reçoit des variables préparées par le Controller (ici : `$articles`, `$message`, `$erreur`)
- produit uniquement du HTML

La vue peut contenir des `if` et des `foreach` — mais uniquement pour des décisions **d'affichage** (afficher ou non un bloc HTML, répéter une ligne de tableau...), pas pour des décisions métier.

### Créez `views/article/liste.php` :

```php
<?php require __DIR__ . '/../layout/header.php'; ?>

<h2>Liste des articles</h2>

<?php if ($message): ?>
    <div class="alerte alerte-succes">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($erreur): ?>
    <div class="alerte alerte-erreur">
        <?= htmlspecialchars($erreur) ?>
    </div>
<?php endif; ?>

<?php if (empty($articles)): ?>
    <p>Aucun article dans la médiathèque.</p>
<?php else: ?>

<table>
    <thead>
        <tr>
            <th>Type</th>
            <th>Description</th>
            <th>Disponibilité</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($articles as $article): ?>
        <tr>
            <td>
                <span class="badge badge-<?= $article->getType() ?>">
                    <?= strtoupper($article->getType()) ?>
                </span>
            </td>

            <td><?= htmlspecialchars($article->description()) ?></td>

            <td>
                <?php if ($article->isDisponible()): ?>
                    <span class="dispo-oui">✔ Disponible</span>
                <?php else: ?>
                    <span class="dispo-non">✘ Emprunté</span>
                <?php endif; ?>
            </td>

            <td>
                <?php if ($article->isDisponible()): ?>
                    <a class="btn btn-success"
                       href="index.php?action=emprunter&id=<?= $article->getId() ?>&type=emprunter">
                        Emprunter
                    </a>
                <?php else: ?>
                    <a class="btn btn-warning"
                       href="index.php?action=emprunter&id=<?= $article->getId() ?>&type=rendre">
                        Rendre
                    </a>
                <?php endif; ?>

                <a class="btn"
                   href="index.php?action=formulaire&id=<?= $article->getId() ?>">
                    Modifier
                </a>

                <a class="btn btn-danger"
                   href="index.php?action=supprimer&id=<?= $article->getId() ?>"
                   onclick="return confirm('Supprimer définitivement cet article ?');">
                    Supprimer
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php endif; ?>

<p style="margin-top:20px;">
    <a class="btn" href="index.php?action=formulaire">+ Ajouter un article</a>
</p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
```

### Explication

**Les variables `$articles`, `$message`, `$erreur` viennent de `render()`**

La vue ne sait pas d'où viennent ces variables. Elle sait juste qu'elles existent. C'est le Controller qui les a préparées et transmises via `extract()`. C'est la règle fondamentale : **la Vue est passive, elle affiche ce qu'on lui donne**.

**`require __DIR__ . '/../layout/header.php'`**

Depuis `views/article/liste.php`, `__DIR__` vaut `.../views/article`. On remonte d'un niveau (`/../`) pour accéder à `views/layout/header.php`.

**Tous les liens vers `index.php?action=...`**

```html
href="index.php?action=formulaire&id=<?= $article->getId() ?>"
href="index.php?action=supprimer&id=<?= $article->getId() ?>"
href="index.php?action=emprunter&id=<?= $article->getId() ?>&type=emprunter"
```

Plus de liens vers `formulaire.php`, `supprimer.php`, `emprunter.php`. Tout passe par `index.php`. Le paramètre `action` indique au front controller quoi faire.

**Le polymorphisme est toujours là**

```php
foreach ($articles as $article) {
    echo $article->description(); // Livre ou Dvd : même appel, résultat différent
    echo $article->getType();
}
```

La vue bénéficie du polymorphisme sans même le savoir — elle appelle les mêmes méthodes sur tous les objets.

---

## Étape 6 — La vue formulaire : `views/article/formulaire.php`

### Créez `views/article/formulaire.php` :

```php
<?php require __DIR__ . '/../layout/header.php'; ?>

<?php
// Le Controller nous a transmis :
// $article : null (ajout) ou un objet Article (modification)
// $erreurs : tableau de messages d'erreur (vide si premier chargement)
// $post    : $_POST du dernier envoi (vide si premier chargement)

$mode = ($article !== null) ? 'modification' : 'ajout';
$id   = ($article !== null) ? $article->getId() : 0;
?>

<h2>
    <?= $mode === 'modification'
        ? "Modifier : " . htmlspecialchars($article->getTitre())
        : "Ajouter un article" ?>
</h2>

<?php foreach ($erreurs as $erreur): ?>
    <div class="alerte alerte-erreur">
        <?= htmlspecialchars($erreur) ?>
    </div>
<?php endforeach; ?>

<form method="post"
      action="index.php?action=formulaire<?= $id > 0 ? "&id=$id" : '' ?>">

    <?php if ($mode === 'modification'): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
    <?php endif; ?>

    <?php if ($mode === 'ajout'): ?>
        <label for="type">Type d'article</label>
        <select id="type" name="type">
            <option value="">-- Choisir --</option>
            <option value="livre"
                <?= (($post['type'] ?? '') === 'livre') ? 'selected' : '' ?>>
                Livre
            </option>
            <option value="dvd"
                <?= (($post['type'] ?? '') === 'dvd') ? 'selected' : '' ?>>
                DVD
            </option>
        </select>
    <?php else: ?>
        <label>Type (non modifiable)</label>
        <input type="text"
               value="<?= strtoupper($article->getType()) ?>"
               disabled>
    <?php endif; ?>

    <label for="titre">Titre</label>
    <input type="text"
           id="titre"
           name="titre"
           value="<?= htmlspecialchars(
               $post['titre'] ?? ($article ? $article->getTitre() : '')
           ) ?>">

    <label for="auteur">Auteur (livre) / Réalisateur (DVD)</label>
    <input type="text"
           id="auteur"
           name="auteur"
           value="<?= htmlspecialchars(
               $post['auteur'] ?? ($article ? $article->getAuteur() : '')
           ) ?>">

    <label for="annee">Année</label>
    <input type="number"
           id="annee"
           name="annee"
           value="<?= htmlspecialchars(
               $post['annee'] ?? ($article ? (string) $article->getAnnee() : '')
           ) ?>">

    <button type="submit" class="btn">
        <?= $mode === 'modification' ? 'Mettre à jour' : 'Enregistrer' ?>
    </button>
</form>

<a class="lien-retour" href="index.php?action=liste">← Retour à la liste</a>

<?php require __DIR__ . '/../layout/footer.php'; ?>
```

### Explication

**`$mode` est déterminé dans la Vue ici, est-ce du Controller ?**

Non — c'est une décision **d'affichage** : selon qu'on est en ajout ou modification, on affiche un titre différent, des champs différents, un libellé de bouton différent. C'est bien la responsabilité de la Vue.

La règle est : si la décision change **ce qu'on voit**, c'est la Vue. Si elle change **ce qu'on fait** (enregistrer, supprimer...), c'est le Controller.

**`$post` au lieu de `$_POST`**

La Vue ne lit jamais `$_POST` directement. C'est le Controller qui a passé `'post' => $_POST` à `render()`. Pourquoi ? Parce que si on voulait tester la Vue indépendamment (sans HTTP), on pourrait lui passer n'importe quel tableau `$post`. La Vue est ainsi complètement indépendante de l'environnement HTTP.

**`action="index.php?action=formulaire<?= $id > 0 ? "&id=$id" : '' ?>"`**

Le formulaire pointe vers `index.php`. L'action `formulaire` avec la méthode POST sera interceptée par le front controller et dirigée vers `$controller->enregistrer()`.

---

## Étape 7 — Nettoyage : supprimer les anciens fichiers

Maintenant que tout est en place, supprimez les fichiers qui ne servent plus :

- `formulaire.php` (à la racine) → remplacé par `views/article/formulaire.php` + Controller
- `supprimer.php` → remplacé par `ArticleController::supprimer()`
- `emprunter.php` → remplacé par `ArticleController::emprunter()`
- `includes/` → remplacé par `views/layout/`

Le `index.php` original a déjà été remplacé à l'étape 2.

---

## Étape 8 — Tester l'application

Ouvrez `http://localhost/mediatheque/index.php`.

L'application doit fonctionner **exactement comme avant** du point de vue de l'utilisateur. Vérifiez :

- [ ] La liste s'affiche : `http://localhost/mediatheque/index.php`
- [ ] L'URL de la liste est `index.php` ou `index.php?action=liste`
- [ ] Ajouter un article → `index.php?action=formulaire` dans la barre d'adresse
- [ ] Erreur de validation → le formulaire réapparaît avec les erreurs
- [ ] Modifier → `index.php?action=formulaire&id=X` dans la barre d'adresse
- [ ] Formulaire pré-rempli en modification
- [ ] Supprimer → confirmation → suppression → retour liste
- [ ] Emprunter / Rendre → disponibilité mise à jour
- [ ] Les anciens URLs (`formulaire.php`, `supprimer.php`...) ne fonctionnent plus (erreur 404 normale)

---

## Étape 9 — Bilan : avant / après

Voici le récapitulatif visuel de ce qui a bougé et pourquoi.

### Arborescence finale

```
mediatheque/
│
├── index.php                          Front Controller (routeur)
│                                      → lit ?action= et délègue
│
├── classes/                           MODEL (inchangé)
│   ├── config.php
│   ├── DAO.class.php
│   ├── Article.class.php
│   ├── Empruntable.class.php
│   ├── Livre.class.php
│   └── Dvd.class.php
│
├── controllers/                       CONTROLLER
│   └── ArticleController.class.php
│       ├── liste()
│       ├── formulaire()
│       ├── enregistrer()
│       ├── supprimer()
│       ├── emprunter()
│       ├── render()      ← privée
│       └── redirect()    ← privée
│
└── views/                             VIEW
    ├── layout/
    │   ├── header.php
    │   └── footer.php
    └── article/
        ├── liste.php
        └── formulaire.php
```

### Qui fait quoi maintenant ?

| Responsabilité | Avant | Après |
|---|---|---|
| Recevoir la requête HTTP | `index.php`, `formulaire.php`... | `index.php` seul |
| Lire `$_GET` / `$_POST` | Partout | Controller uniquement |
| Appeler la DAO | Partout | Controller uniquement |
| Valider les données | `formulaire.php` | Controller (`enregistrer()`) |
| Créer/modifier des objets | `formulaire.php` | Controller |
| Rediriger | `supprimer.php`, `emprunter.php`... | Controller (`redirect()`) |
| Générer du HTML | Partout | Vues uniquement |
| Accéder à la base de données | DAO (déjà bien isolé) | DAO (inchangé) |

### Ce que chaque fichier a le droit de faire

```
index.php        → lire $_GET['action'], instancier le Controller, appeler une méthode
Controller       → lire $_GET/$_POST, appeler la DAO, appeler render() ou redirect()
Vue              → afficher les variables reçues, inclure layout/header et footer
Model (classes/) → manipuler des données, parler à MySQL via PDO
```

---

## Étape 10 (bonus) — Ajouter une deuxième entité : `Adherent`

Pour consolider l'architecture MVC, ajoutez la gestion des adhérents. C'est l'exercice qui prouve que l'architecture est bien pensée : ajouter une entité ne nécessite que d'ajouter des fichiers, sans toucher à l'existant.

### Ce qu'il faut créer

1. **Model** (déjà vu dans le tutoriel POO) : `classes/Adherent.class.php` avec `Adherent` et `AdherentDAO`.

2. **Controller** : `controllers/AdherentController.class.php` avec les mêmes méthodes (`liste`, `formulaire`, `enregistrer`, `supprimer`). Il hérite de rien (chaque Controller est indépendant), mais a la même méthode privée `render()`.

3. **Vues** : `views/adherent/liste.php` et `views/adherent/formulaire.php`.

4. **Front Controller** : ajouter les nouvelles actions dans le `switch` de `index.php` :

```php
case 'adherents':
    $controller = new AdherentController($pdo);
    $controller->liste();
    break;

case 'formulaire-adherent':
    $controller = new AdherentController($pdo);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->enregistrer();
    } else {
        $controller->formulaire();
    }
    break;
```

5. **Navigation** : ajouter le lien dans `views/layout/header.php` :

```html
<a href="index.php?action=adherents">Adhérents</a>
```

**Remarquez** : on n'a pas touché à `ArticleController`, ni aux vues des articles, ni aux classes du Model existant. L'architecture MVC permet d'**étendre** l'application sans risquer de **casser** ce qui existe.

---

