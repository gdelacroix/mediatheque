# Tutoriel Tests Unitaires en PHP — PHPUnit & Composer
## Partie 3 — Projet Médiathèque

**Prérequis :** avoir terminé la Partie 2 (architecture MVC). L'application médiathèque fonctionne.

**Objectif :** écrire des tests automatisés pour les classes du projet, comprendre ce qu'on teste et pourquoi, et lire correctement les résultats.

**Durée :** une journée.

---

## Étape 0 — Comprendre ce qu'est un test unitaire

### Le problème sans tests

Imaginez que vous modifiez la méthode `emprunter()` dans la classe `Livre`. Comment savoir que vous n'avez pas cassé quelque chose ? Vous ouvrez le navigateur, vous naviguez dans l'application, vous essayez d'emprunter un livre, vous vérifiez que ça marche... Mais vous ne testez qu'un seul chemin. Et si vous oubliez de tester le cas où le livre est déjà emprunté ?

Plus le projet grandit, plus cette vérification manuelle devient longue, fastidieuse, et incomplète. Un jour, un bug passe en production parce que personne n'a pensé à tester ce cas-là.

### La solution : les tests automatisés

Un **test unitaire** est un programme PHP qui vérifie qu'une **unité de code** (une méthode, une classe) se comporte exactement comme prévu — automatiquement, en quelques secondes, à chaque fois qu'on le lance.

```
Sans tests                        Avec tests
────────────────────              ─────────────────────────────────
Modifier le code                  Modifier le code
    ↓                                 ↓
Ouvrir le navigateur              Lancer : ./vendor/bin/phpunit
Naviguer à la main                    ↓
Espérer ne rien avoir cassé       ✅ 12 tests passent — ou ❌ voici exactement ce qui est cassé
```

### Ce qu'est une "unité"

On ne teste pas toute l'application d'un coup. On teste une **méthode** à la fois, dans un contexte précis. Par exemple :

- `Livre::getType()` doit renvoyer `'livre'`
- `Livre::emprunter()` doit marquer le livre comme indisponible
- `Livre::emprunter()` doit lever une exception si le livre est déjà emprunté
- `Article::setAnnee(-300)` doit lever une exception (année invalide)

Chaque cas de test est indépendant des autres. Un test ne dépend pas du résultat d'un autre test.

### Le cycle TDD (Test-Driven Development)

Votre cours présente le TDD, une méthode de travail en 3 étapes :

```
🔴 ROUGE   Écrire le test D'ABORD (il échoue, le code n'existe pas encore)
    ↓
🟢 VERT    Écrire le code MINIMAL pour faire passer le test
    ↓
🔄 REFACTOR Améliorer le code sans casser les tests
```

Dans ce tutoriel, on va appliquer cette philosophie : on écrira nos tests sur les classes qu'on a déjà, puis on vérifiera que tout passe — et on verra comment le test nous signale quand quelque chose ne va pas.

---

## Étape 1 — Composer : l'outil indispensable

### Qu'est-ce que Composer ?

**Composer** est le **gestionnaire de dépendances** de PHP. Une dépendance, c'est une librairie externe dont votre projet a besoin — ici, PHPUnit.

Sans Composer, il faudrait télécharger PHPUnit à la main, le placer dans le bon dossier, gérer ses propres dépendances... Composer fait tout ça automatiquement. Il télécharge la bonne version, l'installe dans un dossier `vendor/`, et garde une trace de ce que le projet utilise dans deux fichiers :

- `composer.json` : la liste des dépendances (que vous lisez et éditez)
- `composer.lock` : les versions exactes installées (géré automatiquement par Composer, à mettre dans Git)

Composer est à PHP ce que `npm` est à JavaScript (ou `pip` à Python).

### Installer Composer

#### Sous Windows (WAMP / XAMPP)

1. Rendez-vous sur `https://getcomposer.org/download/`
2. Téléchargez et exécutez **Composer-Setup.exe**
3. L'installateur détectera automatiquement votre PHP
4. Une fois installé, ouvrez un **nouveau** terminal (PowerShell ou CMD) et vérifiez :

```bash
composer --version
# Doit afficher quelque chose comme : Composer version 2.x.x
```

#### Sous macOS (MAMP)

```bash
# Dans le terminal :
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Vérification :
composer --version
```

#### Sous Linux

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### Le terminal : votre meilleur ami pour cette partie

Tout ce qui suit se fait dans un **terminal** (PowerShell sous Windows, Terminal sous macOS/Linux). Positionnez-vous dans le dossier du projet :

```bash
# Sous Windows
cd C:\wamp64\www\mediatheque

# Sous macOS/Linux
cd /Applications/MAMP/htdocs/mediatheque
```

---

## Étape 2 — Installer PHPUnit via Composer

### Créer le `composer.json` à la main

> ⚠️ **Ne pas utiliser `composer init`** — cette commande interactive tente de contacter des serveurs externes et peut provoquer des erreurs SSL sous Windows/XAMPP (`Unable to locate a valid CA certificate file`). On crée le fichier directement dans VS Code, c'est plus simple et plus fiable.

Dans VS Code, créez un fichier `composer.json` à la racine du projet (`C:\xampp\htdocs\mediatheque\`) avec ce contenu :

```json
{
    "name": "mediatheque/app",
    "description": "Application Médiathèque - Parcours POO & MVC",
    "type": "project",
    "require": {},
    "require-dev": {
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": {
            "Mediatheque\\": "classes/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Mediatheque\\Tests\\": "tests/"
        }
    }
}
```

### Explication du `composer.json`

**`require`** : les dépendances nécessaires en production (vide ici).

**`require-dev`** : les dépendances nécessaires uniquement en développement (tests, outils de debug...). PHPUnit ne doit jamais aller en production — on le place ici.

**`"phpunit/phpunit": "^11.0"`** : on veut PHPUnit version 11 ou supérieure (mais pas 12). Le `^` signifie "compatible avec cette version majeure".

**`autoload`** : dit à Composer comment charger automatiquement les classes. Ici, on lui dit que le namespace `Mediatheque\` correspond au dossier `classes/`. Grâce à ça, plus besoin de `require_once` dans les fichiers de test — Composer s'en charge.

**`autoload-dev`** : même principe pour les classes de test, dans le dossier `tests/`.

> **Remarque importante :** l'autoload de Composer utilise le standard **PSR-4**. Pour que ça fonctionne, le nom du fichier doit correspondre exactement au nom de la classe, et le namespace doit correspondre au chemin du dossier. Nos classes respectent déjà ça (`Livre.class.php` contient `class Livre` dans `namespace Mediatheque`) — mais le suffixe `.class.php` peut poser problème avec certains autoloaders. Pour les tests, on fera les `require_once` manuellement dans un fichier d'initialisation (`init.php`). On y reviendra.

### Installer les dépendances

```bash
composer install
```

Composer va :
1. Lire `composer.json`
2. Télécharger PHPUnit et toutes ses propres dépendances dans `vendor/`
3. Créer `composer.lock`
4. Générer l'autoloader dans `vendor/autoload.php`

```bash
# Vous devez voir quelque chose comme :
Installing dependencies from lock file
  - Installing phpunit/phpunit (11.x.x)
