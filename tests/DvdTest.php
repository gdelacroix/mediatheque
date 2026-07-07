<?php

use PHPUnit\Framework\TestCase;
use Mediatheque\Dvd;

class DvdTest extends TestCase
{
    private Dvd $dvd;

    protected function setUp(): void
    {
        $this->dvd = new Dvd("Inception", "Christopher Nolan", 2010);
    }

    // ── Tests de getType() ───────────────────────────────────────

    public function testGetTypeRetourneDvd(): void
    {
        $this->assertEquals('dvd', $this->dvd->getType());
    }

    public function testGetTypeNestPasLivre(): void
    {
        // La contrepartie exacte du test dans LivreTest :
        // un DVD ne doit pas se prendre pour un livre.
        $this->assertNotEquals('livre', $this->dvd->getType());
    }

    // ── Tests de description() ───────────────────────────────────

    public function testDescriptionContientTitre(): void
    {
        $this->assertStringContainsString('Inception', $this->dvd->description());
    }

    public function testDescriptionMentionneRealisateur(): void
    {
        // Un DVD est "réalisé par", pas "écrit par"
        $this->assertStringContainsString('réalisé par', $this->dvd->description());
        $this->assertStringNotContainsString('écrit par', $this->dvd->description());
    }

    // ── Tests d'emprunt ──────────────────────────────────────────

    public function testDvdDisponibleParDefaut(): void
    {
        $this->assertTrue($this->dvd->isDisponible());
    }

    public function testEmprunterDvdDejaEmprunteLanceException(): void
    {
        $this->dvd->emprunter();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Ce DVD est déjà emprunté.");

        $this->dvd->emprunter();
    }
}