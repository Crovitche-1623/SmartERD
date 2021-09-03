<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
abstract class AbstractEntity implements \Stringable
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

    // ID can be nullable because the id is only received after the data has
    // been persisted.
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    #[ApiProperty(identifier: false)]
    protected ?int $id = null;

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

    // This method won't be available if you use the database less provider
    // for JWT.
    public function getId(): ?int
    {
        return $this->id;
    }

    // Warning: This method should be only used to define ID from JWT Payload.
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    abstract public function __toString(): string;

    /**
     * Used for DataFixtures reference or cache purpose.
     * This string can be built using id or natural
     * identifier.
     *
     * @see  BaseFixture::addSafeReference()
     *
     * @return  string  a UNIQUE string within the current class. It MUST
     *                  identify the current instance.
     */
    abstract public function toUniqueString(): string;
}