Generating autoload files
```

**Vérifiez** que le dossier `vendor/` est apparu à la racine du projet, avec dedans un dossier `phpunit/`.

### Ajouter `vendor/` au `.gitignore`

Le dossier `vendor/` peut contenir des centaines de Mo. On ne le versionne jamais — Composer peut le recréer depuis `composer.json`. Créez (ou complétez) `.gitignore` à la racine :

```
vendor/
```


---

## Étape 3 — Configurer PHPUnit : `phpunit.xml`

PHPUnit a besoin d'un fichier de configuration pour savoir où chercher les tests, comment les nommer, etc.

### Créez `phpunit.xml` à la racine du projet :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/init.php"
         colors="true"
         stopOnFailure="false">

    <testsuites>
        <testsuite name="Mediatheque Test Suite">
            <directory>tests/</directory>
        </testsuite>
    </testsuites>

</phpunit>
```

### Explication

**`bootstrap="tests/init.php"`**
Avant de lancer les tests, PHPUnit exécute ce fichier. C'est là qu'on chargera nos classes (via `require_once`). On le créera à l'étape suivante.

**`colors="true"`**
Les résultats sont affichés en couleur dans le terminal : vert pour les succès, rouge pour les échecs. Bien plus lisible.

**`stopOnFailure="false"`**
PHPUnit continue à exécuter tous les tests même si l'un d'eux échoue. On voit ainsi tous les problèmes d'un coup. Passez à `true` si vous voulez qu'il s'arrête au premier échec.

**`<directory>tests/</directory>`**
PHPUnit cherchera les fichiers de test dans le dossier `tests/`. Par convention, les fichiers de test se terminent par `Test.php` (`LivreTest.php`, `ArticleTest.php`...).

### Créez le fichier `tests/init.php` :

```php
<?php

// Ce fichier est chargé par PHPUnit avant tous les tests.
// Il charge toutes les classes nécessaires.

require_once __DIR__ . '/../classes/config.php';
require_once __DIR__ . '/../classes/DAO.class.php';
require_once __DIR__ . '/../classes/Article.class.php';
require_once __DIR__ . '/../classes/Empruntable.class.php';
require_once __DIR__ . '/../classes/Livre.class.php';
require_once __DIR__ . '/../classes/Dvd.class.php';
```

### Créez le dossier `tests/`

```bash
mkdir tests
```

### Structure attendue à ce stade

```
mediatheque/
├── classes/
├── controllers/
├── views/
├── tests/
│   └── init.php                ← chargement des classes
├── vendor/                 ← créé par Composer
├── composer.json           ← nouveau
├── composer.lock           ← créé par Composer
├── phpunit.xml             ← nouveau
└── index.php
```

### ✅ Vérification de l'installation

Lancez PHPUnit sans aucun test pour vérifier que tout est installé correctement :

```bash
./vendor/bin/phpunit
```

Vous devez voir :

```
PHPUnit 11.x.x by Sebastian Bergmann and contributors.

No tests executed!
```

Pas de tests pour l'instant — mais pas d'erreur non plus. L'installation est correcte. Passons à l'écriture des tests.

---

## Étape 4 — Anatomie d'un test PHPUnit

Avant d'écrire les tests du projet, comprendre la structure d'un fichier de test.

### Structure d'une classe de test

```php
<?php

use PHPUnit\Framework\TestCase;

class LivreTest extends TestCase          // 1. On hérite de TestCase
{
    public function testGetType(): void   // 2. Chaque méthode de test
    {                                     //    commence par "test"
        $livre = new \Mediatheque\Livre(  // 3. On crée l'objet à tester
            "1984",
            "George Orwell",
            1949
        );

        $this->assertEquals(              // 4. On vérifie le résultat
            'livre',
            $livre->getType()
        );
    }
}
```

**`extends TestCase`**
Toute classe de test hérite de `PHPUnit\Framework\TestCase`. C'est elle qui fournit toutes les méthodes d'assertion (`assertEquals`, `assertNotEquals`, `expectException`...).

**Les méthodes de test**
PHPUnit détecte automatiquement les méthodes de test grâce au préfixe `test`. Chaque méthode teste **un seul scénario** (bonne pratique : un test = un cas).

**Les assertions**
Une assertion est une vérification. Si elle échoue, le test est marqué comme raté (❌) et PHPUnit affiche un message expliquant pourquoi.

### Les assertions principales

| Assertion | Ce qu'elle vérifie |
|---|---|
| `assertEquals($attendu, $obtenu)` | Les deux valeurs sont égales |
| `assertNotEquals($inattendu, $obtenu)` | Les deux valeurs sont différentes |
| `assertTrue($valeur)` | La valeur est `true` |
| `assertFalse($valeur)` | La valeur est `false` |
| `assertNull($valeur)` | La valeur est `null` |
| `assertInstanceOf($classe, $objet)` | L'objet est une instance de la classe |
| `expectException($classe)` | Le code doit lever cette exception |
| `expectExceptionMessage($message)` | Le message de l'exception doit correspondre |

---

## Étape 5 — `assertEquals` et `assertNotEquals` : les deux faces d'un test solide

### Pourquoi tester les deux ?

Quand on teste une méthode, on a réflexe naturel de vérifier que ça renvoie la **bonne valeur**. C'est `assertEquals`. Mais un test vraiment solide vérifie aussi que ça ne renvoie **pas une mauvaise valeur**. C'est `assertNotEquals`.

Prenons `getType()` sur un `Livre` :

```php
// ✅ On vérifie que c'est bien 'livre'
$this->assertEquals('livre', $livre->getType());

// ✅ On vérifie que ce n'est PAS 'dvd'
$this->assertNotEquals('dvd', $livre->getType());
```

Si les **deux** passent, vous avez la certitude que `getType()` renvoie exactement `'livre'` — ni `'dvd'`, ni autre chose.

### Comprendre ce que signifie un échec d'`assertNotEquals`

C'est le point crucial. Voici le raisonnement :

**`assertNotEquals('dvd', $livre->getType())` dit :**
> "Je veux que le type du livre soit DIFFÉRENT de 'dvd'."

**Si ce test PASSE ✅ :**
`getType()` renvoie quelque chose de différent de `'dvd'` — c'est le comportement attendu.

