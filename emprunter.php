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