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