**Si ce test ÉCHOUE ❌ (message d'erreur affiché) :**
`getType()` a renvoyé `'dvd'` sur un objet `Livre`. C'est un bug. PHPUnit affiche :
```
Failed asserting that 'dvd' is not equal to 'dvd'.
```

> **Ce message d'erreur est une bonne nouvelle.** Il prouve que votre test fonctionne correctement : il a détecté un bug réel. Votre `assertNotEquals` a rempli son rôle — il a attrapé quelque chose qui n'aurait pas dû se passer.

### Analogie pour bien retenir

Imaginez un détecteur de fumée :
- `assertEquals` c'est vérifier que la maison EST bien là (normal).
- `assertNotEquals` c'est vérifier que la maison N'EST PAS en feu (l'alarme).

Si l'alarme sonne (`assertNotEquals` échoue), c'est qu'il y a un problème. **L'alarme qui sonne prouve que le détecteur fonctionne.** Un test qui ne peut jamais échouer est inutile — comme un détecteur de fumée éteint.

### Illustration concrète avec un bug volontaire

Imaginez qu'on introduise un bug dans `Livre::getType()` :

```php
// BUG introduit intentionnellement
public function getType(): string
{
    return 'dvd'; // ← erreur : un livre renvoie 'dvd'
}
```

**Avec seulement `assertEquals('livre', ...)` :**
→ ❌ ÉCHOUE : `Failed asserting that 'dvd' matches expected 'livre'`
→ Bug détecté ✓

**Avec seulement `assertNotEquals('dvd', ...)` :**
→ ❌ ÉCHOUE : `Failed asserting that 'dvd' is not equal to 'dvd'`
→ Bug détecté ✓

**Les deux ensemble :** on attrape le bug quelle que soit la nature de l'erreur. C'est une double filet de sécurité.
---

## Étape 6 — Premier fichier de test : `tests/LivreTest.php`

On commence par tester la classe `Livre` : c'est concrete, pas abstraite, et elle concentre plusieurs comportements intéressants à vérifier.

### Créez `tests/LivreTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;
use Mediatheque\Livre;

class LivreTest extends TestCase
{
    // ────────────────────────────────────────────────────────────
    //  setUp() : méthode spéciale appelée AVANT chaque test.
    //  Elle prépare un état "propre" pour chaque test.
    //  Chaque test repart d'un objet Livre tout neuf.
    // ────────────────────────────────────────────────────────────
    private Livre $livre;

    protected function setUp(): void
    {
        $this->livre = new Livre("1984", "George Orwell", 1949);
    }

    // ── Tests de getType() ───────────────────────────────────────

    public function testGetTypeRetournelivre(): void
    {
        // assertEquals : on vérifie que le type EST bien 'livre'
        $this->assertEquals('livre', $this->livre->getType());
    }

    public function testGetTypeNestPasDvd(): void
    {
        // assertNotEquals : on vérifie que le type N'EST PAS 'dvd'
        // Si ce test échoue, ça signifie que getType() a renvoyé 'dvd'
        // sur un Livre — ce serait un bug réel.
        $this->assertNotEquals('dvd', $this->livre->getType());
    }

    // ── Tests de description() ───────────────────────────────────

    public function testDescriptionContientTitre(): void
    {
        $description = $this->livre->description();

        // On vérifie que la description contient bien le titre
        $this->assertStringContainsString('1984', $description);
    }

    public function testDescriptionContientAuteur(): void
    {
        $this->assertStringContainsString(
            'George Orwell',
            $this->livre->description()
        );
    }

    public function testDescriptionContientAnnee(): void
    {
        $this->assertStringContainsString(
            '1949',
            $this->livre->description()
        );
    }

    public function testDescriptionNeMentionnePasRealisateur(): void
    {
        // Un livre est "écrit par", pas "réalisé par"
        // assertNotEquals sur une chaîne plus ciblée :
        $this->assertStringNotContainsString(
            'réalisé par',
            $this->livre->description()
        );
    }

    // ── Tests de la disponibilité (encapsulation) ─────────────────

    public function testLivreDisponibleParDefaut(): void
    {
        // Par défaut, un livre est disponible
        $this->assertTrue($this->livre->isDisponible());
    }

    public function testLivreNonDisponibleApresEmprunt(): void
    {
        $this->livre->emprunter();

        // Après emprunt : assertTrue sur isDisponible doit ÉCHOUER
        // donc on utilise assertFalse
        $this->assertFalse($this->livre->isDisponible());

        // Et pour renforcer : assertNotEquals entre true et false
        $this->assertNotEquals(true, $this->livre->isDisponible());
    }

    public function testLivreDisponibleApresRetour(): void
    {
        $this->livre->emprunter();
        $this->livre->rendre();

        $this->assertTrue($this->livre->isDisponible());
    }

    // ── Tests de emprunter() — cas nominal ───────────────────────

    public function testEmprunterMarqueLivreIndisponible(): void
    {
        $this->livre->emprunter();
        $this->assertFalse($this->livre->isDisponible());
    }

    // ── Tests de emprunter() — cas d'erreur (exception) ──────────

    public function testEmprunterDejaEmprunteLanceException(): void
    {
        // On emprunte une première fois (correct)
        $this->livre->emprunter();

        // On s'attend à ce que le deuxième emprunt lève une RuntimeException
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Ce livre est déjà emprunté.");

        // Cette ligne doit déclencher l'exception
        $this->livre->emprunter();
    }
}
```

### Lancer ce premier test

```bash
./vendor/bin/phpunit tests/LivreTest.php
```

Si tout va bien, vous devez voir :

```
PHPUnit 11.x.x by Sebastian Bergmann and contributors.

.........                                          11 / 11 (100%)

Time: 00:00.012, Memory: 6.00 MB

OK (11 tests, 13 assertions)
```

Chaque point `.` représente un test qui passe. Décortiquons ce que PHPUnit nous dit :

- `11 tests` : 11 méthodes de test ont été exécutées
- `13 assertions` : 13 vérifications ont été faites
- `OK` : toutes ont réussi

### Que se passe-t-il si un test échoue ?

Pour comprendre, introduisez volontairement un bug : dans `Livre.class.php`, changez temporairement `getType()` pour qu'il renvoie `'dvd'` au lieu de `'livre'`. Relancez les tests :

```
PHPUnit 11.x.x by Sebastian Bergmann and contributors.

FF.......                                          11 / 11 (100%)

Time: 00:00.015, Memory: 6.00 MB

FAILURES!
There were 2 failures:

1) LivreTest::testGetTypeRetournelivre
Failed asserting that two strings are equal.
--- Expected
+++ Actual
@@ @@
-'livre'
+'dvd'

2) LivreTest::testGetTypeNestPasDvd
Failed asserting that 'dvd' is not equal to 'dvd'.
```

Les `F` (Failures) remplacent les `.` pour les tests qui échouent. PHPUnit vous dit :
- **Quel test** a échoué (`testGetTypeRetournelivre`)
- **Pourquoi** (`Failed asserting that 'dvd' matches expected 'livre'`)

Notez que les **deux** tests ont échoué : `assertEquals` ET `assertNotEquals` ont tous deux détecté le bug, chacun à sa façon. Corrigez le bug avant de continuer.

Dans `Livre.class.php`, N'oubliez pas dans `getType()` de remettre `'livre'` au lieu de `'dvd'`

---

## Étape 7 — Tester la classe `Dvd` : `tests/DvdTest.php`

### Créez `tests/DvdTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;
use Mediatheque\Dvd;

class DvdTest extends TestCase
{
    private Dvd $dvd;

