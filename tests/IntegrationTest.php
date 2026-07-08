<?php

use PHPUnit\Framework\TestCase;
use Mediatheque\ArticleDAO;
use Mediatheque\Livre;
use Mediatheque\Dvd;

/**
 * Tests d'intégration : utilisent une vraie connexion à la base de données.
 * À lancer uniquement dans l'environnement Docker (pas en local sans DB).
 *
 * Lancement :
 *   docker compose exec app bash -c "./vendor/bin/phpunit tests/IntegrationTest.php"
 */
class IntegrationTest extends TestCase
{
    private \PDO        $pdo;
    private ArticleDAO  $dao;

    protected function setUp(): void
    {
        // Contrairement aux tests unitaires, on utilise une VRAIE connexion PDO
        // vers le conteneur MySQL. Si la connexion échoue, les tests sont skippés.
        try {
            $this->pdo = getConnexion();
            $this->dao = new ArticleDAO($this->pdo);
        } catch (\Exception $e) {
            $this->markTestSkipped(
                "Base de données non disponible. Lancez ce test dans Docker. "
                . $e->getMessage()
            );
        }
    }

    public function testConnexionBaseDeDonneesEtablie(): void
    {
        // Si on arrive ici, la connexion a réussi dans setUp()
        $this->assertInstanceOf(\PDO::class, $this->pdo);
    }

    public function testTableArticleExiste(): void
    {
        $stmt   = $this->pdo->query("SHOW TABLES LIKE 'article'");
        $result = $stmt->fetch();

        $this->assertNotFalse($result, "La table 'article' doit exister en base.");
        $this->assertNotEmpty($result);
    }

    public function testDonneesInitialesPresentes(): void
    {
        $stmt   = $this->pdo->query("SELECT COUNT(*) as total FROM article");
        $result = $stmt->fetch();

        // Le script bdd_mediatheque.sql insère 6 articles au démarrage
        $this->assertGreaterThanOrEqual(6, (int) $result['total']);
    }

    public function testFindAllRetourneDesObjetsMetier(): void
    {
        $articles = $this->dao->findAll();

        $this->assertNotEmpty($articles);

        foreach ($articles as $article) {
            // Chaque ligne doit être un objet Livre ou Dvd (polymorphisme)
            $this->assertThat(
                $article,
                $this->logicalOr(
                    $this->isInstanceOf(Livre::class),
                    $this->isInstanceOf(Dvd::class)
                )
            );
        }
    }

    public function testCrudCompletEnBase(): void
    {
        // CREATE
        $livre  = new Livre("Test intégration Docker", "Auteur Test", 2024);
        $id     = $this->dao->create($livre);

        $this->assertGreaterThan(0, $id, "L'id inséré doit être positif.");

        // READ
        $relu = $this->dao->find($id);
        $this->assertInstanceOf(Livre::class, $relu);
        $this->assertEquals("Test intégration Docker", $relu->getTitre());

        // UPDATE
        $relu->setTitre("Test intégration modifié");
        $this->dao->update($id, $relu);

        $verifie = $this->dao->find($id);
        $this->assertEquals("Test intégration modifié", $verifie->getTitre());
        $this->assertNotEquals("Test intégration Docker", $verifie->getTitre());

        // DELETE
        $this->dao->delete($id);
        $supprime = $this->dao->find($id);
        $this->assertNull($supprime, "L'article supprimé ne doit plus être trouvable.");
    }

    protected function tearDown(): void
    {
        // Nettoyage : on s'assure qu'aucune donnée de test ne reste en base
        // (cas où un test aurait planté avant le DELETE)
        if (isset($this->pdo)) {
            $this->pdo->exec("DELETE FROM article WHERE titre LIKE 'Test intégration%'");
        }
    }
}