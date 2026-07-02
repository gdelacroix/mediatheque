-- =========================================================
-- TD POO PHP — Médiathèque
-- Script de création et d'alimentation de la base
--
-- Comment l'utiliser :
--   1. Ouvrir phpMyAdmin (http://localhost/phpmyadmin)
--   2. Cliquer sur l'onglet "SQL"
--   3. Coller ce script et cliquer sur "Exécuter"
--   4. Vérifier que la base "mediatheque" apparaît à gauche
--      avec une table "article" contenant 6 lignes
-- =========================================================

CREATE DATABASE IF NOT EXISTS mediatheque
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE mediatheque;

-- On supprime la table si elle existait déjà,
-- pour pouvoir relancer le script proprement.
DROP TABLE IF EXISTS article;

-- -------------------------------------------------------------
-- La table "article"
--
-- Une seule table pour les livres ET les DVD.
-- La colonne "type" ('livre' ou 'dvd') permet de distinguer.
-- En PHP, on transformera chaque ligne en objet Livre ou Dvd
-- selon la valeur de cette colonne (c'est le rôle de la DAO).
--
-- Colonnes :
--   id          : identifiant unique, auto-incrémenté
--   type        : 'livre' ou 'dvd'
--   titre       : titre de l'article
--   auteur      : auteur du livre OU réalisateur du DVD
--   annee       : année de publication / de sortie
--   disponible  : 1 = peut être emprunté, 0 = déjà emprunté
-- -------------------------------------------------------------
CREATE TABLE article (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    type        ENUM('livre', 'dvd') NOT NULL,
    titre       VARCHAR(150)         NOT NULL,
    auteur      VARCHAR(150)         NOT NULL,
    annee       INT                  NOT NULL,
    disponible  TINYINT(1)           NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Données de départ (6 articles)
-- -------------------------------------------------------------
INSERT INTO article (type, titre, auteur, annee, disponible) VALUES
('livre', 'Le Petit Prince',                      'Antoine de Saint-Exupéry', 1943, 1),
('livre', '1984',                                 'George Orwell',            1949, 1),
('livre', 'Harry Potter à l''école des sorciers', 'J.K. Rowling',             1997, 0),
('dvd',   'Le Voyage de Chihiro',                 'Hayao Miyazaki',           2001, 1),
('dvd',   'Inception',                            'Christopher Nolan',        2010, 1),
('dvd',   'Le Fabuleux Destin d''Amélie Poulain', 'Jean-Pierre Jeunet',       2001, 0);

-- Vérification : doit afficher 6 lignes
SELECT * FROM article;
