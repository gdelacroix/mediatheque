<?php

use PHPUnit\Framework\TestCase;
use Mediatheque\Livre;

class LivreTest extends TestCase
{
    // ────────────────────────────────────────────────────────────
    //  setUp() : méthode spéciale appelée AVANT chaque test.
    //  Elle prépare un état "propre" pour chaque test.
    //  Chaque test repart d'un objet Livre tout neuf.
    // ────────────────────────────────────────────────────────────
    private Livre $livre;

    protected function setUp(): void
    {
        $this->livre = new Livre("1984", "George Orwell", 1949);
    }

    // ── Tests de getType() ───────────────────────────────────────

    public function testGetTypeRetournelivre(): void
    {
        // assertEquals : on vérifie que le type EST bien 'livre'
        $this->assertEquals('livre', $this->livre->getType());
    }

    public function testGetTypeNestPasDvd(): void
    {
        // assertNotEquals : on vérifie que le type N'EST PAS 'dvd'
        // Si ce test échoue, ça signifie que getType() a renvoyé 'dvd'
        // sur un Livre — ce serait un bug réel.
        $this->assertNotEquals('dvd', $this->livre->getType());
    }

    // ── Tests de description() ───────────────────────────────────

    public function testDescriptionContientTitre(): void
    {
        $description = $this->livre->description();

        // On vérifie que la description contient bien le titre
        $this->assertStringContainsString('1984', $description);
    }

    public function testDescriptionContientAuteur(): void
    {
        $this->assertStringContainsString(
            'George Orwell',
            $this->livre->description()
        );
    }

    public function testDescriptionContientAnnee(): void
    {
        $this->assertStringContainsString(
            '1949',
            $this->livre->description()
        );
    }

    public function testDescriptionNeMentionnePasRealisateur(): void
    {
        // Un livre est "écrit par", pas "réalisé par"
        // assertNotEquals sur une chaîne plus ciblée :
        $this->assertStringNotContainsString(
            'réalisé par',
            $this->livre->description()
        );
    }

    // ── Tests de la disponibilité (encapsulation) ─────────────────

    public function testLivreDisponibleParDefaut(): void
    {
        // Par défaut, un livre est disponible
        $this->assertTrue($this->livre->isDisponible());
    }

    public function testLivreNonDisponibleApresEmprunt(): void
    {
        $this->livre->emprunter();

        // Après emprunt : assertTrue sur isDisponible doit ÉCHOUER
        // donc on utilise assertFalse
        $this->assertFalse($this->livre->isDisponible());

        // Et pour renforcer : assertNotEquals entre true et false
        $this->assertNotEquals(true, $this->livre->isDisponible());
    }

    public function testLivreDisponibleApresRetour(): void
    {
        $this->livre->emprunter();
        $this->livre->rendre();

        $this->assertTrue($this->livre->isDisponible());
    }

    // ── Tests de emprunter() — cas nominal ───────────────────────

    public function testEmprunterMarqueLivreIndisponible(): void
    {
        $this->livre->emprunter();
        $this->assertFalse($this->livre->isDisponible());
    }

    // ── Tests de emprunter() — cas d'erreur (exception) ──────────

    public function testEmprunterDejaEmprunteLanceException(): void
    {
        // On emprunte une première fois (correct)
        $this->livre->emprunter();

        // On s'attend à ce que le deuxième emprunt lève une RuntimeException
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Ce livre est déjà emprunté.");

        // Cette ligne doit déclencher l'exception
        $this->livre->emprunter();
    }
}