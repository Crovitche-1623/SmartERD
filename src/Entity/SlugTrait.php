<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait SlugTrait
{
    #[ORM\Column(length: 180, unique: true)]
    #[ApiProperty(iri: 'https://schema.org/identifier', identifier: true)]
    #[Assert\DisableAutoMapping]
    #[Assert\Type('string')]
    #[Assert\Length(max: 180)]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_\-~]+/',
        htmlPattern: '^[a-zA-Z0-9_\-~]+$',
    )]
    protected ?string $slug = null;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param  string|null  $slug  Null can be set if you want the slug to be
     *                             redefined.
     *
     * @return  $this
     */
    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }
}
