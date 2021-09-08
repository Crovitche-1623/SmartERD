<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractEntity implements \Stringable
{
    // ID can be nullable because the id is only received after the data has
    // been persisted.
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    #[ApiProperty(identifier: false)]
    protected ?int $id = null;

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