    protected function setUp(): void
    {
        $this->dvd = new Dvd("Inception", "Christopher Nolan", 2010);
    }

    // ── Tests de getType() ───────────────────────────────────────

    public function testGetTypeRetourneDvd(): void
    {
        $this->assertEquals('dvd', $this->dvd->getType());
    }

    public function testGetTypeNestPasLivre(): void
    {
        // La contrepartie exacte du test dans LivreTest :
        // un DVD ne doit pas se prendre pour un livre.
        $this->assertNotEquals('livre', $this->dvd->getType());
    }

    // ── Tests de description() ───────────────────────────────────

    public function testDescriptionContientTitre(): void
    {
        $this->assertStringContainsString('Inception', $this->dvd->description());
    }

    public function testDescriptionMentionneRealisateur(): void
    {
        // Un DVD est "réalisé par", pas "écrit par"
        $this->assertStringContainsString('réalisé par', $this->dvd->description());
        $this->assertStringNotContainsString('écrit par', $this->dvd->description());
    }

    // ── Tests d'emprunt ──────────────────────────────────────────

    public function testDvdDisponibleParDefaut(): void
    {
        $this->assertTrue($this->dvd->isDisponible());
    }

    public function testEmprunterDvdDejaEmprunteLanceException(): void
    {
        $this->dvd->emprunter();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Ce DVD est déjà emprunté.");

        $this->dvd->emprunter();
    }
}
```

---

## Étape 8 — Tester l'encapsulation et les validations : `tests/ArticleValidationTest.php`

Ce fichier teste les règles de validation dans les setters d'`Article` — le cœur de l'encapsulation. On ne peut pas instancier `Article` directement (classe abstraite), on passe donc par `Livre`.

### Créez `tests/ArticleValidationTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;
use Mediatheque\Livre;

class ArticleValidationTest extends TestCase
{
    // ── Tests du setter setTitre() ────────────────────────────────

    public function testSetTitreModifieLeTitre(): void
    {
        $livre = new Livre("Titre initial", "Auteur", 2000);
        $livre->setTitre("Nouveau titre");

        // assertEquals : le titre EST bien le nouveau
        $this->assertEquals("Nouveau titre", $livre->getTitre());

        // assertNotEquals : le titre N'EST PLUS l'ancien
        $this->assertNotEquals("Titre initial", $livre->getTitre());
    }

    public function testSetTitreVideLanceException(): void
    {
        $livre = new Livre("Titre initial", "Auteur", 2000);

        // Un titre vide doit être refusé
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le titre ne peut pas être vide.");

        $livre->setTitre('');
    }

    public function testSetTitreEspacesSeulsLanceException(): void
    {
        $livre = new Livre("Titre initial", "Auteur", 2000);

        // Un titre composé uniquement d'espaces doit aussi être refusé
        // (trim() dans le setter convertit "   " en "")
        $this->expectException(\InvalidArgumentException::class);

        $livre->setTitre('   ');
    }

    // ── Tests du setter setAnnee() ────────────────────────────────

    public function testSetAnneeValideModifieLAnnee(): void
    {
        $livre = new Livre("Titre", "Auteur", 2000);
        $livre->setAnnee(1985);

        $this->assertEquals(1985, $livre->getAnnee());
        $this->assertNotEquals(2000, $livre->getAnnee());
    }

    public function testSetAnneeNegativeLanceException(): void
    {
        $livre = new Livre("Titre", "Auteur", 2000);

        $this->expectException(\InvalidArgumentException::class);

        $livre->setAnnee(-300);
    }

    public function testSetAnneeInferieure1000LanceException(): void
    {
        $livre = new Livre("Titre", "Auteur", 2000);

        $this->expectException(\InvalidArgumentException::class);

        $livre->setAnnee(999);
    }

    public function testSetAnneeSuperieurAnneeActuelleLanceException(): void
    {
        $livre = new Livre("Titre", "Auteur", 2000);
        $anneeInvalide = (int) date('Y') + 10;

        $this->expectException(\InvalidArgumentException::class);

        $livre->setAnnee($anneeInvalide);
    }

    // ── Tests du constructeur (qui passe par les setters) ─────────

    public function testConstructeurAvecTitreVideLanceException(): void
    {
        // Le constructeur appelle setTitre() → la validation s'applique dès la création
        $this->expectException(\InvalidArgumentException::class);

        new Livre('', 'Auteur', 2000);
    }

    public function testConstructeurAvecAnneeInvalideLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Livre('Titre', 'Auteur', -100);
    }
}
```

### Lancer tous les tests jusqu'ici

```bash
./vendor/bin/phpunit
```

PHPUnit va lancer tous les fichiers `*Test.php` dans le dossier `tests/` :

```
PHPUnit 11.x.x by Sebastian Bergmann and contributors.

.................                                 26 / 26 (100%)

Time: 00:00.025, Memory: 8.00 MB

OK (26 tests, 33 assertions)
```

---

## Étape 9 — Tester le polymorphisme : `tests/PolymorphismeTest.php`

Ce fichier démontre que le polymorphisme fonctionne correctement : un même appel de méthode produit des résultats différents selon le type de l'objet.

### Créez `tests/PolymorphismeTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;
use Mediatheque\Livre;
use Mediatheque\Dvd;

class PolymorphismeTest extends TestCase
{
    // ── Polymorphisme par héritage ────────────────────────────────

    public function testDescriptionsDifferentesSelonLeType(): void
    {
        $livre = new Livre("Le Seigneur des Anneaux", "J.R.R. Tolkien", 1954);
        $dvd   = new Dvd("Le Seigneur des Anneaux", "Peter Jackson", 2001);

        // Les deux descriptions sont différentes (même titre, objets différents)
        $this->assertNotEquals(
            $livre->description(),
            $dvd->description()
        );
    }

    public function testGetTypeDifferentParClasse(): void
    {
        $livre = new Livre("Test", "Auteur", 2020);
        $dvd   = new Dvd("Test", "Auteur", 2020);

        // Les types sont différents l'un de l'autre
        $this->assertNotEquals($livre->getType(), $dvd->getType());

        // Et chacun est bien le sien
        $this->assertEquals('livre', $livre->getType());
        $this->assertEquals('dvd',   $dvd->getType());
    }

    public function testTableauMixtePolymorphique(): void
    {
        // On peut mettre Livre et Dvd dans le même tableau
        // et appeler les mêmes méthodes sur tous
        $articles = [
            new Livre("1984",      "George Orwell",       1949),
            new Dvd("Inception",   "Christopher Nolan",   2010),
            new Livre("Dune",      "Frank Herbert",       1965),
            new Dvd("Parasite",    "Bong Joon-ho",        2019),
        ];

        foreach ($articles as $article) {
            // description() et getType() fonctionnent sur tous sans if/else
            $this->assertNotEmpty($article->description());
            $this->assertNotEmpty($article->getType());
            $this->assertContains($article->getType(), ['livre', 'dvd']);
        }
    }

    // ── Polymorphisme par interface (Empruntable) ─────────────────

