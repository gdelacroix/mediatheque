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