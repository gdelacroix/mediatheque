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

# unzip est requis par Composer pour extraire les packages
# zip est requis par l'extension zip de PHP
RUN apt-get update \
    && apt-get install -y unzip \
    && rm -rf /var/lib/apt/lists/*

# On copie Composer depuis son image officielle
# C'est la méthode recommandée : propre, toujours à jour
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

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