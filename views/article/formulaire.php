<?php require __DIR__ . '/../layout/header.php'; ?>

<?php
// Le Controller nous a transmis :
// $article : null (ajout) ou un objet Article (modification)
// $erreurs : tableau de messages d'erreur (vide si premier chargement)
// $post    : $_POST du dernier envoi (vide si premier chargement)

$mode = ($article !== null) ? 'modification' : 'ajout';
$id   = ($article !== null) ? $article->getId() : 0;
?>

<h2>
    <?= $mode === 'modification'
        ? "Modifier : " . htmlspecialchars($article->getTitre())
        : "Ajouter un article" ?>
</h2>

<?php foreach ($erreurs as $erreur): ?>
    <div class="alerte alerte-erreur">
        <?= htmlspecialchars($erreur) ?>
    </div>
<?php endforeach; ?>

<form method="post"
      action="index.php?action=formulaire<?= $id > 0 ? "&id=$id" : '' ?>">

    <?php if ($mode === 'modification'): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
    <?php endif; ?>

    <?php if ($mode === 'ajout'): ?>
        <label for="type">Type d'article</label>
        <select id="type" name="type">
            <option value="">-- Choisir --</option>
            <option value="livre"
                <?= (($post['type'] ?? '') === 'livre') ? 'selected' : '' ?>>
                Livre
            </option>
            <option value="dvd"
                <?= (($post['type'] ?? '') === 'dvd') ? 'selected' : '' ?>>
                DVD
            </option>
        </select>
    <?php else: ?>
        <label>Type (non modifiable)</label>
        <input type="text"
               value="<?= strtoupper($article->getType()) ?>"
               disabled>
    <?php endif; ?>

    <label for="titre">Titre</label>
    <input type="text"
           id="titre"
           name="titre"
           value="<?= htmlspecialchars(
               $post['titre'] ?? ($article ? $article->getTitre() : '')
           ) ?>">

    <label for="auteur">Auteur (livre) / Réalisateur (DVD)</label>
    <input type="text"
           id="auteur"
           name="auteur"
           value="<?= htmlspecialchars(
               $post['auteur'] ?? ($article ? $article->getAuteur() : '')
           ) ?>">

    <label for="annee">Année</label>
    <input type="number"
           id="annee"
           name="annee"
           value="<?= htmlspecialchars(
               $post['annee'] ?? ($article ? (string) $article->getAnnee() : '')
           ) ?>">

    <button type="submit" class="btn">
        <?= $mode === 'modification' ? 'Mettre à jour' : 'Enregistrer' ?>
    </button>
</form>

<a class="lien-retour" href="index.php?action=liste">← Retour à la liste</a>

<?php require __DIR__ . '/../layout/footer.php'; ?>