<?php

use PHPUnit\Framework\TestCase;
use Mediatheque\Livre;
use Mediatheque\Dvd;

class PolymorphismeTest extends TestCase
{
    // ── Polymorphisme par héritage ────────────────────────────────

    public function testDescriptionsDifferentesSelonLeType(): void
    {
        $livre = new Livre("Le Seigneur des Anneaux", "J.R.R. Tolkien", 1954);
        $dvd   = new Dvd("Le Seigneur des Anneaux", "Peter Jackson", 2001);

        // Les deux descriptions sont différentes (même titre, objets différents)
        $this->assertNotEquals(
            $livre->description(),
            $dvd->description()
        );
    }

    public function testGetTypeDifferentParClasse(): void
    {
        $livre = new Livre("Test", "Auteur", 2020);
        $dvd   = new Dvd("Test", "Auteur", 2020);

        // Les types sont différents l'un de l'autre
        $this->assertNotEquals($livre->getType(), $dvd->getType());

        // Et chacun est bien le sien
        $this->assertEquals('livre', $livre->getType());
        $this->assertEquals('dvd',   $dvd->getType());
    }

    public function testTableauMixtePolymorphique(): void
    {
        // On peut mettre Livre et Dvd dans le même tableau
        // et appeler les mêmes méthodes sur tous
        $articles = [
            new Livre("1984",      "George Orwell",       1949),
            new Dvd("Inception",   "Christopher Nolan",   2010),
            new Livre("Dune",      "Frank Herbert",       1965),
            new Dvd("Parasite",    "Bong Joon-ho",        2019),
        ];

        foreach ($articles as $article) {
            // description() et getType() fonctionnent sur tous sans if/else
            $this->assertNotEmpty($article->description());
            $this->assertNotEmpty($article->getType());
            $this->assertContains($article->getType(), ['livre', 'dvd']);
        }
    }

    // ── Polymorphisme par interface (Empruntable) ─────────────────

    public function testLivreEtDvdImplententEmpruntable(): void
    {
        $livre = new Livre("Test", "Auteur", 2020);
        $dvd   = new Dvd("Test", "Auteur", 2020);

        // Les deux implémentent l'interface Empruntable
        $this->assertInstanceOf(\Mediatheque\Empruntable::class, $livre);
        $this->assertInstanceOf(\Mediatheque\Empruntable::class, $dvd);
    }

    public function testEmpruntFonctionneSurLesDeuxTypes(): void
    {
        $livre = new Livre("Test", "Auteur", 2020);
        $dvd   = new Dvd("Test", "Auteur", 2020);

        // Même appel, même comportement, deux types différents
        $livre->emprunter();
        $dvd->emprunter();

        $this->assertFalse($livre->isDisponible());
        $this->assertFalse($dvd->isDisponible());

        $livre->rendre();
        $dvd->rendre();

        $this->assertTrue($livre->isDisponible());
        $this->assertTrue($dvd->isDisponible());
    }
}