    public function testLivreEtDvdImplententEmpruntable(): void
    {
        $livre = new Livre("Test", "Auteur", 2020);
        $dvd   = new Dvd("Test", "Auteur", 2020);

        // Les deux implémentent l'interface Empruntable
        $this->assertInstanceOf(\Mediatheque\Empruntable::class, $livre);
        $this->assertInstanceOf(\Mediatheque\Empruntable::class, $dvd);
    }

    public function testEmpruntFonctionneSurLesDeuxTypes(): void
    {
        $livre = new Livre("Test", "Auteur", 2020);
        $dvd   = new Dvd("Test", "Auteur", 2020);

        // Même appel, même comportement, deux types différents
        $livre->emprunter();
        $dvd->emprunter();

        $this->assertFalse($livre->isDisponible());
        $this->assertFalse($dvd->isDisponible());

        $livre->rendre();
        $dvd->rendre();

        $this->assertTrue($livre->isDisponible());
        $this->assertTrue($dvd->isDisponible());
    }
}
```

---

---

## Étape 10 — Tester la DAO avec des mocks : `tests/ArticleDAOTest.php`

### Pourquoi on ne peut pas tester la DAO directement ?

`ArticleDAO` a besoin d'une connexion à une base de données MySQL pour fonctionner. Si on écrit un test qui appelle `$dao->create($livre)` avec une vraie connexion PDO, plusieurs problèmes apparaissent :

- Le test dépend d'un serveur MySQL qui doit être démarré
- Il modifie de vraies données (il faut les nettoyer après)
- Il est lent (connexion réseau, même en local)
- Il peut échouer pour une raison extérieure au code (serveur éteint, base absente...)

Un bon test unitaire doit être **isolé**, **rapide** et **reproductible**. Pour ça, on va remplacer la vraie connexion PDO par un **mock**.

### Qu'est-ce qu'un mock ?

Un **mock** (ou "objet simulé") est un faux objet qui remplace une vraie dépendance dans un test. Il se comporte comme le vrai objet, mais on contrôle exactement ce qu'il renvoie.

```
Test SANS mock                    Test AVEC mock
──────────────────────────────    ──────────────────────────────────
ArticleDAO                        ArticleDAO
    │                                 │
    │── PDO réel ──> MySQL réel       │── PDO simulé (mock)
          ↓                                 ↓
    Besoin du serveur !            Renvoie ce qu'on lui dit
    Lent, dépendant                Rapide, isolé, prévisible
```

PHPUnit fournit `createMock()` pour créer ces objets simulés. On lui dit :
- quelle classe simuler (`PDO`, `PDOStatement`...)
- quelles méthodes doivent être appelées
- ce qu'elles doivent renvoyer (`willReturn()`)

### Créez `tests/ArticleDAOTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;
use Mediatheque\Livre;
use Mediatheque\Dvd;
use Mediatheque\ArticleDAO;

class ArticleDAOTest extends TestCase
{
    // Les mocks sont créés dans setUp() et partagés entre les tests
    private \PDO             $pdoMock;
    private \PDOStatement    $stmtMock;
    private ArticleDAO       $dao;

    protected function setUp(): void
    {
        // createMock() crée un faux objet qui a toutes les méthodes
        // de la vraie classe, mais qui ne fait rien par défaut.
        // On va ensuite lui dire quoi renvoyer selon le test.
        $this->pdoMock  = $this->createMock(\PDO::class);
        $this->stmtMock = $this->createMock(\PDOStatement::class);

        // On crée la DAO en lui passant le faux PDO
        // Elle ne saura pas que ce n'est pas un vrai PDO.
        $this->dao = new ArticleDAO($this->pdoMock);
    }

    // ── Tests de find() ──────────────────────────────────────────

    public function testFindRetourneLivreQuandTypeLivre(): void
    {
        // Ce tableau simule ce que MySQL renverrait pour un livre
        $ligneSimulee = [
            'id'         => 1,
            'type'       => 'livre',
            'titre'      => '1984',
            'auteur'     => 'George Orwell',
            'annee'      => 1949,
            'disponible' => 1,
        ];

        // On configure le mock :
        // - prepare() renvoie notre faux statement
        // - fetch() renvoie notre ligne simulée
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn($ligneSimulee);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $article = $this->dao->find(1);

        // On vérifie le résultat
        $this->assertInstanceOf(Livre::class, $article);
        $this->assertEquals('1984', $article->getTitre());
        $this->assertEquals('George Orwell', $article->getAuteur());
        $this->assertEquals(1949, $article->getAnnee());
        $this->assertEquals(1, $article->getId());

        // assertNotEquals : on s'assure que ce n'est PAS un Dvd
        $this->assertNotInstanceOf(Dvd::class, $article);
    }

    public function testFindRetourneDvdQuandTypeDvd(): void
    {
        $ligneSimulee = [
            'id'         => 2,
            'type'       => 'dvd',
            'titre'      => 'Inception',
            'auteur'     => 'Christopher Nolan',
            'annee'      => 2010,
            'disponible' => 0,
        ];

        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn($ligneSimulee);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $article = $this->dao->find(2);

        $this->assertInstanceOf(Dvd::class, $article);
        $this->assertEquals('Inception', $article->getTitre());
        $this->assertFalse($article->isDisponible()); // disponible = 0

        // assertNotEquals : on confirme que ce n'est pas un Livre
        $this->assertNotInstanceOf(Livre::class, $article);
    }

    public function testFindRetourneNullSiArticleInexistant(): void
    {
        // fetch() renvoie false quand aucune ligne n'est trouvée
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn(false);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $article = $this->dao->find(9999);

        $this->assertNull($article);
        // assertNotEquals : l'article inexistant ne vaut pas un objet vide
        $this->assertNotEquals(new Livre('x', 'x', 2000), $article);
    }

    // ── Tests de findAll() ───────────────────────────────────────

    public function testFindAllRetourneTableauDObjets(): void
    {
        // On simule deux lignes renvoyées par MySQL
        $lignesSimulees = [
            [
                'id' => 1, 'type' => 'livre',
                'titre' => '1984', 'auteur' => 'George Orwell',
                'annee' => 1949, 'disponible' => 1,
            ],
            [
                'id' => 2, 'type' => 'dvd',
                'titre' => 'Inception', 'auteur' => 'Christopher Nolan',
                'annee' => 2010, 'disponible' => 0,
            ],
        ];

        // findAll() utilise query() (pas prepare())
        $this->stmtMock->method('fetchAll')->willReturn($lignesSimulees);
        $this->pdoMock->method('query')->willReturn($this->stmtMock);

        $articles = $this->dao->findAll();

        // On vérifie la quantité
        $this->assertCount(2, $articles);
        $this->assertNotEmpty($articles);

        // On vérifie que les bons types ont été créés
        $this->assertInstanceOf(Livre::class, $articles[0]);
        $this->assertInstanceOf(Dvd::class, $articles[1]);
    }

