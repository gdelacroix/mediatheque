# Tutoriel Docker & Déploiement — Partie 4
## Containerisation de la Médiathèque

**Prérequis :** avoir terminé la Partie 3 (tests PHPUnit). L'application fonctionne et les tests passent.

**Objectif :** containeriser l'application avec Docker (3 conteneurs : PHP, MySQL, phpMyAdmin), tester l'environnement, puis déployer sur un serveur CloudPanel.

**Durée :** une journée.

---

## Étape 0 — Comprendre le problème que Docker résout

### "Ça marche chez moi..."

Vous avez développé l'application sur XAMPP/WAMP. Elle tourne parfaitement. Vous la déployez sur le serveur de production — et rien ne fonctionne. PHP n'a pas la même version. L'extension PDO n'est pas activée. MySQL a une configuration différente. Les chemins ne sont pas les mêmes.

Ce problème a un nom : **le problème d'environnement**. Et il est l'une des principales causes de bugs en production.

### La solution : containeriser

Un **conteneur Docker** est un environnement isolé qui embarque exactement ce dont l'application a besoin pour tourner : la bonne version de PHP, les bonnes extensions, la bonne configuration Apache. Ce conteneur se comporte **identiquement** sur votre machine, sur celle de votre collègue, et sur le serveur de production.

```
Sans Docker                           Avec Docker
─────────────────────────────         ──────────────────────────────────
Machine dev : PHP 8.2, XAMPP          Machine dev   ┐
Serveur SIT : PHP 7.4, Apache         Serveur SIT   ├── même conteneur → même résultat
Serveur PROD: PHP 8.0, Nginx          Serveur PROD  ┘
→ comportements différents !          → comportement identique garanti
```

### Les environnements de déploiement (rappel du cours)

Dans un environnement SI classique, on pourrait avoir quatre environnements. Voici comment Docker s'y intègre :

| Environnement | Rôle | Qui l'utilise |
|---|---|---|
| **DEV** | Développement local | Vous, avec Docker Desktop |
| **SIT** | Tests d'intégration système | Équipe technique, automatisé via CI |
| **UAT** | Validation par les utilisateurs (recette) | Client, chef de projet |
| **PROD** | Application en ligne pour les utilisateurs finaux | Tout le monde, déployé sur CloudPanel |

Dans ce tutoriel, on construit l'environnement **DEV** avec Docker Desktop, et on prépare le déploiement **PROD** sur CloudPanel.

### Ce qu'on va créer : 3 conteneurs

```
┌─────────────────────────────────────────────────┐
│              Docker (sur votre machine)          │
│                                                  │
│  ┌─────────────┐  ┌───────────┐  ┌───────────┐  │
│  │  conteneur  │  │ conteneur │  │ conteneur │  │
│  │     app     │  │    db     │  │   pma     │  │
│  │  PHP+Apache │  │  MySQL    │  │ phpMyAdmin│  │
│  │  port 8080  │  │ port 3306 │  │ port 8081 │  │
│  └──────┬──────┘  └─────┬─────┘  └─────┬─────┘  │
│         │               │              │         │
│         └───────────────┴──────────────┘         │
│                  réseau interne Docker            │
└─────────────────────────────────────────────────┘
```

- **app** : notre application PHP + Apache. Accessible sur `http://localhost:8080`
- **db** : le serveur MySQL. Les conteneurs communiquent entre eux via le réseau interne Docker.
- **pma** : phpMyAdmin pour administrer la base. Accessible sur `http://localhost:8081`

---

## Étape 1 — Le YAML : la syntaxe à maîtriser avant tout

### Une particularité que vous n'avez pas rencontrée jusqu'ici

Vous êtes habitués à coder en PHP, HTML, CSS et JavaScript. Dans ces langages, **la structure est indiquée par des délimiteurs** : accolades `{}`, parenthèses `()`, balises `<>`, point-virgules `;`. Prettier ou tout autre formateur automatique peut lire ces délimiteurs et reformater votre code proprement d'un seul raccourci clavier.

```php
// PHP : les accolades délimitent les blocs
// Prettier sait où commence et où finit chaque bloc
function emprunter() {
$this->setDisponible(false);
} // ← Prettier peut indenter automatiquement grâce aux accolades
```

**YAML n'a aucun délimiteur.** Pas d'accolades, pas de parenthèses, pas de point-virgules. **L'indentation EST la syntaxe.** Le nombre d'espaces devant une ligne détermine son niveau hiérarchique dans la structure.

