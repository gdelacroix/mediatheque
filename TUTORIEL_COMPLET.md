# Tutoriel POO en PHP — Application "Médiathèque"


**Objectif :** construire une application web PHP pas à pas, en comprenant chaque ligne de code.

**Durée :** une journée.

**Prérequis :** WAMP / MAMP / XAMPP installé, VS Code ou tout autre éditeur.

> **Comment utiliser ce tutoriel ?**
> Chaque étape vous dit quel fichier créer, vous donne le code exact à taper, et vous explique ce que fait chaque ligne. Lisez les explications **avant** de taper le code.

---

## Ce qu'on va construire

Une application de gestion de médiathèque : liste des livres et DVD, ajout, modification, suppression, emprunt/retour.

Notions mises en pratique : **namespaces**, **encapsulation**, **héritage**, **polymorphisme par héritage**, **polymorphisme par interface**, **CRUD**, **DAO**.

---

## Convention de nommage des fichiers

- Les fichiers contenant des classes : extension **`.class.php`** (ex : `Article.class.php`)
- Un même fichier `.class.php` regroupe **tout ce qui concerne une entité** : la classe métier ET sa DAO. Exemple : `Article.class.php` contiendra la classe `Article` et la classe `ArticleDAO`.
- Les pages web gardent l'extension `.php` classique (ex : `index.php`)

---

## Arborescence complète du projet

Voici tout ce qu'on va construire. Gardez ce plan sous les yeux.

```
mediatheque/
│
├── bdd_mediatheque.sql          ← fourni au départ
│
├── index.php                     ← liste des articles
├── formulaire.php                ← ajout ET modification (une seule page)
├── supprimer.php                 ← suppression
├── emprunter.php                 ← emprunter / rendre
│
├── includes/
│   ├── header.php
│   └── footer.php
│
└── classes/
    ├── config.php                ← connexion PDO
    ├── DAO.class.php             ← DAO générique et abstraite
    ├── Article.class.php         ← class Article + class ArticleDAO
    ├── Empruntable.class.php     ← interface Empruntable
    ├── Livre.class.php           ← class Livre
    └── Dvd.class.php             ← class Dvd
```

> **Remarquez :** il n'y a pas d'`ajouter.php` et de `modifier.php` séparés. Une seule page `formulaire.php` gère les deux cas. Si un `id` est présent dans l'URL (`?id=3`), on est en mode modification. Sinon, on est en mode ajout.

---

## Étape 1 — Créer la base de données

1. Démarrez WAMP, MAMP ou XAMPP. Ouvrez phpMyAdmin : `http://localhost/phpmyadmin`
2. Onglet **SQL** → copiez-collez le contenu de `bdd_mediatheque.sql` → **Exécuter**
3. Vérifiez que la base `mediatheque` et la table `article` avec 6 lignes sont bien là.

Cliquez sur `article` → onglet **Structure** pour bien visualiser les colonnes :

| Colonne | Type | Rôle |
|---|---|---|
| id | INT AUTO_INCREMENT | Identifiant unique, géré par MySQL |
| type | ENUM('livre','dvd') | Distingue un livre d'un DVD |
| titre | VARCHAR(150) | Titre |
| auteur | VARCHAR(150) | Auteur ou réalisateur |
| annee | INT | Année de publication / sortie |
| disponible | TINYINT(1) | 1 = disponible, 0 = emprunté |

Livres et DVD cohabitent dans **la même table**. C'est la colonne `type` qui les distingue. La DAO s'occupera de transformer chaque ligne en objet `Livre` ou `Dvd`.

Créez ensuite la structure de dossiers dans `www/` (WAMP) ou `htdocs/` (XAMPP) :

```
mediatheque/
├── classes/
└── includes/
```

---

## Étape 2 — Connexion à la base : `classes/config.php`

Ce fichier ne contient pas de classe (juste une fonction utilitaire), donc pas de `.class.php` et pas de namespace.

### Créez `classes/config.php` :

```php
<?php

function getConnexion(): PDO
{
    $host     = '127.0.0.1';
    $dbname   = 'mediatheque';
    $user     = 'root';
    $password = '';

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        return $pdo;

    } catch (PDOException $e) {
        die('Erreur de connexion : ' . $e->getMessage());
    }
}
```

### Explication

`PDO` (PHP Data Objects) est la façon moderne de parler à une base de données en PHP.

Le premier argument du constructeur est le **DSN** (Data Source Name) : il décrit le type de base, le serveur et le nom de la base. Les options passées dans le tableau `[ ]` sont importantes :
- `ERRMODE_EXCEPTION` : si une requête SQL échoue, PHP lève une exception au lieu de rater silencieusement. Bien plus facile à déboguer.
- `FETCH_ASSOC` : les résultats des `SELECT` seront des tableaux associatifs (`$row['titre']`).

Le `try/catch` intercepte toute erreur de connexion (mauvais mot de passe, base inexistante...) et affiche un message clair via `die()`.

### ✅ Test

Créez un fichier temporaire `test_connexion.php` à la racine :
```php
<?php
require_once __DIR__ . '/classes/config.php';
$pdo = getConnexion();
echo "Connexion OK !";
```
Ouvrez `http://localhost/mediatheque/test_connexion.php`. Supprimez ce fichier une fois le test passé.

---

## Étape 3 — Les namespaces

C'est une notion fondamentale à comprendre **avant** d'écrire les classes. Lisez attentivement cette étape.

### Le problème qu'ils résolvent

Imaginez deux projets qui définissent chacun une classe `Article`. Si on charge les deux dans la même application, PHP ne sait plus laquelle utiliser et plante avec une erreur *"Cannot redeclare class Article"*.

Plus généralement, dans un projet réel qui utilise des librairies tierces, les conflits de noms de classes sont fréquents.

### La solution : les namespaces

