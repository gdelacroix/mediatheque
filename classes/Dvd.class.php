<?php

namespace Mediatheque;

class Dvd extends Article implements Empruntable
{
    // Polymorphisme par héritage :
    // Dvd écrit SA version de description(), différente de Livre.
    public function description(): string
    {
        return "DVD « " . $this->getTitre() . " »"
             . ", réalisé par " . $this->getAuteur()
             . " (" . $this->getAnnee() . ")";
    }

    public function getType(): string
    {
        return 'dvd';
    }

    public function emprunter(): void
    {
        if (!$this->isDisponible()) {
            throw new \RuntimeException("Ce DVD est déjà emprunté.");
        }
        $this->setDisponible(false);
    }

    public function rendre(): void
    {
        $this->setDisponible(true);
    }
}