    public function testFindAllRetourneTableauVideSiAucunArticle(): void
    {
        $this->stmtMock->method('fetchAll')->willReturn([]);
        $this->pdoMock->method('query')->willReturn($this->stmtMock);

        $articles = $this->dao->findAll();

        $this->assertEmpty($articles);
        $this->assertCount(0, $articles);
        $this->assertNotNull($articles); // c'est un tableau vide, pas null
    }

    // ── Tests de create() ────────────────────────────────────────

    public function testCreateRetourneLIdInsere(): void
    {
        // lastInsertId() renvoie l'id attribué par MySQL après l'INSERT
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->pdoMock->method('lastInsertId')->willReturn('42');

        $livre = new Livre('Le Petit Prince', 'Saint-Exupéry', 1943);
        $id    = $this->dao->create($livre);

        // assertEquals : l'id retourné est bien 42
        $this->assertEquals(42, $id);

        // assertNotEquals : l'id n'est pas 0 (ce qui indiquerait un échec)
        $this->assertNotEquals(0, $id);
    }

    public function testCreateAvecDvd(): void
    {
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->pdoMock->method('lastInsertId')->willReturn('7');

        $dvd = new Dvd('Parasite', 'Bong Joon-ho', 2019);
        $id  = $this->dao->create($dvd);

        $this->assertEquals(7, $id);
        $this->assertNotEquals(0, $id);
    }

    // ── Tests de update() ────────────────────────────────────────

    public function testUpdateRetourneTrue(): void
    {
        // execute() renvoie true quand la requête s'est bien passée
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $livre    = new Livre('Titre modifié', 'Auteur', 2020);
        $resultat = $this->dao->update(1, $livre);

        // assertEquals : update() doit renvoyer true
        $this->assertTrue($resultat);

        // assertNotEquals : update() ne doit pas renvoyer false
        $this->assertNotEquals(false, $resultat);
    }

    public function testUpdateDvd(): void
    {
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $dvd      = new Dvd('Interstellar', 'Christopher Nolan', 2014);
        $resultat = $this->dao->update(3, $dvd);

        $this->assertTrue($resultat);
    }

    // ── Tests de delete() ────────────────────────────────────────

    public function testDeleteRetourneTrue(): void
    {
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $resultat = $this->dao->delete(1);

        // assertEquals : delete() doit renvoyer true
        $this->assertTrue($resultat);

        // assertNotEquals : ne doit pas renvoyer false
        $this->assertNotEquals(false, $resultat);
    }

    public function testDeleteAvecIdInexistantRetourneQuandMemeTrue(): void
    {
        // MySQL ne lève pas d'erreur si on supprime un id inexistant,
        // il renvoie juste 0 lignes affectées — execute() retourne true quand même.
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $resultat = $this->dao->delete(9999);

        $this->assertTrue($resultat);
    }

    // ── Test de la méthode hydrate() via find() ───────────────────
    // hydrate() est protected : on la teste indirectement via find()

    public function testHydrateAssigneBienLIdDepuisLaBDD(): void
    {
        $ligneSimulee = [
            'id'         => 99,
            'type'       => 'livre',
            'titre'      => 'Dune',
            'auteur'     => 'Frank Herbert',
            'annee'      => 1965,
            'disponible' => 1,
        ];

        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn($ligneSimulee);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $article = $this->dao->find(99);

        // L'id doit être celui qui vient de la base, pas null
        $this->assertEquals(99, $article->getId());
        $this->assertNotNull($article->getId());
    }
}
```

### Explication des concepts clés

**`createMock(\PDO::class)`**

PHPUnit génère à la volée une fausse classe qui hérite de `PDO`. Toutes ses méthodes (`prepare`, `query`, `lastInsertId`...) existent, mais ne font rien par défaut — elles renvoient `null`. On va les configurer avec `willReturn()`.

**`method('prepare')->willReturn($this->stmtMock)`**

```php
$this->pdoMock->method('prepare')->willReturn($this->stmtMock);
```

Se lit : "Quand on appellera `prepare()` sur ce mock, renvoie `$this->stmtMock`." On chaîne ainsi le comportement : `prepare()` renvoie un faux statement, et ce faux statement renvoie lui-même ce qu'on veut via `fetch()` ou `fetchAll()`.

**Pourquoi deux mocks (`$pdoMock` et `$stmtMock`) ?**

Dans le vrai code de `DAO`, les appels sont chaînés :
```php
$stmt = $this->pdo->prepare("SELECT ...");  // retourne un PDOStatement
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
```

Il faut donc simuler les deux : `PDO` (qui retourne un `PDOStatement`) et `PDOStatement` (qui retourne les données).

**`assertInstanceOf(Livre::class, $article)`**

Vérifie que l'objet retourné est bien une instance de `Livre`. C'est la façon de tester le polymorphisme au niveau de la DAO : selon `$row['type']`, `hydrate()` crée le bon type d'objet.

**`assertNotInstanceOf(Dvd::class, $article)`**

Le pendant de `assertNotEquals` pour les objets : on confirme que le livre récupéré n'est **pas** un DVD. Les deux assertions ensemble garantissent l'identité exacte de l'objet.

### Lancer les tests de la DAO

```bash
./vendor/bin/phpunit tests/ArticleDAOTest.php
```

```
PHPUnit 11.x.x by Sebastian Bergmann and contributors.

..........                                        10 / 10 (100%)

Time: 00:00.163, Memory: 8.00 MB

OK (12 tests, 31 assertions)
```

Remarquez la vitesse : **0.163 seconde** pour 10 tests. Pas de connexion MySQL, pas de données à nettoyer. C'est toute la puissance du mock.

### Comprendre un échec de mock

Introduisez un bug dans `ArticleDAO::hydrate()` : supprimez la ligne `$article->setId((int) $row['id'])`.

```
FAILURES!
Tests: 43, Assertions: 84, Failures: 2.

1) ArticleDAOTest::testFindRetourneLivreQuandTypeLivre
Failed asserting that null matches expected 1.

2) ArticleDAOTest::testHydrateAssigneBienLIdDepuisLaBDD
Failed asserting that null matches expected 99.
```

Le mock renvoie pourtant bien `'id' => 99` dans sa ligne simulée. Si `getId()` renvoie `null`, c'est que `hydrate()` n'assigne pas l'id — le bug est dans votre code, pas dans le test ni dans la base.

## Étape 11 — Lancer tous les tests et lire les résultats

```bash
./vendor/bin/phpunit
```

### Résultat attendu

```
PHPUnit 11.x.x by Sebastian Bergmann and contributors.

......................                             22 / 22 (100%)

Time: 00:00.046, Memory: 8.00 MB

OK (43 tests, 86 assertions)
```

### Lire un résultat d'échec

Modifiez volontairement `Livre::rendre()` pour qu'il ne fasse rien :

```php
public function rendre(): void
{
    // bug intentionnel : on oublie de changer la disponibilité
}
```

Relancez les tests :

```
PHPUnit 11.x.x by Sebastian Bergmann and contributors.

..................FF..                             43 / 43 (100%)

FAILURES!
Tests: 43, Assertions: 85, Failures: 2.

