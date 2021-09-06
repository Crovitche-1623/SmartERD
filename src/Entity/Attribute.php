<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use App\Repository\AttributeRepository;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(AttributeRepository::class), ORM\Table('SERD_Attributes')]
class Attribute extends AbstractEntity
{
    #[ORM\Column(length: 180)]
    #[ApiProperty(iri: 'https://schema.org/name')]
    #[Groups('project:details')]
    #[Assert\Regex('/^[a-z]+$/i', htmlPattern: '^[a-zA-Z]+$')]
    private ?string $name = null;

    #[ORM\ManyToOne(Entity::class, inversedBy: 'attributes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Entity $entity = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEntity(): ?Entity
    {
        return $this->entity;
    }

    public function setEntity(?Entity $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    #[Pure]
    public function __toString(): string
    {
        return (string) $this->getName();
    }

    /**
     * {@inheritDoc}
     */
    #[Pure]
    public function toUniqueString(): string
    {
        return $this->__toString() . ' ' . $this->entity->toUniqueString();
    }
}
