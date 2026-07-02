<?php
require_once __DIR__ . '/classes/config.php';
require_once __DIR__ . '/classes/DAO.class.php';
require_once __DIR__ . '/classes/Article.class.php';
require_once __DIR__ . '/classes/Empruntable.class.php';
require_once __DIR__ . '/classes/Livre.class.php';
require_once __DIR__ . '/classes/Dvd.class.php';

use Mediatheque\ArticleDAO;
use Mediatheque\Livre;
use Mediatheque\Dvd;

$pdo = getConnexion();
$dao = new ArticleDAO($pdo);

// ── Détermination du mode ─────────────────────────────────────
// Si $_GET['id'] existe, on est en modification.
// Sinon, on est en ajout.

$id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$article = null;
$mode    = 'ajout';

if ($id > 0) {
    $article = $dao->find($id);

    if ($article === null) {
        header('Location: index.php?erreur=' . urlencode("Article introuvable."));
        exit;
    }

    $mode = 'modification';
}

// ── Traitement du formulaire ──────────────────────────────────
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // En modification, l'id vient du champ caché du formulaire
    if ($mode === 'modification') {
        $id = (int) ($_POST['id'] ?? 0);
    }

    $titre  = trim($_POST['titre']  ?? '');
    $auteur = trim($_POST['auteur'] ?? '');
    $annee  = $_POST['annee'] ?? '';

    // Le type n'est demandé qu'en mode ajout
    $type   = $_POST['type'] ?? ($article ? $article->getType() : '');

    // Validation commune aux deux modes
    if ($titre === '') {
        $erreurs[] = "Le titre est obligatoire.";
    }
    if ($auteur === '') {
        $erreurs[] = "L'auteur / réalisateur est obligatoire.";
    }
    if (!ctype_digit($annee)) {
        $erreurs[] = "L'année doit être un nombre entier.";
    }

    // Validation spécifique au mode ajout
    if ($mode === 'ajout' && !in_array($type, ['livre', 'dvd'], true)) {
        $erreurs[] = "Veuillez choisir un type (Livre ou DVD).";
    }

    if (empty($erreurs)) {
        try {
            if ($mode === 'modification') {
                // ── MODE MODIFICATION ──
                // On applique les nouvelles valeurs sur l'objet existant
                $article->setTitre($titre);
                $article->setAuteur($auteur);
                $article->setAnnee((int) $annee);
                $dao->update($id, $article);

                $msg = 'Article mis à jour avec succès !';

            } else {
                // ── MODE AJOUT ──
                // On crée le bon objet selon le type choisi
                $article = ($type === 'livre')
                    ? new Livre($titre, $auteur, (int) $annee)
                    : new Dvd($titre, $auteur, (int) $annee);

                $dao->create($article);
                $msg = 'Article ajouté avec succès !';
            }

            header('Location: index.php?message=' . urlencode($msg));
            exit;

        } catch (\Exception $e) {
            $erreurs[] = "Erreur : " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';

// Titre de la page selon le mode
$titrePage = ($mode === 'modification')
    ? "Modifier : " . htmlspecialchars($article->getTitre())
    : "Ajouter un article";
?>

<h2><?= $titrePage ?></h2>

<?php foreach ($erreurs as $erreur): ?>
    <div class="alerte alerte-erreur">
        <?= htmlspecialchars($erreur) ?>
    </div>
<?php endforeach; ?>

<form method="post" action="formulaire.php<?= $id > 0 ? "?id=$id" : '' ?>">

    <?php if ($mode === 'modification'): ?>
        <!-- En modification : on conserve l'id dans un champ caché -->
        <input type="hidden" name="id" value="<?= $id ?>">
    <?php endif; ?>

    <?php if ($mode === 'ajout'): ?>
        <!-- Le type n'est demandé qu'à la création -->
        <label for="type">Type d'article</label>
        <select id="type" name="type">
            <option value="">-- Choisir --</option>
            <option value="livre" <?= (($_POST['type'] ?? '') === 'livre') ? 'selected' : '' ?>>
                Livre
            </option>
            <option value="dvd" <?= (($_POST['type'] ?? '') === 'dvd') ? 'selected' : '' ?>>
                DVD
            </option>
        </select>

    <?php else: ?>
        <!-- En modification : le type est affiché mais non modifiable -->
        <label>Type (non modifiable)</label>
        <input type="text"
               value="<?= strtoupper($article->getType()) ?>"
               disabled>
    <?php endif; ?>

    <label for="titre">Titre</label>
    <input type="text"
           id="titre"
           name="titre"
           value="<?= htmlspecialchars($_POST['titre'] ?? ($article ? $article->getTitre() : '')) ?>">

    <label for="auteur">Auteur (livre) / Réalisateur (DVD)</label>
    <input type="text"
           id="auteur"
           name="auteur"
           value="<?= htmlspecialchars($_POST['auteur'] ?? ($article ? $article->getAuteur() : '')) ?>">

    <label for="annee">Année</label>
    <input type="number"
           id="annee"
           name="annee"
           value="<?= htmlspecialchars($_POST['annee'] ?? ($article ? (string)$article->getAnnee() : '')) ?>">

    <button type="submit" class="btn">
        <?= $mode === 'modification' ? 'Mettre à jour' : 'Enregistrer' ?>
    </button>
</form>

<a class="lien-retour" href="index.php">← Retour à la liste</a>

<?php require_once __DIR__ . '/includes/footer.php'; ?>