1) LivreTest::testLivreDisponibleApresRetour
Failed asserting that false is true.
/tests/LivreTest.php:54

2) PolymorphismeTest::testEmpruntFonctionneSurLesDeuxTypes
Failed asserting that false is true.
/tests/PolymorphismeTest.php:72
```

PHPUnit vous dit :
- `FF` : 2 tests ont échoué (les autres ont réussi)
- Le nom exact du test et du fichier
- La ligne précise qui a échoué
- Ce qui était attendu vs ce qui a été obtenu

Corrigez le bug avant de continuer.

---

## Étape 12 — Récapitulatif et bonnes pratiques

### Arborescence finale

```
mediatheque/
├── tests/
│   ├── init.php                   ← chargement des classes
│   ├── LivreTest.php              ← tests de la classe Livre
│   ├── DvdTest.php                ← tests de la classe Dvd
│   ├── ArticleValidationTest.php  ← tests de l'encapsulation (setters)
│   └── PolymorphismeTest.php      ← tests du polymorphisme
├── vendor/                        ← installé par Composer
├── composer.json
├── composer.lock
└── phpunit.xml
```

### Les commandes utiles

```bash
# Lancer tous les tests
./vendor/bin/phpunit

# Lancer un seul fichier de test
./vendor/bin/phpunit tests/LivreTest.php

# Lancer un seul test précis
./vendor/bin/phpunit --filter testGetTypeRetournelivre

# Afficher le détail de chaque test (verbose)
./vendor/bin/phpunit --testdox
```

La commande `--testdox` affiche les tests sous forme de phrases lisibles :

```
Livre
 ✔ Get type retourne livre
 ✔ Get type n est pas dvd
 ✔ Description contient titre
 ✔ Livre disponible par defaut
 ✔ Emprunter deja emprunte lance exception
```

### Bonnes pratiques

**Nommez clairement vos tests.** `testEmprunterDejaEmprunteLanceException` est bien meilleur que `test1`. Le nom doit décrire exactement le scénario testé.

**Un test = un scénario.** Ne testez pas deux comportements différents dans la même méthode. Si un test échoue, vous devez savoir exactement ce qui ne va pas.

**Utilisez `setUp()`.** Plutôt que de créer un `new Livre(...)` au début de chaque test, centralisez la création dans `setUp()`. PHPUnit l'appelle avant chaque test, garantissant un état propre.

**Testez les cas limites.** Le cas nominal (tout va bien) est facile à tester. Les cas d'erreur (valeur vide, exception, valeur limite) sont ceux qui attrapent le plus de bugs.

**Testez toujours avec `assertEquals` ET `assertNotEquals`** sur les valeurs importantes. Vous êtes ainsi couvert dans les deux sens : vous vérifiez que la bonne valeur est là ET qu'une mauvaise valeur ne l'est pas.

---

## Étape 13 (bonus) — Intégration continue avec GitHub Actions

### Qu'est-ce que l'intégration continue ?

Jusqu'ici, vous lancez vos tests manuellement avec `./vendor/bin/phpunit`. C'est bien, mais ça suppose que chaque développeur pense à le faire avant de pousser son code. Dans une équipe, quelqu'un finira forcément par oublier — et du code cassé se retrouvera en production.

L'**intégration continue** (CI — Continuous Integration) automatise cette vérification : à chaque fois qu'un développeur pousse du code sur le dépôt, les tests se lancent automatiquement sur un serveur distant. Si un test échoue, tout le monde est prévenu immédiatement.

**GitHub Actions** est l'outil CI intégré directement dans GitHub. Il est gratuit pour les dépôts publics et inclus dans les plans payants pour les dépôts privés. Pur GitLab, ce sera GitLab CI.

### Comment ça fonctionne

```
Développeur                  GitHub                        Serveur CI (ubuntu)
    │                           │                                  │
    │── git push ──────────────>│                                  │
    │                           │── déclenche le workflow ────────>│
    │                           │                        1. Clone le dépôt
    │                           │                        2. Installe PHP 8.2
    │                           │                        3. composer install
    │                           │                        4. ./vendor/bin/phpunit
    │                           │<── résultat (✅ ou ❌) ──────────│
    │<── notification ──────────│
```

GitHub Actions crée une **machine virtuelle Ubuntu toute propre** à chaque push, installe les dépendances, lance les tests, et rapporte le résultat. Le serveur repart de zéro à chaque fois : aucune pollution entre les exécutions.

### Prérequis : avoir le projet sur GitHub

Si ce n'est pas encore fait :

1. Créez un compte sur `https://github.com`
2. Créez un nouveau dépôt (bouton `New repository`)
3. Dans votre terminal, initialisez Git et poussez le projet :

```bash
cd C:\xampp\htdocs\mediatheque

git init
git add .
git commit -m "Initial commit - Partie 3 PHPUnit"
git branch -M main
git remote add origin https://github.com/votre-compte/mediatheque.git
git push -u origin main
```

> **Rappel :** vérifiez que votre `.gitignore` contient bien `vendor/` — on ne pousse jamais ce dossier sur GitHub.

### Créer la structure du workflow

GitHub Actions cherche ses fichiers de configuration dans un dossier précis à la racine du projet. Créez cette arborescence :

```
mediatheque/
└── .github/
    └── workflows/
        └── phpunit.yml
```

```bash
# Dans le terminal
mkdir -p .github/workflows
```

Le dossier `.github` commence par un point : il est caché sous macOS/Linux. Sous Windows, il s'affiche normalement dans l'explorateur de fichiers.

### Créez `.github/workflows/phpunit.yml` :

```yaml
# Nom affiché dans l'interface GitHub Actions
name: PHPUnit Tests

# Déclencheurs : quand lancer ce workflow ?
on:
  push:
    branches: [ main ]          # à chaque push sur la branche main, mettre [ main, develop ] si besoin aussi sur la branche develop
  pull_request:
    branches: [ main ]          # à chaque pull request vers main, mettre [ main, develop ] si besoin aussi sur la branche develop

# Les jobs : ce qu'on veut faire
jobs:

  # Nom du job (libre, doit être en minuscules sans espaces)
  tests:

    # Type de machine virtuelle à utiliser
    # ubuntu-latest = dernière version stable d'Ubuntu, gratuit sur GitHub
    runs-on: ubuntu-latest

    # Les étapes du job, exécutées dans l'ordre
    steps:

      # Étape 1 : récupérer le code du dépôt sur la machine virtuelle
      # "actions/checkout" est une action officielle GitHub
      - name: Récupérer le code
        uses: actions/checkout@v4

      # Étape 2 : installer PHP sur la machine virtuelle
      # "shivammathur/setup-php" est une action communautaire très utilisée
      - name: Installer PHP 8.2
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: none          # on ne génère pas de rapport de couverture ici

      # Étape 3 : installer PHPUnit et les autres dépendances via Composer
      # --no-interaction : pas de questions interactives
      # --prefer-dist : télécharge les archives plutôt que les sources (plus rapide)
      - name: Installer les dépendances Composer
        run: composer install --no-interaction --prefer-dist

      # Étape 4 : lancer PHPUnit
      - name: Lancer les tests PHPUnit
        run: ./vendor/bin/phpunit --testdox
```