Un namespace (espace de noms) est comme un **chemin de dossier pour les classes**. Au lieu de juste `Article`, la classe s'appelle `Mediatheque\Article`. Au lieu de juste `DAO`, ce sera `Mediatheque\DAO`. Impossible de confondre avec une classe `Article` d'une autre librairie, qui s'appellerait par exemple `MonPlugin\Article`.

### La syntaxe

**Dans le fichier de la classe**, tout en haut (avant toute autre instruction) :

```php
<?php

namespace Mediatheque;

class Article { ... }
```

Le namespace `Mediatheque` correspond au nom de notre application. On pourrait aussi faire des sous-namespaces (`Mediatheque\Model\Article`, `Mediatheque\Repository\ArticleDAO`...) mais pour commencer, un seul niveau suffit.

**Dans les fichiers qui utilisent ces classes** (les pages web), deux façons de s'en servir :

```php
// Option 1 : écrire le chemin complet à chaque fois (verbeux)
$dao = new Mediatheque\ArticleDAO($pdo);

// Option 2 : déclarer un `use` une seule fois en haut du fichier,
// puis utiliser le nom court partout
use Mediatheque\ArticleDAO;
use Mediatheque\Livre;
use Mediatheque\Dvd;

$dao = new ArticleDAO($pdo);   // PHP sait grâce au `use` que c'est Mediatheque\ArticleDAO
```

On utilisera toujours l'option 2 : plus lisible.

### Règle importante : `use` ne charge pas les fichiers

`use` ne remplace **pas** `require_once`. Ce sont deux mécanismes distincts :
- `require_once` **charge** le fichier PHP qui contient la classe.
- `use` dit à PHP sous **quel nom court** on veut l'appeler.

Il faut toujours les deux :
```php
require_once __DIR__ . '/classes/Livre.class.php';   // charge le fichier
use Mediatheque\Livre;                                // déclare le raccourci
```

### Namespace et classe dans le même namespace

Quand deux classes sont dans le même namespace, elles se voient directement sans `use`. Par exemple, `Livre` et `Article` seront toutes les deux dans `namespace Mediatheque;` : `Livre extends Article` fonctionnera sans `use`.

### Récapitulatif visuel

```
Fichier classes/Livre.class.php          Fichier index.php
─────────────────────────────────        ─────────────────────────────────
<?php                                    <?php
namespace Mediatheque;        ←──────    require_once '.../Livre.class.php';
                                         use Mediatheque\Livre;
class Livre extends Article { }
                                         $l = new Livre("1984", "Orwell", 1949);
                                         //  PHP sait que Livre = Mediatheque\Livre
```

---

## Étape 4 — La DAO générique : `classes/DAO.class.php`

On crée ce fichier en premier parce qu'`ArticleDAO` (dans `Article.class.php`) en hérite : PHP doit connaître `DAO` avant de pouvoir définir `ArticleDAO extends DAO`.

### Pourquoi une DAO générique ?

La DAO (Data Access Object) est la seule partie du code qui écrit du SQL. Les pages web ne manipulent que des objets PHP. Si la structure SQL change un jour, on ne modifie qu'un seul endroit.

On la rend **générique** : au lieu de réécrire les 5 opérations CRUD pour chaque entité, on les écrit une fois ici. Chaque DAO concrète (`ArticleDAO`, future `AdherentDAO`...) hérite de tout ça et ne fournit que deux méthodes de conversion :
- `hydrate()` : ligne SQL → objet PHP
- `dehydrate()` : objet PHP → tableau de colonnes SQL

### Créez `classes/DAO.class.php` :

```php
<?php

namespace Mediatheque;

abstract class DAO
{
    protected \PDO    $pdo;
    protected string $table;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    abstract protected function hydrate(array $row): object;
    abstract protected function dehydrate(object $entite): array;

    // ── READ : un seul enregistrement ──────────────────────────
    public function find(int $id): ?object
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return ($row !== false) ? $this->hydrate($row) : null;
    }

    // ── READ : tous les enregistrements ────────────────────────
    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM {$this->table} ORDER BY id DESC"
        );
        $resultats = [];
        foreach ($stmt->fetchAll() as $row) {
            $resultats[] = $this->hydrate($row);
        }
        return $resultats;
    }

    // ── CREATE ─────────────────────────────────────────────────
    public function create(object $entite): int
    {
        $donnees      = $this->dehydrate($entite);
        $colonnes     = implode(', ', array_keys($donnees));
        $placeholders = ':' . implode(', :', array_keys($donnees));

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} ($colonnes) VALUES ($placeholders)"
        );

        $params = [];
        foreach ($donnees as $col => $val) {
            $params[':' . $col] = $val;
        }
        $stmt->execute($params);

        return (int) $this->pdo->lastInsertId();
    }

    // ── UPDATE ─────────────────────────────────────────────────
    public function update(int $id, object $entite): bool
    {
        $donnees = $this->dehydrate($entite);

        $affectations = [];
        foreach (array_keys($donnees) as $col) {
            $affectations[] = "$col = :$col";
        }

        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET "
            . implode(', ', $affectations)
            . " WHERE id = :id"
        );

        $params = [':id' => $id];
        foreach ($donnees as $col => $val) {
            $params[':' . $col] = $val;
        }
        return $stmt->execute($params);
    }

    // ── DELETE ─────────────────────────────────────────────────
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }
}
```

### Explication

**`namespace Mediatheque;`**
Toutes les classes du projet portent ce namespace. `DAO` s'appelle donc en réalité `Mediatheque\DAO`.

**`protected` sur `$pdo` et `$table`**
`protected` : accessible depuis la classe elle-même ET depuis ses classes filles (`ArticleDAO`). Avec `private`, les filles n'y auraient pas accès.

**`\PDO`**
Dès qu'on est dans un namespace, toutes les classes natives de PHP (PDO, Exception, RuntimeException, InvalidArgumentException, DateTime...) doivent être préfixées par \ pour indiquer à PHP qu'elles viennent du namespace global et pas du vôtre.

