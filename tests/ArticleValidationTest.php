<?php

use PHPUnit\Framework\TestCase;
use Mediatheque\Livre;

class ArticleValidationTest extends TestCase
{
    // ── Tests du setter setTitre() ────────────────────────────────

    public function testSetTitreModifieLeTitre(): void
    {
        $livre = new Livre("Titre initial", "Auteur", 2000);
        $livre->setTitre("Nouveau titre");

        // assertEquals : le titre EST bien le nouveau
        $this->assertEquals("Nouveau titre", $livre->getTitre());

        // assertNotEquals : le titre N'EST PLUS l'ancien
        $this->assertNotEquals("Titre initial", $livre->getTitre());
    }

    public function testSetTitreVideLanceException(): void
    {
        $livre = new Livre("Titre initial", "Auteur", 2000);

        // Un titre vide doit être refusé
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le titre ne peut pas être vide.");

        $livre->setTitre('');
    }

    public function testSetTitreEspacesSeulsLanceException(): void
    {
        $livre = new Livre("Titre initial", "Auteur", 2000);

        // Un titre composé uniquement d'espaces doit aussi être refusé
        // (trim() dans le setter convertit "   " en "")
        $this->expectException(\InvalidArgumentException::class);

        $livre->setTitre('   ');
    }

    // ── Tests du setter setAnnee() ────────────────────────────────

    public function testSetAnneeValideModifieLAnnee(): void
    {
        $livre = new Livre("Titre", "Auteur", 2000);
        $livre->setAnnee(1985);

        $this->assertEquals(1985, $livre->getAnnee());
        $this->assertNotEquals(2000, $livre->getAnnee());
    }

    public function testSetAnneeNegativeLanceException(): void
    {
        $livre = new Livre("Titre", "Auteur", 2000);

        $this->expectException(\InvalidArgumentException::class);

        $livre->setAnnee(-300);
    }

    public function testSetAnneeInferieure1000LanceException(): void
    {
        $livre = new Livre("Titre", "Auteur", 2000);

        $this->expectException(\InvalidArgumentException::class);

        $livre->setAnnee(999);
    }

    public function testSetAnneeSuperieurAnneeActuelleLanceException(): void
    {
        $livre = new Livre("Titre", "Auteur", 2000);
        $anneeInvalide = (int) date('Y') + 10;

        $this->expectException(\InvalidArgumentException::class);

        $livre->setAnnee($anneeInvalide);
    }

    // ── Tests du constructeur (qui passe par les setters) ─────────

    public function testConstructeurAvecTitreVideLanceException(): void
    {
        // Le constructeur appelle setTitre() → la validation s'applique dès la création
        $this->expectException(\InvalidArgumentException::class);

        new Livre('', 'Auteur', 2000);
    }

    public function testConstructeurAvecAnneeInvalideLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Livre('Titre', 'Auteur', -100);
    }
}