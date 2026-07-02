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