```yaml
# YAML : l'indentation définit la structure
services:        # niveau 0
  app:           # niveau 1 (2 espaces)
    ports:       # niveau 2 (4 espaces)
      - "8080:80"  # niveau 3 (6 espaces)
```

### Pourquoi Prettier ne peut pas vous aider

Prettier ne connaît pas la **signification** de votre YAML. Si vous écrivez un `ports:` au mauvais niveau d'indentation, Prettier ne peut pas deviner que vous avez fait une erreur — il ne sait pas que `ports` doit être sous `app`. Il laisserait votre erreur en place.

```yaml
# ❌ INCORRECT — ports est au même niveau qu'app
services:
  app:
    image: php:8.2-apache
  ports:          # ← Prettier ne signalera pas cette erreur
    - "8080:80"   #   mais Docker refusera de démarrer

# ✅ CORRECT — ports est bien sous app
services:
  app:
    image: php:8.2-apache
    ports:        # ← 4 espaces, pas 2
      - "8080:80" # ← 6 espaces, pas 4
```

### Les règles d'or du YAML

**Règle 1 — Toujours 2 espaces par niveau, jamais de tabulations.**
La touche `Tab` est interdite en YAML. Configurez VS Code pour qu'il insère des espaces quand vous appuyez sur Tab :
- Cliquez en bas à droite de VS Code sur `Spaces: 4` (ou `Tab Size: 4`)
- Choisissez `Indent Using Spaces` → `2`

**Règle 2 — Comptez vos espaces visuellement.**
Avant d'enregistrer un fichier YAML, relisez chaque ligne en comptant les espaces. Un décalage d'un seul espace peut rendre tout le fichier invalide.

**Règle 3 — Installez l'extension YAML dans VS Code.**
Cherchez `YAML` par Red Hat dans les extensions VS Code. Elle affiche les erreurs d'indentation en temps réel avec un soulignement rouge.

**Règle 4 — Les listes commencent par un tiret `- ` (tiret + espace).**
```yaml
ports:
  - "8080:80"    # tiret + espace + valeur
  - "8081:80"
```

**Règle 5 — Les clés-valeurs s'écrivent `clé: valeur` (deux-points + espace).**
```yaml
image: mysql:8.0        # ✅ espace après les deux-points
image:mysql:8.0         # ❌ pas d'espace → erreur de parsing
```

### Valider un fichier YAML en ligne

Si vous avez un doute, collez votre YAML sur `https://www.yamllint.com/` : il vous indiquera les erreurs d'indentation avec le numéro de ligne.

---

## Étape 2 — Installer Docker Desktop

### Qu'est-ce que Docker Desktop ?

Docker Desktop est l'application qui installe et gère Docker sur votre machine. Elle contient (comme vu dans le cours) :
- **Docker Engine** : le moteur qui fait tourner les conteneurs
- **Docker CLI** : les commandes en ligne (`docker run`, `docker ps`...)
- **Docker Compose** : l'outil pour orchestrer plusieurs conteneurs via un fichier YAML
- **Une interface graphique** (GUI) pour visualiser et gérer les conteneurs sans taper de commandes

### Installation

1. Rendez-vous sur `https://www.docker.com/products/docker-desktop/`
2. Téléchargez la version pour **Windows** (ou macOS/Linux selon votre OS)
3. Lancez l'installateur et suivez les instructions
4. **Sous Windows** : Docker Desktop nécessite WSL 2 (Windows Subsystem for Linux). L'installateur vous guidera pour l'activer si ce n'est pas déjà fait. Un redémarrage peut être nécessaire.
5. Une fois installé, lancez Docker Desktop. L'icône de la baleine 🐳 apparaît dans la barre des tâches.

### Vérifier l'installation

Ouvrez un terminal et tapez :

```bash
docker --version
# Docker version 27.x.x, build ...

docker compose version
# Docker Compose version v2.x.x
```

> **Important :** Docker Desktop doit être **lancé et en cours d'exécution** (baleine visible dans la barre des tâches) avant de taper des commandes Docker dans le terminal. Si Docker n't pas démarré, vous obtiendrez l'erreur `Cannot connect to the Docker daemon`.

### Explorer l'interface graphique

