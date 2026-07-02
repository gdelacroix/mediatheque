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