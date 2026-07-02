<?php

namespace Mediatheque;

class Livre extends Article implements Empruntable
{
    // Polymorphisme par héritage :
    // Livre écrit SA version de description().
    public function description(): string
    {
        return "Livre « " . $this->getTitre() . " »"
             . ", écrit par " . $this->getAuteur()
             . " (" . $this->getAnnee() . ")";
    }

    public function getType(): string
    {
        return 'livre';
    }

    // Polymorphisme par interface :
    // Livre respecte le contrat Empruntable.
    public function emprunter(): void
    {
        if (!$this->isDisponible()) {
            throw new \RuntimeException("Ce livre est déjà emprunté.");
        }
        $this->setDisponible(false);
    }

    public function rendre(): void
    {
        $this->setDisponible(true);
    }
}