Lancez Docker Desktop et explorez les onglets :
- **Containers** : liste de tous les conteneurs (en cours d'exécution, arrêtés)
- **Images** : les images Docker téléchargées sur votre machine
- **Volumes** : les volumes de données persistants

Vous verrez ces onglets se remplir au fur et à mesure qu'on crée nos conteneurs.

---

## Étape 3 — Mettre à jour `config.php` pour les variables d'environnement

Actuellement, `classes/config.php` a les identifiants de connexion écrits en dur :

```php
$host     = '127.0.0.1';
$dbname   = 'mediatheque';
$user     = 'root';
$password = '';
```

En environnement Docker, ces valeurs seront différentes. Et on ne veut surtout pas avoir des mots de passe dans le code source versionné sur GitHub. La solution : lire ces valeurs depuis des **variables d'environnement**, que Docker injectera dans le conteneur.

### Modifiez `classes/config.php` :

```php
<?php

function getConnexion(): \PDO
{
    // getenv() lit les variables d'environnement injectées par Docker.
    // Si la variable n'existe pas (ex : en local sans Docker),
    // on utilise la valeur par défaut après le "??".
    $host     = getenv('DB_HOST')     ?: '127.0.0.1';
    $dbname   = getenv('DB_NAME')     ?: 'mediatheque';
    $user     = getenv('DB_USER')     ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';

    try {
        $pdo = new \PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $user,
            $password,
            [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
        return $pdo;

    } catch (\PDOException $e) {
        die('Erreur de connexion : ' . $e->getMessage());
    }
}
```

### Explication

`getenv('DB_HOST')` lit la variable d'environnement `DB_HOST`. L'opérateur `?:` (Elvis) renvoie la valeur de droite si celle de gauche est `false` ou vide :
- **Avec Docker** : Docker injecte `DB_HOST=db` (le nom du conteneur MySQL) → la connexion se fait vers le conteneur `db`
- **Sans Docker** (XAMPP local) : `DB_HOST` n'existe pas → on utilise `127.0.0.1` comme avant

**Aucune modification des autres fichiers.** `config.php` est le seul endroit qui connaît la configuration — c'est pour ça qu'on l'a centralisé dès la Partie 1.

---

## Étape 4 — Le fichier `.env` : les secrets hors du code

Créez un fichier `.env` à la racine du projet. Il contient les valeurs des variables d'environnement :

```dotenv
# Variables d'environnement pour Docker
# Ce fichier est lu automatiquement par docker compose

DB_ROOT_PASSWORD=root_secret_2024
DB_NAME=mediatheque
DB_USER=mediatheque_user
DB_PASSWORD=mediatheque_pass_2024
```

### Impérativement : ajoutez `.env` au `.gitignore`

```
vendor/
.env
```

**Pourquoi ?** Le `.env` contient des mots de passe. On ne les pousse jamais sur GitHub. Chaque environnement (DEV, PROD) aura son propre `.env` avec ses propres valeurs.

### Créez `.env.example` (lui, on le versionne)

```dotenv
# Copiez ce fichier en .env et remplissez les valeurs
DB_ROOT_PASSWORD=
DB_NAME=mediatheque
DB_USER=
DB_PASSWORD=
```

Ce fichier sert de documentation : il montre quelles variables sont nécessaires, sans révéler les vraies valeurs. Convention courante dans les projets professionnels.

---

## Étape 5 — Le Dockerfile : construire l'image de l'application

Le **Dockerfile** décrit comment construire l'image Docker de notre application PHP. Il part d'une image de base officielle et y ajoute ce dont notre projet a spécifiquement besoin.

### Créez `Dockerfile` à la racine du projet :

```dockerfile
# ─────────────────────────────────────────────────────────────
#  IMAGE DE BASE
#  php:8.2-apache = PHP 8.2 + serveur Apache, image officielle
# ─────────────────────────────────────────────────────────────
FROM php:8.2-apache

# ─────────────────────────────────────────────────────────────
#  EXTENSIONS PHP
#  Notre application utilise PDO pour parler à MySQL.
#  Ces extensions ne sont pas incluses par défaut dans l'image.
# ─────────────────────────────────────────────────────────────
RUN docker-php-ext-install pdo pdo_mysql

# ─────────────────────────────────────────────────────────────
#  CONFIGURATION APACHE
#  On active mod_rewrite (nécessaire pour les URL propres)
#  et on configure Apache pour lire les fichiers .htaccess.
# ─────────────────────────────────────────────────────────────
RUN a2enmod rewrite

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# ─────────────────────────────────────────────────────────────
#  CODE SOURCE
#  On copie tout le projet dans le dossier servi par Apache.
#  Le WORKDIR définit le répertoire courant pour les commandes suivantes.
# ─────────────────────────────────────────────────────────────
WORKDIR /var/www/html

COPY . .

# ─────────────────────────────────────────────────────────────
#  PERMISSIONS
#  Apache tourne sous l'utilisateur www-data.
#  Il doit pouvoir lire les fichiers du projet.
# ─────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html

# ─────────────────────────────────────────────────────────────
#  PORT
#  On documente que ce conteneur écoute sur le port 80 en interne.
#  La correspondance avec un port de l'hôte se fait dans docker-compose.yml.
# ─────────────────────────────────────────────────────────────
EXPOSE 80
```

### Créez `docker/apache.conf` :

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/html
    DirectoryIndex index.php

    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

### Explication du Dockerfile instruction par instruction

**`FROM php:8.2-apache`**
Le point de départ. Docker Hub (le registre public d'images) propose des images officielles pour PHP. `php:8.2-apache` est maintenue par l'équipe PHP : elle inclut PHP 8.2 et Apache préconfigurés. On ne repart pas de zéro.

**`RUN docker-php-ext-install pdo pdo_mysql`**
`RUN` exécute une commande lors de la construction de l'image. `docker-php-ext-install` est un script fourni par l'image officielle PHP pour installer des extensions. On installe `pdo` (l'interface générique) et `pdo_mysql` (le driver spécifique à MySQL).

**`RUN a2enmod rewrite`**
Active le module `mod_rewrite` d'Apache, nécessaire pour les URL propres. `a2enmod` est une commande Apache disponible dans le conteneur.

**`COPY . .`**
Copie tout le contenu du répertoire courant (votre projet) dans `/var/www/html` (le `WORKDIR` défini juste avant). Le premier `.` = source (votre machine), le second `.` = destination (le WORKDIR dans le conteneur).

**`EXPOSE 80`**
Documentation : ce conteneur écoute sur le port 80. Ne rend pas le port accessible de l'extérieur — c'est `docker-compose.yml` qui fait ça.

### Créez `.dockerignore` pour exclure les fichiers inutiles :

```
vendor/
.env
.git/
tests/
*.md
node_modules/
```

Même logique que `.gitignore` : on n'envoie pas `vendor/` (des centaines de Mo de PHPUnit) dans l'image Docker de production.

---

## Étape 6 — Le fichier `docker-compose.yml` : orchestrer les 3 conteneurs

C'est le fichier central de l'étape. Il décrit les 3 conteneurs, comment ils communiquent, et quelles données ils partagent avec votre machine. **Lisez attentivement la section YAML de l'Étape 1 avant d'écrire ce fichier.**

### Créez `docker-compose.yml` à la racine du projet :

```yaml
# ─────────────────────────────────────────────────────────────────────
#  Version du format docker-compose
# ─────────────────────────────────────────────────────────────────────
version: '3.8'

# ─────────────────────────────────────────────────────────────────────
#  SERVICES : les 3 conteneurs de l'application
# ─────────────────────────────────────────────────────────────────────
services:

  # ── 1. Conteneur de l'application PHP ─────────────────────────────
  app:
    # "build: ." signifie : construire l'image à partir du Dockerfile
    # situé dans le répertoire courant (la racine du projet)
    build: .
    container_name: mediatheque_app
    ports:
      # "hôte:conteneur" — le port 8080 de votre machine
      # redirige vers le port 80 du conteneur Apache
      - "8080:80"
    volumes:
      # Monte le répertoire courant dans le conteneur.
      # Tout changement de fichier sur votre machine est
      # immédiatement visible dans le conteneur (rechargement à chaud).
      - .:/var/www/html
    environment:
      # Ces variables sont lues dans config.php via getenv()
      DB_HOST: db          # "db" est le nom du service MySQL ci-dessous
      DB_NAME: ${DB_NAME}  # La valeur vient du fichier .env
      DB_USER: ${DB_USER}
      DB_PASSWORD: ${DB_PASSWORD}
    depends_on:
      db:
        condition: service_healthy
    networks:
      - mediatheque_network
    restart: unless-stopped

  # ── 2. Conteneur MySQL ─────────────────────────────────────────────
  db:
    # On utilise l'image officielle MySQL 8.0 (pas besoin de Dockerfile)
    image: mysql:8.0
    container_name: mediatheque_db
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      # Volume nommé pour la persistance des données.
      # Sans ça, toutes les données seraient perdues à l'arrêt du conteneur.
      - db_data:/var/lib/mysql
      # Script SQL exécuté automatiquement à la première création de la BDD.
      # C'est notre fichier d'initialisation !
      - ./bdd_mediatheque.sql:/docker-entrypoint-initdb.d/init.sql
    healthcheck:
      # Docker attend que MySQL soit vraiment prêt avant de démarrer "app"
      # (depends_on avec condition: service_healthy)
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-p${DB_ROOT_PASSWORD}"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - mediatheque_network
    restart: unless-stopped

  # ── 3. Conteneur phpMyAdmin ────────────────────────────────────────
  phpmyadmin:
    image: phpmyadmin:latest
    container_name: mediatheque_phpmyadmin
    environment:
      # PMA_HOST doit correspondre au nom du service MySQL ("db")
      PMA_HOST: db
      PMA_USER: ${DB_USER}
      PMA_PASSWORD: ${DB_PASSWORD}
    ports:
      # phpMyAdmin accessible sur http://localhost:8081
      - "8081:80"
    depends_on:
      - db
    networks:
      - mediatheque_network
    restart: unless-stopped

# ─────────────────────────────────────────────────────────────────────
#  VOLUMES : stockage persistant des données MySQL
#  Sans ce volume, les données sont perdues à chaque docker compose down
# ─────────────────────────────────────────────────────────────────────
volumes:
  db_data:

# ─────────────────────────────────────────────────────────────────────
#  RÉSEAU : les 3 conteneurs communiquent entre eux sur ce réseau interne
#  Depuis l'extérieur, seuls les ports 8080 et 8081 sont accessibles
# ─────────────────────────────────────────────────────────────────────
networks:
  mediatheque_network:
    driver: bridge
```

### Explication des points clés

**`DB_HOST: db`**

Dans Docker, les conteneurs d'un même réseau se reconnaissent **par leur nom de service**. Quand `app` veut parler à MySQL, il n'utilise pas `127.0.0.1` — il utilise `db` (le nom du service déclaré dans `docker-compose.yml`). Docker résout ce nom en adresse IP interne automatiquement.

**`${DB_NAME}`**

La syntaxe `${VARIABLE}` dit à docker-compose de lire la valeur dans le fichier `.env`. Si `.env` contient `DB_NAME=mediatheque`, alors `${DB_NAME}` sera remplacé par `mediatheque`. Les secrets ne sont jamais écrits en dur dans `docker-compose.yml`.

**`./bdd_mediatheque.sql:/docker-entrypoint-initdb.d/init.sql`**

L'image MySQL officielle exécute automatiquement tous les fichiers `.sql` placés dans `/docker-entrypoint-initdb.d/` lors de la **première** création du conteneur. Notre script SQL sera donc exécuté automatiquement : la base sera créée et peuplée sans aucune action manuelle.

**`healthcheck` et `condition: service_healthy`**

MySQL met quelques secondes à démarrer après le lancement du conteneur. Sans `healthcheck`, le conteneur `app` pourrait tenter de se connecter à MySQL avant qu'il soit prêt et échouer. Le `healthcheck` fait attendre `app` jusqu'à ce que MySQL réponde correctement au ping.

**`volumes: db_data`**

Sans ce volume, les données MySQL existent uniquement dans le conteneur. Quand on fait `docker compose down`, le conteneur est supprimé — et toutes les données avec lui. Le volume `db_data` stocke les données **en dehors** du conteneur, sur votre machine. Les données survivent aux redémarrages.

**`restart: unless-stopped`**

Si un conteneur plante, Docker le redémarre automatiquement — sauf si on l'a arrêté manuellement. Utile en production.

---

## Étape 7 — Les commandes Docker essentielles

### Démarrer l'environnement

```bash
# Depuis la racine du projet (là où se trouve docker-compose.yml)
docker compose up -d
```

- `up` : crée et démarre tous les conteneurs définis dans `docker-compose.yml`
- `-d` : mode "détaché" (detached) — les conteneurs tournent en arrière-plan, le terminal reste disponible

**Première exécution** : Docker va télécharger les images (`mysql:8.0`, `phpmyadmin:latest`) et construire l'image `app` depuis le Dockerfile. Cela peut prendre quelques minutes selon votre connexion.

```
[+] Running 3/3
 ✔ Container mediatheque_db          Started
 ✔ Container mediatheque_phpmyadmin  Started
 ✔ Container mediatheque_app         Started
```

### Vérifier que les conteneurs tournent

```bash
docker compose ps
```

```
NAME                      IMAGE               STATUS
mediatheque_app           mediatheque-app     Up 2 minutes
mediatheque_db            mysql:8.0           Up 2 minutes (healthy)
mediatheque_phpmyadmin    phpmyadmin:latest   Up 2 minutes
```

La colonne `STATUS` indique `Up` (en cours) ou `Exited` (arrêté). `(healthy)` confirme que le healthcheck de MySQL passe.

### Consulter les logs d'un conteneur

```bash
# Logs du conteneur PHP (erreurs Apache, erreurs PHP...)
docker compose logs app

# Logs MySQL
docker compose logs db

# Suivre les logs en temps réel (comme tail -f)
docker compose logs -f app
```

Si l'application ne fonctionne pas, les logs sont votre premier outil de diagnostic.

### Entrer dans un conteneur (terminal interactif)

```bash
# Ouvrir un shell bash dans le conteneur app
docker compose exec app bash

# Maintenant vous êtes DANS le conteneur :
# ls /var/www/html          → vos fichiers PHP
# php -v                    → version PHP du conteneur
# php -m | grep pdo         → vérifier les extensions PDO
# exit                      → quitter le conteneur
```

C'est l'équivalent d'ouvrir un terminal sur un serveur distant, mais ici c'est votre conteneur local.

### Reconstruire l'image après modification du Dockerfile

```bash
# Si vous modifiez le Dockerfile, il faut reconstruire l'image
docker compose build

# Ou reconstruire et redémarrer en une commande
docker compose up -d --build
```

### Arrêter les conteneurs

```bash
# Arrête les conteneurs sans les supprimer (données conservées)
docker compose stop

# Redémarrer après un stop
docker compose start

# Arrête ET supprime les conteneurs (mais garde les volumes = données MySQL)
docker compose down

# Arrête, supprime conteneurs ET volumes (⚠️ données MySQL effacées)
docker compose down -v
```

> **Attention à `down -v`** : cette commande supprime les données MySQL. Utilisez-la uniquement quand vous voulez repartir de zéro (par exemple pour tester l'initialisation de la BDD).

### Récapitulatif des commandes

| Commande | Ce qu'elle fait |
|---|---|
| `docker compose up -d` | Démarre tous les conteneurs en arrière-plan |
| `docker compose down` | Arrête et supprime les conteneurs |
| `docker compose ps` | Affiche l'état des conteneurs |
| `docker compose logs app` | Affiche les logs d'un conteneur |
| `docker compose logs -f app` | Suit les logs en temps réel |
| `docker compose exec app bash` | Ouvre un terminal dans un conteneur |
| `docker compose build` | Reconstruit les images |
| `docker compose restart app` | Redémarre un conteneur spécifique |
| `docker ps -a` | Liste tous les conteneurs (tous projets confondus) |
| `docker images` | Liste toutes les images téléchargées |
| `docker system prune` | Nettoie les conteneurs/images inutilisés |

---

## Étape 8 — Tester l'environnement Docker

### ✅ Test 1 — L'application est accessible

Ouvrez `http://localhost:8080/index.php` dans le navigateur. La liste des articles doit s'afficher. Si vous voyez une erreur, consultez les logs :

```bash
docker compose logs app
```

### ✅ Test 2 — La base de données a été initialisée

Ouvrez `http://localhost:8081` (phpMyAdmin). Connectez-vous avec les identifiants du `.env` (`DB_USER` / `DB_PASSWORD`). Vérifiez que la base `mediatheque` existe et contient la table `article` avec les 6 articles.

### ✅ Test 3 — La connexion PHP → MySQL fonctionne

Créez un fichier temporaire `test_docker.php` à la racine :

```php
<?php
require_once __DIR__ . '/classes/config.php';

try {
    $pdo = getConnexion();
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM article");
    $result = $stmt->fetch();
    echo "✅ Connexion MySQL OK — {$result['total']} articles en base.";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
```

Ouvrez `http://localhost:8080/test_docker.php`. Vous devez voir `✅ Connexion MySQL OK — 6 articles en base.` Supprimez ce fichier après le test.

### ✅ Test 4 — Lancer les tests PHPUnit dans le conteneur

```bash
docker compose exec app bash -c "cd /var/www/html && ./vendor/bin/phpunit"
```

Tous les tests de la Partie 3 doivent passer — y compris dans l'environnement Docker. Si les tests échouent, les logs vous indiqueront pourquoi.

---

## Étape 9 — Tests d'intégration : vérifier que les services communiquent

### Différence entre tests unitaires et tests d'intégration

Dans la Partie 3, vous avez écrit des **tests unitaires** : chaque classe est testée isolément, avec des mocks à la place des vraies dépendances (PDO mocké à la place de MySQL).

Les **tests d'intégration** testent que les composants fonctionnent **ensemble** : le conteneur PHP communique-t-il vraiment avec le conteneur MySQL ? Le script d'initialisation SQL a-t-il bien créé les tables ?

### Créez `tests/IntegrationTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;
use Mediatheque\ArticleDAO;
use Mediatheque\Livre;
use Mediatheque\Dvd;

/**
 * Tests d'intégration : utilisent une vraie connexion à la base de données.
 * À lancer uniquement dans l'environnement Docker (pas en local sans DB).
 *
 * Lancement :
 *   docker compose exec app bash -c "./vendor/bin/phpunit tests/IntegrationTest.php"
 */
class IntegrationTest extends TestCase
{
    private \PDO        $pdo;
    private ArticleDAO  $dao;

    protected function setUp(): void
    {
        // Contrairement aux tests unitaires, on utilise une VRAIE connexion PDO
        // vers le conteneur MySQL. Si la connexion échoue, les tests sont skippés.
        try {
            $this->pdo = getConnexion();
            $this->dao = new ArticleDAO($this->pdo);
        } catch (\Exception $e) {
            $this->markTestSkipped(
                "Base de données non disponible. Lancez ce test dans Docker. "
                . $e->getMessage()
            );
        }
    }

    public function testConnexionBaseDeDonneesEtablie(): void
    {
        // Si on arrive ici, la connexion a réussi dans setUp()
        $this->assertInstanceOf(\PDO::class, $this->pdo);
    }

    public function testTableArticleExiste(): void
    {
        $stmt   = $this->pdo->query("SHOW TABLES LIKE 'article'");
        $result = $stmt->fetch();

        $this->assertNotFalse($result, "La table 'article' doit exister en base.");
        $this->assertNotEmpty($result);
    }

    public function testDonneesInitialesPresentes(): void
    {
        $stmt   = $this->pdo->query("SELECT COUNT(*) as total FROM article");
        $result = $stmt->fetch();

        // Le script bdd_mediatheque.sql insère 6 articles au démarrage
        $this->assertGreaterThanOrEqual(6, (int) $result['total']);
    }

    public function testFindAllRetourneDesObjetsMetier(): void
    {
        $articles = $this->dao->findAll();

        $this->assertNotEmpty($articles);

        foreach ($articles as $article) {
            // Chaque ligne doit être un objet Livre ou Dvd (polymorphisme)
            $this->assertThat(
                $article,
                $this->logicalOr(
                    $this->isInstanceOf(Livre::class),
                    $this->isInstanceOf(Dvd::class)
                )
            );
        }
    }

    public function testCrudCompletEnBase(): void
    {
        // CREATE
        $livre  = new Livre("Test intégration Docker", "Auteur Test", 2024);
        $id     = $this->dao->create($livre);

        $this->assertGreaterThan(0, $id, "L'id inséré doit être positif.");

        // READ
        $relu = $this->dao->find($id);
        $this->assertInstanceOf(Livre::class, $relu);
        $this->assertEquals("Test intégration Docker", $relu->getTitre());

        // UPDATE
        $relu->setTitre("Test intégration modifié");
        $this->dao->update($id, $relu);

        $verifie = $this->dao->find($id);
        $this->assertEquals("Test intégration modifié", $verifie->getTitre());
        $this->assertNotEquals("Test intégration Docker", $verifie->getTitre());

        // DELETE
        $this->dao->delete($id);
        $supprime = $this->dao->find($id);
        $this->assertNull($supprime, "L'article supprimé ne doit plus être trouvable.");
    }

    protected function tearDown(): void
    {
        // Nettoyage : on s'assure qu'aucune donnée de test ne reste en base
        // (cas où un test aurait planté avant le DELETE)
        if (isset($this->pdo)) {
            $this->pdo->exec("DELETE FROM article WHERE titre LIKE 'Test intégration%'");
        }
    }
}
```

### Lancer les tests d'intégration

```bash
# Tests d'intégration dans le conteneur (avec vraie BDD)
docker compose exec app bash -c "./vendor/bin/phpunit tests/IntegrationTest.php"

# Tests unitaires seuls (avec mocks, sans BDD)
./vendor/bin/phpunit --exclude-group integration
```

> **Bonne pratique :** les tests unitaires (Partie 3) s'exécutent partout et rapidement. Les tests d'intégration nécessitent Docker et sont plus lents. On les sépare pour que la CI puisse choisir lesquels lancer selon le contexte.

---

## Étape 10 (bonus) — Mettre à jour GitHub Actions pour Docker

Mettez à jour `.github/workflows/phpunit.yml` pour ajouter une étape de build Docker et les tests d'intégration :

```yaml
name: CI — Tests & Docker

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:

  # ── Job 1 : Tests unitaires (rapides, sans Docker) ────────────────
  tests-unitaires:
    runs-on: ubuntu-latest
    steps:
      - name: Récupérer le code
        uses: actions/checkout@v4

      - name: Installer PHP 8.2
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: none

      - name: Installer les dépendances Composer
        run: composer install --no-interaction --prefer-dist

      - name: Lancer les tests unitaires (avec mocks)
        run: ./vendor/bin/phpunit --testdox --exclude-group integration

  # ── Job 2 : Tests d'intégration (avec Docker) ────────────────────
  tests-integration:
    runs-on: ubuntu-latest
    needs: tests-unitaires   # Ce job ne se lance que si les tests unitaires passent

    steps:
      - name: Récupérer le code
        uses: actions/checkout@v4

      - name: Créer le fichier .env pour les tests
        run: |
          echo "DB_ROOT_PASSWORD=root_ci_password" >> .env
          echo "DB_NAME=mediatheque" >> .env
          echo "DB_USER=ci_user" >> .env
          echo "DB_PASSWORD=ci_password" >> .env

      - name: Démarrer les conteneurs Docker
        run: docker compose up -d --build

      - name: Attendre que MySQL soit prêt
        run: |
          echo "Attente du démarrage de MySQL..."
          sleep 20

      - name: Lancer les tests d'intégration dans le conteneur
        run: |
          docker compose exec -T app composer install --no-interaction
          docker compose exec -T app ./vendor/bin/phpunit tests/IntegrationTest.php --testdox

      - name: Arrêter les conteneurs
        if: always()   # S'exécute même si les tests échouent
        run: docker compose down -v

  # ── Job 3 : Vérifier que l'image Docker se build sans erreur ──────
  docker-build:
    runs-on: ubuntu-latest
    needs: tests-unitaires

    steps:
      - name: Récupérer le code
        uses: actions/checkout@v4

      - name: Vérifier que l'image se construit
        run: docker build -t mediatheque-app .
```

---

## Récapitulatif : arborescence finale du projet

```
mediatheque/
│
├── .env                          ← variables d'environnement (non versionné)
├── .env.example                  ← template des variables (versionné)
├── .dockerignore                 ← fichiers exclus de l'image Docker
├── .gitignore                    ← vendor/, .env
├── Dockerfile                    ← recette de l'image PHP+Apache
├── docker-compose.yml            ← orchestration des 3 conteneurs
├── bdd_mediatheque.sql           ← auto-exécuté par le conteneur MySQL
│
├── docker/
│   └── apache.conf               ← configuration Apache
│
├── .github/
│   └── workflows/
│       └── phpunit.yml           ← CI : tests unitaires + intégration + Docker build
│
├── classes/                      ← MODEL (config.php utilise getenv())
├── controllers/                  ← CONTROLLER
├── views/                        ← VIEW
├── tests/
│   ├── init.php
│   ├── LivreTest.php
│   ├── DvdTest.php
│   ├── ArticleValidationTest.php
│   ├── PolymorphismeTest.php
│   ├── ArticleDAOTest.php        ← tests unitaires avec mocks
│   └── IntegrationTest.php       ← tests d'intégration (nécessitent Docker)
│
├── composer.json
├── composer.lock
├── phpunit.xml
└── vendor/
```

---

