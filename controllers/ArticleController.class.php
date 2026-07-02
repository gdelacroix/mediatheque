<?php

namespace Mediatheque\Controller;

use Mediatheque\ArticleDAO;
use Mediatheque\Livre;
use Mediatheque\Dvd;

// ============================================================
//  CLASSE ArticleController
//
//  Contient une méthode par action possible sur les articles.
//  Le Controller ne produit jamais de HTML directement.
//  Il prépare des données et les confie à une Vue.
// ============================================================

class ArticleController
{
    private ArticleDAO $dao;

    public function __construct(\PDO $pdo)
    {
        $this->dao = new ArticleDAO($pdo);
    }

    // ── Action : afficher la liste ────────────────────────────
    public function liste(): void
    {
        $articles = $this->dao->findAll();
        $message  = $_GET['message'] ?? null;
        $erreur   = $_GET['erreur']  ?? null;

        $this->render('article/liste', [
            'articles' => $articles,
            'message'  => $message,
            'erreur'   => $erreur,
        ]);
    }

    // ── Action : afficher le formulaire (ajout ou modification) ──
    public function formulaire(): void
    {
        $id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $article = null;

        if ($id > 0) {
            $article = $this->dao->find($id);
            if ($article === null) {
                $this->redirect('liste', erreur: "Article introuvable.");
            }
        }

        $this->render('article/formulaire', [
            'article' => $article,
            'erreurs' => [],
            'post'    => [],
        ]);
    }

    // ── Action : traiter le formulaire soumis (POST) ──────────
    public function enregistrer(): void
    {
        $id     = (int) ($_POST['id']     ?? 0);
        $type   = $_POST['type']   ?? '';
        $titre  = trim($_POST['titre']  ?? '');
        $auteur = trim($_POST['auteur'] ?? '');
        $annee  = $_POST['annee']  ?? '';
        $mode   = $id > 0 ? 'modification' : 'ajout';

        // ── Validation ────────────────────────────────────────
        $erreurs = [];

        if ($mode === 'ajout' && !in_array($type, ['livre', 'dvd'], true)) {
            $erreurs[] = "Veuillez choisir un type (Livre ou DVD).";
        }
        if ($titre === '') {
            $erreurs[] = "Le titre est obligatoire.";
        }
        if ($auteur === '') {
            $erreurs[] = "L'auteur / réalisateur est obligatoire.";
        }
        if (!ctype_digit($annee)) {
            $erreurs[] = "L'année doit être un nombre entier.";
        }

        // ── Si erreurs : réafficher le formulaire ─────────────
        if (!empty($erreurs)) {
            $article = $id > 0 ? $this->dao->find($id) : null;

            $this->render('article/formulaire', [
                'article' => $article,
                'erreurs' => $erreurs,
                'post'    => $_POST,
            ]);
            return;
        }

        // ── Sinon : enregistrer ───────────────────────────────
        try {
            if ($mode === 'modification') {
                $article = $this->dao->find($id);
                $article->setTitre($titre);
                $article->setAuteur($auteur);
                $article->setAnnee((int) $annee);
                $this->dao->update($id, $article);
                $this->redirect('liste', message: 'Article mis à jour !');

            } else {
                $article = ($type === 'livre')
                    ? new Livre($titre, $auteur, (int) $annee)
                    : new Dvd($titre, $auteur, (int) $annee);
                $this->dao->create($article);
                $this->redirect('liste', message: 'Article ajouté !');
            }

        } catch (\Exception $e) {
            $article = $id > 0 ? $this->dao->find($id) : null;
            $this->render('article/formulaire', [
                'article' => $article,
                'erreurs' => ["Erreur : " . $e->getMessage()],
                'post'    => $_POST,
            ]);
        }
    }

    // ── Action : supprimer un article ─────────────────────────
    public function supprimer(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $this->dao->delete($id);
        }

        $this->redirect('liste', message: 'Article supprimé.');
    }

    // ── Action : emprunter ou rendre ──────────────────────────
    public function emprunter(): void
    {
        $id     = (int) ($_GET['id']   ?? 0);
        $action = $_GET['type'] ?? '';

        $article = $this->dao->find($id);

        if ($article === null) {
            $this->redirect('liste', erreur: "Article introuvable.");
        }

        try {
            if ($action === 'emprunter') {
                $article->emprunter();
                $msg = "« {$article->getTitre()} » a été emprunté.";
            } else {
                $article->rendre();
                $msg = "« {$article->getTitre()} » a été rendu.";
            }

            $this->dao->update($id, $article);
            $this->redirect('liste', message: $msg);

        } catch (\RuntimeException $e) {
            $this->redirect('liste', erreur: $e->getMessage());
        }
    }

    // ── Méthodes privées utilitaires ──────────────────────────

    /**
     * Inclut un fichier de vue en lui transmettant des variables.
     * Le nom de vue est relatif au dossier views/.
     * Exemple : render('article/liste', ['articles' => $articles])
     */
    private function render(string $vue, array $donnees = []): void
    {
        // extract() transforme ['articles' => [...]] en $articles = [...]
        // Ces variables deviennent disponibles dans le fichier de vue.
        extract($donnees);
        require __DIR__ . '/../views/' . $vue . '.php';
    }

    /**
     * Redirige vers une action du front controller.
     * Les arguments nommés PHP 8 permettent d'écrire :
     *   $this->redirect('liste', message: 'OK')
     * au lieu de :
     *   $this->redirect('liste', 'OK', null)
     */
    private function redirect(
        string  $action,
        ?string $message = null,
        ?string $erreur  = null
    ): never {
        $params = ['action' => $action];
        if ($message !== null) $params['message'] = $message;
        if ($erreur  !== null) $params['erreur']  = $erreur;

        header('Location: index.php?' . http_build_query($params));
        exit;
    }
}