**Les requêtes préparées**
```php
"SELECT * FROM {$this->table} WHERE id = :id"
$stmt->execute([':id' => $id]);
```
On ne colle **jamais** une variable directement dans le SQL. `:id` est un paramètre nommé, dont la valeur est passée séparément via `execute()`. PDO se charge de l'échapper. C'est la protection contre les **injections SQL**.

**`create()` dynamique**
`dehydrate()` renvoie par exemple `['type' => 'livre', 'titre' => '1984', ...]`. On en extrait automatiquement les noms de colonnes et les placeholders :
```
colonnes     : "type, titre, auteur, annee, disponible"
placeholders : ":type, :titre, :auteur, :annee, :disponible"
```
La requête construite est identique à un `INSERT` écrit à la main, mais elle fonctionnera pour **n'importe quelle entité**, sans modification.

**`lastInsertId()`**
Après un `INSERT`, MySQL a attribué un `id` auto-incrémenté à la nouvelle ligne. Cette méthode PDO le récupère et on le renvoie.
---

## Étape 5 — Article et ArticleDAO : `classes/Article.class.php`

Ce fichier regroupe tout ce qui concerne l'entité Article :
- La classe abstraite `Article` (le modèle de données)
- La classe `ArticleDAO` (l'accès en base)

### Partie 1 — La classe `Article`

#### L'encapsulation

Sans encapsulation, n'importe quel code peut écrire :
```php
$article->annee = -300; // valeur absurde, rien ne le contrôle
```
En déclarant les attributs `private`, ils deviennent inaccessibles depuis l'extérieur. On passe obligatoirement par les **getters** (lire) et **setters** (modifier), qui peuvent valider la valeur.

#### Créez `classes/Article.class.php` — première partie :

```php
<?php

namespace Mediatheque;

// ================================================================
//  CLASSE Article
//  Modèle commun à Livre et Dvd.
//  "abstract" : impossible de faire new Article() directement.
//  Elle sert uniquement de base à l'héritage.
// ================================================================

abstract class Article
{
    private ?int   $id         = null;
    private string $titre;
    private string $auteur;
    private int    $annee;
    private bool   $disponible;

    public function __construct(
        string $titre,
        string $auteur,
        int    $annee,
        bool   $disponible = true
    ) {
        // On passe par les setters dès le constructeur
        // pour que les validations s'appliquent même à la création.
        $this->setTitre($titre);
        $this->setAnnee($annee);
        $this->auteur     = $auteur;
        $this->disponible = $disponible;
    }

    // ── Getters ──────────────────────────────────────────────────

    public function getId(): ?int       { return $this->id;         }
    public function getTitre(): string  { return $this->titre;      }
    public function getAuteur(): string { return $this->auteur;     }
    public function getAnnee(): int     { return $this->annee;      }
    public function isDisponible(): bool{ return $this->disponible; }

    // ── Setters ──────────────────────────────────────────────────

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setTitre(string $titre): void
    {
        if (trim($titre) === '') {
            throw new \InvalidArgumentException("Le titre ne peut pas être vide.");
        }
        $this->titre = $titre;
    }

    public function setAuteur(string $auteur): void
    {
        $this->auteur = $auteur;
    }

    public function setAnnee(int $annee): void
    {
        $max = (int) date('Y');
        if ($annee < 1000 || $annee > $max) {
            throw new \InvalidArgumentException("L'année $annee n'est pas valide.");
        }
        $this->annee = $annee;
    }

    public function setDisponible(bool $disponible): void
    {
        $this->disponible = $disponible;
    }

    // ── Méthodes abstraites ───────────────────────────────────────
    // Pas de code ici. Chaque classe fille DOIT les écrire.

    abstract public function description(): string;
    abstract public function getType(): string;
}
```

### Points importants à retenir

**`abstract class Article`**
- Impossible de faire `new Article(...)`. PHP refusera avec *"Cannot instantiate abstract class"*.
- Elle peut contenir des méthodes abstraites.

**`private ?int $id = null`**
`?int` signifie "entier ou null". À la création d'un objet, il n'est pas encore en base donc n'a pas d'id. La DAO appellera `setId()` après l'INSERT pour le renseigner.

**Le constructeur appelle les setters**
```php
$this->setTitre($titre);
$this->setAnnee($annee);
```
Ainsi les validations s'appliquent à la création. Impossible de faire `new Livre("", "Auteur", 1984)` sans déclencher une exception.

**`\InvalidArgumentException`**
Le `\` devant le nom signifie : cette classe vient du namespace **global** de PHP (pas du namespace `Mediatheque`). Les exceptions natives de PHP (`InvalidArgumentException`, `RuntimeException`...) sont dans le namespace global, il faut donc les préfixer par `\` quand on est dans un namespace.

**Les getters en une ligne**
```php
public function getId(): ?int { return $this->id; }
```
Un getter ne fait qu'une chose : renvoyer une valeur. L'écriture en une ligne est tout à fait lisible ici.

**Pourquoi `isDisponible()` et non `getDisponible()` ?**
Convention PHP : pour un attribut booléen, le getter commence par `is`. Ça se lit mieux : `if ($article->isDisponible())`.

**Les méthodes abstraites**
```php
abstract public function description(): string;
abstract public function getType(): string;
```
Pas de corps `{ }`, juste un `;`. Les classes filles `Livre` et `Dvd` devront obligatoirement les implémenter. Si elles oublient, PHP affiche une erreur fatale.

---

### Partie 2 — La classe `ArticleDAO`

**Toujours dans `classes/Article.class.php`**, ajoutez ce code après la fermeture de la classe `Article` :

```php

// ================================================================
//  CLASSE ArticleDAO
//  Gère les accès MySQL pour les articles.
//  Hérite de DAO : find(), findAll(), create(), update(), delete()
//  sont fournis sans avoir à les réécrire.
//  Il suffit d'implémenter hydrate() et dehydrate().
// ================================================================

class ArticleDAO extends DAO
{
    protected string $table = 'article';

    protected function hydrate(array $row): object
    {
        // On choisit la classe concrète selon la colonne "type"
        if ($row['type'] === 'livre') {
            $article = new Livre(
                $row['titre'],
                $row['auteur'],
                (int)  $row['annee'],
                (bool) $row['disponible']
            );
        } else {
            $article = new Dvd(
                $row['titre'],
                $row['auteur'],
                (int)  $row['annee'],
                (bool) $row['disponible']
            );
        }

        $article->setId((int) $row['id']);
        return $article;
    }

    protected function dehydrate(object $entite): array
    {
        return [
            'type'       => $entite->getType(),
            'titre'      => $entite->getTitre(),
            'auteur'     => $entite->getAuteur(),
            'annee'      => $entite->getAnnee(),
            'disponible' => $entite->isDisponible() ? 1 : 0,
        ];
    }
}
```

### Explication

**Même namespace, donc pas de `use`**
`Article`, `ArticleDAO`, `Livre`, `Dvd` sont tous dans `namespace Mediatheque;`. À l'intérieur du même namespace, les classes se voient directement. `new Livre(...)` dans `hydrate()` fonctionne sans `use Mediatheque\Livre`.

**`(int)` et `(bool)` dans `hydrate()`**
MySQL renvoie tout sous forme de chaîne de caractères, même les colonnes INT et TINYINT. Les conversions de type forcent les bonnes valeurs PHP : `"2010"` devient `2010`, `"1"` devient `true`.

**`dehydrate()` et le polymorphisme**
```php
'type' => $entite->getType(),
```
On appelle `getType()` sans tester `if ($entite instanceof Livre)`. L'objet sait lui-même ce qu'il est : c'est le polymorphisme qui travaille.

**`'disponible' => $entite->isDisponible() ? 1 : 0`**
MySQL stocke les booléens comme `0` ou `1`. L'opérateur ternaire convertit le booléen PHP en entier MySQL.

---

## Étape 6 — L'interface : `classes/Empruntable.class.php`

### Pourquoi une interface ?

On veut que `Livre` et `Dvd` puissent être empruntés. On pourrait mettre `emprunter()` directement dans `Article`. Mais si on ajoute demain une classe `Magazine` consultable sur place uniquement, tous les articles auraient cette méthode, même ceux qui ne devraient pas.

Une **interface** est un contrat séparé : seules les classes qui veulent l'honorer écrivent `implements Empruntable`. Elle ne contient aucun code, juste les signatures.

| | `extends` | `implements` |
|---|---|---|
| Ce qu'on partage | Attributs + code | Uniquement des signatures |
| Limite | Une seule classe mère | Plusieurs interfaces possibles |
| Contient du code | Oui | Non, jamais |

### Créez `classes/Empruntable.class.php` :

```php
<?php

namespace Mediatheque;

interface Empruntable
{
    public function emprunter(): void;
    public function rendre(): void;
}
```

Toute classe déclarant `implements Empruntable` **doit** écrire `emprunter()` et `rendre()`, sinon PHP refuse de fonctionner.

---

## Étape 7 — La classe Livre : `classes/Livre.class.php`

### Créez `classes/Livre.class.php` :

```php
<?php

namespace Mediatheque;

class Livre extends Article implements Empruntable
{
    // Polymorphisme par héritage :
    // Livre écrit SA version de description().
    public function description(): string
    {
        return "Livre « " . $this->getTitre() . " »"
             . ", écrit par " . $this->getAuteur()
             . " (" . $this->getAnnee() . ")";
    }

    public function getType(): string
    {
        return 'livre';
    }

    // Polymorphisme par interface :
    // Livre respecte le contrat Empruntable.
    public function emprunter(): void
    {
        if (!$this->isDisponible()) {
            throw new \RuntimeException("Ce livre est déjà emprunté.");
        }
        $this->setDisponible(false);
    }

    public function rendre(): void
    {
        $this->setDisponible(true);
    }
}
```

### Explication

**`class Livre extends Article implements Empruntable`**
Grâce à `extends Article`, `Livre` hérite de tous les attributs (`$titre`, `$auteur`...) et de toutes les méthodes (`getTitre()`, `setTitre()`...) sans les réécrire. Elle doit juste écrire ce qui la différencie.

**`$this->getTitre()` et non `$this->titre`**
`$titre` est `private` dans `Article`. Même les classes filles n'y accèdent pas directement. On passe par le getter hérité. C'est une subtilité importante : `private` protège l'attribut même vis-à-vis des enfants.

**`\RuntimeException`**
Même principe que `\InvalidArgumentException` : le `\` indique le namespace global de PHP.

---

## Étape 8 — La classe Dvd : `classes/Dvd.class.php`

### Créez `classes/Dvd.class.php` :

```php
<?php

namespace Mediatheque;

class Dvd extends Article implements Empruntable
{
    // Polymorphisme par héritage :
    // Dvd écrit SA version de description(), différente de Livre.
    public function description(): string
    {
        return "DVD « " . $this->getTitre() . " »"
             . ", réalisé par " . $this->getAuteur()
             . " (" . $this->getAnnee() . ")";
    }

    public function getType(): string
    {
        return 'dvd';
    }

    public function emprunter(): void
    {
        if (!$this->isDisponible()) {
            throw new \RuntimeException("Ce DVD est déjà emprunté.");
        }
        $this->setDisponible(false);
    }

    public function rendre(): void
    {
        $this->setDisponible(true);
    }
}
```

Même structure que `Livre`, avec `description()` et `getType()` adaptés.

### ✅ Test des étapes 5 à 8

Créez un fichier temporaire `test_classes.php` à la racine :

```php
<?php
require_once __DIR__ . '/classes/config.php';
require_once __DIR__ . '/classes/DAO.class.php';
require_once __DIR__ . '/classes/Article.class.php';
require_once __DIR__ . '/classes/Empruntable.class.php';
require_once __DIR__ . '/classes/Livre.class.php';
require_once __DIR__ . '/classes/Dvd.class.php';

use Mediatheque\Livre;
use Mediatheque\Dvd;
use Mediatheque\ArticleDAO;

echo "<h3>Polymorphisme par héritage</h3>";

$articles = [
    new Livre("Le Petit Prince", "Antoine de Saint-Exupéry", 1943),
    new Dvd("Inception", "Christopher Nolan", 2010),
    new Livre("1984", "George Orwell", 1949),
];

// Même appel pour tous, résultat différent selon le type : polymorphisme
foreach ($articles as $article) {
    echo $article->description() . "<br>";
}

echo "<h3>Polymorphisme par interface + exception</h3>";

$livre = new Livre("Dune", "Frank Herbert", 1965);
echo "Disponible : " . var_export($livre->isDisponible(), true) . "<br>";

$livre->emprunter();
echo "Après emprunter() : " . var_export($livre->isDisponible(), true) . "<br>";

$livre->rendre();
echo "Après rendre() : " . var_export($livre->isDisponible(), true) . "<br>";

try {
    $livre->emprunter();
    $livre->emprunter(); // doit lever une exception
} catch (\RuntimeException $e) {
    echo "Exception : " . $e->getMessage() . "<br>";
}

echo "<h3>CRUD via ArticleDAO</h3>";

$pdo = getConnexion();
$dao = new ArticleDAO($pdo);

foreach ($dao->findAll() as $a) {
    echo $a->description() . "<br>";
}

$id = $dao->create(new Livre("Test namespace", "Auteur", 2023));
echo "Créé id=$id<br>";
$dao->delete($id);
echo "Supprimé<br>";
```

> **L'ordre des `require_once` est important :**
> 1. `config.php` — pas de namespace, fournit `getConnexion()`
> 2. `DAO.class.php` — `ArticleDAO extends DAO`, donc DAO doit être connu en premier
> 3. `Article.class.php` — `Article` doit être connu avant `Livre` et `Dvd`
> 4. `Empruntable.class.php` — l'interface doit être connue avant les classes qui l'implémentent
> 5. `Livre.class.php` et `Dvd.class.php`

Les `use` viennent **après** tous les `require_once`, ils ne font que déclarer les raccourcis.

Supprimez `test_classes.php` une fois le test passé.

---

## Étape 9 — Les pages communes

### Créez `includes/header.php` :

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
        .btn:hover        { background: #3a4f8c; }
        .btn-danger       { background: #b85042; }
        .btn-danger:hover { background: #922e22; }
        .btn-success      { background: #2e7d4f; }
        .btn-success:hover{ background: #245f3c; }
        .btn-warning      { background: #b07d1a; }
        .btn-warning:hover{ background: #8a6114; }
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
        <a href="index.php">Liste des articles</a>
        <a href="formulaire.php">Ajouter un article</a>
    </nav>
</header>
<main>
```

### Créez `includes/footer.php` :

```php
</main>
</body>
</html>
```

---

## Étape 10 — La liste des articles : `index.php`

### Créez `index.php` à la racine :

```php
<?php
require_once __DIR__ . '/classes/config.php';
require_once __DIR__ . '/classes/DAO.class.php';
require_once __DIR__ . '/classes/Article.class.php';
require_once __DIR__ . '/classes/Empruntable.class.php';
require_once __DIR__ . '/classes/Livre.class.php';
require_once __DIR__ . '/classes/Dvd.class.php';

use Mediatheque\ArticleDAO;

$pdo      = getConnexion();
$dao      = new ArticleDAO($pdo);
$articles = $dao->findAll();

require_once __DIR__ . '/includes/header.php';
?>

<h2>Liste des articles</h2>

<?php if (isset($_GET['message'])): ?>
    <div class="alerte alerte-succes">
        <?= htmlspecialchars($_GET['message']) ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['erreur'])): ?>
    <div class="alerte alerte-erreur">
        <?= htmlspecialchars($_GET['erreur']) ?>
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
                       href="emprunter.php?id=<?= $article->getId() ?>&action=emprunter">
                        Emprunter
                    </a>
                <?php else: ?>
                    <a class="btn btn-warning"
                       href="emprunter.php?id=<?= $article->getId() ?>&action=rendre">
                        Rendre
                    </a>
                <?php endif; ?>

                <a class="btn"
                   href="formulaire.php?id=<?= $article->getId() ?>">
                    Modifier
                </a>

                <a class="btn btn-danger"
                   href="supprimer.php?id=<?= $article->getId() ?>"
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
    <a class="btn" href="formulaire.php">+ Ajouter un article</a>
</p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
```

### Explication

**`use Mediatheque\ArticleDAO;`**
Après tous les `require_once`, on déclare les raccourcis namespace. Ici on n'a besoin que d'`ArticleDAO` (les autres classes, `Livre` et `Dvd`, sont utilisées en interne par la DAO). On peut ensuite écrire `new ArticleDAO(...)` au lieu de `new Mediatheque\ArticleDAO(...)`.

**`htmlspecialchars()`**
Toute valeur affichée dans du HTML qui vient de la base de données doit passer par cette fonction. Elle convertit `<`, `>`, `"` et `&` en entités HTML inoffensives. Sans ça, du code malveillant injecté en base s'exécuterait dans le navigateur des utilisateurs (**faille XSS**).

**Les liens vers `formulaire.php`**
- Ajout : `href="formulaire.php"` — pas d'id dans l'URL
- Modification : `href="formulaire.php?id=3"` — l'id en GET indique qu'on modifie

C'est `formulaire.php` qui déterminera lui-même son mode en regardant si `$_GET['id']` existe.

**Le polymorphisme dans la vue**
```php
foreach ($articles as $article) {
    echo $article->description(); // Livre ou Dvd : résultat différent, même appel
    echo $article->getType();
}
```
PHP choisit automatiquement la bonne version de la méthode. Aucun `if` dans la vue.
---

## Étape 11 — Ajout ET modification : `formulaire.php`

C'est le **C** et le **U** du CRUD en une seule page.

### Le principe : un seul formulaire, deux modes

Plutôt que d'avoir `ajouter.php` et `modifier.php` séparés avec un code très similaire, on crée une seule page intelligente qui détecte dans quel mode elle se trouve :

- Lien "Ajouter" → `formulaire.php` → pas d'`id` dans l'URL → **mode ajout**
- Lien "Modifier" → `formulaire.php?id=3` → un `id` dans l'URL → **mode modification**

La détection se fait avec `isset($_GET['id'])`. Si cette variable existe, c'est une modification. Sinon, c'est un ajout.

### Créez `formulaire.php` à la racine :

```php
<?php
require_once __DIR__ . '/classes/config.php';
require_once __DIR__ . '/classes/DAO.class.php';
require_once __DIR__ . '/classes/Article.class.php';
require_once __DIR__ . '/classes/Empruntable.class.php';
require_once __DIR__ . '/classes/Livre.class.php';
require_once __DIR__ . '/classes/Dvd.class.php';

use Mediatheque\ArticleDAO;
use Mediatheque\Livre;
use Mediatheque\Dvd;

$pdo = getConnexion();
$dao = new ArticleDAO($pdo);

// ── Détermination du mode ─────────────────────────────────────
// Si $_GET['id'] existe, on est en modification.
// Sinon, on est en ajout.

$id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$article = null;
$mode    = 'ajout';

if ($id > 0) {
    $article = $dao->find($id);

    if ($article === null) {
        header('Location: index.php?erreur=' . urlencode("Article introuvable."));
        exit;
    }

    $mode = 'modification';
}

// ── Traitement du formulaire ──────────────────────────────────
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // En modification, l'id vient du champ caché du formulaire
    if ($mode === 'modification') {
        $id = (int) ($_POST['id'] ?? 0);
    }

    $titre  = trim($_POST['titre']  ?? '');
    $auteur = trim($_POST['auteur'] ?? '');
    $annee  = $_POST['annee'] ?? '';

    // Le type n'est demandé qu'en mode ajout
    $type   = $_POST['type'] ?? ($article ? $article->getType() : '');

    // Validation commune aux deux modes
    if ($titre === '') {
        $erreurs[] = "Le titre est obligatoire.";
    }
    if ($auteur === '') {
        $erreurs[] = "L'auteur / réalisateur est obligatoire.";
    }
    if (!ctype_digit($annee)) {
        $erreurs[] = "L'année doit être un nombre entier.";
    }

    // Validation spécifique au mode ajout
    if ($mode === 'ajout' && !in_array($type, ['livre', 'dvd'], true)) {
        $erreurs[] = "Veuillez choisir un type (Livre ou DVD).";
    }

    if (empty($erreurs)) {
        try {
            if ($mode === 'modification') {
                // ── MODE MODIFICATION ──
                // On applique les nouvelles valeurs sur l'objet existant
                $article->setTitre($titre);
                $article->setAuteur($auteur);
                $article->setAnnee((int) $annee);
                $dao->update($id, $article);

                $msg = 'Article mis à jour avec succès !';

            } else {
                // ── MODE AJOUT ──
                // On crée le bon objet selon le type choisi
                $article = ($type === 'livre')
                    ? new Livre($titre, $auteur, (int) $annee)
                    : new Dvd($titre, $auteur, (int) $annee);

                $dao->create($article);
                $msg = 'Article ajouté avec succès !';
            }

            header('Location: index.php?message=' . urlencode($msg));
            exit;

        } catch (\Exception $e) {
            $erreurs[] = "Erreur : " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';

// Titre de la page selon le mode
$titrePage = ($mode === 'modification')
    ? "Modifier : " . htmlspecialchars($article->getTitre())
    : "Ajouter un article";
?>

<h2><?= $titrePage ?></h2>

<?php foreach ($erreurs as $erreur): ?>
    <div class="alerte alerte-erreur">
        <?= htmlspecialchars($erreur) ?>
    </div>
<?php endforeach; ?>

<form method="post" action="formulaire.php<?= $id > 0 ? "?id=$id" : '' ?>">

    <?php if ($mode === 'modification'): ?>
        <!-- En modification : on conserve l'id dans un champ caché -->
        <input type="hidden" name="id" value="<?= $id ?>">
    <?php endif; ?>

    <?php if ($mode === 'ajout'): ?>
        <!-- Le type n'est demandé qu'à la création -->
        <label for="type">Type d'article</label>
        <select id="type" name="type">
            <option value="">-- Choisir --</option>
            <option value="livre" <?= (($_POST['type'] ?? '') === 'livre') ? 'selected' : '' ?>>
                Livre
            </option>
            <option value="dvd" <?= (($_POST['type'] ?? '') === 'dvd') ? 'selected' : '' ?>>
                DVD
            </option>
        </select>

    <?php else: ?>
        <!-- En modification : le type est affiché mais non modifiable -->
        <label>Type (non modifiable)</label>
        <input type="text"
               value="<?= strtoupper($article->getType()) ?>"
               disabled>
    <?php endif; ?>

    <label for="titre">Titre</label>
    <input type="text"
           id="titre"
           name="titre"
           value="<?= htmlspecialchars($_POST['titre'] ?? ($article ? $article->getTitre() : '')) ?>">

    <label for="auteur">Auteur (livre) / Réalisateur (DVD)</label>
    <input type="text"
           id="auteur"
           name="auteur"
           value="<?= htmlspecialchars($_POST['auteur'] ?? ($article ? $article->getAuteur() : '')) ?>">

    <label for="annee">Année</label>
    <input type="number"
           id="annee"
           name="annee"
           value="<?= htmlspecialchars($_POST['annee'] ?? ($article ? (string)$article->getAnnee() : '')) ?>">

    <button type="submit" class="btn">
        <?= $mode === 'modification' ? 'Mettre à jour' : 'Enregistrer' ?>
    </button>
</form>

<a class="lien-retour" href="index.php">← Retour à la liste</a>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
```

### Explication bloc par bloc

**La détection du mode**

```php
$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$mode = 'ajout';

if ($id > 0) {
    $article = $dao->find($id);
    $mode    = 'modification';
}
```

`isset($_GET['id'])` renvoie `true` si la variable existe dans l'URL. L'opérateur ternaire `? :` initialise `$id` à l'id récupéré (converti en entier) ou à `0` s'il n'y a pas d'id. Si `$id > 0`, on charge l'article depuis la base et on bascule en mode `'modification'`.

**La variable `$article` dans les deux modes**

En mode ajout, `$article` est `null` au départ (il n'existe pas encore).
En mode modification, `$article` est l'objet chargé depuis la base.

C'est pour ça que le pré-remplissage des champs utilise l'opérateur `??` en cascade :
```php
value="<?= htmlspecialchars($_POST['titre'] ?? ($article ? $article->getTitre() : '')) ?>"
```
- Si `$_POST['titre']` existe (retour après erreur) → on l'utilise
- Sinon, si `$article` existe (mode modification, premier chargement) → on prend sa valeur
- Sinon (mode ajout, premier chargement) → champ vide `''`

**`action="formulaire.php<?= $id > 0 ? "?id=$id" : '' ?>"`**

L'attribut `action` du formulaire s'adapte au mode :
- Mode ajout → `action="formulaire.php"` (pas d'id)
- Mode modification → `action="formulaire.php?id=3"` (l'id reste dans l'URL pour le POST)

**Le champ caché en modification**

```php
<input type="hidden" name="id" value="<?= $id ?>">
```

Quand le formulaire est soumis (POST), on a besoin de l'id pour faire `update($id, ...)`. L'URL conserve l'id grâce à l'action, mais on l'ajoute aussi en champ caché pour le récupérer proprement dans `$_POST['id']` si nécessaire.

**`$_SERVER['REQUEST_METHOD'] === 'POST'`**

Le traitement n'a lieu que sur envoi du formulaire. Au premier chargement de la page (GET), ce bloc est ignoré et on affiche le formulaire.

**Redirection après succès (POST/Redirect/GET)**

```php
header('Location: index.php?message=' . urlencode($msg));
exit;
```

Après un enregistrement réussi, on redirige vers la liste. Cela évite qu'un F5 renvoie le formulaire et crée un doublon. `exit` est indispensable : sans lui, PHP continuerait d'exécuter le code après `header()`.

---

## Étape 12 — Suppression : `supprimer.php`

C'est le **D** du CRUD. Cette page n'affiche rien : elle agit et redirige.

### Créez `supprimer.php` à la racine :

```php
<?php
require_once __DIR__ . '/classes/config.php';
require_once __DIR__ . '/classes/DAO.class.php';
require_once __DIR__ . '/classes/Article.class.php';
require_once __DIR__ . '/classes/Empruntable.class.php';
require_once __DIR__ . '/classes/Livre.class.php';
require_once __DIR__ . '/classes/Dvd.class.php';

use Mediatheque\ArticleDAO;

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    $pdo = getConnexion();
    $dao = new ArticleDAO($pdo);
    $dao->delete($id);
}

header('Location: index.php?message=' . urlencode('Article supprimé.'));
exit;
```

La confirmation était déjà demandée dans `index.php` par `onclick="return confirm(...)"`. Ici on exécute et on redirige.

---

## Étape 13 — Emprunter et rendre : `emprunter.php`

C'est ici que le **polymorphisme par interface** prend tout son sens dans l'application.

### Créez `emprunter.php` à la racine :

```php
<?php
require_once __DIR__ . '/classes/config.php';
require_once __DIR__ . '/classes/DAO.class.php';
require_once __DIR__ . '/classes/Article.class.php';
require_once __DIR__ . '/classes/Empruntable.class.php';
require_once __DIR__ . '/classes/Livre.class.php';
require_once __DIR__ . '/classes/Dvd.class.php';

use Mediatheque\ArticleDAO;

$id     = (int) ($_GET['id']     ?? 0);
$action = $_GET['action'] ?? '';

$pdo     = getConnexion();
$dao     = new ArticleDAO($pdo);
$article = $dao->find($id);

if ($article === null) {
    header('Location: index.php?erreur=' . urlencode("Article introuvable."));
    exit;
}

try {
    if ($action === 'emprunter') {
        $article->emprunter();
        $msg = "« " . $article->getTitre() . " » a été emprunté.";

    } elseif ($action === 'rendre') {
        $article->rendre();
        $msg = "« " . $article->getTitre() . " » a été rendu.";

    } else {
        header('Location: index.php');
        exit;
    }

    // On sauvegarde le changement de disponibilité en base
    $dao->update($id, $article);

    header('Location: index.php?message=' . urlencode($msg));
    exit;

} catch (\RuntimeException $e) {
    header('Location: index.php?erreur=' . urlencode($e->getMessage()));
    exit;
}
```

### Explication

**Le polymorphisme par interface en action**

```php
$article = $dao->find($id);
// $article est un Livre OU un Dvd — on ne sait pas lequel ici.

$article->emprunter(); // fonctionne dans les deux cas
```

Cette page ne connaît pas le type réel de `$article`. Elle sait juste que l'interface `Empruntable` **garantit** que la méthode `emprunter()` existe. C'est le contrat de l'interface qui permet d'écrire ce code générique.

Sans interface, il faudrait écrire :
```php
// ❌ Fragile : à modifier si on ajoute un nouveau type
if ($article instanceof Livre) {
    $article->emprunter();
} elseif ($article instanceof Dvd) {
    $article->emprunter();
}
```

Avec l'interface :
```php
// ✅ Extensible : fonctionne pour tout type qui implements Empruntable
$article->emprunter();
```

**`$dao->update()` après `emprunter()`**

`emprunter()` change la disponibilité dans l'objet PHP (`setDisponible(false)`) mais pas en base. Il faut appeler `update()` explicitement pour persister ce changement en MySQL.

**`catch (\RuntimeException $e)`**

Si `emprunter()` est appelé sur un article déjà emprunté, la méthode lève une `\RuntimeException`. Le `catch` l'attrape et redirige avec un message d'erreur plutôt que de faire planter la page.

---

## Étape 14 — Tester l'application complète

Ouvrez `http://localhost/mediatheque/index.php` et vérifiez :

- [ ] Les 6 articles s'affichent, avec les badges LIVRE et DVD.
- [ ] Cliquer sur "Ajouter un article" → formulaire vide, sans id dans l'URL.
- [ ] Soumettre le formulaire vide → les messages d'erreur s'affichent.
- [ ] Ajouter un livre et un DVD → ils apparaissent dans la liste.
- [ ] Cliquer sur "Modifier" → formulaire pré-rempli, avec `?id=X` dans l'URL.
- [ ] Modifier un titre → la modification est enregistrée.
- [ ] Cliquer "Emprunter" → l'article passe à "Emprunté".
- [ ] Cliquer "Rendre" → il redevient "Disponible".
- [ ] Essayer d'emprunter un article déjà emprunté → message d'erreur.
- [ ] Cliquer "Supprimer" → confirmation demandée → l'article disparaît.

---

## Récapitulatif final

```
mediatheque/
│
├── bdd_mediatheque.sql
│
├── index.php          (READ — liste)
├── formulaire.php     (CREATE + UPDATE — ajout et modification en une page)
├── supprimer.php      (DELETE)
├── emprunter.php      (emprunter / rendre — polymorphisme par interface)
│
├── includes/
│   ├── header.php
│   └── footer.php
│
└── classes/
    ├── config.php                 pas de namespace (fonction utilitaire)
    ├── DAO.class.php              namespace Mediatheque — classe abstraite générique
    ├── Article.class.php          namespace Mediatheque — Article (abstract) + ArticleDAO
    ├── Empruntable.class.php      namespace Mediatheque — interface
    ├── Livre.class.php            namespace Mediatheque — extends Article, implements Empruntable
    └── Dvd.class.php              namespace Mediatheque — extends Article, implements Empruntable
```

---

## Étape 15 (bonus) — Aller plus loin avec les namespaces : sous-espaces de noms

Dans ce projet, on a utilisé un namespace plat : tout le monde est dans `Mediatheque`. Dans un projet plus grand, on peut affiner avec des **sous-namespaces**, comme des sous-dossiers :

```
Mediatheque\Model\Article       → les classes métier
Mediatheque\Repository\DAO      → les classes d'accès aux données
Mediatheque\Contract\Empruntable → les interfaces
```

Concrètement, dans `Livre.class.php` cela donnerait :

```php
<?php

namespace Mediatheque\Model;

use Mediatheque\Repository\DAO;
use Mediatheque\Contract\Empruntable;

// Ici on doit utiliser `use` même entre classes du projet,
// car elles ne sont plus dans le même namespace.
class Livre extends Article implements Empruntable { ... }
```

Et dans les pages :
```php
use Mediatheque\Repository\ArticleDAO;
use Mediatheque\Model\Livre;
use Mediatheque\Model\Dvd;
```

**Quand utiliser des sous-namespaces ?**
Dès qu'un projet grossit et que le dossier `classes/` commence à contenir beaucoup de fichiers. Pour un premier projet DWWM, le namespace plat `Mediatheque` est tout à fait suffisant et plus simple à comprendre.

---

## Étape 16 (bonus) — Ajouter une entité `Adherent`

Cet exercice prouve que votre `DAO` générique est vraiment réutilisable.

### Ajoutez la table en base (phpMyAdmin → SQL) :

```sql
USE mediatheque;

CREATE TABLE adherent (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nom     VARCHAR(100) NOT NULL,
    prenom  VARCHAR(100) NOT NULL,
    email   VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO adherent (nom, prenom, email) VALUES
('MARTIN',  'Sophie', 'sophie.martin@email.fr'),
('BERNARD', 'Lucas',  'lucas.bernard@email.fr');
```

### Créez `classes/Adherent.class.php` :

```php
<?php

namespace Mediatheque;

// ================================================================
//  CLASSE Adherent
// ================================================================

class Adherent
{
    private ?int   $id     = null;
    private string $nom;
    private string $prenom;
    private string $email;

    public function __construct(string $nom, string $prenom, string $email)
    {
        $this->nom    = $nom;
        $this->prenom = $prenom;
        $this->email  = $email;
    }

    public function getId(): ?int       { return $this->id;     }
    public function getNom(): string    { return $this->nom;    }
    public function getPrenom(): string { return $this->prenom; }
    public function getEmail(): string  { return $this->email;  }

    public function setId(int $id): void       { $this->id     = $id;    }
    public function setNom(string $n): void    { $this->nom    = $n;     }
    public function setPrenom(string $p): void { $this->prenom = $p;     }
    public function setEmail(string $e): void  { $this->email  = $e;     }
}

// ================================================================
//  CLASSE AdherentDAO
//  Hérite de DAO : les 5 méthodes CRUD sont déjà là.
//  On écrit uniquement hydrate() et dehydrate().
// ================================================================

class AdherentDAO extends DAO
{
    protected string $table = 'adherent';

    protected function hydrate(array $row): object
    {
        $a = new Adherent($row['nom'], $row['prenom'], $row['email']);
        $a->setId((int) $row['id']);
        return $a;
    }

    protected function dehydrate(object $entite): array
    {
        return [
            'nom'    => $entite->getNom(),
            'prenom' => $entite->getPrenom(),
            'email'  => $entite->getEmail(),
        ];
    }
}
```

**Vous venez de créer un CRUD complet pour les adhérents en n'écrivant que `hydrate()` et `dehydrate()`.** Tout le SQL (`find`, `findAll`, `create`, `update`, `delete`) est hérité de `DAO` sans réécriture. C'est ça, l'intérêt d'une DAO générique.

Créez ensuite les pages CRUD pour les adhérents sur le même modèle : un `formulaire_adherent.php` qui gère ajout et modification, un `supprimer_adherent.php`, et un `adherents.php` pour la liste.

---

