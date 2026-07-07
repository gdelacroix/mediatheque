<?php

use PHPUnit\Framework\TestCase;
use Mediatheque\Livre;
use Mediatheque\Dvd;
use Mediatheque\ArticleDAO;

class ArticleDAOTest extends TestCase
{
    // Les mocks sont créés dans setUp() et partagés entre les tests
    private \PDO             $pdoMock;
    private \PDOStatement    $stmtMock;
    private ArticleDAO       $dao;

    protected function setUp(): void
    {
        // createMock() crée un faux objet qui a toutes les méthodes
        // de la vraie classe, mais qui ne fait rien par défaut.
        // On va ensuite lui dire quoi renvoyer selon le test.
        $this->pdoMock  = $this->createMock(\PDO::class);
        $this->stmtMock = $this->createMock(\PDOStatement::class);

        // On crée la DAO en lui passant le faux PDO
        // Elle ne saura pas que ce n'est pas un vrai PDO.
        $this->dao = new ArticleDAO($this->pdoMock);
    }

    // ── Tests de find() ──────────────────────────────────────────

    public function testFindRetourneLivreQuandTypeLivre(): void
    {
        // Ce tableau simule ce que MySQL renverrait pour un livre
        $ligneSimulee = [
            'id'         => 1,
            'type'       => 'livre',
            'titre'      => '1984',
            'auteur'     => 'George Orwell',
            'annee'      => 1949,
            'disponible' => 1,
        ];

        // On configure le mock :
        // - prepare() renvoie notre faux statement
        // - fetch() renvoie notre ligne simulée
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn($ligneSimulee);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $article = $this->dao->find(1);

        // On vérifie le résultat
        $this->assertInstanceOf(Livre::class, $article);
        $this->assertEquals('1984', $article->getTitre());
        $this->assertEquals('George Orwell', $article->getAuteur());
        $this->assertEquals(1949, $article->getAnnee());
        $this->assertEquals(1, $article->getId());

        // assertNotEquals : on s'assure que ce n'est PAS un Dvd
        $this->assertNotInstanceOf(Dvd::class, $article);
    }

    public function testFindRetourneDvdQuandTypeDvd(): void
    {
        $ligneSimulee = [
            'id'         => 2,
            'type'       => 'dvd',
            'titre'      => 'Inception',
            'auteur'     => 'Christopher Nolan',
            'annee'      => 2010,
            'disponible' => 0,
        ];

        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn($ligneSimulee);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $article = $this->dao->find(2);

        $this->assertInstanceOf(Dvd::class, $article);
        $this->assertEquals('Inception', $article->getTitre());
        $this->assertFalse($article->isDisponible()); // disponible = 0

        // assertNotEquals : on confirme que ce n'est pas un Livre
        $this->assertNotInstanceOf(Livre::class, $article);
    }

    public function testFindRetourneNullSiArticleInexistant(): void
    {
        // fetch() renvoie false quand aucune ligne n'est trouvée
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn(false);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $article = $this->dao->find(9999);

        $this->assertNull($article);
        // assertNotEquals : l'article inexistant ne vaut pas un objet vide
        $this->assertNotEquals(new Livre('x', 'x', 2000), $article);
    }

    // ── Tests de findAll() ───────────────────────────────────────

    public function testFindAllRetourneTableauDObjets(): void
    {
        // On simule deux lignes renvoyées par MySQL
        $lignesSimulees = [
            [
                'id' => 1, 'type' => 'livre',
                'titre' => '1984', 'auteur' => 'George Orwell',
                'annee' => 1949, 'disponible' => 1,
            ],
            [
                'id' => 2, 'type' => 'dvd',
                'titre' => 'Inception', 'auteur' => 'Christopher Nolan',
                'annee' => 2010, 'disponible' => 0,
            ],
        ];

        // findAll() utilise query() (pas prepare())
        $this->stmtMock->method('fetchAll')->willReturn($lignesSimulees);
        $this->pdoMock->method('query')->willReturn($this->stmtMock);

        $articles = $this->dao->findAll();

        // On vérifie la quantité
        $this->assertCount(2, $articles);
        $this->assertNotEmpty($articles);

        // On vérifie que les bons types ont été créés
        $this->assertInstanceOf(Livre::class, $articles[0]);
        $this->assertInstanceOf(Dvd::class, $articles[1]);
    }

