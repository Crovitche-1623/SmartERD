<?php

declare(strict_types=1);

namespace App\Entity;

interface SlugInterface
{
    public function getSlug(): ?string;

    /**
     * @param  string|null  $slug  Null can be set if you want the slug to be
     *                             redefined.
     *
     * @return SlugInterface
     */
    public function setSlug(?string $slug): SlugInterface;
}