### Explication de chaque bloc YAML

**`name: PHPUnit Tests`**
Le nom qui apparaîtra dans l'onglet "Actions" de votre dépôt GitHub. Choisissez quelque chose de descriptif.

**`on: push / pull_request`**
Les **déclencheurs**. On peut déclencher un workflow sur de nombreux événements GitHub. Ici :
- `push` sur `main` : chaque fois que quelqu'un pousse du code sur la branche principale
- `pull_request` vers `main` : chaque fois que quelqu'un propose une modification (PR) avant fusion

Résultat concret : impossible de fusionner une PR avec des tests qui échouent sans que ça soit visible.

**`runs-on: ubuntu-latest`**
GitHub met à disposition plusieurs types de machines virtuelles : Ubuntu, Windows, macOS. Ubuntu est le plus rapide et le plus économique en minutes CI. Les tests PHP fonctionnent très bien dessus.

**`uses: actions/checkout@v4`**
Une **action** est un bloc de code réutilisable, développé et maintenu par la communauté ou par GitHub. `actions/checkout` clone votre dépôt sur la machine virtuelle. Sans cette étape, la machine serait vide — elle n'aurait pas votre code. Le `@v4` précise la version de l'action à utiliser.

**`uses: shivammathur/setup-php@v2`**
Ubuntu n'a pas forcément la bonne version de PHP installée. Cette action communautaire (très populaire, des millions d'utilisations) installe exactement la version que vous demandez. `coverage: none` désactive la génération du rapport de couverture de code — inutile ici, ça accélérerait l'exécution.

**`run: composer install --no-interaction --prefer-dist`**
`run` exécute une commande shell classique. Composer installe PHPUnit depuis `composer.lock` (les versions exactes sont figées). `--no-interaction` évite que Composer pose des questions (on est sur un serveur automatisé, personne ne peut répondre).

**`run: ./vendor/bin/phpunit --testdox`**
La commande finale. `--testdox` affiche le résultat de chaque test sous forme de phrase lisible dans les logs GitHub.

### Pousser le workflow et observer le résultat

```bash
git add .
git commit -m "Ajout du workflow GitHub Actions PHPUnit"
git push
```

Dès que le push est envoyé :

1. Allez sur votre dépôt GitHub
2. Cliquez sur l'onglet **Actions**
3. Vous verrez votre workflow en cours d'exécution (icône orange ⏳)
4. Cliquez dessus pour voir les logs en temps réel

Après quelques secondes :

```
✅ Récupérer le code
✅ Installer PHP 8.2
✅ Installer les dépendances Composer
✅ Lancer les tests PHPUnit

Livre
 ✔ Get type retourne livre
 ✔ Get type n est pas dvd
 ...

OK (32 tests, 53 assertions)
```

### Ce que vous voyez si un test échoue

Introduisez un bug, committez et poussez :

```bash
git add .
git commit -m "Modification avec un bug"
git push
```

Dans l'onglet Actions :

```
❌ Lancer les tests PHPUnit

FAILURES!
Tests: 32, Assertions: 51, Failures: 1.

1) LivreTest::testGetTypeRetournelivre
Failed asserting that 'dvd' matches expected 'livre'.
```

GitHub marque le commit avec une croix rouge ❌ visible par toute l'équipe. Si c'est une Pull Request, GitHub bloquera automatiquement la fusion tant que les tests n'auront pas repassé au vert.

### Le badge de statut (optionnel mais professionnel)

Vous pouvez afficher un badge dans votre `README.md` qui montre en temps réel si les tests passent :

```markdown
![PHPUnit Tests](https://github.com/votre-compte/mediatheque/actions/workflows/phpunit.yml/badge.svg)
```

Ce qui donne dans le README :

```
✅ passing   ou   ❌ failing
```

C'est ce badge qu'on voit sur les dépôts open source professionnels. Il montre immédiatement à n'importe qui qui visite le dépôt si le code est stable.

---

### ⚠️ GitHub Actions ne bloque pas le push — comprendre la limite

Une question revient souvent : **"Si les tests échouent, est-ce que GitHub Actions annule le push et refuse le commit ?"**

**Non.** Et c'est une distinction importante à bien comprendre.

Le workflow GitHub Actions se déclenche **après** que le code est arrivé sur GitHub. Le push a déjà eu lieu, le commit est déjà là. GitHub Actions ne peut pas revenir en arrière. Ce qu'il fait, c'est **signaler** que quelque chose ne va pas.

```
git push (depuis le terminal)
    │
    ▼
Code arrivé sur GitHub ← c'est définitif, le commit existe
    │
    ▼
GitHub Actions démarre les tests
    │
    ├── ✅ Tests passent → commit marqué en vert, tout va bien
    │
    └── ❌ Tests échouent → commit marqué en rouge, email envoyé
                            MAIS le code est déjà sur le dépôt
```

### Bloquer vraiment avec les Branch Protection Rules (pas nécessaire dans notre projet ou dans votre fil rouge)

Pour aller plus loin et **interdire concrètement** l'intégration de code cassé, GitHub propose les **Branch Protection Rules** — une fonctionnalité distincte de GitHub Actions.

**Comment les activer :**

1. Allez dans **Settings** du dépôt → **Branches**
2. Cliquez sur **Add branch protection rule**
3. Entrez le nom de la branche à protéger (`main` ou `develop`)
4. Cochez **"Require status checks to pass before merging"**
5. Sélectionnez votre workflow PHPUnit dans la liste
6. Sauvegardez

**Ce que ça change concrètement :**

```
feature/ma-fonctionnalite
        │
        │  git push origin feature/ma-fonctionnalite
        │  puis Pull Request vers main
        ▼
    ❌ Tests échouent sur la PR
        │
        │  GitHub bloque la fusion
        ▼
    Bouton "Merge" grisé → impossible de fusionner
    tant que les tests ne repassent pas au vert
```

> **Remarque importante pour ceux qui travaillent en terminal :** les Branch Protection Rules bloquent les **fusions de Pull Requests** sur GitHub, pas les `git push` directs depuis le terminal. Pour bloquer aussi le push direct sur `main`, cochez également **"Restrict who can push to matching branches"** dans les mêmes paramètres. Ainsi, personne ne peut bypasser les tests en poussant directement sur `main` sans passer par une Pull Request.

**En résumé :**

| Situation | GitHub Actions seul | Avec Branch Protection |
|---|---|---|
| `git push` direct sur `main` | Signale l'erreur, ne bloque pas | Peut interdire le push direct |
| Fusion d'une Pull Request | Signale l'erreur, ne bloque pas | **Bloque la fusion** |

En équipe professionnelle, on combine toujours les deux : **GitHub Actions** pour détecter les problèmes automatiquement, **Branch Protection Rules** pour les rendre bloquants.

---

