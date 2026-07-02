<?php

namespace Mediatheque;

interface Empruntable
{
    public function emprunter(): void;
    public function rendre(): void;
}