    public function testFindAllRetourneTableauVideSiAucunArticle(): void
    {
        $this->stmtMock->method('fetchAll')->willReturn([]);
        $this->pdoMock->method('query')->willReturn($this->stmtMock);

        $articles = $this->dao->findAll();

        $this->assertEmpty($articles);
        $this->assertCount(0, $articles);
        $this->assertNotNull($articles); // c'est un tableau vide, pas null
    }

    // ── Tests de create() ────────────────────────────────────────

    public function testCreateRetourneLIdInsere(): void
    {
        // lastInsertId() renvoie l'id attribué par MySQL après l'INSERT
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->pdoMock->method('lastInsertId')->willReturn('42');

        $livre = new Livre('Le Petit Prince', 'Saint-Exupéry', 1943);
        $id    = $this->dao->create($livre);

        // assertEquals : l'id retourné est bien 42
        $this->assertEquals(42, $id);

        // assertNotEquals : l'id n'est pas 0 (ce qui indiquerait un échec)
        $this->assertNotEquals(0, $id);
    }

    public function testCreateAvecDvd(): void
    {
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->pdoMock->method('lastInsertId')->willReturn('7');

        $dvd = new Dvd('Parasite', 'Bong Joon-ho', 2019);
        $id  = $this->dao->create($dvd);

        $this->assertEquals(7, $id);
        $this->assertNotEquals(0, $id);
    }

    // ── Tests de update() ────────────────────────────────────────

    public function testUpdateRetourneTrue(): void
    {
        // execute() renvoie true quand la requête s'est bien passée
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $livre    = new Livre('Titre modifié', 'Auteur', 2020);
        $resultat = $this->dao->update(1, $livre);

        // assertEquals : update() doit renvoyer true
        $this->assertTrue($resultat);

        // assertNotEquals : update() ne doit pas renvoyer false
        $this->assertNotEquals(false, $resultat);
    }

    public function testUpdateDvd(): void
    {
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $dvd      = new Dvd('Interstellar', 'Christopher Nolan', 2014);
        $resultat = $this->dao->update(3, $dvd);

        $this->assertTrue($resultat);
    }

    // ── Tests de delete() ────────────────────────────────────────

    public function testDeleteRetourneTrue(): void
    {
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $resultat = $this->dao->delete(1);

        // assertEquals : delete() doit renvoyer true
        $this->assertTrue($resultat);

        // assertNotEquals : ne doit pas renvoyer false
        $this->assertNotEquals(false, $resultat);
    }

    public function testDeleteAvecIdInexistantRetourneQuandMemeTrue(): void
    {
        // MySQL ne lève pas d'erreur si on supprime un id inexistant,
        // il renvoie juste 0 lignes affectées — execute() retourne true quand même.
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $resultat = $this->dao->delete(9999);

        $this->assertTrue($resultat);
    }

    // ── Test de la méthode hydrate() via find() ───────────────────
    // hydrate() est protected : on la teste indirectement via find()

    public function testHydrateAssigneBienLIdDepuisLaBDD(): void
    {
        $ligneSimulee = [
            'id'         => 99,
            'type'       => 'livre',
            'titre'      => 'Dune',
            'auteur'     => 'Frank Herbert',
            'annee'      => 1965,
            'disponible' => 1,
        ];

        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn($ligneSimulee);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $article = $this->dao->find(99);

        // L'id doit être celui qui vient de la base, pas null
        $this->assertEquals(99, $article->getId());
        $this->assertNotNull($article->getId());
    }
}